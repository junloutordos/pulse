<?php

use App\Models\ActionRegisterEntry;
use App\Models\DatasetVersion;
use App\Models\MunicipalityIndicator;
use App\Models\User;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

const RANKED_FIRST = '9900200001';   // fixture: rank 1 at balanced weights
const SUPPRESSED = '9900100003';     // fixture: applicant count suppressed

beforeEach(function () {
    Storage::fake('local');
    $this->version = app(DatasetImporter::class)->import(fixtureDir());
    $this->viewer = User::factory()->create();
    $this->planner = User::factory()->planner()->create();
});

test('brief contains provenance fields', function () {
    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)
        ->assertOk()
        ->assertSee('Cycle 2026')
        ->assertSee($this->version->version)
        ->assertSee('Formula 1.0')
        ->assertSee($this->version->refreshed_at->format('Y-m-d'))
        ->assertSee('PSHS-CRC Outreach')
        ->assertSee('Next review')
        ->assertSee('Limitations')
        ->assertSee('Priority #1 of 3');
});

test('synthetic brief has watermark in html', function () {
    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)->assertSee('SYNTHETIC DATA');
});

test('real data brief has no watermark', function () {
    app(DatasetImporter::class)->import(copyVersion('2026.10.19-real', 'real'));

    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)->assertOk()->assertDontSee('SYNTHETIC DATA');
});

test('unranked municipality brief states reason and no score', function () {
    $this->actingAs($this->viewer)->get('/brief/'.SUPPRESSED)
        ->assertOk()
        ->assertSee('Not ranked')
        ->assertSee('applicant count suppressed')
        ->assertDontSee('Priority #')
        ->assertSee('Data verification with the PSHSS custodian');
});

test('unknown municipality is a 404', function () {
    $this->actingAs($this->viewer)->get('/brief/0000000000')->assertNotFound();
});

test('brief uses the weights recorded with the planner decision', function () {
    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 0, 'decline' => 0],
    ]);

    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)
        ->assertSee('Reach 100%')
        ->assertSee('Attendance 0%')
        ->assertSee('Information and application campaign');
});

test('pdf returns 200 application pdf', function () {
    $res = $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST.'/pdf');

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($res->getContent(), 0, 4))->toBe('%PDF');
});

function pdfText(string $pdf): string
{
    return (new \Smalot\PdfParser\Parser())->parseContent($pdf)->getText();
}

test('synthetic pdf carries the watermark text', function () {
    $pdf = $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST.'/pdf')->getContent();

    expect(pdfText($pdf))->toContain('SYNTHETIC DATA')->toContain('Priority #1 of 3');
});

test('real data pdf has no watermark', function () {
    app(DatasetImporter::class)->import(copyVersion('2026.10.19-real', 'real'));
    $pdf = $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST.'/pdf')->getContent();

    expect(pdfText($pdf))->not->toContain('SYNTHETIC DATA')->toContain('Priority #1 of 3');
});

test('brief index lists municipalities and the register', function () {
    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 1, 'decline' => 1],
    ]);

    $this->actingAs($this->viewer)->get('/brief')->assertInertia(
        fn (Assert $p) => $p->component('Brief', false)->has('rows', 6)->has('register', 1)
            ->has('register.0', fn (Assert $r) => $r->hasAll(['id', 'psgc', 'name', 'action', 'status', 'planned_on', 'completed_on', 'office']))
            ->where('canPlan', false)
    );
});

test('register complete requires planner', function () {
    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 1, 'decline' => 1],
    ]);
    $entry = ActionRegisterEntry::first();

    $this->actingAs($this->viewer)->patch("/register/{$entry->id}")->assertForbidden();
    expect($entry->fresh()->status)->toBe('planned');

    $this->actingAs($this->planner)->patch("/register/{$entry->id}")->assertRedirect();
    $done = $entry->fresh();
    expect($done->status)->toBe('completed')->and($done->completed_on->isToday())->toBeTrue();
});

test('completing an entry twice keeps the first completion date', function () {
    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 1, 'decline' => 1],
    ]);
    $entry = ActionRegisterEntry::first();
    $entry->update(['status' => 'completed', 'completed_on' => '2026-10-01']);

    $this->actingAs($this->planner)->patch("/register/{$entry->id}")->assertRedirect();

    expect($entry->fresh()->completed_on->toDateString())->toBe('2026-10-01');
});

test('register entries from an inactive dataset version cannot be completed', function () {
    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 1, 'decline' => 1],
    ]);
    $entry = ActionRegisterEntry::first();
    app(DatasetImporter::class)->import(copyVersion('2026.10.20-syn'));

    $this->actingAs($this->planner)->patch("/register/{$entry->id}")->assertNotFound();

    expect($entry->fresh()->status)->toBe('planned');
});

test('brief says which weights its rank uses', function () {
    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)->assertSee('at Balanced weights');

    $this->actingAs($this->planner)->post('/planner/decisions', [
        'psgc' => RANKED_FIRST, 'status' => 'accepted', 'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 1, 'attendance' => 0, 'decline' => 0],
    ]);

    $this->actingAs($this->viewer)->get('/brief/'.RANKED_FIRST)->assertSee('at the weights recorded with the planner decision')->assertDontSee('at Balanced weights');
});
