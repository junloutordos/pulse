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
