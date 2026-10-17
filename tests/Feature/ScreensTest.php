<?php

use App\Models\DatasetVersion;
use App\Models\MunicipalityIndicator;
use App\Models\User;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->version = app(DatasetImporter::class)->import(fixtureDir());
    $this->viewer = User::factory()->create();
});

test('screens require login', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with(['/trust', '/map', '/pipeline', '/priority', '/data/boundaries.geojson']);

test('screens report a conflict when no dataset is active', function (string $path) {
    DatasetVersion::query()->update(['is_active' => false]);

    $this->actingAs($this->viewer)->get($path)->assertStatus(409);
})->with(['/trust', '/map', '/pipeline', '/priority', '/data/boundaries.geojson']);

// ---- Data Trust -------------------------------------------------------------------------------------------------

test('trust props match imported version', function () {
    $this->actingAs($this->viewer)->get('/trust')->assertInertia(
        fn (Assert $p) => $p->component('Trust', false)
            ->where('counts.total', 6)
            ->where('counts.suppressed', MunicipalityIndicator::where('status', 'suppressed')->count())
            ->where('counts.missing', MunicipalityIndicator::where('status', 'missing')->count())
            ->where('counts.low', MunicipalityIndicator::where('status', 'low')->count())
            ->where('counts.sharedNames', 1)          // "Carmen" appears in two provinces in the fixture
            ->where('counts.aliasFixes', 2)           // Butuan and R.T. Romualdez
            ->where('counts.unmatched', 0)
            ->has('reconciliation')
            ->where('reconciliation.0.matches', true)
    );
});

test('issues list names a reason for each flagged municipality', function () {
    $this->actingAs($this->viewer)->get('/trust')->assertInertia(function (Assert $p) {
        $issues = collect($p->toArray()['props']['issues']);
        $flagged = MunicipalityIndicator::whereIn('status', ['low', 'suppressed', 'missing'])->pluck('psgc');

        foreach ($flagged as $psgc) {
            $match = $issues->first(fn ($i) => $i['psgc'] === $psgc && in_array($i['kind'], ['low', 'suppressed', 'missing']));
            expect($match)->not->toBeNull()->and($match['reason'])->not->toBe('');
        }
        expect($issues->pluck('kind')->unique()->all())->toContain('alias', 'shared_name');
        expect($issues->every(fn ($i) => $i['name'] !== '' && $i['reason'] !== ''))->toBeTrue();
    });
});

test('lineage includes dataset version and formula version', function () {
    $this->actingAs($this->viewer)->get('/trust')->assertInertia(function (Assert $p) {
        $lineage = collect($p->toArray()['props']['lineage']);
        $text = $lineage->map(fn ($s) => $s['detail'].' '.$s['ref'])->implode(' | ');

        expect($lineage)->toHaveCount(5)
            ->and($text)->toContain($this->version->version)
            ->and($text)->toContain('1.0')
            ->and($lineage->first()['label'])->toBe('Source')
            ->and($lineage->first()['detail'])->toContain('Synthetic');
    });
});

// ---- Map ----------------------------------------------------------------------------------------------------------

test('map props exclude suppressed values', function () {
    $this->actingAs($this->viewer)->get('/map')->assertInertia(
        fn (Assert $p) => $p->component('Map', false)
            ->has('rows', 6)
            ->has('metrics', 5)
            ->where('rows', fn ($rows) => collect($rows)->where('status', 'suppressed')->every(fn ($r) => $r['applicants'] === null && $r['rate_per_1000'] === null))
    );
});

test('boundaries endpoint serves the active version as geojson', function () {
    $res = $this->actingAs($this->viewer)->get('/data/boundaries.geojson');

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('json')
        ->and(json_decode($res->getContent(), true)['features'])->toHaveCount(6);
});

// ---- Pipeline explorer --------------------------------------------------------------------------------------------

test('region medians exclude suppressed and missing', function () {
    $rows = MunicipalityIndicator::whereIn('status', ['ok', 'low'])->whereNotNull('rate_per_1000')->pluck('rate_per_1000')->sort()->values();
    $n = $rows->count();
    $median = $n % 2 ? $rows[intdiv($n, 2)] : ($rows[$n / 2 - 1] + $rows[$n / 2]) / 2;

    $this->actingAs($this->viewer)->get('/pipeline')->assertInertia(
        fn (Assert $p) => $p->component('Pipeline', false)
            ->where('regionMedians.rate', fn ($v) => abs($v - $median) < 1e-9)
            ->has('regionMedians.attendance')
            ->where('hidden.suppressed', MunicipalityIndicator::where('status', 'suppressed')->count())
            ->where('hidden.missing', MunicipalityIndicator::where('status', 'missing')->count())
    );
});

test('low sample rows are flagged', function () {
    $this->actingAs($this->viewer)->get('/pipeline')->assertInertia(
        fn (Assert $p) => $p->where('rows', fn ($rows) => collect($rows)->contains('status', 'low') || MunicipalityIndicator::where('status', 'low')->doesntExist())
    );
});

// ---- Priority list ------------------------------------------------------------------------------------------------

test('priority props carry rows with the fields the scoring needs', function () {
    $this->actingAs($this->viewer)->get('/priority')->assertInertia(
        fn (Assert $p) => $p->component('Priority', false)->has('rows', 6)
            ->has('rows.0', fn (Assert $r) => $r->hasAll(['psgc', 'name', 'province', 'status', 'rate_per_1000', 'attendance', 'change', 'applicants', 'examinees', 'qualifiers', 'grade6_pop', 'prior_applicants', 'lisa_cluster', 'lat', 'lon']))
            ->has('canPlan')
    );
});
