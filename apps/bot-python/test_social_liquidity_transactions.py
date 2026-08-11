"""Paket uji piutang & utang. Laporan: python test_social_liquidity_transactions.py --report"""

from __future__ import annotations

import sys
import unittest

from ambiguous_suite import evaluate_cases, format_report, load_cases


class SocialLiquidityTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.cases = load_cases("sosial")

    def test_fixture_has_unique_ids(self) -> None:
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), len(set(ids)))

    def test_covers_four_arah(self) -> None:
        groups = {c.get("group") for c in self.cases}
        for need in ("utang_masuk", "utang_keluar", "piutang_keluar", "piutang_masuk"):
            self.assertIn(need, groups)

    def test_must_cases_match_taxonomy(self) -> None:
        result = evaluate_cases(self.cases, pack="sosial")
        if result["must_fail"]:
            lines = []
            for row in result["must_fail"]:
                parts = ", ".join(f"{k}: want={w!r} got={g!r}" for k, (w, g) in row["diffs"].items())
                lines.append(f"{row['id']} | {row['text']} | {parts}")
            self.fail(f"{len(lines)} kasus wajib gagal:\n" + "\n".join(lines))


if __name__ == "__main__":
    if "--report" in sys.argv:
        print(format_report(pack="sosial"))
        raise SystemExit(0)
    unittest.main()
