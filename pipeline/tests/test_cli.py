import json
from pathlib import Path

import pandas as pd

from pulse_pipeline.cli import main
from pulse_pipeline.reference import load_reference
from pulse_pipeline.synthetic import generate_synthetic

FIXTURE = Path(__file__).parent / "fixtures" / "ref_mini"
EXPECTED_FILES = {"indicators.parquet", "neighbors.parquet", "munis.parquet", "reconciliation.parquet",
                  "issues.parquet", "scores.parquet", "boundaries.geojson", "provenance.json"}


def _run_synthetic(tmp_path):
    return Path(main(["run", "--source", "synthetic", "--ref-dir", str(FIXTURE), "--out", str(tmp_path / "out")]))


def test_cli_synthetic_end_to_end(tmp_path):
    folder = _run_synthetic(tmp_path)
    assert EXPECTED_FILES <= {p.name for p in folder.iterdir()}
    prov = json.loads((folder / "provenance.json").read_text())
    assert (prov["source"], prov["cycle"], prov["prior_cycle"]) == ("synthetic", 2026, 2025)
    assert folder.name.endswith("-syn")
    ind = pd.read_parquet(folder / "indicators.parquet")
    assert len(ind) == 6 and {"status", "rate_per_1000", "attendance", "change", "lisa_cluster", "prior_applicants"} <= set(ind.columns)
    assert "missing" in set(ind.status) and "suppressed" in set(ind.status)
    nb = pd.read_parquet(folder / "neighbors.parquet")
    assert {"knn", "nearby"} <= set(nb.kind)
    rec = pd.read_parquet(folder / "reconciliation.parquet")
    assert rec.matches.all() and len(rec) > 0


def test_real_and_synthetic_produce_identical_schemas(tmp_path):
    syn_folder = _run_synthetic(tmp_path)
    ref = load_reference(FIXTURE)
    raw = generate_synthetic(ref)
    raw.to_csv(tmp_path / "counts.csv", index=False)
    totals = raw.groupby("cycle")[["applicants", "examinees"]].sum().reset_index().assign(scope="Caraga")
    totals.to_csv(tmp_path / "custodian.csv", index=False)
    real_folder = Path(main(["run", "--source", "local", "--ref-dir", str(FIXTURE), "--out", str(tmp_path / "real"),
                             "--counts", str(tmp_path / "counts.csv"), "--custodian", str(tmp_path / "custodian.csv")]))
    assert json.loads((real_folder / "provenance.json").read_text())["source"] == "real"
    assert real_folder.name.endswith("-real")
    for name in EXPECTED_FILES - {"boundaries.geojson", "provenance.json"}:
        a, b = pd.read_parquet(syn_folder / name), pd.read_parquet(real_folder / name)
        assert list(a.columns) == list(b.columns), name
        assert [str(t) for t in a.dtypes] == [str(t) for t in b.dtypes], name


def test_duplicate_rows_go_to_issues(tmp_path):
    ref = load_reference(FIXTURE)
    raw = generate_synthetic(ref)
    raw = pd.concat([raw, raw[(raw.raw_name == "Butuan City") & (raw.cycle == 2026)]], ignore_index=True)
    raw.to_csv(tmp_path / "counts.csv", index=False)
    folder = Path(main(["run", "--source", "local", "--ref-dir", str(FIXTURE), "--out", str(tmp_path / "o"),
                        "--counts", str(tmp_path / "counts.csv")]))
    issues = pd.read_parquet(folder / "issues.parquet")
    assert "duplicate" in set(issues.kind)
    assert len(pd.read_parquet(folder / "indicators.parquet")) == 6
