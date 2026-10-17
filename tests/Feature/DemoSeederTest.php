<?php

use App\Models\DatasetVersion;
use App\Models\User;
use App\Services\Import\DatasetImporter;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    config(['pulse.demo_version_dir' => fixtureDir()]);
});

test('demo seeder creates a planner, a viewer and an active dataset', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'planner@pulse.test')->first()->role)->toBe('planner')
        ->and(User::where('email', 'viewer@pulse.test')->first()->role)->toBe('viewer')
        ->and(DatasetVersion::active())->not->toBeNull()
        ->and(DatasetVersion::active()->source)->toBe('synthetic');
});

test('demo seeder can run twice without duplicating anything', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::count())->toBe(2)->and(DatasetVersion::count())->toBe(1);
});

test('demo seeder refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DemoSeeder::class)->run(app(DatasetImporter::class)))->toThrow(RuntimeException::class);
});
