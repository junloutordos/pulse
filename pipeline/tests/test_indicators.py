import pandas as pd

from pulse_pipeline.indicators import compute_indicators


def _frame():
    I = lambda v: pd.array(v, dtype="Int64")
    return pd.DataFrame({
        "psgc": ["A", "A", "B", "B", "C", "C"],
        "cycle": [2025, 2026, 2025, 2026, 2025, 2026],
        "status": ["ok", "ok", "suppressed", "ok", "ok", "ok"],
        "applicants": I([50, 40, None, 20, 30, 30]),
        "examinees": I([40, 30, None, 15, 25, 24]),
        "qualifiers": I([6, 5, None, None, 5, 5]),
        "grade6_pop": I([2000, 2000, 500, 500, None, None]),
    })


def test_indicator_values_and_preserved_columns():
    out = compute_indicators(_frame(), 2026, 2025).set_index("psgc")
    a = out.loc["A"]
    assert a.rate_per_1000 == 20.0 and a.attendance == 0.75 and a.prior_applicants == 50
    assert round(a.change, 6) == -0.2
    assert {"status", "applicants", "grade6_pop", "cycle"} <= set(out.columns)
    assert list(out.index) == ["A", "B", "C"]


def test_suppressed_prior_gives_null_change_not_zero():
    out = compute_indicators(_frame(), 2026, 2025).set_index("psgc")
    assert pd.isna(out.loc["B", "change"]) and pd.isna(out.loc["B", "prior_applicants"])


def test_rate_null_without_denominator():
    out = compute_indicators(_frame(), 2026, 2025).set_index("psgc")
    assert pd.isna(out.loc["C", "rate_per_1000"]) and out.loc["C", "attendance"] == 0.8


def test_change_null_when_no_prior_cycle():
    out = compute_indicators(_frame(), 2026, None)
    assert out["change"].isna().all() and out["prior_applicants"].isna().all()


def test_rankable_means_status_ok():
    f = _frame()
    f.loc[(f.psgc == "B") & (f.cycle == 2026), "status"] = "low"
    out = compute_indicators(f, 2026, 2025).set_index("psgc")
    assert list(out.rankable) == [True, False, True]
