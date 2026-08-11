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
    match_relative_due_days,
    resolve_settle_choice,
    social_missing_details_question,
    social_settle_target_question,
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

    def test_repay_does_not_ask_purpose_even_if_ai_says_piutang_keluar(self) -> None:
        text = "mengembalikan uang mama 2.500.000 yang saya pinjam"
        parsed = {
            "jenis": "Piutang Keluar",
            "keterangan": "Mengembalikan uang mama yang sudah dipinjam",
            "needs_clarification": True,
            "clarification_question": (
                "Untuk tracker Likuiditas Sosial, sebutkan tujuan pinjaman dan "
                "kapan dikembalikan (contoh: kepentingan kerja, kembali besok). "
                "Ketik 'default' jika belum pasti."
            ),
        }
        self.assertIsNone(social_missing_details_question(parsed, text))

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

    def test_dedupe_keterangan_on_clarification_reply(self) -> None:
        parsed = {
            "jenis": "Utang Masuk",
            "kategori": "Lain-lain",
            "nominal": 52_000_000,
            "keterangan": (
                "Pinjam dari mama untuk kepentingan bisnis | buat Kepentingan bisnis, "
                "kembali belum Tau kapan Pinjam dari mama untuk kepentingan bisnis"
            ),
        }
        enrich_social_liquidity_fields(
            parsed,
            "Pinjam dari mama 52jt\nKlarifikasi user: Kepentingan bisnis, kembali belum Tau kapan",
        )
        note = parsed["keterangan"].lower()
        self.assertEqual(note.count("pinjam dari mama"), 1)
        self.assertIn("kepentingan bisnis", note)

    def test_fuzzy_due_typos_and_variants(self) -> None:
        self.assertEqual(match_relative_due_days("balikin besok"), 1)
        self.assertEqual(match_relative_due_days("di balikin besok"), 1)
        self.assertEqual(match_relative_due_days("kembali besok"), 1)
        self.assertEqual(match_relative_due_days("sebulan ke depan"), 30)
        self.assertEqual(match_relative_due_days("sebulan kedepan"), 30)
        self.assertEqual(match_relative_due_days("bulan depam"), 30)
        self.assertEqual(match_relative_due_days("bulan depa"), 30)
        self.assertEqual(match_relative_due_days("bln depan"), 30)
        self.assertEqual(match_relative_due_days("minggu depan"), 7)
        self.assertTrue(has_explicit_due("pinjam ke mama\nKlarifikasi user: sebulan ke depan"))
        self.assertTrue(has_explicit_due("Klarifikasi user: bulan depam"))
        due = extract_due_date("bulan depam", amount=5_000_000, base=date(2026, 8, 11))
        self.assertEqual(due, date(2026, 9, 10))

    def test_purpose_from_short_clarification(self) -> None:
        text = "pinjam uang ke mama 5jt\nKlarifikasi user: kebutuhan mendesak"
        self.assertIn("kebutuhan mendesak", extract_purpose(text).lower())
        parsed = {"jenis": "Utang Masuk", "keterangan": text, "nominal": 5_000_000}
        q = social_missing_details_question(parsed, text)
        self.assertIsNotNone(q)
        self.assertIn("kapan", (q or "").lower())

        text2 = text + "\nKlarifikasi user: sebulan ke depan"
        self.assertTrue(has_explicit_due(text2))
        self.assertIsNone(social_missing_details_question(
            {"jenis": "Utang Masuk", "keterangan": text2, "nominal": 5_000_000},
            text2,
        ))

    def test_counterparty_pinjem_ke_mama(self) -> None:
        self.assertEqual(extract_counterparty("pinjem duit ke mama 250k").lower(), "mama")
        self.assertEqual(extract_counterparty("dibalikin ayuti 500k").lower(), "ayuti")
        self.assertEqual(extract_counterparty("ngutang sama mama 250k").lower(), "mama")
        self.assertEqual(extract_counterparty("ayuti balikin hutang 500k").lower(), "ayuti")
        self.assertEqual(extract_counterparty("transfer balik dari ayuti 500k").lower(), "ayuti")
        self.assertEqual(
            extract_counterparty("mengembalikan uang mama 2.500.000 yang sudah saya pinjam").lower(),
            "mama",
        )

    def test_counterparty_bukan_tujuan_dokter(self) -> None:
        self.assertEqual(
            extract_counterparty(
                "saya meminjamkan uang kepada ayuti Meminjamkan Ayuti untuk biaya ke dokter"
            ).lower(),
            "ayuti",
        )
        self.assertEqual(
            extract_counterparty("Meminjamkan Sargib untuk ke dokter").lower(),
            "sargib",
        )
        self.assertEqual(
            extract_counterparty("tanggal 25 bulan ini Meminjamkan Sargib untuk ke dokter").lower(),
            "sargib",
        )
        self.assertEqual(
            extract_counterparty("pinjamin ayuti 500k buat biaya ke dokter").lower(),
            "ayuti",
        )

    def test_settle_asks_which_utang_when_ambiguous(self) -> None:
        rows = [
            {"name": "Ayuti", "amount_remaining": 5_000_000, "purpose": "kuliah", "status": "active"},
            {"name": "mama", "amount_remaining": 5_000_000, "purpose": "bayar kuliah", "status": "active"},
        ]
        parsed = {"jenis": "Utang Keluar", "keterangan": "bayar utang 2.5jt"}
        q = social_settle_target_question(parsed, "bayar utang 2.5jt", rows)
        self.assertIsNotNone(q)
        self.assertIn("mama", (q or "").lower())
        self.assertIn("ayuti", (q or "").lower())
        self.assertIn("dicicil", (q or "").lower())

        named = social_settle_target_question(
            {"jenis": "Utang Keluar", "keterangan": "bayar utang ke mama 2.5jt"},
            "bayar utang ke mama 2.5jt",
            rows,
        )
        self.assertIsNone(named)
        self.assertIsNone(
            social_settle_target_question(parsed, "bayar utang 2.5jt", rows[:1])
        )
        self.assertEqual(resolve_settle_choice("2", rows).lower(), "mama")
        self.assertEqual(resolve_settle_choice("ibu", rows).lower(), "mama")
        self.assertEqual(
            extract_counterparty("bayar utang 2.5jt\nKlarifikasi user: mama").lower(),
            "mama",
        )


if __name__ == "__main__":
    unittest.main()
