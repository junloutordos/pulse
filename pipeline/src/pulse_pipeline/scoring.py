from typing import NamedTuple

import numpy as np
import pandas as pd

from .config import LOW_SAMPLE_BELOW


class Weights(NamedTuple):
    reach: float
    attendance: float
    decline: float


# (weight key, indicator column, output column). A low value on each indicator means higher priority.
COMPONENTS = (
    ("reach", "rate_per_1000", "c_reach"),
    ("attendance", "attendance", "c_attendance"),
    ("decline", "change", "c_decline"),
)

_STATUS_REASON = {
    "low": f"fewer than {LOW_SAMPLE_BELOW} applicants",
    "suppressed": "applicant count suppressed",
    "missing": "no data for this cycle",
}


_MISSING_REASON = {
    "reach": "no Grade 6 population (rate not computed)",
    "attendance": "no exam attendance",
    "decline": "no prior-cycle comparison",
}


def _low_is_high_priority(values: pd.Series) -> pd.Series:
    n = len(values)
    if n < 2:
        return pd.Series(1.0, index=values.index)
    pct = (values.rank(method="average") - 1) / (n - 1)
    return 1.0 - pct


def priority_scores(ind: pd.DataFrame, w: Weights) -> pd.DataFrame:
    if any(v < 0 for v in w) or sum(w) == 0:
        raise ValueError("weights must be non-negative and not all zero")

    basic = ind["status"] == "ok"
    active = [(k, col, out) for k, col, out in COMPONENTS if ind.loc[basic, col].notna().any()]
    weight_sum = sum(getattr(w, k) for k, _, _ in active)
    if weight_sum <= 0:
        raise ValueError("weights are all zero for the components available in this dataset")

    eligible = basic.copy()
    for _, col, _ in active:
        eligible &= ind[col].notna()

    res = pd.DataFrame({"psgc": ind["psgc"].to_numpy()}, index=ind.index)
    for _, _, out in COMPONENTS:
        res[out] = np.nan
    res["score"] = np.nan
    total = pd.Series(0.0, index=ind.index[eligible])
    for k, col, out in active:
        c = _low_is_high_priority(ind.loc[eligible, col].astype("float64"))
        res.loc[eligible, out] = c
        total += getattr(w, k) * c
    res.loc[eligible, "score"] = 100 * total / weight_sum
    res["rank"] = res["score"].rank(method="min", ascending=False).astype("Int64")

    def reason(i):
        if eligible[i]:
            return None
        if not basic[i]:
            return _STATUS_REASON[ind.at[i, "status"]]
        missing = next(k for k, col, _ in active if pd.isna(ind.at[i, col]))
        return _MISSING_REASON[missing]

    res["not_ranked_reason"] = [reason(i) for i in ind.index]
    return res.reset_index(drop=True)
