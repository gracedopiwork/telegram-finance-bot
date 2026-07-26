"""Smoke tests for offline reclassify."""

from __future__ import annotations

import unittest

from reclassify_offline import reclassify_row


class ReclassifyOfflineTests(unittest.TestCase):
    def test_jajan_alias_and_emotional_impulse(self) -> None:
        result = reclassify_row(
            {
                "id": 1,
                "type": "Pengeluaran",
                "category": "Jajan",
                "nature": "Need",
                "is_impulsive": False,
                "notes": "makan malam karena capek 65000",
                "amount": 65000,
                "mood": "Tired",
            }
        )
        self.assertEqual(result["category"], "Makanan & Minuman")
        self.assertTrue(result["is_impulsive"])
        self.assertIn("category", result["changes"])

    def test_gym_is_lifestyle_wants(self) -> None:
        result = reclassify_row(
            {
                "id": 2,
                "type": "Pengeluaran",
                "category": "Kesehatan",
                "nature": "Need",
                "is_impulsive": False,
                "notes": "bayar membership gym bulanan",
                "amount": 350000,
                "mood": "Neutral",
            }
        )
        self.assertEqual(result["category"], "Lifestyle & Hiburan")
        self.assertEqual(result["nature"], "Wants")


if __name__ == "__main__":
    unittest.main()
