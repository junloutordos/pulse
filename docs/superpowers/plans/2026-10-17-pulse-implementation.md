# PULSE Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the PULSE prototype: a Caraga NCE outreach decision-support app that runs end to end on labeled synthetic data and on approved real aggregates without code changes.

**Architecture:** A Python pipeline turns raw municipality aggregates (synthetic, local files, or later the GATES Lakehouse) into a versioned folder of Parquet, GeoJSON and a provenance JSON. A Laravel 13 app imports a version into MySQL and serves six Inertia + Vue screens. Scoring is implemented twice (Python official, TypeScript for live sliders) and pinned together by shared fixtures.

**Tech Stack:** Python 3.12 (DuckDB, GeoPandas, Pandera, esda), Laravel 13, Inertia.js, Vue 3 + TypeScript, Vite, Tailwind, Breeze, MySQL 8.4.x (latest stable patch, pinned), MapLibre GL JS, vue-echarts, DomPDF, Pest, Vitest, pytest, Playwright, Docker Compose.

**Spec:** `docs/specs/2026-10-17-pulse-design.md`

## Global Constraints

- Laravel 13, MySQL latest stable 8.4.x or newer with the exact patch pinned in Docker and recorded in the README. Python 3.12. Vue 3 with TypeScript.
- Synthetic seed is `2026`. Suppress counts below `5`. Low sample (mapped, never ranked) below `15` applicants. Neighbours are the `4` nearest. Grouping radius `28` km.
- Missing values are never zero. Municipalities with insufficient data are listed as "not ranked" with a reason.
- Qualification rate, straight-line distance and adjacency are context only and never labelled as evidence of access barriers.
- Every generated result carries admission cycle, dataset version, formula version and refresh date.
- Synthetic data is labelled in three places: a persistent app banner, a watermark on every brief and export, and the provenance `source` field.
- No personally identifiable information. No unapproved public-source imports into the sandbox. No external map or API keys.
- Staff-only access with roles `viewer` and `planner`. Public demo uses only organizer-cleared outputs.
- UI is clean and minimalist and avoids generic AI-generated design: no gradient heroes, glassmorphism, glow, emoji icons, identical rounded cards everywhere, or accent bars on cards. Hierarchy comes from type, spacing and alignment. Copy is plain and specific.
- Both light and dark themes. Keyboard accessible. Works on a laptop projector and a phone.
- AI narrative is optional, off by default, aggregate data only; fixed templates always work.
- Development stays in git with dated commits and a `CHANGELOG.md` (the event requires substantial development inside 8 Oct to 8 Nov).

## Review Focus

Inputs the spec implies but no feature test would otherwise exercise, most likely first:

- All three scoring weights set to zero: the app keeps the previous valid weights and says why, never divides by zero.
- Outreach slots greater than the number of rankable municipalities: the shortlist caps at the rankable count and says so.
- Only one cycle available: change versus prior cycle is null and the decline component drops out with weights renormalised.
- No valid Grade 6 denominator: population-normalised scoring is disabled and the UI states it.
- Same municipality name in different provinces (Carmen, Loreto, San Francisco): matched by province, never merged.

Each line has its test in the owning task below.

---

## File Structure

```
docker-compose.yml  docker/app/Dockerfile  docker/pipeline/Dockerfile
pipeline/pyproject.toml
pipeline/src/pulse_pipeline/{config,reference,synthetic,geo_match,validate,indicators,scoring,spatial,output,cli}.py
pipeline/src/pulse_pipeline/sources/{base,synthetic,local,lakehouse}.py
pipeline/tests/                      # includes tiny 6-municipality fixture
shared/fixtures/scoring_cases.json   # read by pytest and Vitest
data/reference/                      # PSGC list + boundaries (see Task 2)
data/versions/<version>/             # pipeline output, git-ignored except sample
app/Console/Commands/PulseImport.php
app/Services/Import/DatasetImporter.php
app/Models/{DatasetVersion,Municipality,MunicipalityIndicator,MunicipalityNeighbor,PlannerDecision,ActionRegisterEntry}.php
app/Http/Controllers/{Trust,Map,Pipeline,Priority,Planner,Brief,ActionRegister}Controller.php
resources/js/Pages/{Trust,Map,Pipeline,Priority,Planner,Brief}.vue
resources/js/lib/scoring.ts
resources/js/Components/{SyntheticBanner,StatusPill,MunicipalityMap,Funnel,LineageStrip}.vue
resources/css/tokens.css
resources/views/brief.blade.php
tests/{Feature,Unit}/   tests/e2e/demo.spec.ts   CHANGELOG.md
```

