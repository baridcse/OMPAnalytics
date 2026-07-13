"""Core statistical helpers for the OMP Analysis Tool."""

from __future__ import annotations

from typing import Dict, Sequence


def mean(values: Sequence[float]) -> float:
    """Return the arithmetic mean of ``values``.

    Raises:
        ValueError: if ``values`` is empty.
    """
    if not values:
        raise ValueError("mean() requires at least one value")
    return sum(values) / len(values)


def median(values: Sequence[float]) -> float:
    """Return the median of ``values``.

    Raises:
        ValueError: if ``values`` is empty.
    """
    if not values:
        raise ValueError("median() requires at least one value")
    ordered = sorted(values)
    n = len(ordered)
    mid = n // 2
    if n % 2 == 1:
        return float(ordered[mid])
    return (ordered[mid - 1] + ordered[mid]) / 2


def summarize(values: Sequence[float]) -> Dict[str, float]:
    """Return a dictionary of summary statistics for ``values``.

    Raises:
        ValueError: if ``values`` is empty.
    """
    if not values:
        raise ValueError("summarize() requires at least one value")
    return {
        "count": len(values),
        "mean": mean(values),
        "median": median(values),
        "min": min(values),
        "max": max(values),
    }
