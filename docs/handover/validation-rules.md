# Validation rules

Applied in this order by `python -m pulse_pipeline run`:

1. **Schema.** Counts must be whole numbers of zero or more (Pandera). A negative count stops the run.
2. **Geography.** Each source row is matched to a PSGC code by municipality name and province, case and spacing ignored, then through `pipeline/aliases.yml` for known alternative spellings. A name shared by several provinces (Carmen, Loreto, San Francisco) is matched by province and never merged. A row that cannot be matched safely goes to the issues list and is never dropped silently.
3. **Duplicates.** A second row for the same municipality and cycle is kept out and listed as an issue.
4. **Reconciliation.** Totals by province and region are compared with the custodian's approved totals before any suppression. Any difference is shown on the Data trust screen.
5. **Missing cycles.** A municipality with no row for a cycle becomes status `missing`, with empty counts, never zero.
6. **Suppression.** Any count below 5 is withheld. An applicant count below 5 makes the municipality `suppressed` (all counts withheld, not mapped, not ranked). Fewer than 15 applicants makes it `low` (mapped, never ranked).
7. **Indicators and ranking.** A municipality is ranked only when its status is `ok` and every component in use has a value. Otherwise it is listed under "Not ranked" with the reason.

Thresholds live in `pipeline/src/pulse_pipeline/config.py` (`SUPPRESS_BELOW`, `LOW_SAMPLE_BELOW`). Agree any change with the data custodian and note it in the changelog.

Every imported version stores its checksums. `php artisan pulse:import` refuses a folder whose files do not match them and changes nothing.