Team split (from the proposal, a suggestion): the Python tasks (2 to 6) and the Laravel tasks (7, 8) are independent once Task 1 lands, so Gilbert and Junlou can work them in parallel. Michael takes the Vue screens after Task 9.

---

## Phase A: by the 20 Oct check-in

### Task 1: Project scaffold and containers (17 Oct)

**Files:**
- Create: `docker-compose.yml`, `docker/app/Dockerfile`, `docker/pipeline/Dockerfile`, `.env.example`, `CHANGELOG.md`
- Modify: `README.md` (stack, pinned MySQL patch, run instructions)
- Test: `tests/Feature/HealthTest.php`

**Interfaces:**
- Produces: services `app` (Laravel 13, port 8000), `mysql` (8.4.x), `pipeline` (Python 3.12, one-off). Shared volume `./data`. Route `GET /up` returns 200.

- [ ] **Step 1:** Create the Laravel 13 project in the repo root (existing `docs/`, `README.md`, `.gitignore` stay). Add Breeze with the Vue + TypeScript Inertia stack.
- [ ] **Step 2:** Write `HealthTest::test_database_is_mysql_84_or_newer` asserting `SELECT VERSION()` is `>= 8.4` and `test_up_route_ok` asserting `GET /up` is 200.
- [ ] **Step 3:** Run `docker compose up -d --build` then `docker compose exec app php artisan test`. Expected: both tests fail, then pass once the compose file points at the pinned MySQL image. Record the pinned patch in the README.
- [ ] **Step 4:** Commit `chore: scaffold Laravel 13, Docker Compose, MySQL 8.4`.

### Task 2: Reference data and synthetic generator (17 to 18 Oct)

**Files:**
- Create: `pipeline/pyproject.toml`, `pipeline/src/pulse_pipeline/{config,reference,synthetic}.py`, `pipeline/tests/fixtures/ref_mini/{psgc.csv,boundaries.geojson}`
- Test: `pipeline/tests/test_synthetic.py`

**Interfaces:**
- Produces:
  - `config.py`: `SEED=2026`, `SUPPRESS_BELOW=5`, `LOW_SAMPLE_BELOW=15`, `NEIGHBOR_K=4`, `GROUP_RADIUS_KM=28.0`, `FORMULA_VERSION="1.0"`.
  - `reference.py`: `@dataclass Reference(munis: DataFrame, boundaries: GeoDataFrame)`; `load_reference(dir: Path) -> Reference`. `munis` columns: `psgc, name, province_psgc, province, lat, lon`.
  - `synthetic.py`: `generate_synthetic(ref: Reference, seed: int = SEED) -> DataFrame` with columns `raw_name, raw_province, cycle, applicants, examinees, qualifiers, grade6_pop` (nullable ints), two cycles. `PLANTED_CASES: dict[str, str]` naming each deliberate data problem.

**Open dependency:** real PSGC codes and Caraga boundaries need an authorized source (PSA code list; organizer-provided or authorized boundaries). Until confirmed, tests use the 6-municipality fixture and the full reference folder is a user-supplied input documented in the README.

