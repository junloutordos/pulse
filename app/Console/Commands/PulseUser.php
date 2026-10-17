<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PulseUser extends Command
{
    protected $signature = 'pulse:user {email} {name} {--role=viewer : viewer or planner} {--password= : At least 12 characters; generated when omitted}';

    protected $description = 'Create a staff account. There is no public sign-up.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->option('role');
        $password = $this->option('password') ?: Str::password(16, symbols: false);

        if (! in_array($role, ['viewer', 'planner'], true)) {
            $this->error('The role must be viewer or planner.');

            return self::FAILURE;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }
        if (strlen($password) < 12) {
            $this->error('The password must be at least 12 characters.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error("An account for {$email} already exists; nothing was changed.");

            return self::FAILURE;
        }

        $user = new User(['name' => $this->argument('name'), 'email' => $email, 'password' => Hash::make($password)]);
        $user->email_verified_at = now();
        $user->role = $role;
        $user->save();

        $this->info("Created {$role} account for {$email}.");
        $this->line("Password: {$password}");
        $this->line('Share it through a secure channel and ask them to change it after signing in.');

        return self::SUCCESS;
    }
}
