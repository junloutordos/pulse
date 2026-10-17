from pathlib import Path

import pandas as pd
import pytest

from pulse_pipeline.reference import load_reference
from pulse_pipeline.synthetic import ALIAS_SPELLINGS, PLANTED_CASES, generate_synthetic

FIXTURE = Path(__file__).parent / "fixtures" / "ref_mini"


@pytest.fixture(scope="module")
def ref():
    return load_reference(FIXTURE)


def _row(df, ref, psgc, cycle):
    m = ref.munis.set_index("psgc").loc[psgc]
    names = {m["name"], ALIAS_SPELLINGS.get(m["name"], m["name"])}
    return df[(df.raw_province == m["province"]) & (df.cycle == cycle) & df.raw_name.isin(names)]


def test_reference_loads_mini_fixture(ref):
    assert list(ref.munis.columns) == ["psgc", "name", "province_psgc", "province", "lat", "lon"]
    assert len(ref.munis) == 6
    assert set(ref.boundaries["psgc"]) == set(ref.munis["psgc"])


def test_same_seed_same_output(ref):
    a = generate_synthetic(ref, seed=2026)
    b = generate_synthetic(ref, seed=2026)
    pd.testing.assert_frame_equal(a, b)
    assert not a.equals(generate_synthetic(ref, seed=7))


def test_planted_cases_present(ref):
    df = generate_synthetic(ref)
    t = df.attrs["planted"]
    assert set(t) == set(PLANTED_CASES)

    alias_row = _row(df, ref, t["alias"], 2026).iloc[0]
    assert alias_row.raw_name != ref.munis.set_index("psgc").loc[t["alias"], "name"]

    sup = _row(df, ref, t["suppressed"], 2026).iloc[0]
    assert 1 <= sup.applicants < 5

    assert _row(df, ref, t["missing"], 2026).empty
    assert not _row(df, ref, t["missing"], 2025).empty

    d26 = _row(df, ref, t["decline"], 2026).iloc[0].applicants
    d25 = _row(df, ref, t["decline"], 2025).iloc[0].applicants
    assert (d26 - d25) / d25 < -0.20


def test_missing_is_null_not_zero(ref):
    df = generate_synthetic(ref)
    t = df.attrs["planted"]
    nd = df[df.raw_province.eq(ref.munis.set_index("psgc").loc[t["no_denominator"], "province"])
            & df.raw_name.eq(ref.munis.set_index("psgc").loc[t["no_denominator"], "name"])]
    assert nd.grade6_pop.isna().all()
    for col in ["applicants", "examinees", "qualifiers", "grade6_pop"]:
        assert str(df[col].dtype) == "Int64"
        assert (df[col] == 0).sum() == 0


def test_shared_names_stay_distinct(ref):
    df = generate_synthetic(ref)
    carmen = df[(df.raw_name == "Carmen") & (df.cycle == 2026)]
    assert set(carmen.raw_province) == {"Agusan del Norte", "Surigao del Sur"}


def test_the_missing_case_is_never_a_city(ref):
    from pulse_pipeline.synthetic import generate_synthetic

    t = generate_synthetic(ref).attrs["planted"]
    name = ref.munis.set_index("psgc").loc[t["missing"], "name"]
    assert "City" not in name