- [ ] **Step 1: Failing tests** in `test_synthetic.py`: `test_same_seed_same_output` (two calls equal), `test_planted_cases_present` (alias spelling differs from reference name; one municipality has `applicants < 5`; one has no rows in the later cycle; one shows a decline above 20% versus the prior cycle), `test_missing_is_null_not_zero`.
- [ ] **Step 2:** Run `pytest pipeline/tests/test_synthetic.py -v`. Expected: FAIL (module missing).
- [ ] **Step 3:** Implement `load_reference` and `generate_synthetic` using `numpy.random.default_rng(seed)`. Rates fall with straight-line distance from the campus and attendance falls for island municipalities, so the data has a plausible spatial pattern.
- [ ] **Step 4:** Run the tests. Expected: PASS.
- [ ] **Step 5:** Commit `feat(pipeline): reference loader and seeded synthetic generator`.

### Task 3: PSGC matching and suppression rules (18 Oct)

**Files:**
- Create: `pipeline/src/pulse_pipeline/{geo_match,validate}.py`, `pipeline/aliases.yml`
- Test: `pipeline/tests/{test_geo_match,test_validate}.py`

**Interfaces:**
- Consumes: `Reference`, raw counts frame from Task 2.
- Produces:
  - `geo_match.py`: `@dataclass MatchResult(matched: DataFrame, issues: DataFrame)`; `match_psgc(raw: DataFrame, ref: Reference, aliases: dict[str, str]) -> MatchResult`. `matched` adds `psgc`. `issues` columns `raw_name, raw_province, reason`.
  - `validate.py`: `apply_suppression(df, suppress_below=SUPPRESS_BELOW, low_sample_below=LOW_SAMPLE_BELOW) -> DataFrame` adding `status` in `{"ok","low","suppressed","missing"}` and nulling suppressed counts; `reconcile(df, custodian_totals: DataFrame) -> DataFrame` with `scope, pulse_sum, custodian_sum, matches: bool`.

- [ ] **Step 1: Failing tests:**
  - `test_alias_spelling_matches` (e.g. "R.T. Romualdez" to Remedios T. Romualdez).
  - `test_same_name_different_province_not_merged` (Carmen in Agusan del Norte and Surigao del Sur resolve to different codes).
  - `test_unmatched_row_goes_to_issues_not_dropped_silently`.
  - `test_missing_cycle_is_status_missing_not_zero`.
  - `test_suppression_thresholds` (4 becomes `suppressed` with count null, 14 becomes `low`, 15 becomes `ok`).
  - `test_reconcile_flags_mismatch`.
- [ ] **Step 2:** Run `pytest pipeline/tests/test_geo_match.py pipeline/tests/test_validate.py -v`. Expected: FAIL.
- [ ] **Step 3:** Implement both modules. Match on normalised name plus province, falling back to `aliases.yml`. Validate schema with Pandera.
- [ ] **Step 4:** Run the tests. Expected: PASS.
- [ ] **Step 5:** Commit `feat(pipeline): PSGC matching, suppression, reconciliation`.

### Task 4: Indicators and priority scoring (18 Oct)

**Files:**
- Create: `pipeline/src/pulse_pipeline/{indicators,scoring}.py`, `shared/fixtures/scoring_cases.json`
- Test: `pipeline/tests/{test_indicators,test_scoring}.py`

**Interfaces:**
- Consumes: `apply_suppression` output.
- Produces:
  - `indicators.py`: `compute_indicators(df: DataFrame, cycle: int, prior_cycle: int | None) -> DataFrame` adding `rate_per_1000`, `attendance`, `change`, `rankable: bool`. Rate is null when `grade6_pop` is missing. `change` is null when `prior_cycle is None`.
  - `scoring.py`: `class Weights(NamedTuple): reach: float; attendance: float; decline: float`; `priority_scores(ind: DataFrame, w: Weights) -> DataFrame` with `psgc, score, c_reach, c_attendance, c_decline, rank, not_ranked_reason`. Components are 0 to 1 percentiles among rankable municipalities (low rate, low attendance, large decline score higher). Components unavailable for every municipality drop out and the remaining weights renormalise. All-zero weights raise `ValueError`.
  - `shared/fixtures/scoring_cases.json`: list of `{name, rows, weights, expected}` including the cases below. Both pytest and Vitest read it.

