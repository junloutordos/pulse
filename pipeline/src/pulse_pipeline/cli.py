import argparse
from pathlib import Path

from .geo_match import load_aliases
from .reference import load_reference
from .run import run_pipeline
from .sources.local import LocalFilesSource
from .sources.synthetic import SyntheticSource

DEFAULT_ALIASES = Path(__file__).resolve().parents[2] / "aliases.yml"


def main(argv: list[str] | None = None) -> str:
    parser = argparse.ArgumentParser(prog="pulse_pipeline")
    sub = parser.add_subparsers(dest="command", required=True)
    run = sub.add_parser("run", help="build a versioned dataset folder")
    run.add_argument("--source", choices=["synthetic", "local"], required=True)
    run.add_argument("--ref-dir", type=Path, required=True)
    run.add_argument("--out", type=Path, required=True)
    run.add_argument("--counts", type=Path)
    run.add_argument("--custodian", type=Path)
    run.add_argument("--aliases", type=Path, default=DEFAULT_ALIASES)
    args = parser.parse_args(argv)

    ref = load_reference(args.ref_dir)
    aliases = load_aliases(args.aliases) if args.aliases.exists() else {}
    if args.source == "synthetic":
        source = SyntheticSource(ref)
    else:
        if args.counts is None:
            parser.error("--counts is required with --source local")
        source = LocalFilesSource(args.counts, args.custodian)
    folder = run_pipeline(source, ref, aliases, args.out)
    print(folder)
    return str(folder)


if __name__ == "__main__":
    main()
