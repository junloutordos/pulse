import hashlib
import json
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Literal

import geopandas as gpd
import pandas as pd


@dataclass(frozen=True)
class Provenance:
    source: Literal["synthetic", "real"]
    cycle: int
    prior_cycle: int | None
    dataset_version: str
    formula_version: str
    refreshed_at: str
    schema_version: int


def _sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def write_version(out_dir: Path, tables: dict[str, pd.DataFrame], boundaries: gpd.GeoDataFrame, prov: Provenance) -> Path:
    folder = Path(out_dir) / prov.dataset_version
    folder.mkdir(parents=True, exist_ok=True)
    files = []
    for name, df in tables.items():
        path = folder / f"{name}.parquet"
        df.to_parquet(path, index=False)
        jsonl = folder / f"{name}.jsonl"  # interchange copy for the Laravel importer (PHP cannot read Parquet)
        jsonl.write_text(df.to_json(orient="records", lines=True))
        files.extend([path, jsonl])
    geo = folder / "boundaries.geojson"
    boundaries.to_crs("EPSG:4326").to_file(geo, driver="GeoJSON")
    files.append(geo)
    (folder / "provenance.json").write_text(
        json.dumps({**asdict(prov), "files": {p.name: _sha256(p) for p in files}}, indent=2)
    )
    return folder
