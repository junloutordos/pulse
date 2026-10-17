<?php

use App\Models\User;
use App\Services\Import\DatasetImporter;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('local'));

test('shared dataset prop reports synthetic source', function () {
    app(DatasetImporter::class)->import(fixtureDir());

    $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(
        fn (Assert $page) => $page
            ->where('dataset.source', 'synthetic')
            ->where('dataset.cycle', 2026)
            ->where('dataset.priorCycle', 2025)
            ->has('dataset.version')
            ->has('dataset.refreshedAt')
    );
});

test('shared dataset prop reports real source', function () {
    app(DatasetImporter::class)->import(copyVersion('2026.10.19-real', 'real'));

    $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(
        fn (Assert $page) => $page->where('dataset.source', 'real')
    );
});

test('dataset prop is null when nothing is active', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(
        fn (Assert $page) => $page->where('dataset', null)
    );
});

test('inactive versions are not shared', function () {
    app(DatasetImporter::class)->import(fixtureDir(), activate: false);

    $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(
        fn (Assert $page) => $page->where('dataset', null)
    );
});
