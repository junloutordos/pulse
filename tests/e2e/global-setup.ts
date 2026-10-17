import { execSync } from 'node:child_process';

/** Start every run from the same state: empty database, demo users, newest synthetic dataset version. */
export default function globalSetup() {
    execSync('docker compose exec -T app php artisan migrate:fresh --seed --seeder=DemoSeeder --force', { stdio: 'inherit' });
}
