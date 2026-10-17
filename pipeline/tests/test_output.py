import hashlib
import json

import geopandas as gpd
import pandas as pd
from shapely.geometry import box

from pulse_pipeline.output import Provenance, write_version


def _prov(source="synthetic"):
    return Provenance(source=source, cycle=2026, prior_cycle=2025, dataset_version="2026.10.17-syn",
                      formula_version="1.0", refreshed_at="2026-10-17T00:00:00+00:00", schema_version=1)


def test_written_checksums_match_files(tmp_path):
    gdf = gpd.GeoDataFrame({"psgc": ["A"]}, geometry=[box(125, 8, 125.1, 8.1)], crs="EPSG:4326")
    folder = write_version(tmp_path, {"indicators": pd.DataFrame({"psgc": ["A"], "n": pd.array([1], dtype="Int64")})}, gdf, _prov())
    prov = json.loads((folder / "provenance.json").read_text())
    assert set(prov["files"]) == {"indicators.parquet", "indicators.jsonl", "boundaries.geojson"}
    for name, digest in prov["files"].items():
        assert hashlib.sha256((folder / name).read_bytes()).hexdigest() == digest
    assert folder.name == "2026.10.17-syn"


def test_provenance_records_source_cycle_and_versions(tmp_path):
    gdf = gpd.GeoDataFrame({"psgc": ["A"]}, geometry=[box(125, 8, 125.1, 8.1)], crs="EPSG:4326")
    folder = write_version(tmp_path, {}, gdf, _prov("real"))
    prov = json.loads((folder / "provenance.json").read_text())
    assert (prov["source"], prov["cycle"], prov["prior_cycle"]) == ("real", 2026, 2025)
    assert prov["formula_version"] == "1.0" and prov["schema_version"] == 1 and prov["refreshed_at"]


def test_jsonl_keeps_nulls_and_integers(tmp_path):
    gdf = gpd.GeoDataFrame({"psgc": ["A"]}, geometry=[box(125, 8, 125.1, 8.1)], crs="EPSG:4326")
    df = pd.DataFrame({"psgc": ["A", "B"], "n": pd.array([7, None], dtype="Int64"), "x": [0.5, float("nan")]})
    folder = write_version(tmp_path, {"t": df}, gdf, _prov())
    rows = [json.loads(line) for line in (folder / "t.jsonl").read_text().splitlines()]
    assert rows == [{"psgc": "A", "n": 7, "x": 0.5}, {"psgc": "B", "n": None, "x": None}]


def test_version_name_includes_the_time_so_same_day_runs_do_not_collide():
    from datetime import datetime, timezone

    from pulse_pipeline.sources.base import make_provenance

    raw = pd.DataFrame({"cycle": [2025, 2026]})
    now = datetime(2026, 10, 17, 9, 5, tzinfo=timezone.utc)
    assert make_provenance("synthetic", raw, now).dataset_version == "2026.10.17-0905-syn"
    assert make_provenance("real", raw, now).dataset_version == "2026.10.17-0905-real"
