import { execSync } from 'node:child_process';

/**
 * Start every run from the same state: empty database, demo users, newest synthetic dataset version.
 * This WIPES the app's database, so it needs an explicit opt-in and never runs against production.
 */
export default function globalSetup() {
    const appEnv = execSync('docker compose exec -T app php artisan env').toString();
    if (/production/i.test(appEnv)) {
        throw new Error('Refusing to reset the database: APP_ENV is production.');
    }
    if (process.env.PULSE_E2E_RESET !== '1') {
        throw new Error('The e2e run wipes the app database (users, decisions, register). Run it on a throwaway stack with: PULSE_E2E_RESET=1 npm run e2e');
    }
    execSync('docker compose exec -T app php artisan migrate:fresh --seed --seeder=DemoSeeder --force', { stdio: 'inherit' });
}
