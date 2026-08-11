"""Matriks anti-tabrakan: keyword pendek, Proteksi, makeup, tumbler, sosial."""

from __future__ import annotations

import unittest

from bucket_resolver import resolve_bucket
from clarification_rules import clarification_question
from context_rules import classify_from_text
from keyword_match import keyword_in
from offline_classify import classify_offline


class KeywordMatchCollisionTests(unittest.TestCase):
    def test_les_not_inside_sales(self) -> None:
        self.assertFalse(keyword_in("sales meeting", "les"))
        self.assertTrue(keyword_in("bayar les piano", "les"))

    def test_idi_not_inside_pendidikan(self) -> None:
        self.assertFalse(keyword_in("pendidikan anak", "idi"))
        self.assertTrue(keyword_in("iuran idi", "idi"))

    def test_pph_matches_pph21(self) -> None:
        self.assertTrue(keyword_in("bayar pph21", "pph"))


class ClassifyCollisionTests(unittest.TestCase):
    def test_sales_is_not_pendidikan(self) -> None:
        hit = classify_from_text("sales meeting klien 200rb")
        assert hit is not None
        self.assertNotEqual(hit["kategori"], "Pendidikan")

    def test_premium_cushion_is_not_proteksi(self) -> None:
        hit = classify_from_text("beli cushion premium maybelline 185rb")
        assert hit is not None
        self.assertEqual(hit["kategori"], "Kesehatan & Kebersihan Diri")
        self.assertEqual(hit["sifat"], "Wants")
        self.assertNotEqual(hit["kategori"], "Proteksi")

    def test_makeup_not_social_and_no_clarification(self) -> None:
        parsed = classify_offline("beli makeup cushion maybeline 185 rb")
        self.assertEqual(parsed["jenis"], "Pengeluaran")
        self.assertEqual(parsed["kategori"], "Kesehatan & Kebersihan Diri")
        self.assertEqual(parsed["bucket"], "Flexible + Social")
        self.assertIsNone(clarification_question(parsed, "beli makeup cushion maybeline 185 rb"))

    def test_tumbler_ganti_rusak_essential_not_protection(self) -> None:
        parsed = classify_offline("beli tumbler karena tumbler lama rusak 200 rb")
        self.assertEqual(parsed["kategori"], "Tempat Tinggal")
        self.assertEqual(parsed["sifat"], "Need")
        self.assertEqual(parsed["bucket"], "Essential Living")
        mislabeled = resolve_bucket(
            {
                "jenis": "Pengeluaran",
                "kategori": "Proteksi",
                "sifat": "Need",
                "keterangan": "Beli tumbler ganti yang rusak 200000",
            }
        )
        self.assertEqual(mislabeled, "Essential Living")

    def test_tumbler_koleksi_flexible(self) -> None:
        parsed = classify_offline("beli tumbler koleksi 250rb")
        self.assertEqual(parsed["kategori"], "Tempat Tinggal")
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_ke_gym_transport_flexible(self) -> None:
        parsed = classify_offline("grab ke gym 21rb")
        self.assertEqual(parsed["kategori"], "Transportasi")
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_gym_membership_flexible_not_essential(self) -> None:
        parsed = classify_offline("bayar membership gym bulanan 450rb")
        self.assertEqual(parsed["kategori"], "Lifestyle & Hiburan")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_meeting_kerja_future_building(self) -> None:
        parsed = classify_offline("ngopi meeting kerja dengan klien 85rb")
        self.assertEqual(parsed["bucket"], "Future Building")

    def test_asuransi_jiwa_protection(self) -> None:
        parsed = classify_offline("bayar premi asuransi jiwa 500rb")
        self.assertEqual(parsed["kategori"], "Proteksi")
        self.assertEqual(parsed["bucket"], "Protection")

    def test_bpjs_protection(self) -> None:
        parsed = classify_offline("bayar bpjs kesehatan 150rb")
        self.assertEqual(parsed["kategori"], "Proteksi")
        self.assertEqual(parsed["bucket"], "Protection")

    def test_pinjaman_mama_not_income(self) -> None:
        parsed = classify_offline("terima pinjaman dari mama 2jt")
        self.assertEqual(parsed["jenis"], "Utang Masuk")
        self.assertIsNone(parsed["bucket"])

    def test_pph21_is_tax_excluded(self) -> None:
        parsed = classify_offline("bayar PPh 21 1jt")
        self.assertEqual(parsed["jenis"], "Kewajiban Pajak")
        self.assertIsNone(parsed["bucket"])

    def test_tiktok_gift_commission_is_income_not_hadiah(self) -> None:
        parsed = classify_offline("komisi gift dari TikTok 200 rb")
        self.assertEqual(parsed["jenis"], "Pemasukan")
        self.assertEqual(parsed["kategori"], "Affiliate")
        self.assertIsNone(parsed["bucket"])

    def test_income_excluded(self) -> None:
        parsed = classify_offline("gaji bulan ini 8jt")
        self.assertEqual(parsed["jenis"], "Pemasukan")
        self.assertIsNone(parsed["bucket"])

    def test_sunscreen_flexible_not_essential(self) -> None:
        parsed = classify_offline("beli sunscreen 89rb")
        self.assertEqual(parsed["kategori"], "Kesehatan & Kebersihan Diri")
        self.assertEqual(parsed["bucket"], "Flexible + Social")


if __name__ == "__main__":
    unittest.main()
