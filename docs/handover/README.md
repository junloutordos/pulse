# PULSE handover

PULSE helps PSHS-CRC decide which Caraga municipalities to prioritize for NCE outreach, and records why. This folder is for the people who will run and maintain it after the GATES sandbox closes.

## What is in the box

| Part | What it does | Where |
|---|---|---|
| Pipeline (Python 3.12) | Turns municipality aggregates into a versioned folder (Parquet, JSONL, GeoJSON, provenance with checksums) | `pipeline/` |
| App (Laravel 13, Inertia, Vue 3) | Imports a version into MySQL and serves six screens, the evidence brief (HTML and PDF) and the action register | `app/`, `resources/` |
| Database | MySQL 8.4.11 (pinned in `docker-compose.yml`) | Docker service `mysql` |
| Reference data | Municipality list and boundaries. **The copy in `data/reference/` is approximate and for development only** (placeholder codes, Voronoi shapes). Replace it with the official PSGC list and boundaries. | `data/reference/` |

## Roles to name before the sandbox closes

- **Campus data steward:** owns the approved aggregates, the custodian totals and the suppression thresholds (5 and 15).
- **IT maintainer:** runs the stack, applies updates, owns backups and access.

## Start from a clean machine

Needs Docker, Git and Node 22 or newer on the host.

```sh
git clone git@github.com:junloutordos/pulse.git && cd pulse
cp .env.example .env
docker compose up -d --build          # app on http://localhost:${APP_PORT:-8000}; installs PHP packages on first start
npm install && npm run build          # run on the host, not in the container
docker compose --profile tools run --rm pipeline \
  python -m pulse_pipeline run --source synthetic --ref-dir /work/data/reference --out /work/data/versions
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=DemoSeeder --force   # demo users and the newest dataset (never in production)
```

Sign in at `/login` as `planner@pulse.test` or `viewer@pulse.test`, password `password`. Change or delete these accounts before any real use.

## Tests

```sh
docker compose exec app php vendor/bin/pest                 # Laravel
docker compose --profile tools run --rm pipeline pytest     # pipeline
npm test                                                    # frontend logic and components
npm run e2e                                                 # scripted demo path (needs the stack up)
node scripts/check-contrast.mjs                             # colour contrast of the design tokens
```

## What PULSE will not do

It does not use personal data, replace PSHSS or DOST-SEI systems, estimate travel time, or claim that an outreach action caused a change. Synthetic data is labelled in the banner, on every brief and in the provenance record.

## Hosting after the event

Sandbox access ends with the event. To keep PULSE running, host the Docker Compose stack on a campus server, set `APP_ENV=production`, a real `APP_KEY`, strong database passwords and HTTPS in front, create real staff accounts, and back up the `mysql-data` volume. Review privacy, access and maintenance with the data steward first.

## More

- `indicators.md` definitions, `validation-rules.md` data rules, `refresh-runbook.md` each NCE cycle, `licenses.md` third-party software.
- `../specs/2026-10-17-pulse-design.md` is the design; `../design/visual-direction.md` is the visual direction.
