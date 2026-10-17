from pathlib import Path

import pandas as pd
import pytest

from pulse_pipeline.geo_match import load_aliases, match_psgc
from pulse_pipeline.reference import load_reference

FIXTURE = Path(__file__).parent / "fixtures" / "ref_mini"
ALIASES = {"R.T. Romualdez": "Remedios T. Romualdez"}


@pytest.fixture(scope="module")
def ref():
    return load_reference(FIXTURE)


def _raw(*rows):
    return pd.DataFrame(rows, columns=["raw_name", "raw_province", "cycle", "applicants"])


def test_alias_spelling_matches(ref):
    res = match_psgc(_raw(("R.T. Romualdez", "Agusan del Norte", 2026, 40)), ref, ALIASES)
    assert res.matched.loc[0, "psgc"] == "9900100002"
    assert res.matched.loc[0, "match_method"] == "alias"
    assert res.issues.empty


def test_exact_match_ignores_case_and_spacing(ref):
    res = match_psgc(_raw(("  butuan CITY ", "agusan del norte", 2026, 90)), ref, {})
    assert res.matched.loc[0, "psgc"] == "9900100001"
    assert res.matched.loc[0, "match_method"] == "exact"


def test_same_name_different_province_not_merged(ref):
    res = match_psgc(_raw(("Carmen", "Agusan del Norte", 2026, 30), ("Carmen", "Surigao del Sur", 2026, 31)), ref, {})
    assert list(res.matched.psgc) == ["9900100003", "9900200001"]


def test_wrong_province_is_an_issue_not_a_guess(ref):
    res = match_psgc(_raw(("Carmen", "Dinagat Islands", 2026, 30)), ref, {})
    assert res.matched.empty
    assert res.issues.loc[0, "reason"] != ""


def test_missing_province_on_shared_name_is_ambiguous(ref):
    res = match_psgc(_raw(("Carmen", None, 2026, 30), ("Tandag City", None, 2026, 50)), ref, {})
    assert list(res.matched.psgc) == ["9900200002"]
    assert "ambiguous" in res.issues.loc[0, "reason"]


def test_unmatched_row_goes_to_issues_not_dropped_silently(ref):
    raw = _raw(("Atlantis", "Agusan del Norte", 2026, 12), ("Butuan City", "Agusan del Norte", 2026, 90))
    res = match_psgc(raw, ref, {})
    assert len(res.matched) + len(res.issues) == len(raw)
    assert list(res.issues.raw_name) == ["Atlantis"]
    assert res.issues.loc[0, "reason"]


def test_load_aliases_reads_yaml(tmp_path):
    p = tmp_path / "aliases.yml"
    p.write_text("R.T. Romualdez: Remedios T. Romualdez\n")
    assert load_aliases(p) == ALIASES
