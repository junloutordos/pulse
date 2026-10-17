import json
from pathlib import Path

import pandas as pd
import pytest

from pulse_pipeline.scoring import Weights, priority_scores

FIXTURES = Path(__file__).resolve().parents[2] / "shared" / "fixtures" / "scoring_cases.json"
CASES = json.loads(FIXTURES.read_text())
COLUMNS = ["psgc", "status", "rate_per_1000", "attendance", "change"]


def _ind(rows):
    df = pd.DataFrame(rows, columns=COLUMNS)
    for c in ["rate_per_1000", "attendance", "change"]:
        df[c] = pd.to_numeric(df[c])
    return df


@pytest.mark.parametrize("case", CASES, ids=[c["name"] for c in CASES])
def test_scoring_matches_shared_fixtures(case):
    out = priority_scores(_ind(case["rows"]), Weights(**case["weights"])).set_index("psgc")
    for exp in case["expected"]:
        got = out.loc[exp["psgc"]]
        if exp["score"] is None:
            assert pd.isna(got.score) and pd.isna(got["rank"])
        else:
            assert got.score == pytest.approx(exp["score"], abs=1e-9)
            assert int(got["rank"]) == exp["rank"]
        reason = got.not_ranked_reason
        assert (None if pd.isna(reason) else reason) == exp["not_ranked_reason"]


def test_low_and_suppressed_never_ranked():
    rows = [("A", "ok", 10, 0.9, 0.0), ("B", "ok", 20, 0.8, 0.0), ("L", "low", 1, 0.1, -0.9), ("S", "suppressed", None, None, None)]
    out = priority_scores(_ind(rows), Weights(1, 1, 1)).set_index("psgc")
    for p in ("L", "S"):
        assert pd.isna(out.loc[p, "score"]) and out.loc[p, "not_ranked_reason"]


def test_all_zero_weights_raises():
    with pytest.raises(ValueError):
        priority_scores(_ind(CASES[0]["rows"]), Weights(0, 0, 0))


def test_zero_weight_on_only_available_component_raises():
    case = CASES[2]  # only attendance + decline are active
    with pytest.raises(ValueError):
        priority_scores(_ind(case["rows"]), Weights(1, 0, 0))


def test_negative_weight_raises():
    with pytest.raises(ValueError):
        priority_scores(_ind(CASES[0]["rows"]), Weights(1, -1, 1))


def test_component_reasons_are_plain_language():
    rows = [("A", "ok", 10, 0.9, 0.1), ("B", "ok", 20, 0.8, 0.0), ("C", "ok", None, 0.7, 0.2), ("D", "ok", 30, None, 0.2), ("E", "ok", 40, 0.6, None)]
    out = priority_scores(_ind(rows), Weights(1, 1, 1)).set_index("psgc")
    assert out.loc["C", "not_ranked_reason"] == "no Grade 6 population (rate not computed)"
    assert out.loc["D", "not_ranked_reason"] == "no exam attendance"
    assert out.loc["E", "not_ranked_reason"] == "no prior-cycle comparison"
