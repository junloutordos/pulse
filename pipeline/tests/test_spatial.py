from pathlib import Path

import numpy as np
import pandas as pd

from pulse_pipeline.reference import load_reference
from pulse_pipeline.spatial import knn_neighbors, local_morans, nearby

FIXTURE = Path(__file__).parent / "fixtures" / "ref_mini"


def _grid(n=10):
    rows = [(f"G{i:02d}{j:02d}", 8 + i * 0.05, 125 + j * 0.05) for i in range(n) for j in range(n)]
    return pd.DataFrame(rows, columns=["psgc", "lat", "lon"])


def _ind(munis, values, status="ok"):
    return pd.DataFrame({"psgc": munis.psgc, "status": status, "rate_per_1000": values})


def test_knn_returns_k_neighbours_excluding_self():
    munis = load_reference(FIXTURE).munis
    nb = knn_neighbors(munis, k=4)
    assert len(nb) == 6 * 4
    assert (nb.psgc != nb.neighbor_psgc).all()
    for _, g in nb.groupby("psgc"):
        assert list(g.order) == [1, 2, 3, 4]
        assert list(g.km) == sorted(g.km)


def test_nearby_respects_radius():
    munis = pd.DataFrame({"psgc": ["A", "B", "C"], "lat": [8.0, 8.05, 9.5], "lon": [125.0, 125.0, 125.0]})
    out = nearby(munis, radius_km=28.0)
    assert set(zip(out.psgc, out.neighbor_psgc)) == {("A", "B"), ("B", "A")}
    assert (out.km <= 28.0).all()


def test_cluster_requires_significance():
    munis = _grid()
    values = np.random.default_rng(3).normal(20, 5, len(munis))
    out = local_morans(_ind(munis, values), knn_neighbors(munis, k=4), permutations=199, seed=2026)
    sig = out[out.cluster != "ns"]
    assert (sig.p_sim < 0.05).all()
    assert len(sig) / len(out) < 0.2


def test_spatially_clustered_toy_data_yields_HH_and_LL():
    munis = _grid()
    j = munis.psgc.str[3:].astype(int)
    values = np.where(j < 5, 40.0, 10.0) + np.random.default_rng(1).normal(0, 1, len(munis))
    out = local_morans(_ind(munis, values), knn_neighbors(munis, k=4), permutations=199, seed=2026)
    assert (out.cluster == "HH").sum() >= 5 and (out.cluster == "LL").sum() >= 5
    assert (out.cluster.isin(["HL", "LH"])).sum() == 0


def test_suppressed_excluded_from_morans():
    munis = _grid(4)
    ind = _ind(munis, np.arange(16, dtype=float))
    ind.loc[ind.psgc == "G0000", ["status", "rate_per_1000"]] = ["suppressed", np.nan]
    out = local_morans(ind, knn_neighbors(munis, k=4), permutations=99, seed=2026)
    assert "G0000" not in set(out.psgc) and len(out) == 15


def test_same_seed_same_result():
    munis = _grid(5)
    ind = _ind(munis, np.random.default_rng(9).normal(20, 5, len(munis)))
    nb = knn_neighbors(munis, k=4)
    pd.testing.assert_frame_equal(local_morans(ind, nb, 99, 2026), local_morans(ind, nb, 99, 2026))


def test_participant_without_participating_neighbours_is_ns():
    munis = pd.DataFrame({"psgc": ["A", "B", "C", "D", "E"], "lat": [8, 8.01, 8.02, 8.03, 9.5], "lon": [125.0] * 5})
    nb = knn_neighbors(munis, k=1)
    ind = _ind(munis, [10.0, 12.0, 30.0, 32.0, 50.0])
    ind.loc[ind.psgc == "D", ["status", "rate_per_1000"]] = ["suppressed", np.nan]
    out = local_morans(ind, nb, permutations=99, seed=2026).set_index("psgc")
    assert out.loc["E", "cluster"] == "ns"
