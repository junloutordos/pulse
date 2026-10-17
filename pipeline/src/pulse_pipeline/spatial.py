import numpy as np
import pandas as pd
from esda.moran import Moran_Local
from libpysal.weights import W

from .config import GROUP_RADIUS_KM, NEIGHBOR_K, SEED

_QUADRANT = {1: "HH", 2: "LH", 3: "LL", 4: "HL"}
_PARTICIPATING = {"ok", "low"}


def _distance_matrix(munis: pd.DataFrame) -> np.ndarray:
    lat, lon = np.radians(munis["lat"].to_numpy(float)), np.radians(munis["lon"].to_numpy(float))
    dlat, dlon = lat[:, None] - lat[None, :], lon[:, None] - lon[None, :]
    a = np.sin(dlat / 2) ** 2 + np.cos(lat)[:, None] * np.cos(lat)[None, :] * np.sin(dlon / 2) ** 2
    return 12742 * np.arcsin(np.sqrt(a))


def _pairs(munis: pd.DataFrame, pick) -> pd.DataFrame:
    ids, dist = munis["psgc"].to_numpy(), _distance_matrix(munis)
    rows = []
    for i, psgc in enumerate(ids):
        others = [j for j in np.argsort(dist[i], kind="stable") if j != i]
        for order, j in enumerate(pick(others, dist[i]), start=1):
            rows.append((psgc, ids[j], order, float(dist[i, j])))
    return pd.DataFrame(rows, columns=["psgc", "neighbor_psgc", "order", "km"])


def knn_neighbors(munis: pd.DataFrame, k: int = NEIGHBOR_K) -> pd.DataFrame:
    return _pairs(munis, lambda others, d: others[:k])


def nearby(munis: pd.DataFrame, radius_km: float = GROUP_RADIUS_KM) -> pd.DataFrame:
    return _pairs(munis, lambda others, d: [j for j in others if d[j] <= radius_km])


def local_morans(ind: pd.DataFrame, neighbors: pd.DataFrame, permutations: int = 999, seed: int = SEED) -> pd.DataFrame:
    part = ind[ind["status"].isin(_PARTICIPATING) & ind["rate_per_1000"].notna()]
    ids = list(part["psgc"])
    idset = set(ids)
    adj = {i: [] for i in ids}
    for r in neighbors.itertuples(index=False):
        if r.psgc in idset and r.neighbor_psgc in idset:
            adj[r.psgc].append(r.neighbor_psgc)
    has_neighbours = {i: bool(n) for i, n in adj.items()}
    cols = ["psgc", "lisa", "p_sim", "cluster"]
    if len(ids) < 3 or not any(has_neighbours.values()):
        return pd.DataFrame({"psgc": ids, "lisa": 0.0, "p_sim": 1.0, "cluster": "ns"}, columns=cols)

    w = W(adj, silence_warnings=True)
    y = part.set_index("psgc").loc[w.id_order, "rate_per_1000"].to_numpy(float)
    lm = Moran_Local(y, w, transformation="r", permutations=permutations, seed=seed, alternative="two-sided")
    out = pd.DataFrame({"psgc": w.id_order, "lisa": lm.Is, "p_sim": lm.p_sim})
    out["cluster"] = [
        _QUADRANT[q] if p < 0.05 and has_neighbours[i] else "ns"
        for i, q, p in zip(out["psgc"], lm.q, out["p_sim"])
    ]
    return out.set_index("psgc").loc[ids].reset_index()[cols]
