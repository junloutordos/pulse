<?php

use App\Models\ActionRegisterEntry;
use App\Models\DatasetVersion;
use App\Models\MunicipalityIndicator;
use App\Models\PlannerDecision;
use App\Models\User;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->version = app(DatasetImporter::class)->import(fixtureDir());
    $this->okPsgc = MunicipalityIndicator::where('status', 'ok')->first()->psgc;
});

function decision(array $over = []): array
{
    return array_merge([
        'psgc' => test()->okPsgc,
        'status' => 'accepted',
        'reason' => 'Matches local knowledge',
        'action' => 'Information and application campaign',
        'weights' => ['reach' => 0.34, 'attendance' => 0.33, 'decline' => 0.33],
    ], $over);
}

test('guest cannot record a decision', function () {
    $this->post('/planner/decisions', decision())->assertRedirect('/login');
});

test('viewer cannot record decision', function () {
    $this->actingAs(User::factory()->create())->post('/planner/decisions', decision())->assertForbidden();
    expect(PlannerDecision::count())->toBe(0);
});

test('accept creates register entry', function () {
    $this->actingAs(User::factory()->planner()->create())->post('/planner/decisions', decision())->assertRedirect();

    $entry = ActionRegisterEntry::first();
    expect(PlannerDecision::count())->toBe(1)
        ->and($entry->psgc)->toBe($this->okPsgc)
        ->and($entry->status)->toBe('planned')
        ->and($entry->action)->toBe('Information and application campaign')
        ->and($entry->office)->toBe('PSHS-CRC Outreach')
        ->and($entry->planned_on->isToday())->toBeTrue()
        ->and($entry->dataset_version_id)->toBe($this->version->id);
});

test('defer removes register entry', function () {
    $planner = User::factory()->planner()->create();
    $this->actingAs($planner)->post('/planner/decisions', decision());
    $this->actingAs($planner)->post('/planner/decisions', decision(['status' => 'deferred', 'reason' => 'Already served this cycle', 'action' => null]))->assertRedirect();

    expect(ActionRegisterEntry::count())->toBe(0)
        ->and(PlannerDecision::first()->status)->toBe('deferred')
        ->and(PlannerDecision::count())->toBe(1);
});

test('defer is rejected when the action is already completed', function () {
    $planner = User::factory()->planner()->create();
    $this->actingAs($planner)->post('/planner/decisions', decision());
    ActionRegisterEntry::first()->update(['status' => 'completed', 'completed_on' => today()]);

    $this->actingAs($planner)->post('/planner/decisions', decision(['status' => 'deferred', 'reason' => 'Changed my mind', 'action' => null]))
        ->assertSessionHasErrors('psgc');

    expect(ActionRegisterEntry::first()->status)->toBe('completed')
        ->and(PlannerDecision::first()->status)->toBe('accepted');
});

test('reason required', function () {
    $this->actingAs(User::factory()->planner()->create())
        ->post('/planner/decisions', decision(['reason' => '']))
        ->assertSessionHasErrors('reason');
});

test('action required when accepting', function () {
    $this->actingAs(User::factory()->planner()->create())
        ->post('/planner/decisions', decision(['action' => null]))
        ->assertSessionHasErrors('action');
});

test('unknown municipality, bad status and bad weights are rejected', function () {
    $planner = User::factory()->planner()->create();

    $this->actingAs($planner)->post('/planner/decisions', decision(['psgc' => '0000000000']))->assertSessionHasErrors('psgc');
    $this->actingAs($planner)->post('/planner/decisions', decision(['status' => 'maybe']))->assertSessionHasErrors('status');
    $this->actingAs($planner)->post('/planner/decisions', decision(['weights' => ['reach' => -1, 'attendance' => 1, 'decline' => 1]]))
        ->assertSessionHasErrors('weights.reach');
});

test('recording twice updates instead of duplicating', function () {
    $planner = User::factory()->planner()->create();
    $this->actingAs($planner)->post('/planner/decisions', decision());
    $this->actingAs($planner)->post('/planner/decisions', decision(['action' => 'Exam reminder and access review']));

    expect(PlannerDecision::count())->toBe(1)
        ->and(ActionRegisterEntry::count())->toBe(1)
        ->and(ActionRegisterEntry::first()->action)->toBe('Exam reminder and access review');
});

test('no active dataset is a conflict', function () {
    DatasetVersion::query()->update(['is_active' => false]);

    $this->actingAs(User::factory()->planner()->create())->post('/planner/decisions', decision())->assertStatus(409);
});

test('planner page exposes rows, decisions, register and nearby municipalities', function () {
    $this->actingAs(User::factory()->planner()->create())->post('/planner/decisions', decision());

    $this->actingAs(User::factory()->create())->get('/planner')->assertInertia(
        fn (Assert $page) => $page->component('Planner', false)
            ->has('rows', 6)
            ->has('rows.0', fn (Assert $r) => $r->hasAll(['psgc', 'name', 'province', 'status', 'rate_per_1000', 'attendance', 'change', 'applicants', 'examinees', 'qualifiers', 'grade6_pop', 'prior_applicants', 'lisa_cluster', 'lat', 'lon']))
            ->has('decisions', 1)
            ->has('register', 1)
            ->has('nearby')
            ->where('canPlan', false)
    );
});

test('a planner who recorded decisions can delete their account and the decision stays', function () {
    $planner = User::factory()->planner()->create();
    $this->actingAs($planner)->post('/planner/decisions', decision());

    $this->actingAs($planner)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

    expect(User::find($planner->id))->toBeNull()
        ->and(PlannerDecision::count())->toBe(1)
        ->and(PlannerDecision::first()->user_id)->toBeNull();
});
