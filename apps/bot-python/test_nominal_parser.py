"""Unit tests for nominal_parser — run: python test_nominal_parser.py"""

from __future__ import annotations

import unittest

from nominal_parser import parse_nominal_from_text, reconcile_nominal


class NominalParserTests(unittest.TestCase):
    def test_plain_trailing_amount_with_makan(self) -> None:
        self.assertEqual(parse_nominal_from_text("makan malam 65700"), 65700)

    def test_plain_trailing_amount_83800(self) -> None:
        self.assertEqual(parse_nominal_from_text("beli indomaret kemarin 83800"), 83800)

    def test_suffix_rb(self) -> None:
        self.assertEqual(parse_nominal_from_text("mkn malm 50rb"), 50_000)

    def test_suffix_k_attached(self) -> None:
        self.assertEqual(parse_nominal_from_text("kopi 25k"), 25_000)

    def test_suffix_ribu_spaced(self) -> None:
        self.assertEqual(parse_nominal_from_text("parkir 15 ribu"), 15_000)

    def test_suffix_jt(self) -> None:
        self.assertEqual(parse_nominal_from_text("bayar cicilan 1.2jt"), 1_200_000)

    def test_grouped_indonesian(self) -> None:
        self.assertEqual(parse_nominal_from_text("makan malam 65.700"), 65_700)

    def test_reconcile_ai_thousand_scale_error(self) -> None:
        self.assertEqual(
            reconcile_nominal(65_700_000, "makan malam 65700"),
            65700,
        )

    def test_reconcile_ai_exact_multiple(self) -> None:
        self.assertEqual(reconcile_nominal(83_800_000, "beli 83800"), 83800)


if __name__ == "__main__":
    unittest.main()
