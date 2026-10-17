<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('creates a verified viewer by default and shows the password once', function () {
    $this->artisan('pulse:user', ['email' => 'staff@example.com', 'name' => 'Staff One'])
        ->expectsOutputToContain('Password:')
        ->assertSuccessful();

    $user = User::where('email', 'staff@example.com')->first();
    expect($user->role)->toBe('viewer')
        ->and($user->name)->toBe('Staff One')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('creates a planner with the role option', function () {
    $this->artisan('pulse:user', ['email' => 'p@example.com', 'name' => 'Plan', '--role' => 'planner'])->assertSuccessful();

    expect(User::where('email', 'p@example.com')->first()->role)->toBe('planner');
});

test('uses a password given on the command line', function () {
    $this->artisan('pulse:user', ['email' => 'c@example.com', 'name' => 'Chosen', '--password' => 'a-long-chosen-pass'])->assertSuccessful();

    expect(Hash::check('a-long-chosen-pass', User::first()->password))->toBeTrue();
});

test('rejects an unknown role', function () {
    $this->artisan('pulse:user', ['email' => 'x@example.com', 'name' => 'X', '--role' => 'admin'])->assertFailed();

    expect(User::count())->toBe(0);
});

test('rejects a short password and a duplicate email without changing the account', function () {
    $this->artisan('pulse:user', ['email' => 's@example.com', 'name' => 'S', '--password' => 'short'])->assertFailed();
    expect(User::count())->toBe(0);

    User::factory()->create(['email' => 'dup@example.com']);
    $this->artisan('pulse:user', ['email' => 'dup@example.com', 'name' => 'Dup', '--role' => 'planner'])->assertFailed();

    expect(User::where('email', 'dup@example.com')->first()->role)->toBe('viewer');
});
