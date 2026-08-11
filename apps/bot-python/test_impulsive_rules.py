"""Unit tests for YFD impulsive-spending guardrails."""

from __future__ import annotations

import unittest

from impulsive_rules import resolve_impulsif


class ImpulsiveRulesTests(unittest.TestCase):
    def test_unplanned_discretionary_purchase_is_impulsive(self) -> None:
        parsed = {
            "keterangan": "Beli aksesori lucu",
            "jenis": "Pengeluaran",
            "kategori": "Belanja",
            "sifat": "Wants",
            "mood": "Neutral",
            "nominal": 95_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "tidak terencana"), "Yes")

    def test_unplanned_replacement_of_broken_account_is_not_impulsive(self) -> None:
        parsed = {
            "keterangan": "Langganan CapCut untuk kerja edit video",
            "jenis": "Pengeluaran",
            "kategori": "Lifestyle & Hiburan",
            "sifat": "Need",
            "mood": "Sad",
            "nominal": 95_000,
        }
        text = (
            "langganan capcut untuk kerja edit video karena akun sebelumnya "
            "gabisa dipake, tidak terencana, 95k"
        )
        self.assertEqual(
            resolve_impulsif(parsed, text, ai_suggested="Yes", trust_ai=True),
            "No",
        )

    def test_broken_device_replacement_is_not_impulsive(self) -> None:
        parsed = {
            "keterangan": "Ganti HP utama yang rusak",
            "jenis": "Pengeluaran",
            "kategori": "Lifestyle & Hiburan",
            "sifat": "Need",
            "mood": "Tired",
            "nominal": 3_000_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "tiba-tiba hp rusak"), "No")

    def test_small_tired_coffee_healing_is_not_impulsive(self) -> None:
        parsed = {
            "keterangan": "Beli Kopi",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "mood": "Tired",
            "nominal": 15_000,
        }
        self.assertEqual(
            resolve_impulsif(
                parsed,
                "beli kopi 15 ribu karena capek banget healing",
                ai_suggested="Yes",
                trust_ai=True,
            ),
            "No",
        )

    def test_larger_tired_wants_can_still_be_impulsive(self) -> None:
        parsed = {
            "keterangan": "Beli kopi specialty",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "mood": "Tired",
            "nominal": 75_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "beli kopi 75rb karena capek"), "Yes")

    def test_tired_dinner_is_impulsive_even_if_need_and_ai_says_no(self) -> None:
        parsed = {
            "keterangan": "makan malam 100 rb karena capek",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
            "mood": "Tired",
            "nominal": 100_000,
        }
        self.assertEqual(
            resolve_impulsif(
                parsed,
                "makan malam 100 rb karena capek",
                ai_suggested="No",
                trust_ai=True,
            ),
            "Yes",
        )

    def test_small_jajan_karena_capek_is_impulsive(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
            "nominal": 11000,
            "mood": "Tired",
            "keterangan": "Jajan karena capek",
        }
        self.assertEqual(
            resolve_impulsif(parsed, "jajan karena capek 11000", ai_suggested="No", trust_ai=True),
            "Yes",
        )

    def test_explicit_word_impulsif_is_yes(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 11000,
        }
        self.assertEqual(
            resolve_impulsif(parsed, "beli snack impulsif 11rb"),
            "Yes",
        )

    def test_hadiah_spontan_impulsif(self) -> None:
        parsed = {
            "keterangan": "Hadiah atas jasa orang 5k",
            "jenis": "Pengeluaran",
            "kategori": "Social",
            "sifat": "Wants",
            "mood": "Happy",
            "nominal": 5_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "hadiah atas jasa orang 5k happy"), "Yes")

    def test_hadiah_terencana_not_impulsif(self) -> None:
        parsed = {
            "keterangan": "Hadiah beli iPhone untuk keluarga",
            "jenis": "Pengeluaran",
            "kategori": "Hadiah",
            "sifat": "Wants",
            "nominal": 15_000_000,
        }
        self.assertEqual(
            resolve_impulsif(
                parsed,
                "Hadiah beli iphone 15 jt buat Keluarga. Terencana",
            ),
            "No",
        )

    def test_tidak_terencana_tetap_impulsif(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 80_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "gofood 80rb tidak terencana"), "Yes")


if __name__ == "__main__":
    unittest.main()
