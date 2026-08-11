"""Paket uji cakupan data baru. Laporan: python test_coverage_transactions.py --report"""

from __future__ import annotations

import sys
import unittest

from ambiguous_suite import evaluate_cases, format_report, load_cases


class CoverageTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.cases = load_cases("cakup")

    def test_fixture_has_unique_ids(self) -> None:
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), len(set(ids)))

    def test_must_cases_match_taxonomy(self) -> None:
        result = evaluate_cases(self.cases, pack="cakup")
        if result["must_fail"]:
            lines = []
            for row in result["must_fail"]:
                parts = ", ".join(f"{k}: want={w!r} got={g!r}" for k, (w, g) in row["diffs"].items())
                lines.append(f"{row['id']} | {row['text']} | {parts}")
            self.fail(f"{len(lines)} kasus wajib gagal:\n" + "\n".join(lines))


if __name__ == "__main__":
    if "--report" in sys.argv:
        print(format_report(pack="cakup"))
        raise SystemExit(0)
    unittest.main()
