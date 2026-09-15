"""Paket uji 500 kasus (mega). Laporan: python test_mega_transactions.py --report"""

from __future__ import annotations

import sys
import unittest

from ambiguous_suite import evaluate_cases, format_report, load_cases


class MegaTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.cases = load_cases("mega")

    def test_fixture_has_exactly_500_unique_ids(self) -> None:
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), 500)
        self.assertEqual(len(ids), len(set(ids)))

    def test_covers_debt_grey_and_flags(self) -> None:
        groups = {c.get("group") for c in self.cases}
        for need in (
            "grey",
            "utang_masuk",
            "piutang_keluar",
            "piutang_masuk",
            "utang_keluar",
            "utang_ambigu",
            "flags",
            "social_impulse",
        ):
            self.assertIn(need, groups)

    def test_must_cases_match_taxonomy(self) -> None:
        result = evaluate_cases(self.cases, pack="mega")
        if result["must_fail"]:
            lines = []
            for row in result["must_fail"][:30]:
                parts = ", ".join(
                    f"{k}: want={w!r} got={g!r}" for k, (w, g) in row["diffs"].items()
                )
                lines.append(f"{row['id']} | {row['text']} | {parts}")
            extra = len(result["must_fail"]) - len(lines)
            msg = f"{len(result['must_fail'])} kasus wajib gagal:\n" + "\n".join(lines)
            if extra > 0:
                msg += f"\n... dan {extra} lagi"
            self.fail(msg)


if __name__ == "__main__":
    if "--report" in sys.argv:
        print(format_report(pack="mega"))
        raise SystemExit(0)
    unittest.main()
