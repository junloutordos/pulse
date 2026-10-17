from datetime import datetime, timezone
from typing import Literal, Protocol

import pandas as pd

from ..config import FORMULA_VERSION
from ..output import Provenance

SCHEMA_VERSION = 1


class Source(Protocol):
    def load(self) -> tuple[pd.DataFrame, Provenance]: ...

    def custodian_totals(self) -> pd.DataFrame | None: ...


def make_provenance(source: Literal["synthetic", "real"], raw: pd.DataFrame, now: datetime | None = None) -> Provenance:
    now = now or datetime.now(timezone.utc)
    cycles = sorted(int(c) for c in raw["cycle"].unique())
    return Provenance(
        source=source,
        cycle=cycles[-1],
        prior_cycle=cycles[-2] if len(cycles) > 1 else None,
        dataset_version=f"{now:%Y.%m.%d-%H%M}-{'syn' if source == 'synthetic' else 'real'}",
        formula_version=FORMULA_VERSION,
        refreshed_at=now.isoformat(timespec="seconds"),
        schema_version=SCHEMA_VERSION,
    )
