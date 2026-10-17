import numpy as np
import pandas as pd

from .config import SEED
from .reference import Reference

# Generator-only stand-in for the campus location; real coordinates are not secured.
_SYNTH_CAMPUS = (8.949, 125.543)
_CYCLES = (2025, 2026)
_ISLAND_NAMES = {"Burgos", "Dapa", "Del Carmen", "General Luna", "Pilar", "San Benito",
                 "San Isidro", "Santa Monica", "Socorro"}
ALIAS_SPELLINGS = {"Remedios T. Romualdez": "R.T. Romualdez", "Tagana-an": "Taganaan",
                    "Santa Josefa": "Sta. Josefa", "Butuan City": "Butuan"}

PLANTED_CASES = {
    "alias": "Source spells the municipality differently from the PSGC name",
    "suppressed": "Applicant count below the suppression threshold",
    "missing": "No rows at all for the later cycle",
    "decline": "Applications fell more than 20% versus the prior cycle",
    "no_denominator": "Grade 6 population is missing in every cycle",
}


def _km(lat1, lon1, lat2, lon2):
    r = np.pi / 180
    a = np.sin((lat2 - lat1) * r / 2) ** 2 + np.cos(lat1 * r) * np.cos(lat2 * r) * np.sin((lon2 - lon1) * r / 2) ** 2
    return 12742 * np.arcsin(np.sqrt(a))


def generate_synthetic(ref: Reference, seed: int = SEED) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    munis = ref.munis.sort_values("psgc").reset_index(drop=True)
    pop = {m.psgc: int(rng.integers(300, 1700) if "City" not in m.name else rng.integers(1800, 3400)) for m in munis.itertuples()}

    alias_holders = [m.psgc for m in munis.itertuples() if m.name in ALIAS_SPELLINGS]
    candidates = [p for p in munis.psgc if p not in alias_holders]
    suppressed = min(candidates, key=lambda p: (pop[p], p))
    candidates.remove(suppressed)
    missing = candidates.pop()
    decline = candidates.pop(0)
    no_denominator = candidates.pop(0)
    planted = {"alias": alias_holders[0], "suppressed": suppressed, "missing": missing,
               "decline": decline, "no_denominator": no_denominator}

    rows = []
    for m in munis.itertuples():
        dist = _km(*_SYNTH_CAMPUS, m.lat, m.lon)
        island = m.name in _ISLAND_NAMES or "Dinagat" in m.province
        rate = float(np.clip(34 * np.exp(-dist / 110) + 6 + rng.normal(0, 4), 2.5, 60))
        attendance = float(np.clip(0.92 - (0.17 if island else 0) - dist / 1600 + rng.normal(0, 0.05), 0.45, 0.98))
        base = max(8, round(pop[m.psgc] * rate / 1000))
        for cycle in _CYCLES:
            if m.psgc == missing and cycle == 2026:
                continue
            drift = 1 + rng.normal(0, 0.06)
            if m.psgc == decline:
                apps = round(base * (1.0 if cycle == 2025 else 0.65 * drift))
            elif m.psgc == suppressed and cycle == 2026:
                apps = int(rng.integers(1, 5))
            else:
                apps = max(5, round(base * drift))
            exams = max(1, round(apps * float(np.clip(attendance + rng.normal(0, 0.03), 0.3, 1.0))))
            exams = min(exams, apps)
            quals = max(1, round(exams * float(rng.uniform(0.06, 0.15))))
            rows.append({
                "raw_name": ALIAS_SPELLINGS.get(m.name, m.name),
                "raw_province": m.province,
                "cycle": cycle,
                "applicants": apps,
                "examinees": exams,
                "qualifiers": quals,
                "grade6_pop": None if m.psgc == no_denominator else round(pop[m.psgc] * (1 + rng.normal(0, 0.02))),
            })
    df = pd.DataFrame(rows)
    for col in ["applicants", "examinees", "qualifiers", "grade6_pop"]:
        df[col] = df[col].astype("Int64")
    df["cycle"] = df["cycle"].astype("int64")
    df.attrs["planted"] = planted
    return df
