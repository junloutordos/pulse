import pandas as pd
import pandera.pandas as pa

from .config import LOW_SAMPLE_BELOW, SUPPRESS_BELOW

COUNT_COLUMNS = ["applicants", "examinees", "qualifiers"]

_count = pa.Column("Int64", pa.Check.ge(0), nullable=True, coerce=True, required=False)
RAW_SCHEMA = pa.DataFrameSchema(
    {"cycle": pa.Column(int, coerce=True), "applicants": _count, "examinees": _count,
     "qualifiers": _count, "grade6_pop": _count},
    strict=False,
)


def validate_raw(df: pd.DataFrame) -> pd.DataFrame:
    return RAW_SCHEMA.validate(df)


def apply_suppression(df: pd.DataFrame, suppress_below: int = SUPPRESS_BELOW,
                      low_sample_below: int = LOW_SAMPLE_BELOW, universe: pd.DataFrame | None = None) -> pd.DataFrame:
    out = df.copy()
    if universe is not None:
        have = set(zip(out["psgc"], out["cycle"]))
        absent = [r for r in universe[["psgc", "cycle"]].itertuples(index=False) if (r.psgc, r.cycle) not in have]
        if absent:
            out = pd.concat([out, pd.DataFrame(absent, columns=["psgc", "cycle"])], ignore_index=True)
    for col in [*COUNT_COLUMNS, "grade6_pop"]:
        out[col] = out[col].astype("Int64")

    apps = out["applicants"]
    status = pd.Series("ok", index=out.index, dtype="object")
    status[apps < low_sample_below] = "low"
    status[apps < suppress_below] = "suppressed"
    status[apps.isna()] = "missing"
    out["status"] = status

    for col in COUNT_COLUMNS:
        small = out[col].notna() & (out[col] < suppress_below)
        out[col] = out[col].mask(small | (out["status"] == "suppressed"), pd.NA)
    return out


def reconcile(df: pd.DataFrame, custodian_totals: pd.DataFrame, region_scope: str = "Caraga") -> pd.DataFrame:
    rows = []
    for c in custodian_totals.itertuples(index=False):
        part = df[df["cycle"] == c.cycle]
        if c.scope != region_scope:
            part = part[part["province"] == c.scope]
        for metric in ("applicants", "examinees"):
            pulse_sum = int(part[metric].sum())
            custodian_sum = int(getattr(c, metric))
            rows.append({"scope": c.scope, "cycle": c.cycle, "metric": metric, "pulse_sum": pulse_sum,
                         "custodian_sum": custodian_sum, "matches": pulse_sum == custodian_sum})
    return pd.DataFrame(rows, columns=["scope", "cycle", "metric", "pulse_sum", "custodian_sum", "matches"])
