"""Paket uji grey area + perhutangan + flag klinis v1.8.

Laporan:
  python test_gray_debt_transactions.py --report

Telegram:
  /uji4
"""

from __future__ import annotations

import sys
import unittest

from ambiguous_suite import evaluate_cases, format_report, load_cases


class GrayDebtTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.cases = load_cases("grey")

    def test_fixture_has_unique_ids(self) -> None:
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), len(set(ids)))

    def test_covers_grey_debt_and_flags(self) -> None:
        groups = {c.get("group") for c in self.cases}
        for need in (
            "grey_tumbler",
            "grey_hp",
            "utang_ambigu",
            "piutang_keluar",
            "utang_masuk",
            "piutang_masuk",
            "utang_keluar",
            "risk_alert",
            "late_pattern",
            "life_event",
            "scope_v18",
        ):
            self.assertIn(need, groups)

    def test_must_cases_match_taxonomy(self) -> None:
        result = evaluate_cases(self.cases, pack="grey")
        if result["must_fail"]:
            lines = []
            for row in result["must_fail"]:
                parts = ", ".join(
                    f"{k}: want={w!r} got={g!r}" for k, (w, g) in row["diffs"].items()
                )
                lines.append(f"{row['id']} | {row['text']} | {parts}")
            self.fail(f"{len(lines)} kasus wajib gagal:\n" + "\n".join(lines))


if __name__ == "__main__":
    if "--report" in sys.argv:
        print(format_report(pack="grey"))
        raise SystemExit(0)
    unittest.main()
