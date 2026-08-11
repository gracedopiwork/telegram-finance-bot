"""100-transaction golden suite — run: python test_golden_transactions.py [--report]

Edit fixtures/golden_transactions.json to adjust expected jenis/kategori/sifat/bucket.
"""

from __future__ import annotations

import argparse
import json
import sys
import unittest
from pathlib import Path
from typing import Any

from offline_classify import classify_offline

FIXTURE = Path(__file__).resolve().parent / "fixtures" / "golden_transactions.json"
COMPARE_KEYS = ("jenis", "kategori", "sifat", "bucket", "nominal", "impulsif", "needs_clarification")


def load_cases() -> list[dict[str, Any]]:
    data = json.loads(FIXTURE.read_text(encoding="utf-8"))
    cases = list(data.get("cases") or [])
    if len(cases) != 100:
        raise AssertionError(f"golden fixture must have 100 cases, got {len(cases)}")
    return cases


def case_diffs(case: dict[str, Any]) -> dict[str, tuple[Any, Any]]:
    expected = dict(case.get("expected") or {})
    actual = classify_offline(str(case["text"]))
    diffs: dict[str, tuple[Any, Any]] = {}
    for key in COMPARE_KEYS:
        if key not in expected:
            continue
        got = actual.get(key)
        want = expected.get(key)
        if got != want:
            diffs[key] = (want, got)
    return diffs


def format_report(cases: list[dict[str, Any]]) -> str:
    rows = ["id     group        result  jenis / kategori / sifat / bucket / nominal"]
    failed = 0
    by_group: dict[str, list[int]] = {}
    for case in cases:
        diffs = case_diffs(case)
        ok = not diffs
        if not ok:
            failed += 1
        group = str(case.get("group") or "-")
        stats = by_group.setdefault(group, [0, 0])
        stats[0] += 1
        stats[1] += int(ok)
        actual = classify_offline(str(case["text"]))
        mark = "PASS" if ok else "FAIL"
        rows.append(
            f"{case['id']:<6} {group:<12} {mark:<6} "
            f"{actual.get('jenis')} / {actual.get('kategori')} / {actual.get('sifat')} / "
            f"{actual.get('bucket')} / {actual.get('nominal')}"
        )
        if diffs:
            for key, (want, got) in diffs.items():
                rows.append(f"       mismatch {key}: expected={want!r} actual={got!r}")
            if actual.get("needs_clarification"):
                rows.append(f"       ask: {actual.get('clarification_question')}")
    total = len(cases)
    passed = total - failed
    summary = [f"\n{passed}/{total} lulus"]
    for group, (n, ok_n) in by_group.items():
        summary.append(f"  {group}: {ok_n}/{n}")
    return "\n".join(rows + summary)


class GoldenTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.cases = load_cases()

    def test_fixture_has_unique_ids(self) -> None:
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), len(set(ids)))

    def test_all_golden_cases(self) -> None:
        failures: list[str] = []
        for case in self.cases:
            diffs = case_diffs(case)
            if diffs:
                parts = ", ".join(f"{k}: want={w!r} got={g!r}" for k, (w, g) in diffs.items())
                failures.append(f"{case['id']} | {case['text']} | {parts}")
        if failures:
            self.fail(f"{len(failures)} kasus gagal:\n" + "\n".join(failures))


if __name__ == "__main__":
    if "--report" in sys.argv:
        sys.argv.remove("--report")
        print(format_report(load_cases()))
        raise SystemExit(0)
    parser = argparse.ArgumentParser(add_help=False)
    parser.add_argument("--report", action="store_true")
    unittest.main()
