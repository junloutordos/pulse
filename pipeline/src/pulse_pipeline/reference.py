from dataclasses import dataclass
from pathlib import Path

import geopandas as gpd
import pandas as pd

MUNI_COLUMNS = ["psgc", "name", "province_psgc", "province", "lat", "lon"]


@dataclass(frozen=True)
class Reference:
    munis: pd.DataFrame
    boundaries: gpd.GeoDataFrame


def load_reference(dir: Path) -> Reference:
    dir = Path(dir)
    munis = pd.read_csv(dir / "psgc.csv", dtype={"psgc": str, "province_psgc": str})
    if list(munis.columns) != MUNI_COLUMNS:
        raise ValueError(f"psgc.csv columns must be {MUNI_COLUMNS}, got {list(munis.columns)}")
    boundaries = gpd.read_file(dir / "boundaries.geojson")
    boundaries["psgc"] = boundaries["psgc"].astype(str)
    if set(boundaries["psgc"]) != set(munis["psgc"]):
        raise ValueError("boundaries.geojson and psgc.csv must list the same PSGC codes")
    return Reference(munis=munis, boundaries=boundaries)
