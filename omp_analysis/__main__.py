"""Command-line entry point for the OMP Analysis Tool.

Reads numbers from command-line arguments, or from standard input (one value
per line) when no arguments are given, and prints summary statistics.
"""

from __future__ import annotations

import sys
from typing import List

from .stats import summarize


def _parse_values(tokens: List[str]) -> List[float]:
    values = []
    for token in tokens:
        token = token.strip()
        if not token:
            continue
        values.append(float(token))
    return values


def main(argv: List[str] | None = None) -> int:
    argv = list(sys.argv[1:] if argv is None else argv)

    if argv:
        raw_tokens = argv
    else:
        raw_tokens = sys.stdin.read().split()

    try:
        values = _parse_values(raw_tokens)
    except ValueError as exc:
        print(f"error: could not parse a number ({exc})", file=sys.stderr)
        return 2

    if not values:
        print("error: no numeric values provided", file=sys.stderr)
        return 1

    stats = summarize(values)
    print(f"count:  {int(stats['count'])}")
    print(f"mean:   {stats['mean']:.4f}")
    print(f"median: {stats['median']:.4f}")
    print(f"stdev:  {stats['stdev']:.4f}")
    print(f"min:    {stats['min']:.4f}")
    print(f"max:    {stats['max']:.4f}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