- [ ] **Step 1: Failing tests:** `test_rate_null_without_denominator`, `test_no_denominator_drops_reach_component_and_renormalises`, `test_single_cycle_drops_decline_component`, `test_low_and_suppressed_never_ranked` (reason is non-empty), `test_all_zero_weights_raises`, `test_scoring_matches_shared_fixtures` (loops `scoring_cases.json`).
- [ ] **Step 2:** Run `pytest pipeline/tests/test_indicators.py pipeline/tests/test_scoring.py -v`. Expected: FAIL.
- [ ] **Step 3:** Implement. Percentile by `rank(pct=True)` over rankable rows.
- [ ] **Step 4:** Run the tests. Expected: PASS.
- [ ] **Step 5:** Commit `feat(pipeline): indicators and explainable priority score`.

### Task 5: Spatial analysis (18 Oct)

**Files:**
- Create: `pipeline/src/pulse_pipeline/spatial.py`
- Test: `pipeline/tests/test_spatial.py`

**Interfaces:**
- Produces: `knn_neighbors(munis: DataFrame, k: int = NEIGHBOR_K) -> DataFrame` with `psgc, neighbor_psgc, order, km`; `nearby(munis: DataFrame, radius_km: float = GROUP_RADIUS_KM) -> DataFrame` same columns; `local_morans(ind: DataFrame, neighbors: DataFrame, permutations: int = 999, seed: int = SEED) -> DataFrame` with `psgc, lisa, p_sim, cluster` where `cluster in {"HH","LL","HL","LH","ns"}`. A cluster label other than `ns` requires `p_sim < 0.05`. Only rows with `status in {"ok","low"}` participate.

- [ ] **Step 1: Failing tests:** `test_knn_returns_k_neighbours_excluding_self`, `test_nearby_respects_radius`, `test_cluster_requires_significance` (random data yields mostly `ns`), `test_spatially_clustered_toy_data_yields_HH_and_LL`, `test_suppressed_excluded_from_morans`.
- [ ] **Step 2:** Run `pytest pipeline/tests/test_spatial.py -v`. Expected: FAIL.
- [ ] **Step 3:** Implement with haversine distances and `esda.Moran_Local`.
- [ ] **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(pipeline): neighbours, groupable municipalities, local Moran's I`.

### Task 6: Sources, output writer and CLI (18 Oct)

**Files:**
- Create: `pipeline/src/pulse_pipeline/{output,cli}.py`, `pipeline/src/pulse_pipeline/sources/{base,synthetic,local}.py`
- Test: `pipeline/tests/{test_output,test_cli}.py`

**Interfaces:**
- Produces:
  - `sources/base.py`: `class Source(Protocol): def load(self) -> tuple[DataFrame, Provenance]`. `SyntheticSource(ref, seed)` and `LocalFilesSource(counts_csv, custodian_csv | None)`.
  - `output.py`: `@dataclass Provenance(source: Literal["synthetic","real"], cycle: int, prior_cycle: int | None, dataset_version: str, formula_version: str, refreshed_at: str, schema_version: int)`; `write_version(out_dir: Path, tables: dict[str, DataFrame], boundaries: GeoDataFrame, prov: Provenance) -> Path`. Writes `indicators.parquet`, `neighbors.parquet`, `munis.parquet`, `reconciliation.parquet`, `issues.parquet`, `boundaries.geojson`, `provenance.json` with SHA-256 of every file.
  - CLI: `python -m pulse_pipeline run --source {synthetic,local} --ref-dir PATH --out PATH [--counts CSV --custodian CSV]` prints the version folder path. Version string `YYYY.MM.DD-syn` or `-real`.

