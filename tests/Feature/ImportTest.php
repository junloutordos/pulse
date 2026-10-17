<?php

use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityIndicator;
use App\Models\MunicipalityNeighbor;
use App\Services\Import\ChecksumMismatch;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

function fixtureDir(): string
{
    return base_path('tests/fixtures/version_mini');
}

function copyVersion(?string $newVersion = null): string
{
    $dest = sys_get_temp_dir().'/pulse-'.uniqid();
    File::copyDirectory(fixtureDir(), $dest);
    if ($newVersion) {
        $prov = json_decode(file_get_contents("$dest/provenance.json"), true);
        $prov['dataset_version'] = $newVersion;
        file_put_contents("$dest/provenance.json", json_encode($prov));
    }

    return $dest;
}

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
