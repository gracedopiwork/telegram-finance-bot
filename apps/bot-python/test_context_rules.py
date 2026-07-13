"""Unit tests for context_rules — run: python test_context_rules.py"""

from __future__ import annotations

import unittest

from context_rules import apply_context_rules, classify_from_text


class ContextRulesTests(unittest.TestCase):
    def assertClass(self, text: str, jenis: str, kategori: str) -> None:
        hit = classify_from_text(text)
        self.assertIsNotNone(hit, msg=f"no rule for: {text}")
        assert hit is not None
        self.assertEqual(hit["jenis"], jenis, msg=text)
        self.assertEqual(hit["kategori"], kategori, msg=text)

    def test_affiliate(self) -> None:
        self.assertClass("dapat shopee affiliate 50000", "Pemasukan", "Affiliate")

    def test_bunga_investasi(self) -> None:
        self.assertClass("terima bunga investasi sebesar 5000", "Pemasukan", "Bunga Investasi")

    def test_dividen_cair(self) -> None:
        self.assertClass("dividen BBCA cair 200rb", "Pemasukan", "Dividen")

    def test_beli_saham(self) -> None:
        self.assertClass("beli saham BBCA 1jt", "Saving/Investment", "Saham")

    def test_dividen_reinvest(self) -> None:
        self.assertClass("dividen reinvest 100rb", "Saving/Investment", "Saham")

    def test_cashback(self) -> None:
        self.assertClass("cashback marketplace 20rb", "Pemasukan", "Cashback")

    def test_refund(self) -> None:
        self.assertClass("refund tiket 100rb", "Pemasukan", "Refund")

    def test_gaji(self) -> None:
        self.assertClass("gaji bulan ini 8jt", "Pemasukan", "Gaji")

    def test_freelance(self) -> None:
        self.assertClass("honor freelance 1500000", "Pemasukan", "Freelance")

    def test_explicit_pengeluaran_freelancer_bukan_pemasukan(self) -> None:
        text = (
            "Pengeluaran melunasi jasa freelancer IT dan web developer "
            "pelunasan buat proyek YFD Rp 5.750.000"
        )
        self.assertClass(text, "Pengeluaran", "Jasa")

    def test_bayar_jasa_freelancer_bukan_pemasukan(self) -> None:
        text = (
            "Bayar jasa freelancer IT dan web developer pelunasan "
            "buat proyek YFD Rp 5.750.000"
        )
        self.assertClass(text, "Pengeluaran", "Jasa")

    def test_apply_rules_does_not_override_ai_jenis(self) -> None:
        """AI sudah isi jenis+kategori → rule jangan timpa ke Pemasukan/Freelance."""
        parsed = {
            "keterangan": "Pelunasan Jasa Freelancer IT dan Web Developer Proyek YFD",
            "jenis": "Pengeluaran",
            "kategori": "Jasa",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "Pengeluaran melunasi jasa freelancer IT dan web developer "
            "pelunasan buat proyek YFD Rp 5.750.000",
        )
        self.assertEqual(out["jenis"], "Pengeluaran")
        self.assertEqual(out["kategori"], "Jasa")

    def test_apply_rules_respects_explicit_user_jenis(self) -> None:
        """User tulis 'Pengeluaran' di awal → jenis mengikuti user (bukan rule freelance)."""
        parsed = {
            "keterangan": "Pelunasan Jasa Freelancer IT Proyek YFD",
            "jenis": "Pemasukan",
            "kategori": "Freelance",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "Pengeluaran melunasi jasa freelancer IT Rp 5.750.000",
        )
        self.assertEqual(out["jenis"], "Pengeluaran")

    def test_apply_rules_does_not_force_income_over_ai(self) -> None:
        """Tanpa jenis eksplisit: AI Pengeluaran tetap menang meski ada kata freelancer."""
        parsed = {
            "keterangan": "Bayar jasa freelancer web developer",
            "jenis": "Pengeluaran",
            "kategori": "Jasa",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "Bayar jasa freelancer IT dan web developer pelunasan "
            "buat proyek YFD Rp 5.750.000",
        )
        self.assertEqual(out["jenis"], "Pengeluaran")
        self.assertEqual(out["kategori"], "Jasa")

    def test_asuransi(self) -> None:
        self.assertClass("bayar BPJS 150rb", "Pengeluaran", "Asuransi")

    def test_subscription(self) -> None:
        self.assertClass("netflix bulanan 54rb", "Pengeluaran", "Subscription")

    def test_skincare(self) -> None:
        self.assertClass("skincare serum 120rb", "Pengeluaran", "Skincare")

    def test_makan(self) -> None:
        self.assertClass("makan malam 65700", "Pengeluaran", "Makan")

    def test_transport(self) -> None:
        self.assertClass("grab ke kantor 28rb", "Pengeluaran", "Transport")

    def test_kos(self) -> None:
        self.assertClass("bayar sewa kos 1500000", "Pengeluaran", "Sewa/Tempat Tinggal")

    def test_sewa_masuk(self) -> None:
        self.assertClass("terima sewa kontrakan 2jt", "Pemasukan", "Sewa Masuk")

    def test_reksadana(self) -> None:
        self.assertClass("nabung reksadana 500rb", "Saving/Investment", "Reksadana")

    def test_bunga_bukan_saving(self) -> None:
        hit = classify_from_text("bunga deposito 15000")
        self.assertEqual(hit["jenis"], "Pemasukan")
        self.assertNotEqual(hit["jenis"], "Saving/Investment")

    def test_headset_elektronik_bukan_jajan(self) -> None:
        hit = classify_from_text("beli headset 350rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Pengeluaran")
        self.assertEqual(hit["kategori"], "Elektronik")
        self.assertEqual(hit["sifat"], "Wants")

    def test_earphone_elektronik(self) -> None:
        self.assertClass("earphone 150rb", "Pengeluaran", "Elektronik")

    def test_hp_rusak_elektronik_need(self) -> None:
        hit = classify_from_text("ganti hp rusak 3jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Elektronik")
        self.assertEqual(hit["sifat"], "Need")

    def test_laptop_kerja_elektronik_need(self) -> None:
        hit = classify_from_text("laptop kerja 8jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Elektronik")
        self.assertEqual(hit["sifat"], "Need")

    def test_trust_ai_expense_category(self) -> None:
        parsed = {
            "keterangan": "beli headset",
            "jenis": "Pengeluaran",
            "kategori": "Elektronik",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "beli headset 350rb")
        self.assertEqual(out["kategori"], "Elektronik")

    def test_correct_ai_jajan_to_elektronik(self) -> None:
        parsed = {
            "keterangan": "beli headset",
            "jenis": "Pengeluaran",
            "kategori": "Jajan",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "beli headset 350rb")
        self.assertEqual(out["kategori"], "Elektronik")

    def test_do_not_override_ai_makan(self) -> None:
        parsed = {
            "keterangan": "makan siang meeting",
            "jenis": "Pengeluaran",
            "kategori": "Makan",
            "sifat": "Need",
        }
        out = apply_context_rules(parsed, "makan siang meeting 80rb")
        self.assertEqual(out["kategori"], "Makan")

    def test_grabfood_jajan_bukan_transport(self) -> None:
        hit = classify_from_text(
            "Tanggal 4:7/2026 jajan di grabfood 60k beli kue soesweet bali happy"
        )
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Jajan")
        self.assertEqual(hit["sifat"], "Wants")
        self.assertNotEqual(hit["kategori"], "Transport")

    def test_grab_ojek_tetap_transport(self) -> None:
        self.assertClass("grab ke kantor 28rb", "Pengeluaran", "Transport")

    def test_correct_ai_transport_grabfood(self) -> None:
        parsed = {
            "keterangan": "Jajan di Grabfood beli kue Soesweet Bali",
            "jenis": "Pengeluaran",
            "kategori": "Transport",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "jajan di grabfood 60k beli kue soesweet bali happy",
        )
        self.assertEqual(out["kategori"], "Jajan")
        self.assertEqual(out["sifat"], "Wants")


if __name__ == "__main__":
    unittest.main()
