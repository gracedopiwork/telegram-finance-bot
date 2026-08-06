"""Unit tests for social liquidity meta extraction."""

from __future__ import annotations

import unittest
from datetime import date

from social_meta import (
    default_due_days,
    enrich_social_liquidity_fields,
    extract_counterparty,
    extract_due_date,
    extract_purpose,
    has_explicit_due,
    social_missing_details_question,
)


class SocialMetaTest(unittest.TestCase):
    def test_extract_grace_example(self) -> None:
        text = "Di pinjam grace 2,7 jt buat kepentingan kerja. Besok di transfer kembali."
        self.assertEqual(extract_counterparty(text).lower(), "grace")
        self.assertIn("kepentingan kerja", extract_purpose(text).lower())
        self.assertEqual(extract_due_date(text, amount=2_700_000, base=date(2026, 8, 6)), date(2026, 8, 7))
        self.assertTrue(has_explicit_due(text))

    def test_default_due_days(self) -> None:
        self.assertEqual(default_due_days(100_000), 30)
        self.assertEqual(default_due_days(500_000), 60)
        self.assertEqual(default_due_days(2_000_001), 90)

    def test_enrich_sets_sub_kategori(self) -> None:
        parsed = {
            "jenis": "Piutang Keluar",
            "kategori": "Lain-lain",
            "nominal": 2_700_000,
            "keterangan": "Di pinjam Grace 2,7 jt buat kepentingan kerja. Besok kembali.",
        }
        enrich_social_liquidity_fields(parsed, parsed["keterangan"])
        self.assertEqual(parsed.get("sub_kategori"), "Grace")
        self.assertTrue(parsed.get("social_expected_back_at"))

    def test_asks_purpose_and_due_when_only_name(self) -> None:
        parsed = {"jenis": "Utang Masuk", "keterangan": "pinjam dari ayuti 1jt"}
        q = social_missing_details_question(parsed, "pinjam dari ayuti 1jt")
        self.assertIsNotNone(q)
        self.assertIn("tujuan", (q or "").lower())
        self.assertTrue("kapan" in (q or "").lower() or "dibayar" in (q or "").lower())

    def test_no_question_when_complete(self) -> None:
        text = "pinjam dari ayuti 1jt buat biaya RS, bulan depan"
        parsed = {"jenis": "Utang Masuk", "keterangan": text}
        self.assertIsNone(social_missing_details_question(parsed, text))

    def test_clarification_reply_purpose(self) -> None:
        text = "pinjam dari ayuti 1jt\nKlarifikasi user: kepentingan kerja, besok"
        self.assertIn("kepentingan kerja", extract_purpose(text).lower())
        self.assertTrue(has_explicit_due(text))

    def test_skip_default(self) -> None:
        text = "pinjam dari ayuti 1jt\nKlarifikasi user: default"
        parsed = {"jenis": "Utang Masuk", "keterangan": text}
        self.assertIsNone(social_missing_details_question(parsed, text))

    def test_missing_name_question(self) -> None:
        parsed = {"jenis": "Piutang Keluar", "keterangan": "pinjamin 500rb"}
        q = social_missing_details_question(parsed, "pinjamin 500rb")
        self.assertIsNotNone(q)
        self.assertIn("nama", (q or "").lower() + "siapa")


if __name__ == "__main__":
    unittest.main()
