import numpy as np
import pandas as pd


def _f(series: pd.Series) -> np.ndarray:
    return series.to_numpy(dtype="float64", na_value=np.nan)


def compute_indicators(df: pd.DataFrame, cycle: int, prior_cycle: int | None) -> pd.DataFrame:
    out = df[df["cycle"] == cycle].copy().reset_index(drop=True)
    if prior_cycle is None:
        out["prior_applicants"] = pd.array([None] * len(out), dtype="Int64")
    else:
        prior = df[df["cycle"] == prior_cycle].set_index("psgc")["applicants"]
        out["prior_applicants"] = out["psgc"].map(prior).astype("Int64")

    apps, exams, pop, prior_apps = _f(out["applicants"]), _f(out["examinees"]), _f(out["grade6_pop"]), _f(out["prior_applicants"])
    with np.errstate(divide="ignore", invalid="ignore"):
        out["rate_per_1000"] = np.where(pop > 0, apps / pop * 1000, np.nan)
        out["attendance"] = np.where(apps > 0, exams / apps, np.nan)
        out["change"] = np.where(prior_apps > 0, (apps - prior_apps) / prior_apps, np.nan)
    out["rankable"] = out["status"] == "ok"
    return out
