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
            "kategori": "Subscription",
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
            "kategori": "Elektronik",
            "sifat": "Need",
            "mood": "Tired",
            "nominal": 3_000_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "tiba-tiba hp rusak"), "No")

    def test_small_tired_coffee_healing_is_not_impulsive(self) -> None:
        parsed = {
            "keterangan": "Beli Kopi",
            "jenis": "Pengeluaran",
            "kategori": "Jajan",
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
            "kategori": "Jajan",
            "sifat": "Wants",
            "mood": "Tired",
            "nominal": 75_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "beli kopi 75rb karena capek"), "Yes")


if __name__ == "__main__":
    unittest.main()
