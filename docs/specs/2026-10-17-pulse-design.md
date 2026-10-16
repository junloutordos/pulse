# PULSE Design

Status: approved 17 Oct 2026 · Team: Modern Voyagers (PSHS-CRC) · GATES GeoHack 2026
Development period: 8 Oct to 8 Nov 2026 · Mandatory check-ins: 20 Oct, 3 Nov · Freeze: 8 Nov

Items marked **(my call)** are decisions I made for the builder, not decisions you made. Check them while reading.

## 1. Intent

PULSE (Pipeline Understanding, Location Intelligence, and Strategic Engagement) helps PSHS-Caraga Region Campus (PSHS-CRC) decide which municipalities to prioritize for National Competitive Examination (NCE) outreach, and records why.

Today the applicant, examinee and qualifier records are separate, municipality names are inconsistent, and staff spend substantial time reconciling spreadsheets before outreach can even be discussed. The result is hard to reproduce, compare across cycles or explain.

PULSE turns authorized, non-identifying municipality aggregates into an auditable outreach shortlist: where participation warrants attention, which stage of the admission pathway needs review, how strong the evidence is, and what action to consider. It is decision support. Staff validate every suggestion.

The submitted proposal (Annex A) is the governing scope. The concept paper (national, full pipeline through SEI scholarships, AI-led) is the long-term roadmap and appears in the pitch only as a future direction.

## 2. Goals, non-goals, anti-goals

**Goals**
- One working source-to-decision workflow for Caraga: approved data in, geography validated, map, pipeline view, ranked shortlist, limited-slot scenario, printed evidence brief, action record.
- Make geography do analytical work: adjacency grouping, spatial clusters, and context distance, not only a coloured map.
- Make data quality visible: a Data Trust screen, suppression, and a "not ranked" state.
- Make the GATES Lakehouse visibly load-bearing through a lineage view.
- Run end to end on synthetic data, and on real approved aggregates when they arrive, with no code change.

**Non-goals (this hackathon)**
- Graduation, SEI scholarship and national-scale analysis (roadmap slide only).
- Routing or travel-time estimates.
- Individual-level or personally identifiable data of any kind.
- Replacing any PSHSS or DOST-SEI operational system.
- AI as a dependency. AI narrative is optional and off by default.

**Anti-goals (failure even if it technically works)**
- A map that only decorates and drives no decision.
- Treating missing values as zero, or ranking municipalities with insufficient data.
- Presenting qualification rates, distance or proximity as evidence of inequity or access barriers.
- Presenting synthetic data as real in any screen, export or screenshot.
- Claims that PULSE caused an outcome.

## 3. Users

- **Primary:** PSHS-CRC outreach planners, the registrar and the Campus Director.
- **Pilot validators:** three campus staff running matched planning tasks (target: at least 50% lower median preparation time with full reconciliation to approved totals).
- **Later adopters:** other PSHS campuses, PSHSS OED, DOST regional offices and SEI, after validation.

## 4. Data

### 4.1 Inputs
| Input | Status | Fallback |
|---|---|---|
| Non-identifying NCE counts by municipality, cycle and stage (applicants, examinees, optionally qualifiers), plus cohort and coverage definitions | Access unconfirmed. Minimum: one Caraga cycle. | Synthetic data |
| Second comparable cycle | Included if available | Synthetic |
| PSGC code list and municipality boundaries | Core. Versions and sandbox use to confirm at onboarding. | Real PSGC codes and boundaries from an authorized source |
| Target-grade population (Grade 6) or age-band proxy | Unconfirmed. A proxy is not an exact NCE-eligible count. | Without a valid denominator, disable population-normalized scoring |
| PSHS-CRC and testing-center coordinates | Not secured | Omit distance context |
| GATES Lakehouse sandbox | Access expected at onboarding. Interfaces unconfirmed. | Local files through the same pipeline |

No student names, ID numbers, addresses or other personal information are used or needed. No unapproved public-source imports.

