<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Import\DatasetImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Demo users and an active dataset for rehearsals and the scripted demo path. Never runs in production. */
class DemoSeeder extends Seeder
{
    public function run(DatasetImporter $importer): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder creates users with a known password and must not run in production.');
        }

        foreach ([['planner@pulse.test', 'Demo Planner', 'planner'], ['viewer@pulse.test', 'Demo Viewer', 'viewer']] as [$email, $name, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );
            $user->forceFill(['role' => $role])->save();
        }

        $importer->import($this->versionDir(), activate: true);
    }

    private function versionDir(): string
    {
        if ($configured = config('pulse.demo_version_dir')) {
            return $configured;
        }

        $versions = glob(base_path('data/versions/*/provenance.json')) ?: [];
        rsort($versions);

        return $versions ? dirname($versions[0]) : base_path('tests/fixtures/version_mini');
    }
}
