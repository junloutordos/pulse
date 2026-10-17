# PULSE

Pipeline Understanding, Location Intelligence, and Strategic Engagement.
Geospatial decision support for PSHS-CRC NCE outreach, built by Modern Voyagers for DOST GATES GeoHack 2026.

Design: `docs/specs/2026-10-17-pulse-design.md` · Plan: `docs/superpowers/plans/2026-10-17-pulse-implementation.md`

Synthetic data is always labeled. No personally identifiable information is used.

## Stack

Laravel 13 · Inertia.js · Vue 3 + TypeScript · Tailwind · MySQL **8.4.11** (pinned in `docker-compose.yml`) · Python 3.12 pipeline · MapLibre GL JS · Pest · Vitest · pytest · Playwright

## Run

```sh
cp .env.example .env
docker compose up -d --build        # app on http://localhost:8000, mysql 8.4.11
docker compose exec app php artisan migrate
docker compose exec app npm install && docker compose exec app npm run build
```

## Test

```sh
docker compose exec app php vendor/bin/pest        # Laravel (runs against MySQL, database pulse_test)
docker compose run --rm pipeline pytest            # pipeline (added in Task 2)
```
