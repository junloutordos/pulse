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

test('registration cannot create a planner', function () {
    $this->post('/register', [
        'name' => 'Pat', 'email' => 'pat@example.com', 'role' => 'planner',
        'password' => 'password-123', 'password_confirmation' => 'password-123',
    ]);

    expect(User::where('email', 'pat@example.com')->first()->role)->toBe('viewer');
});
