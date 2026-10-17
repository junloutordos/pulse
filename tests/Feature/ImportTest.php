<?php

use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityIndicator;
use App\Models\MunicipalityNeighbor;
use App\Services\Import\ChecksumMismatch;
use App\Services\Import\VersionConflict;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

function importer(): DatasetImporter
{
    return app(DatasetImporter::class);
}

test('import loads all tables', function () {
    $v = importer()->import(fixtureDir());

    expect($v->source)->toBe('synthetic')
        ->and($v->cycle)->toBe(2026)
        ->and($v->prior_cycle)->toBe(2025)
        ->and($v->formula_version)->toBe('1.0')
        ->and(Municipality::count())->toBe(6)
        ->and(MunicipalityIndicator::where('dataset_version_id', $v->id)->count())->toBe(6)
        ->and(MunicipalityNeighbor::where('dataset_version_id', $v->id)->count())
        ->toBe(count(file(fixtureDir().'/neighbors.jsonl')))
        ->and($v->reconciliation)->not->toBeEmpty()
        ->and($v->issues)->not->toBeEmpty();
    Storage::disk('local')->assertExists($v->boundaries_path);
});

test('checksum mismatch aborts and changes nothing', function () {
    $dir = copyVersion();
    file_put_contents("$dir/indicators.jsonl", "\n", FILE_APPEND);

    expect(fn () => importer()->import($dir))->toThrow(ChecksumMismatch::class);

    expect(DatasetVersion::count())->toBe(0)
        ->and(Municipality::count())->toBe(0)
        ->and(MunicipalityIndicator::count())->toBe(0);
});

test('missing values stay null not zero', function () {
    importer()->import(fixtureDir());

    $missing = MunicipalityIndicator::where('status', 'missing')->first();
    $suppressed = MunicipalityIndicator::where('status', 'suppressed')->first();

    expect($missing)->not->toBeNull()
        ->and($missing->applicants)->toBeNull()
        ->and($missing->rate_per_1000)->toBeNull()
        ->and($suppressed->applicants)->toBeNull()
        ->and(MunicipalityIndicator::where('applicants', 0)->count())->toBe(0);
});

test('only one active version', function () {
    $first = importer()->import(fixtureDir());
    $second = importer()->import(copyVersion('2026.10.18-syn'));

    expect(DatasetVersion::where('is_active', true)->count())->toBe(1)
        ->and($second->fresh()->is_active)->toBeTrue()
        ->and($first->fresh()->is_active)->toBeFalse();
});

test('import without activate leaves version inactive', function () {
    $v = importer()->import(fixtureDir(), activate: false);

    expect($v->is_active)->toBeFalse();
});

test('reimport same version is idempotent', function () {
    $first = importer()->import(fixtureDir());
    $second = importer()->import(fixtureDir());

    expect($second->id)->toBe($first->id)
        ->and(DatasetVersion::count())->toBe(1)
        ->and(MunicipalityIndicator::count())->toBe(6)
        ->and(Municipality::count())->toBe(6);
});

test('pulse import command activates with the flag', function () {
    $this->artisan('pulse:import', ['versionDir' => fixtureDir(), '--activate' => true])->assertSuccessful();

    expect(DatasetVersion::first()->is_active)->toBeTrue();
});

test('pulse import command leaves version inactive without the flag', function () {
    $this->artisan('pulse:import', ['versionDir' => fixtureDir()])->assertSuccessful();

    expect(DatasetVersion::first()->is_active)->toBeFalse();
});

test('score components and not-ranked reasons are imported', function () {
    importer()->import(fixtureDir());

    $scores = collect(file(fixtureDir().'/scores.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
        ->map(fn ($l) => json_decode($l, true))->keyBy('psgc');
    $ranked = MunicipalityIndicator::whereNotNull('c_reach')->first();
    $unranked = MunicipalityIndicator::whereNotNull('not_ranked_reason')->first();

    expect($ranked)->not->toBeNull()
        ->and($ranked->c_reach)->toBe((float) $scores[$ranked->psgc]['c_reach'])
        ->and($ranked->c_attendance)->toBe((float) $scores[$ranked->psgc]['c_attendance'])
        ->and($unranked)->not->toBeNull()
        ->and($unranked->c_reach)->toBeNull()
        ->and($unranked->not_ranked_reason)->toBe($scores[$unranked->psgc]['not_ranked_reason']);
});

function changedContentSameVersion(): string
{
    $dir = copyVersion();
    $rows = file("$dir/indicators.jsonl", FILE_IGNORE_NEW_LINES);
    $first = json_decode($rows[0], true);
    $first['applicants'] = 9999;
    $rows[0] = json_encode($first);
    file_put_contents("$dir/indicators.jsonl", implode("\n", $rows)."\n");
    $prov = json_decode(file_get_contents("$dir/provenance.json"), true);
    $prov['files']['indicators.jsonl'] = hash_file('sha256', "$dir/indicators.jsonl");
    file_put_contents("$dir/provenance.json", json_encode($prov));

    return $dir;
}

test('re-importing a version name with different content is refused', function () {
    importer()->import(fixtureDir());
    $before = MunicipalityIndicator::orderBy('id')->first()->applicants;

    expect(fn () => importer()->import(changedContentSameVersion()))->toThrow(VersionConflict::class);

    expect(MunicipalityIndicator::orderBy('id')->first()->applicants)->toBe($before);
});

test('force replaces a version that has different content', function () {
    importer()->import(fixtureDir());

    importer()->import(changedContentSameVersion(), force: true);

    expect(MunicipalityIndicator::orderBy('id')->first()->applicants)->toBe(9999);
});

test('pulse import command refuses a conflicting version unless forced', function () {
    importer()->import(fixtureDir());
    $dir = changedContentSameVersion();

    $this->artisan('pulse:import', ['versionDir' => $dir])->assertFailed();
    expect(MunicipalityIndicator::orderBy('id')->first()->applicants)->not->toBe(9999);

    $this->artisan('pulse:import', ['versionDir' => $dir, '--force' => true])->assertSuccessful();
    expect(MunicipalityIndicator::orderBy('id')->first()->applicants)->toBe(9999);
});
