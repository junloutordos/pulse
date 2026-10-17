# Refresh for a new NCE cycle

Do this once per admission cycle, with the campus data steward.

1. **Get the approved inputs** (no names, ID numbers or addresses):
   - `counts.csv` with columns `raw_name, raw_province, cycle, applicants, examinees, qualifiers, grade6_pop`, one row per municipality per cycle, for the new cycle and the previous one. Leave a cell empty when unknown; never write 0 for missing.
   - `custodian.csv` with columns `scope, cycle, applicants, examinees`: the custodian's approved totals for each province and for `Caraga`.
   - The official PSGC list and boundaries in `data/reference/` (`psgc.csv`, `boundaries.geojson`), if they changed.
2. **Build a version**
   ```sh
   docker compose --profile tools run --rm pipeline python -m pulse_pipeline run \
     --source local --ref-dir /work/data/reference --out /work/data/versions \
     --counts /work/data/inputs/counts.csv --custodian /work/data/inputs/custodian.csv
   ```
   For the GATES Lakehouse use `--source lakehouse --connection <path> --table <table> [--custodian-table <table>]`.
3. **Import without activating, then check.** Version folders are named by date and time (for example `2026.10.17-0905-real`), so two runs never collide. Re-importing a name that exists with different content is refused unless you add `--force`.
   ```sh
   docker compose exec app php artisan pulse:import data/versions/<version>
   ```
   Open it before switching: activate in a staging copy, or review `issues.jsonl` and `reconciliation.jsonl` in the folder.
4. **Activate**
   ```sh
   docker compose exec app php artisan pulse:import data/versions/<version> --activate
   ```
5. **Verify** on the Data trust screen: every total reconciles, every flagged record has a known reason, the banner does not say Synthetic.
6. **Roll back** by re-importing the previous version folder with `--activate`. Decisions and the action register stay attached to the version they were made on.

Keep the old version folders. They are the audit trail for earlier outreach decisions.