### 4.2 Synthetic data (first-class fallback)
- One pipeline serves real and synthetic data. The app cannot tell them apart apart from a `source` flag in the dataset version.
- Generated for all Caraga municipalities with real PSGC codes and real boundaries. Two cycles of applicants, examinees and qualifiers, plus a Grade 6 denominator.
- Deliberately includes messy cases so every data-quality feature has something to show: renamed or misspelled municipalities, shared names across provinces, suppressed small cells, one municipality missing a cycle, a cycle-over-cycle decline, a high-application low-attendance outlier.
- Reproducible from a fixed seed (2026).
- Always labeled: a persistent banner in the app, a watermark on every brief and export, and a provenance field naming the source. **(my call: this is my reading of the organizers' "labeled synthetic demonstration" requirement.)**
- Switching to real data is a configuration change plus an import.

### 4.3 Rules (apply to all sources)
- Missing values are never zero.
- Counts below 5 are suppressed and not mapped or ranked. **(my call: threshold 5, to be set with the data steward.)**
- Fewer than 15 applicants: mapped and flagged "low sample", never ranked. **(my call: threshold 15.)**
- Municipalities with incomplete or non-comparable data are listed as "not ranked", with the reason.
- Qualification is context, not evidence of access.
- Distance is straight-line and context only. Adjacency does not imply shared barriers or feasible travel routes.
- Every generated result carries admission cycle, dataset version, formula version and refresh date.

## 5. Analysis

- **Indicators:** applications per 1,000 Grade 6 learners, exam attendance (examinees / applicants), change versus prior cycle. Qualification rate is context only.
- **Priority score:** three transparent components (low application rate, low attendance, decline), each converted to a 0 to 1 percentile among rankable municipalities, then combined with weights. Default weights are equal. **(my call.)** Weights are planning preferences, not predictive claims.
- **Sensitivity:** weight sliders and three presets (Balanced, Reach first, Attendance first). Each municipality shows its rank band across presets and a Stable or Sensitive tag.
- **Suggested focus (rule-based):** information and application campaign when participation is the dominant component; exam-reminder and access review when attendance is; follow-up with feeder schools when decline is. Suggestions only.
- **Spatial:** four-nearest-neighbour adjacency, local Moran's I clusters on application rate (precomputed in Python, with a permutation test before any cluster is labelled), nearby-municipality grouping within about 28 km **(my call)** for the planner.
- **Official numbers** are computed in Python. The browser recomputes the score live for sliders. Shared test cases must show both implementations agree.

## 6. Product

Six screens in demo order. The clickable mockup (artifact link in the project notes) shows content and flow. It is a structural reference, not the visual design (see section 8).

1. **Data Trust.** PSGC matching, lineage strip (Lakehouse table, validation, geography, indicators, dataset version), reconciliation against custodian totals, flagged records.
2. **Participation Map.** Choropleth by municipality. Metrics: applications per 1,000, attendance, change versus prior cycle, priority score, spatial clusters. Side panel with counts, rates, status and a funnel compared with the region.
3. **Pipeline Explorer.** Application to examination (and qualification where cohorts align). Scatter of application rate against attendance with regional-median quadrants, to separate "few apply" from "many miss the exam".
4. **Priority List.** Ranked table with component bars, weight sliders, presets, rank bands, suggested focus, and the not-ranked group.
5. **Outreach Planner.** Staff set the number of slots. PULSE suggests a shortlist, shows groupable neighbours, and staff Accept or Defer each with a recorded reason.
6. **Evidence Brief and Action Register.** One-page printable brief per municipality (observations, score components, proposed action, responsible office, next review date, limitations, provenance). Accepted municipalities enter an action register (planned or completed) for next-cycle comparison.

**Access:** authenticated staff only, with viewer and planner roles. Public demonstrations use only organizer-cleared outputs.

**Optional AI narrative:** only if an approved service exists, only on releasable aggregate data, off by default, with fixed templates always working.

## 7. Technical approach

Chosen by the team, except where marked.

| Layer | Choice |
|---|---|
| Pipeline | Python 3.12 **(my call)**, DuckDB, GeoPandas, Pandera. Outputs versioned Parquet and GeoJSON plus a provenance JSON. |
| App | Laravel 13, Inertia.js, Vue 3 with TypeScript, Vite |
| Database | MySQL, latest stable 8.4.x or newer. Pin the exact patch version in Docker and record it. |
| Auth | Laravel Breeze, viewer and planner roles |
| UI | Tailwind plus a Vue component library **(my call: shadcn-vue or PrimeVue, decided during design-system work)** |
| Map | MapLibre GL JS, boundaries as GeoJSON or PMTiles, no API keys, offline-capable basemap |
| Charts, tables | vue-echarts, TanStack Table (Vue) |
| Briefs | Blade print view, PDF via Browsershot or DomPDF **(my call)** |
| Testing | Pest, Vitest, pytest, Playwright for the scripted demo path |
| Deployment | Docker Compose: `app`, `mysql`, one-off `pipeline` |

**Data flow:** Lakehouse or synthetic generator, Python pipeline, versioned files, `artisan pulse:import {version}`, MySQL, Laravel and Inertia, Vue screens.

**MySQL holds** PSGC codes, indicators, `dataset_versions` (source, cycle, refresh date, checksums, formula version), neighbours, users, the action register and decision reasons. **Geometry stays in files.** MySQL spatial types are unnecessary for about 73 municipalities.

Unconfirmed: what interfaces the GATES sandbox exposes. DuckDB reads most formats, so only the ingestion step should change.

## 8. Visual and interaction design

The product must look premium, in the sense of a considered government-grade analytics product, not a template dashboard.

- **Clean and minimalist (user direction, 17 Oct).** Generous whitespace, few elements per screen, one clear job per screen. Every element earns its place. No decoration that carries no information.
- **Avoid generic AI-generated design (user direction, 17 Oct).** No purple-to-blue gradients, glassmorphism, glowing cards, emoji as icons or section markers, stock hero sections, identical rounded cards everywhere, accent bars on cards, or default Inter-plus-shadcn look. Hierarchy comes from type, spacing and alignment rather than boxes and shadows. Copy is plain and specific, not marketing language.
- Calm, high-contrast, restrained palette with one accent and semantic colours reserved for data status. Light and dark themes.
- Characterful display typeface with a legible body face and a monospaced face for figures.
- Real geographic shapes: municipality polygons, not dots, with smooth hover, selection and transitions.
- Data trust and provenance are part of the visual language: status pills, version chips, the synthetic-data band and watermark.
- Print-quality evidence brief.
- Works on a laptop projector and on a phone. Keyboard accessible.
- **(my call:)** the mockup's visual style is a placeholder. A short visual-direction pass (two or three minimalist directions for the user to choose from) happens before screens are built, and is an early task in the build plan.

## 9. Judging priorities

No rubric has been released. Spend polish where the mechanics hint: meaningfully geospatial, visible Lakehouse use, privacy and responsible data, realistic feasibility, substantial development in the event window, plus impact, originality and demo quality.

1. Geography doing analytical work (clusters, adjacency grouping, context distance).
2. Lineage panel proving Lakehouse use.
3. Responsible data visible in the product.
4. A scripted five-minute decision story, Playwright-tested.
5. Pilot evidence: timings and staff feedback.
6. Dated git history and a changelog.

Deliberately not polished: landing page, decorative animation, national-scale views, AI.

## 10. Milestones

| Date | Outcome |
|---|---|
| 20 Oct check-in | Repo, Docker, synthetic generator, import command, Data Trust screen, working map, data-quality report, list of remaining access gaps |
| 21 to 28 Oct | Priority, sensitivity, planner, brief, action register, auth |
| 29 Oct to 8 Nov | Pilot tasks with three campus staff, regression suite, handover package, pitches |
| 3 Nov check-in | End-to-end demo with evidence |
| 8 Nov | Freeze |

## 11. Handover and sustainability

Sandbox access ends after the event. The handover package: source, Docker Compose, indicator definitions, validation rules, test results, licenses and attributions, deployment and refresh instructions, and a named campus data steward plus IT maintainer. Open-source libraries are listed with licenses.

## 12. Risks

| Risk | Mitigation |
|---|---|
| Real data late or never | Synthetic first-class path |
| Dense scope for 4 weeks | One region, one workflow, defer AI, SEI and distance |
| Small cells and incomplete coverage | Suppression, low-sample flag, not-ranked state |
| Sandbox interfaces unknown | Thin ingestion layer, local-file fallback |
| Hosting after the event | Docker handover, steward named |
| Pilot staff unavailable | Treat the pilot as a stretch target and say so in the pitch |

## 13. Open questions for the team

- What fields and cycles does the GATES sandbox actually hold? (Blocks real-data work.)
- Is a Grade 6 denominator or age-band proxy available?
- Are campus and testing-center coordinates available?
- Who is the campus data steward?
- Are suppression thresholds (5 and 15) acceptable to the data custodian?
