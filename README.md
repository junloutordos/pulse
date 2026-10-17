# PULSE

Pipeline Understanding, Location Intelligence, and Strategic Engagement.
Geospatial decision support for PSHS-CRC NCE outreach, built by Modern Voyagers for DOST GATES GeoHack 2026.

Design: `docs/specs/2026-10-17-pulse-design.md` · Plan: `docs/superpowers/plans/2026-10-17-pulse-implementation.md`

Synthetic data is always labeled. No personally identifiable information is used.

## Stack

Laravel 13 · Inertia.js · Vue 3 + TypeScript · Tailwind · MySQL **8.4.11** (pinned in `docker-compose.yml`) · Python 3.12 pipeline · MapLibre GL JS · Pest · Vitest · pytest · Playwright

## Run

See `docs/handover/README.md` for the full steps. In short:

```sh
cp .env.example .env
docker compose up -d --build          # app on http://localhost:8000 (set APP_PORT to change), mysql 8.4.11
npm install && npm run build          # on the host, not in the container
docker compose --profile tools run --rm pipeline python -m pulse_pipeline run --source synthetic --ref-dir /work/data/reference --out /work/data/versions
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=DemoSeeder --force
```

## Test

```sh
docker compose exec app php vendor/bin/pest        # Laravel (runs against MySQL, database pulse_test)
docker compose --profile tools run --rm pipeline pytest   # pipeline
npm test                                           # frontend logic and components
npm run e2e                                        # scripted demo path (stack must be up)
```
