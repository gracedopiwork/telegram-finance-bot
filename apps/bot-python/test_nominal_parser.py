"""Unit tests for nominal_parser — run: python test_nominal_parser.py"""

from __future__ import annotations

import unittest

from nominal_parser import (
    nominal_sanity_warning,
    parse_nominal_from_text,
    reconcile_nominal,
)


class NominalParserTests(unittest.TestCase):
    def test_plain_trailing_amount_with_makan(self) -> None:
        self.assertEqual(parse_nominal_from_text("makan malam 65700"), 65700)

    def test_plain_trailing_amount_83800(self) -> None:
        self.assertEqual(parse_nominal_from_text("beli indomaret kemarin 83800"), 83800)

    def test_suffix_rb(self) -> None:
        self.assertEqual(parse_nominal_from_text("mkn malm 50rb"), 50_000)

    def test_suffix_br_typo_spaced(self) -> None:
        self.assertEqual(parse_nominal_from_text("bayar tagihan listrik 90 br"), 90_000)

    def test_suffix_br_typo_attached(self) -> None:
        self.assertEqual(parse_nominal_from_text("listrik 90br"), 90_000)

    def test_suffix_k_attached(self) -> None:
        self.assertEqual(parse_nominal_from_text("kopi 25k"), 25_000)

    def test_suffix_ribu_spaced(self) -> None:
        self.assertEqual(parse_nominal_from_text("parkir 15 ribu"), 15_000)

    def test_suffix_jt(self) -> None:
        self.assertEqual(parse_nominal_from_text("bayar cicilan 1.2jt"), 1_200_000)

    def test_suffix_ht_typo_for_jt(self) -> None:
        self.assertEqual(parse_nominal_from_text("pinjam uang ke mama 5 ht"), 5_000_000)
        self.assertEqual(parse_nominal_from_text("pinjam 5ht"), 5_000_000)
        self.assertEqual(reconcile_nominal(5, "pinjam uang ke mama 5 ht"), 5_000_000)

    def test_compound_jt_ratus(self) -> None:
        self.assertEqual(
            parse_nominal_from_text("sargib mengembalikan uang yang dia pinjam tapi baru 2 jt 5 ratus"),
            2_500_000,
        )
        self.assertEqual(parse_nominal_from_text("bayar 2 juta 5 ratus"), 2_500_000)

    def test_compound_jt_rb(self) -> None:
        self.assertEqual(parse_nominal_from_text("transfer 2 jt 500rb"), 2_500_000)

    def test_compound_jt_setengah(self) -> None:
        self.assertEqual(parse_nominal_from_text("pinjam 2 jt setengah"), 2_500_000)

    def test_grouped_indonesian(self) -> None:
        self.assertEqual(parse_nominal_from_text("makan malam 65.700"), 65_700)

    def test_reconcile_ai_thousand_scale_error(self) -> None:
        self.assertEqual(
            reconcile_nominal(65_700_000, "makan malam 65700"),
            65700,
        )

    def test_reconcile_ai_exact_multiple(self) -> None:
        self.assertEqual(reconcile_nominal(83_800_000, "beli 83800"), 83800)

    def test_reconcile_ai_missed_br_typo(self) -> None:
        self.assertEqual(
            reconcile_nominal(90, "bayar tagihan listrik 90 br"),
            90_000,
        )

    def test_sanity_warning_listrik_too_small(self) -> None:
        warn = nominal_sanity_warning(90, "bayar tagihan listrik", "Listrik")
        self.assertIsNotNone(warn)
        assert warn is not None
        self.assertIn("90rb", warn)

    def test_sanity_ok_after_br_fix(self) -> None:
        amount = parse_nominal_from_text("bayar tagihan listrik 90 br")
        self.assertIsNone(nominal_sanity_warning(amount, "bayar tagihan listrik", "Listrik"))

    def test_multi_line_shopping_list_sums_all_items(self) -> None:
        """Kasus Catherina: rincian belanja — jumlah semua baris, bukan item pertama saja."""
        text = (
            "Shampo Pantene anti ketombe 27.9k\n"
            "Pepsoden pasta gigi 25.9k\n"
            "Alfa tissue 41.5k\n"
            "Nice soft tissue 21.9k\n"
            "Air 23.4k\n"
            "Tas belanja 6.5k"
        )
        expected = 27_900 + 25_900 + 41_500 + 21_900 + 23_400 + 6_500
        self.assertEqual(parse_nominal_from_text(text), expected)
        self.assertEqual(reconcile_nominal(27_900, text), expected)

    def test_explicit_total_wins_over_line_items(self) -> None:
        text = "item a 10k\nitem b 20k\ntotal 25k"
        self.assertEqual(parse_nominal_from_text(text), 25_000)

    def test_inline_two_amounts_sum(self) -> None:
        self.assertEqual(parse_nominal_from_text("kopi 25k parkir 10k"), 35_000)


if __name__ == "__main__":
    unittest.main()
