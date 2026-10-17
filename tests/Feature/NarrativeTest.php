<?php

use App\Models\User;
use App\Services\Import\DatasetImporter;
use App\Services\Narrative\ClaudeNarrative;
use App\Services\Narrative\TemplateNarrative;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const FIRST = '9900200001'; // fixture: rank 1 of 3

beforeEach(function () {
    Storage::fake('local');
    app(DatasetImporter::class)->import(fixtureDir());
    $this->viewer = User::factory()->create();
});

function briefData(): array
{
    return [
        'municipality' => (object) ['name' => 'Carmen', 'province' => 'Surigao del Sur', 'psgc' => '9900200001'],
        'status' => 'ok', 'rank' => 1, 'rankedCount' => 3, 'score' => 96.0,
        'observations' => ['Applications per 1,000' => 17.4, 'Exam attendance' => '59%', 'Change vs prior cycle' => '-13%'],
        'components' => ['reach' => 0.9, 'attendance' => 1.0, 'decline' => 0.9],
        'weights' => ['reach' => 33, 'attendance' => 33, 'decline' => 34],
        'action' => 'Exam reminder and access review', 'notRankedReason' => null,
        'decision' => ['status' => 'accepted', 'reason' => 'Free text a planner typed, possibly with a name'],
        'office' => 'PSHS-CRC Outreach', 'psgc' => '9900200001', 'weightsBasis' => 'balanced',
    ];
}

test('default uses template and makes no http call', function () {
    Http::fake();

    $this->actingAs($this->viewer)->get('/brief/'.FIRST)->assertOk()->assertSee('ranks #1 of 3');

    Http::assertNothingSent();
});

test('template narrative states the facts it was given and names no cause', function () {
    $text = (new TemplateNarrative)->summarize(briefData());

    expect($text)->toContain('Carmen')->toContain('#1 of 3')->toContain('59%')->toContain('Exam reminder and access review')
        ->and($text)->not->toMatch('/because|caused|due to/i');
});

test('template narrative explains an unranked municipality', function () {
    $data = array_merge(briefData(), ['rank' => null, 'score' => null, 'notRankedReason' => 'applicant count suppressed']);

    expect((new TemplateNarrative)->summarize($data))->toContain('not ranked')->toContain('applicant count suppressed');
});

test('payload contains no fields outside the allowlist', function () {
    $payload = ClaudeNarrative::payload(briefData());

    expect(array_keys($payload))->toEqualCanonicalizing(['municipality', 'province', 'status', 'rank', 'ranked_count', 'observations', 'components', 'weights', 'suggested_action'])
        ->and(json_encode($payload))->not->toContain('Free text')->not->toContain('9900200001')->not->toContain('PSHS-CRC Outreach');
});

test('flag off keeps the template even when a key is present', function () {
    config(['pulse.ai_narrative' => false, 'services.anthropic.key' => 'k']);
    Http::fake();

    $this->actingAs($this->viewer)->get('/brief/'.FIRST)->assertOk();

    Http::assertNothingSent();
});

test('enabled narrative is labelled as AI-assisted', function () {
    config(['pulse.ai_narrative' => true, 'services.anthropic.key' => 'k']);
    Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'A short, careful summary.']]])]);

    $this->actingAs($this->viewer)->get('/brief/'.FIRST)->assertSee('A short, careful summary.')->assertSee('AI-assisted summary');

    Http::assertSentCount(1);
});

test('a failing AI service falls back to the template', function () {
    config(['pulse.ai_narrative' => true, 'services.anthropic.key' => 'k']);
    Http::fake(['api.anthropic.com/*' => Http::response('nope', 500)]);

    $this->actingAs($this->viewer)->get('/brief/'.FIRST)->assertOk()->assertSee('ranks #1 of 3')->assertDontSee('AI-assisted summary');
});
