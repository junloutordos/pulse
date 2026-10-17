import pandas as pd

from ..config import SEED
from ..output import Provenance
from ..reference import Reference
from ..synthetic import generate_synthetic
from .base import make_provenance


class SyntheticSource:
    def __init__(self, ref: Reference, seed: int = SEED):
        self._raw = generate_synthetic(ref, seed)

    def load(self) -> tuple[pd.DataFrame, Provenance]:
        return self._raw, make_provenance("synthetic", self._raw)

    def custodian_totals(self) -> pd.DataFrame:
        by_prov = self._raw.groupby(["raw_province", "cycle"])[["applicants", "examinees"]].sum().reset_index()
        by_prov = by_prov.rename(columns={"raw_province": "scope"})
        region = self._raw.groupby("cycle")[["applicants", "examinees"]].sum().reset_index().assign(scope="Caraga")
        return pd.concat([region, by_prov], ignore_index=True)[["scope", "cycle", "applicants", "examinees"]]
