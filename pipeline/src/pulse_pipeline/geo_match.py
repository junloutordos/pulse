import re
from dataclasses import dataclass
from pathlib import Path

import pandas as pd
import yaml

from .reference import Reference


@dataclass(frozen=True)
class MatchResult:
    matched: pd.DataFrame
    issues: pd.DataFrame


def _norm(value) -> str | None:
    if value is None or (isinstance(value, float) and pd.isna(value)) or pd.isna(value):
        return None
    cleaned = re.sub(r"\s+", " ", str(value)).strip().casefold()
    return cleaned or None


def load_aliases(path: Path) -> dict[str, str]:
    data = yaml.safe_load(Path(path).read_text()) or {}
    return {str(k): str(v) for k, v in data.items()}


def match_psgc(raw: pd.DataFrame, ref: Reference, aliases: dict[str, str]) -> MatchResult:
    by_key: dict[tuple[str, str], str] = {}
    by_name: dict[str, list[str]] = {}
    for m in ref.munis.itertuples():
        by_key[(_norm(m.name), _norm(m.province))] = m.psgc
        by_name.setdefault(_norm(m.name), []).append(m.psgc)
    alias_map = {_norm(k): _norm(v) for k, v in aliases.items()}

    def resolve(name_n: str, prov_n: str | None):
        """Return (psgc, reason). psgc is None when no safe match exists."""
        if prov_n is None:
            found = by_name.get(name_n, [])
            if len(found) == 1:
                return found[0], None
            if len(found) > 1:
                return None, "ambiguous name shared by several provinces and no province given"
            return None, f"no municipality named '{name_n}'"
        psgc = by_key.get((name_n, prov_n))
        if psgc:
            return psgc, None
        if name_n in by_name:
            return None, f"'{name_n}' exists, but not in province '{prov_n}'"
        return None, f"no municipality named '{name_n}'"

    matched_rows, issue_rows = [], []
    for row in raw.to_dict("records"):
        name_n, prov_n = _norm(row["raw_name"]), _norm(row["raw_province"])
        psgc, reason = resolve(name_n, prov_n) if name_n else (None, "empty municipality name")
        method = "exact"
        if psgc is None and name_n in alias_map:
            psgc, alias_reason = resolve(alias_map[name_n], prov_n)
            method = "alias"
            reason = reason if psgc is None and alias_reason is None else (alias_reason or reason)
        if psgc is None:
            issue_rows.append({"raw_name": row["raw_name"], "raw_province": row["raw_province"], "reason": reason})
        else:
            matched_rows.append({**row, "psgc": psgc, "match_method": method})

    matched = pd.DataFrame(matched_rows, columns=[*raw.columns, "psgc", "match_method"]).astype(raw.dtypes.to_dict() | {"psgc": "object", "match_method": "object"})
    issues = pd.DataFrame(issue_rows, columns=["raw_name", "raw_province", "reason"])
    return MatchResult(matched=matched.reset_index(drop=True), issues=issues.reset_index(drop=True))
