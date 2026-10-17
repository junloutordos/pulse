# Changelog

## 2026-10-17
- Scaffold Laravel 13 with Breeze (Vue + TypeScript) and Pest 4.
- Docker Compose with `app`, `mysql` (8.4.11) and a one-off `pipeline` service.
- Health tests: MySQL is 8.4 or newer, `/up` returns 200.
- Pipeline package skeleton, reference loader and seeded synthetic generator (5 planted data problems).
- PSGC matching (exact, alias, province-aware), suppression rules and reconciliation.
- Indicators (rate per 1,000, attendance, change) and explainable priority score with shared fixtures.
- Neighbours, groupable municipalities and local Moran's I clusters (two-sided, seeded).
- Source protocol, versioned output writer with checksums, and the pipeline CLI.
- Dataset import into MySQL (versioned, checksum-verified, idempotent) and the pulse:import command.
- Roles (viewer/planner), shared dataset prop, synthetic banner.
- TypeScript priority scoring with Python parity (shared fixtures) and Vitest.
- Lakehouse source (DuckDB connection stand-in) and `--source lakehouse` CLI option.
- Visual direction (Atlas, sans headings), design tokens with contrast and theme tests, app layout, start page.
- Demo seeder and a Playwright script for the whole source-to-decision demo path.
- Optional AI-assisted brief summary (off by default, aggregate-only payload, template fallback).
- Review fixes: self-registration removed (staff accounts via `pulse:user`), start page removed, same-day version names and a refusal to overwrite a version with different content, plain-language notices when a dataset has no Grade 6 population or only one cycle, saved weights re-checked against each dataset, region exam attendance no longer counts withheld values as zero, brief states which weights its rank uses, keyboard municipality picker on the map, planner account deletion keeps decisions, generated files untracked, e2e reset guarded.
- Real reference data: official 10-digit PSGC codes and open boundary polygons for all 73 Caraga municipalities (replaces the approximate placeholder), plus equal-area hexagon tiles; 'City of X' and 'X City' now match.
- Redesign: deep navy shell, numbered workflow stepper, Bricolage Grotesque and Hanken Grotesk, new tokens and components, a full-bleed map with Tiles and Boundaries modes, hover cards, hatching for no-value areas, and rebuilt Start, Data trust, Pipeline, Priority, Planner, Brief and sign-in screens.
