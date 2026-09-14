"""Unit tests for YFD impulsive-spending guardrails."""

from __future__ import annotations

import unittest

from impulsive_rules import needs_impulse_clarification, resolve_impulsif, stamp_planned_cue


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

    def test_hadiah_terencana_survives_ai_strip_and_clarification(self) -> None:
        parsed = {
            "keterangan": "Beli hadiah iPhone 15 juta untuk adik",
            "jenis": "Pengeluaran",
            "kategori": "Hadiah",
            "sifat": "Wants",
            "nominal": 15_000_000,
        }
        text = (
            "beli hadiah iphone 15 jt buat adik. terencana\n"
            "Klarifikasi user: ganti hp utama"
        )
        self.assertEqual(
            resolve_impulsif(parsed, text, ai_suggested="Yes", trust_ai=True),
            "No",
        )
        stamped = stamp_planned_cue(dict(parsed), text)
        self.assertIn("terencana", stamped["keterangan"].lower())

    def test_tidak_terencana_tetap_impulsif(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 80_000,
        }
        self.assertEqual(resolve_impulsif(parsed, "gofood 80rb tidak terencana"), "Yes")

    def test_ambiguous_jajan_needs_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 26_000,
            "mood": "Happy",
            "keterangan": "Jajan di kantin Grand Hyatt",
        }
        self.assertTrue(
            needs_impulse_clarification(parsed, "Tgl 29/08/2026 jajan di kantin grand Hyatt 26k")
        )

    def test_receipt_cafe_wants_needs_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 68_000,
            "mood": "Happy",
            "keterangan": "Belanja latte dan pastry di Starbucks",
        }
        self.assertTrue(
            needs_impulse_clarification(
                parsed,
                "Belanja latte dan pastry di Starbucks 68000",
                from_receipt=True,
            )
        )

    def test_receipt_nasi_padang_need_skips_without_snack_signal(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
            "nominal": 45_000,
            "keterangan": "Makan siang nasi padang",
        }
        self.assertFalse(
            needs_impulse_clarification(
                parsed,
                "Makan siang nasi padang 45000",
                from_receipt=True,
            )
        )

    def test_text_cafe_without_jajan_skips_clarification(self) -> None:
        # Teks manual tanpa kata jajan: jangan over-ask; foto struk yang diperluas.
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 68_000,
            "keterangan": "Latte di Starbucks",
        }
        self.assertFalse(
            needs_impulse_clarification(parsed, "Latte di Starbucks 68000", from_receipt=False)
        )

    def test_jajan_spontan_skips_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 26_000,
            "keterangan": "Jajan spontan di kantin",
        }
        self.assertFalse(needs_impulse_clarification(parsed, "jajan spontan 26k"))

    def test_jajan_terencana_skips_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
            "nominal": 26_000,
            "keterangan": "Jajan terencana di kantin",
        }
        self.assertFalse(needs_impulse_clarification(parsed, "jajan terencana 26k"))

    def test_jajan_karena_capek_skips_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
            "nominal": 11_000,
            "mood": "Tired",
            "keterangan": "Jajan karena capek",
        }
        self.assertFalse(needs_impulse_clarification(parsed, "jajan karena capek 11000"))

    def test_regular_makan_without_snack_keyword_skips_clarification(self) -> None:
        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
            "nominal": 45_000,
            "keterangan": "Makan siang nasi padang",
        }
        self.assertFalse(needs_impulse_clarification(parsed, "makan siang nasi padang 45k"))

    def test_v18_piutang_keluar_asks_impulse_without_signal(self) -> None:
        parsed = {
            "jenis": "Piutang Keluar",
            "kategori": "Lain-lain",
            "sifat": None,
            "nominal": 200_000,
            "keterangan": "Pinjamin Ayuti 200rb",
        }
        self.assertTrue(needs_impulse_clarification(parsed, "pinjamin ayuti 200rb"))
        self.assertEqual(resolve_impulsif(parsed, "pinjamin ayuti 200rb"), "No")

    def test_v18_piutang_keluar_planned_skips_clarification(self) -> None:
        parsed = {
            "jenis": "Piutang Keluar",
            "kategori": "Lain-lain",
            "sifat": None,
            "nominal": 200_000,
            "keterangan": "Pinjamin Ayuti terencana 200rb",
        }
        self.assertFalse(needs_impulse_clarification(parsed, "pinjamin ayuti terencana 200rb"))
        self.assertEqual(resolve_impulsif(parsed, "pinjamin ayuti terencana 200rb"), "No")

    def test_v18_utang_masuk_spontan_is_impulsive(self) -> None:
        parsed = {
            "jenis": "Utang Masuk",
            "kategori": "Lain-lain",
            "sifat": None,
            "nominal": 500_000,
            "keterangan": "Pinjam ke mama 500rb spontan",
        }
        self.assertEqual(resolve_impulsif(parsed, "pinjam ke mama 500rb spontan"), "Yes")
        self.assertFalse(needs_impulse_clarification(parsed, "pinjam ke mama 500rb spontan"))

    def test_v18_piutang_keluar_dadakan_is_impulsive(self) -> None:
        parsed = {
            "jenis": "Piutang Keluar",
            "kategori": "Lain-lain",
            "sifat": None,
            "nominal": 500_000,
            "keterangan": "Pinjamin Ayuti 500rb dadakan",
        }
        self.assertEqual(resolve_impulsif(parsed, "pinjamin ayuti 500rb dadakan"), "Yes")
        self.assertFalse(needs_impulse_clarification(parsed, "pinjamin ayuti 500rb dadakan"))

    def test_v18_piutang_masuk_not_evaluated(self) -> None:
        parsed = {
            "jenis": "Piutang Masuk",
            "kategori": "Lain-lain",
            "sifat": None,
            "nominal": 200_000,
            "keterangan": "Ayuti balikin 200rb",
        }
        self.assertIsNone(resolve_impulsif(parsed, "ayuti balikin 200rb"))
        self.assertFalse(needs_impulse_clarification(parsed, "ayuti balikin 200rb"))

    def test_v18_pemasukan_and_saving_not_evaluated(self) -> None:
        income = {"jenis": "Pemasukan", "kategori": "Gaji", "sifat": None, "nominal": 5_000_000}
        saving = {"jenis": "Saving/Investment", "kategori": "Investasi & Tabungan", "sifat": None, "nominal": 100_000}
        self.assertIsNone(resolve_impulsif(income, "gaji 5jt"))
        self.assertIsNone(resolve_impulsif(saving, "nabung emas 100rb"))
        self.assertFalse(needs_impulse_clarification(income, "gaji 5jt"))
        self.assertFalse(needs_impulse_clarification(saving, "nabung emas 100rb"))


if __name__ == "__main__":
    unittest.main()
