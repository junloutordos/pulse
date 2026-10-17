<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'can:plan'])->post('/_plan-check', fn () => 'ok');
});

test('guest redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('viewer cannot plan', function () {
    $this->actingAs(User::factory()->create())->post('/_plan-check')->assertForbidden();
});

test('planner can plan', function () {
    $this->actingAs(User::factory()->planner()->create())->post('/_plan-check')->assertOk();
});

test('new users default to viewer', function () {
    expect(User::factory()->create()->fresh()->role)->toBe('viewer');
});