- [ ] **Step 1: Failing tests:** `test_written_checksums_match_files`, `test_provenance_source_flag_matches_source`, `test_cli_synthetic_end_to_end` (runs on the mini fixture, asserts all files exist), `test_real_and_synthetic_produce_identical_schemas`.
- [ ] **Step 2:** Run `pytest pipeline/tests/test_output.py pipeline/tests/test_cli.py -v`. Expected: FAIL.
- [ ] **Step 3:** Implement orchestration: source, match, suppress, reconcile, indicators, spatial, write.
- [ ] **Step 4:** Run. Expected: PASS. Then `docker compose run pipeline python -m pulse_pipeline run --source synthetic ...` and confirm the version folder exists.
- [ ] **Step 5:** Commit `feat(pipeline): sources, versioned output, CLI`.

### Task 7: Import into MySQL (19 Oct)

**Files:**
- Create: migrations for `dataset_versions`, `municipalities`, `municipality_indicators`, `municipality_neighbors`, `planner_decisions`, `action_register_entries`; the six models; `app/Services/Import/DatasetImporter.php`; `app/Console/Commands/PulseImport.php`; a 6-municipality sample version at `tests/fixtures/version_mini/`
- Test: `tests/Feature/ImportTest.php`

**Interfaces:**
- Consumes: the version folder from Task 6.
- Produces: `DatasetImporter::import(string $versionDir): DatasetVersion` (transactional; verifies checksums; marks it the only active version). Command `php artisan pulse:import {versionDir} {--activate}`. `DatasetVersion` columns `version, source ('synthetic'|'real'), cycle, prior_cycle, formula_version, refreshed_at, checksums (json), is_active`. `MunicipalityIndicator` keyed `(dataset_version_id, psgc)` with nullable `applicants, examinees, qualifiers, grade6_pop, prior_applicants, rate_per_1000, attendance, change`, `status`, `lisa_cluster`.

- [ ] **Step 1: Failing tests:** `test_import_loads_all_tables`, `test_checksum_mismatch_aborts_and_changes_nothing`, `test_missing_values_stay_null_not_zero`, `test_only_one_active_version`, `test_reimport_same_version_is_idempotent`.
- [ ] **Step 2:** Run `docker compose exec app php artisan test --filter=ImportTest`. Expected: FAIL.
- [ ] **Step 3:** Implement migrations, models and importer.
- [ ] **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(app): dataset import and schema`.

### Task 8: Roles and shared dataset context (19 Oct)

**Files:**
- Modify: `app/Models/User.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `routes/web.php`
- Create: migration adding `users.role` (`viewer|planner`), `app/Policies` or `Gate::define('plan')`, `resources/js/Components/SyntheticBanner.vue`
- Test: `tests/Feature/{RolesTest,SyntheticBannerTest}.php`

**Interfaces:**
- Produces: Inertia shared prop `dataset: {version, source, cycle, priorCycle, refreshedAt} | null`; gate `plan` (planner only); middleware group so every screen requires login.

