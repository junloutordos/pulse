import pandas as pd
import pandera.errors
import pytest

from pulse_pipeline.validate import apply_suppression, reconcile, validate_raw


def _df(applicants, examinees=None, psgc="A", cycle=2026):
    return pd.DataFrame({
        "psgc": [psgc], "cycle": [cycle],
        "applicants": pd.array([applicants], dtype="Int64"),
        "examinees": pd.array([examinees if examinees is not None else applicants], dtype="Int64"),
        "qualifiers": pd.array([None], dtype="Int64"),
        "grade6_pop": pd.array([1000], dtype="Int64"),
    })


@pytest.mark.parametrize("applicants,status", [(4, "suppressed"), (5, "low"), (14, "low"), (15, "ok")])
def test_suppression_thresholds(applicants, status):
    out = apply_suppression(_df(applicants))
    assert out.loc[0, "status"] == status
    if status == "suppressed":
        assert pd.isna(out.loc[0, "applicants"]) and pd.isna(out.loc[0, "examinees"])


def test_small_examinee_cell_suppressed_without_changing_status():
    out = apply_suppression(_df(40, examinees=3))
    assert out.loc[0, "status"] == "ok"
    assert pd.isna(out.loc[0, "examinees"]) and out.loc[0, "applicants"] == 40


def test_null_applicants_is_status_missing_not_zero():
    out = apply_suppression(_df(None, examinees=None))
    assert out.loc[0, "status"] == "missing"
    assert pd.isna(out.loc[0, "applicants"])


def test_missing_cycle_is_status_missing_not_zero():
    universe = pd.DataFrame({"psgc": ["A", "B"], "cycle": [2026, 2026]})
    out = apply_suppression(_df(40, psgc="A"), universe=universe)
    b = out[out.psgc == "B"].iloc[0]
    assert b.status == "missing" and pd.isna(b.applicants)
    assert len(out) == 2


def test_negative_counts_rejected():
    with pytest.raises(pandera.errors.SchemaError):
        validate_raw(_df(-3))


def test_reconcile_flags_mismatch():
    df = pd.DataFrame({"psgc": ["A", "B"], "cycle": [2026, 2026], "province": ["P1", "P1"],
                       "applicants": [40, 60], "examinees": [30, 50]})
    custodian = pd.DataFrame({"scope": ["P1"], "cycle": [2026], "applicants": [100], "examinees": [81]})
    out = reconcile(df, custodian).set_index("metric")
    assert out.loc["applicants", "pulse_sum"] == 100 and bool(out.loc["applicants", "matches"])
    assert out.loc["examinees", "custodian_sum"] == 81 and not bool(out.loc["examinees", "matches"])
