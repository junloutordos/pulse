from pathlib import Path

import duckdb
import pytest

from pulse_pipeline.cli import main
from pulse_pipeline.reference import load_reference
from pulse_pipeline.sources.lakehouse import LakehouseSource
from pulse_pipeline.synthetic import generate_synthetic

FIXTURE = Path(__file__).parent / "fixtures" / "ref_mini"


@pytest.fixture()
def raw():
    return generate_synthetic(load_reference(FIXTURE))


def _db(tmp_path, raw, table="nce_muni_agg", drop=()):
    path = tmp_path / "lake.duckdb"
    con = duckdb.connect(str(path))
    frame = raw.drop(columns=list(drop)).astype(object).where(raw.drop(columns=list(drop)).notna(), None)
    con.register("frame", frame)
    con.execute(f"CREATE TABLE {table} AS SELECT * FROM frame")
    totals = raw.groupby("cycle")[["applicants", "examinees"]].sum().reset_index().astype("int64")
    totals["scope"] = "Caraga"
    con.register("totals", totals)
    con.execute("CREATE TABLE custodian AS SELECT scope, cycle, applicants, examinees FROM totals")
    con.close()
    return str(path)


def test_lakehouse_source_returns_same_schema_as_synthetic(tmp_path, raw):
    got, prov = LakehouseSource(_db(tmp_path, raw), "nce_muni_agg").load()
    assert list(got.columns) == list(raw.columns)
    assert [str(t) for t in got.dtypes] == [str(t) for t in raw.dtypes]
    assert (prov.source, prov.cycle, prov.prior_cycle) == ("real", 2026, 2025)
    assert got.grade6_pop.isna().sum() == raw.grade6_pop.isna().sum()


def test_missing_column_names_the_column(tmp_path, raw):
    with pytest.raises(ValueError, match="grade6_pop"):
        LakehouseSource(_db(tmp_path, raw, drop=["grade6_pop"]), "nce_muni_agg").load()


def test_table_name_must_be_a_plain_identifier(tmp_path, raw):
    with pytest.raises(ValueError, match="table"):
        LakehouseSource(_db(tmp_path, raw), "nce_muni_agg; drop table x").load()


def test_custodian_table_is_optional(tmp_path, raw):
    db = _db(tmp_path, raw)
    assert LakehouseSource(db, "nce_muni_agg").custodian_totals() is None
    totals = LakehouseSource(db, "nce_muni_agg", custodian_table="custodian").custodian_totals()
    assert list(totals.columns) == ["scope", "cycle", "applicants", "examinees"]


def test_cli_lakehouse_end_to_end(tmp_path, raw):
    db = _db(tmp_path, raw)
    folder = Path(main(["run", "--source", "lakehouse", "--ref-dir", str(FIXTURE), "--out", str(tmp_path / "o"),
                        "--connection", db, "--table", "nce_muni_agg", "--custodian-table", "custodian"]))
    assert folder.name.endswith("-real") and (folder / "indicators.jsonl").exists()