- [ ] **Step 1: Failing tests:** `test_guest_redirected_to_login`, `test_viewer_cannot_post_decisions` (403), `test_planner_can`, `test_shared_dataset_prop_reports_synthetic_source`.
- [ ] **Step 2:** Run the tests. Expected: FAIL.
- [ ] **Step 3:** Implement. `SyntheticBanner` renders only when `dataset.source === 'synthetic'`.
- [ ] **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(app): roles, auth gate, synthetic banner`.

### Task 9: Visual direction and design tokens (19 Oct, needs your choice)

**Files:**
- Create: `docs/design/visual-direction.md`, `resources/css/tokens.css`, `resources/js/Layouts/AppLayout.vue`, `scripts/check-contrast.mjs`
- Test: `resources/js/__tests__/tokens.test.ts`

**Interfaces:**
- Produces: CSS custom properties for light and dark: surface, ink, muted, line, accent, and status colours (ok, low, suppressed, missing); three type roles (display, body, figures); spacing scale. `AppLayout` holds the nav (the six screens), dataset chip and theme toggle.

- [ ] **Step 1:** Present two or three minimalist directions as a private artifact page (clean, no AI-generated tells, premium). The user picks one. Record the choice and rationale in `docs/design/visual-direction.md`.
- [ ] **Step 2: Failing tests:** `tokens.test.ts` asserts every required token exists in the `:root`, dark-mode and `[data-theme=dark]` blocks; `check-contrast.mjs` exits non-zero if any text token is under 4.5:1 against its surface in either theme.
- [ ] **Step 3:** Run `npm run test -- tokens` and `node scripts/check-contrast.mjs`. Expected: FAIL.
- [ ] **Step 4:** Implement tokens and `AppLayout`.
- [ ] **Step 5:** Run both. Expected: PASS. Commit `feat(ui): visual direction, tokens, app layout`.

### Task 10: Data Trust screen (19 to 20 Oct)

**Files:**
- Create: `app/Http/Controllers/TrustController.php`, `resources/js/Pages/Trust.vue`, `resources/js/Components/{StatusPill,LineageStrip}.vue`
- Test: `tests/Feature/TrustScreenTest.php`

**Interfaces:**
- Produces: `GET /trust` renders `Trust` with props `{counts: {matched, total, aliasFixes, sharedNames, low, suppressed, missing}, lineage: Step[], reconciliation: Row[], issues: Issue[]}`. `Step` is `{label, detail, ref}`.

- [ ] **Step 1: Failing tests:** `test_trust_props_match_imported_version`, `test_issues_list_names_reason_for_each_flagged_municipality`, `test_lineage_includes_dataset_version_and_formula_version`.
- [ ] **Step 2:** Run `php artisan test --filter=TrustScreenTest`. Expected: FAIL.
- [ ] **Step 3:** Implement the controller and page.
- [ ] **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(ui): Data Trust screen`.

### Task 11: Participation Map (20 Oct, minimal for check-in)

**Files:**
- Create: `app/Http/Controllers/MapController.php`, `resources/js/Pages/Map.vue`, `resources/js/Components/{MunicipalityMap,Funnel}.vue`
- Test: `tests/Feature/MapScreenTest.php`, `resources/js/__tests__/breaks.test.ts`

**Interfaces:**
- Produces: `GET /map` with props `{rows: IndicatorRow[], metric options}`; `GET /data/boundaries.geojson` (auth). `quantileBreaks(values: (number | null)[], bins = 5): number[]` in `resources/js/lib/breaks.ts`, ignoring nulls. Suppressed and missing municipalities render as unfilled and never enter breaks.

- [ ] **Step 1: Failing tests:** `test_map_props_exclude_suppressed_values` (counts are null), `test_boundaries_requires_auth`, and Vitest `quantileBreaks ignores nulls`.
- [ ] **Step 2:** Run both suites. Expected: FAIL.
- [ ] **Step 3:** Implement with MapLibre fed from the GeoJSON, with metric selector (including the spatial-cluster view), selection, and a detail panel showing the funnel versus region. The panel shows straight-line distance to the campus labelled "context only" when campus coordinates exist in the dataset, and omits it otherwise.
- [ ] **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(ui): participation map`. Then record the 20 Oct demo path in `CHANGELOG.md`.

**20 Oct check-in deliverable:** Tasks 1 to 8 and 10 to 11 running on synthetic data, with a written list of remaining access gaps.

---

## Phase B: 21 to 28 Oct

### Task 12: Lakehouse adapter (21 Oct, conditional)

**Files:** Create `pipeline/src/pulse_pipeline/sources/lakehouse.py`; Test `pipeline/tests/test_lakehouse.py`.

**Interfaces:** Produces `LakehouseSource(connection: str, table: str)` implementing `Source`. Interface details depend on what the sandbox exposes at onboarding, so fill in the connection method (DuckDB reader, SQL endpoint or files) from the real sandbox and record it in the README.

- [ ] **Step 1:** Write `test_lakehouse_source_returns_same_schema_as_synthetic` against a local stand-in table.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(pipeline): GATES Lakehouse source`. If the sandbox is unavailable, skip and note it in the access-gap list.

