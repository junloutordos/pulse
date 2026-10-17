import re

import duckdb
import pandas as pd

from ..output import Provenance
from .base import make_provenance

REQUIRED = ["raw_name", "raw_province", "cycle", "applicants", "examinees", "qualifiers", "grade6_pop"]
_IDENTIFIER = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$")


def _identifier(name: str, what: str) -> str:
    if not _IDENTIFIER.match(name):
        raise ValueError(f"{what} name must be a plain identifier, got {name!r}")
    return name


class LakehouseSource:
    """Reads the raw municipality aggregates from a DuckDB-readable connection (a .duckdb file today).

    The exact GATES sandbox interface is unconfirmed; only `_query` needs to change once it is known.
    """

    def __init__(self, connection: str, table: str, custodian_table: str | None = None):
        self._connection, self._table, self._custodian = connection, table, custodian_table

    def _query(self, table: str) -> pd.DataFrame:
        con = duckdb.connect(self._connection, read_only=True)
        try:
            return con.execute(f"SELECT * FROM {_identifier(table, 'table')}").df()
        finally:
            con.close()

    def load(self) -> tuple[pd.DataFrame, Provenance]:
        raw = self._query(self._table)
        missing = [c for c in REQUIRED if c not in raw.columns]
        if missing:
            raise ValueError(f"table {self._table!r} is missing columns: {', '.join(missing)}")
        raw = raw[REQUIRED].copy()
        for col in ["applicants", "examinees", "qualifiers", "grade6_pop"]:
            raw[col] = raw[col].astype("Int64")
        raw["cycle"] = raw["cycle"].astype("int64")
        return raw, make_provenance("real", raw)

    def custodian_totals(self) -> pd.DataFrame | None:
        if self._custodian is None:
            return None
        return self._query(self._custodian)[["scope", "cycle", "applicants", "examinees"]]
