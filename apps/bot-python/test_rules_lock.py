"""Rules Lock — AI tebakan tidak boleh mengalahkan Layer 1 keyword."""

from __future__ import annotations

import unittest

from context_rules import apply_context_rules, classify_from_text
from offline_classify import classify_offline


class RulesLockTests(unittest.TestCase):
    def test_spagethi_makan_siang_locked_over_ai_health(self) -> None:
        text = "13/09/2026 spagethi makan siang 60rb"
        out = apply_context_rules(
            {
                "jenis": "Pengeluaran",
                "kategori": "Kesehatan & Kebersihan Diri",
                "sifat": "Need",
                "keterangan": "Spagethi makan siang",
            },
            text,
        )
        self.assertEqual(out["kategori"], "Makanan & Minuman")
        self.assertTrue(out.get("rule_locked") or out["kategori"] == "Makanan & Minuman")

    def test_spa_alone_still_health(self) -> None:
        hit = classify_from_text("spa facial 500rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Kesehatan & Kebersihan Diri")

    def test_offline_spagethi_food(self) -> None:
        parsed = classify_offline("spagethi makan siang 60rb")
        self.assertEqual(parsed["kategori"], "Makanan & Minuman")
        self.assertEqual(parsed["bucket"], "Essential Living")

    def test_meeting_food_still_business(self) -> None:
        parsed = classify_offline("makan malam sekalian meeting kerja 213rb")
        self.assertEqual(parsed["kategori"], "Bisnis & Karir")


if __name__ == "__main__":
    unittest.main()