### Task 13: TypeScript scoring with parity (21 Oct)

**Files:** Create `resources/js/lib/scoring.ts`; Test `resources/js/__tests__/scoring.test.ts`.

**Interfaces:** Produces `priorityScores(rows: IndicatorRow[], w: Weights): ScoredRow[]` and `type Weights = {reach: number; attendance: number; decline: number}`. Throws `AllZeroWeightsError`.

- [ ] **Step 1: Failing tests:** `matches shared fixtures` (loops `shared/fixtures/scoring_cases.json`, tolerance 1e-9), `drops decline when no prior cycle`, `drops reach when no denominator`, `all zero weights throws AllZeroWeightsError`.
- [ ] **Step 2:** Run `npm run test -- scoring`. Expected: FAIL. **Step 3:** Implement. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(ui): TypeScript scoring with Python parity`.

### Task 14: Pipeline Explorer (22 Oct)

**Files:** Create `PipelineController.php`, `Pages/Pipeline.vue`; Test `tests/Feature/PipelineScreenTest.php`.

**Interfaces:** `GET /pipeline` props `{rows, regionMedians: {rate, attendance}}`. Quadrant chart (vue-echarts) of rate against attendance; low-sample rows drawn hollow; suppressed and missing rows absent with a count note.

- [ ] **Step 1: Failing tests:** `test_region_medians_exclude_suppressed_and_missing`, `test_low_sample_rows_flagged`.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(ui): pipeline explorer`.

### Task 15: Priority list and sensitivity (23 to 24 Oct)

**Files:** Create `PriorityController.php`, `Pages/Priority.vue`, `resources/js/lib/rankBand.ts`; Tests `tests/Feature/PriorityScreenTest.php`, `resources/js/__tests__/rankBand.test.ts`.

**Interfaces:** `rankBand(rows: IndicatorRow[], presets: Record<string, Weights>, id: string): {lo: number; hi: number; stable: boolean}`. Presets: Balanced (1/3 each), Reach first (0.6/0.2/0.2), Attendance first (0.25/0.55/0.2). `stable` is true when the band spans at most 4 ranks and the worst rank is within the top 10. **(my call, tune with the statistician.)**

- [ ] **Step 1: Failing tests:** `rankBand returns lo and hi across presets`, `all zero weights keeps previous weights and shows message` (Vitest component test), `not ranked list includes reason`, and the Pest prop test.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement sliders, presets, component bars and suggested-focus text from the rule in the spec. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat(ui): priority list with sensitivity`.

### Task 16: Outreach Planner (25 to 26 Oct)

**Files:** Create `PlannerController.php`, `Pages/Planner.vue`, migration tweaks if needed; Test `tests/Feature/PlannerTest.php`, `resources/js/__tests__/shortlist.test.ts`.

**Interfaces:**
- `shortlist(scored: ScoredRow[], slots: number): {picks: ScoredRow[]; capped: boolean}` in `resources/js/lib/shortlist.ts`; `capped` is true when `slots` exceeds rankable count.
- `POST /planner/decisions` body `{psgc, status: 'accepted'|'deferred', reason: string, weights}` (planner only). Accepting creates an `ActionRegisterEntry` with status `planned`.

- [ ] **Step 1: Failing tests:** `slots larger than rankable count caps and flags`, `test_viewer_cannot_record_decision`, `test_accept_creates_register_entry`, `test_defer_removes_register_entry`, `test_reason_required`.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement, including nearby-municipality suggestions from `municipality_neighbors` within `28` km. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: outreach planner and decisions`.

### Task 17: Evidence brief and action register (27 to 28 Oct)

