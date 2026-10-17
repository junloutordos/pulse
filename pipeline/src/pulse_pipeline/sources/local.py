from pathlib import Path

import pandas as pd

from ..output import Provenance
from .base import make_provenance

COUNT_COLUMNS = ["applicants", "examinees", "qualifiers", "grade6_pop"]


class LocalFilesSource:
    def __init__(self, counts_csv: Path, custodian_csv: Path | None = None):
        self._counts, self._custodian = Path(counts_csv), Path(custodian_csv) if custodian_csv else None

    def load(self) -> tuple[pd.DataFrame, Provenance]:
        raw = pd.read_csv(self._counts)
        for col in COUNT_COLUMNS:
            raw[col] = raw[col].astype("Int64")
        raw["cycle"] = raw["cycle"].astype("int64")
        return raw, make_provenance("real", raw)

    def custodian_totals(self) -> pd.DataFrame | None:
        if self._custodian is None:
            return None
        df = pd.read_csv(self._custodian)
        return df[["scope", "cycle", "applicants", "examinees"]]
