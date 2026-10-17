from pathlib import Path

import pandas as pd

from .config import SEED
from .geo_match import match_psgc
from .indicators import compute_indicators
from .output import write_version
from .reference import Reference
from .scoring import Weights, priority_scores
from .sources.base import Source
from .spatial import knn_neighbors, local_morans, nearby
from .validate import apply_suppression, reconcile, validate_raw

ISSUE_COLUMNS = ["raw_name", "raw_province", "reason", "kind", "psgc"]
RECONCILIATION_COLUMNS = ["scope", "cycle", "metric", "pulse_sum", "custodian_sum", "matches"]


def run_pipeline(source: Source, ref: Reference, aliases: dict[str, str], out_dir: Path) -> Path:
    raw, prov = source.load()
    validate_raw(raw)
    result = match_psgc(raw, ref, aliases)
    matched = result.matched
    issues = [result.issues.assign(kind="unmatched", psgc=None)]

    duplicate = matched.duplicated(["psgc", "cycle"], keep="first")
    if duplicate.any():
        issues.append(matched.loc[duplicate, ["raw_name", "raw_province", "psgc"]].assign(
            reason="duplicate row for municipality and cycle", kind="duplicate"))
        matched = matched[~duplicate]
    alias_rows = matched[matched["match_method"] == "alias"]
    issues.append(alias_rows[["raw_name", "raw_province", "psgc"]].assign(reason="matched by alias table", kind="alias"))
    matched = matched.merge(ref.munis[["psgc", "province"]], on="psgc")

    custodian = source.custodian_totals()
    reconciliation = reconcile(matched, custodian) if custodian is not None else pd.DataFrame(columns=RECONCILIATION_COLUMNS)

    cycles = [c for c in (prov.cycle, prov.prior_cycle) if c is not None]
    universe = pd.MultiIndex.from_product([ref.munis["psgc"], cycles], names=["psgc", "cycle"]).to_frame(index=False)
    counts = apply_suppression(matched[["psgc", "cycle", "applicants", "examinees", "qualifiers", "grade6_pop"]], universe=universe)
    indicators = compute_indicators(counts, prov.cycle, prov.prior_cycle)

    knn = knn_neighbors(ref.munis).assign(kind="knn")
    neighbors = pd.concat([knn, nearby(ref.munis).assign(kind="nearby")], ignore_index=True)
    clusters = local_morans(indicators, knn, seed=SEED).rename(columns={"cluster": "lisa_cluster"})
    indicators = indicators.merge(clusters[["psgc", "lisa_cluster"]], on="psgc", how="left")

    scores = priority_scores(indicators, Weights(1, 1, 1))
    tables = {
        "indicators": indicators,
        "neighbors": neighbors,
        "munis": ref.munis,
        "reconciliation": reconciliation,
        "issues": pd.concat(issues, ignore_index=True)[ISSUE_COLUMNS],
        "scores": scores,
    }
    return write_version(out_dir, tables, ref.boundaries, prov)