**Files:** Create `BriefController.php`, `ActionRegisterController.php`, `resources/views/brief.blade.php`, `Pages/Brief.vue`; Test `tests/Feature/BriefTest.php`.

**Interfaces:** `GET /brief/{psgc}` HTML; `GET /brief/{psgc}.pdf` via DomPDF **(my call over Browsershot to avoid a Chrome dependency in Docker)**; `PATCH /register/{id}` marks completed (planner). The brief contains observations, score components, proposed action, responsible office, next review date, limitations, and a provenance line with cycle, dataset version, formula version and refresh date.

- [ ] **Step 1: Failing tests:** `test_brief_contains_provenance_fields`, `test_synthetic_brief_has_watermark_in_html_and_pdf`, `test_unranked_municipality_brief_states_reason_and_no_score`, `test_pdf_returns_200_application_pdf`, `test_register_complete_requires_planner`.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: evidence brief, PDF and action register`.

---

## Phase C: 29 Oct to 8 Nov

### Task 18: Demo path and regression suite (29 to 31 Oct)

**Files:** Create `tests/e2e/demo.spec.ts`, `playwright.config.ts`, `database/seeders/DemoSeeder.php`.

- [ ] **Step 1:** Write `demo.spec.ts` covering: login as planner, banner visible, Data Trust shows flagged records, map selection, priority weight change reorders rows, planner with 5 slots, accept one, brief opens with watermark, register shows the entry. Also assert no console errors and a viewer account cannot accept.
- [ ] **Step 2:** Run `npx playwright test`. Expected: FAIL until the seeder loads the synthetic version and demo users. **Step 3:** Implement `DemoSeeder`. **Step 4:** Run. Expected: PASS, in under 5 minutes of scripted flow.
- [ ] **Step 5:** Commit `test: scripted demo path`.

### Task 19: Handover package and pilot kit (1 to 5 Nov)

**Files:** Create `docs/handover/{README,indicators,validation-rules,refresh-runbook,licenses}.md`, `docs/pilot/{task-sheet,timing-log}.md`; Modify `CHANGELOG.md`.

- [ ] **Step 1:** Write the runbook so a clean machine reaches a working app from `git clone` with the commands listed; verify by doing exactly that in a fresh directory. Expected: login screen loads with the synthetic version.
- [ ] **Step 2:** List every third-party library with its license and attribution. Document the pinned MySQL patch, data steward and maintainer roles, and the real-data refresh procedure.
- [ ] **Step 3:** Prepare the three-staff pilot sheet (matched tasks, timing, accuracy, usability) as a stretch target.
- [ ] **Step 4:** Commit `docs: handover package and pilot kit`.

### Task 20 (optional): AI narrative, off by default (6 Nov, only if approved and time remains)

**Files:** Create `app/Services/Narrative/{TemplateNarrative,ClaudeNarrative}.php`, `config/pulse.php`; Test `tests/Feature/NarrativeTest.php`.

**Interfaces:** `interface Narrative { public function summarize(array $briefData): string; }`. `config('pulse.ai_narrative')` defaults `false`; the template implementation is used unless enabled and an approved service is configured. Only aggregate fields in `$briefData` are sent.

- [ ] **Step 1: Failing tests:** `test_default_uses_template_and_makes_no_http_call` (`Http::fake()` asserts nothing sent), `test_payload_contains_no_fields_outside_allowlist`.
- [ ] **Step 2:** Run. Expected: FAIL. **Step 3:** Implement. **Step 4:** Run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: optional AI narrative`.

### Task 21: Freeze (7 to 8 Nov)

- [ ] **Step 1:** Run the full suites: `pytest`, `php artisan test`, `npm run test`, `npx playwright test`. Expected: all pass.
- [ ] **Step 2:** Tag `v1.0.0-geohack`, update `CHANGELOG.md`, push. Prepare the 10-minute technical and 5-minute executive pitches using the demo path.
