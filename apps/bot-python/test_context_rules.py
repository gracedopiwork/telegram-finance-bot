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
        self.assertClass("beli saham BBCA 1jt", "Saving/Investment", "Investasi & Tabungan")

    def test_dividen_reinvest(self) -> None:
        self.assertClass("dividen reinvest 100rb", "Saving/Investment", "Investasi & Tabungan")

    def test_cashback(self) -> None:
        self.assertClass("cashback marketplace 20rb", "Pemasukan", "Cashback")

    def test_refund(self) -> None:
        self.assertClass("refund tiket 100rb", "Pemasukan", "Refund")

    def test_gaji(self) -> None:
        self.assertClass("gaji bulan ini 8jt", "Pemasukan", "Gaji")

    def test_freelance(self) -> None:
        self.assertClass("honor freelance 1500000", "Pemasukan", "Freelance")

    def test_terima_jasa_freelence_pemasukan(self) -> None:
        self.assertClass("terima jasa freelence 6 jt", "Pemasukan", "Freelance")

    def test_explicit_pengeluaran_freelancer_bukan_pemasukan(self) -> None:
        text = (
            "Pengeluaran melunasi jasa freelancer IT dan web developer "
            "pelunasan buat proyek YFD Rp 5.750.000"
        )
        self.assertClass(text, "Pengeluaran", "Bisnis & Karir")

    def test_bayar_jasa_freelancer_bukan_pemasukan(self) -> None:
        text = (
            "Bayar jasa freelancer IT dan web developer pelunasan "
            "buat proyek YFD Rp 5.750.000"
        )
        self.assertClass(text, "Pengeluaran", "Bisnis & Karir")

    def test_apply_rules_does_not_override_ai_jenis(self) -> None:
        parsed = {
            "keterangan": "Pelunasan Jasa Freelancer IT dan Web Developer Proyek YFD",
            "jenis": "Pengeluaran",
            "kategori": "Bisnis & Karir",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "Pengeluaran melunasi jasa freelancer IT dan web developer "
            "pelunasan buat proyek YFD Rp 5.750.000",
        )
        self.assertEqual(out["jenis"], "Pengeluaran")
        self.assertEqual(out["kategori"], "Bisnis & Karir")

    def test_apply_rules_terima_corrects_ai_pengeluaran(self) -> None:
        parsed = {
            "keterangan": "terima jasa freelence 6 jt",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "terima jasa freelence 6 jt")
        self.assertEqual(out["jenis"], "Pemasukan")
        self.assertEqual(out["kategori"], "Freelance")

    def test_asuransi(self) -> None:
        self.assertClass("bayar BPJS 150rb", "Pengeluaran", "Proteksi")

    def test_subscription(self) -> None:
        self.assertClass("netflix bulanan 54rb", "Pengeluaran", "Lifestyle & Hiburan")

    def test_capcut_untuk_kerja_is_need(self) -> None:
        hit = classify_from_text(
            "langganan capcut untuk kerja edit video karena akun sebelumnya "
            "gabisa dipake, tidak terencana, 95k"
        )
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Bisnis & Karir")
        self.assertEqual(hit["sifat"], "Need")

    def test_skincare(self) -> None:
        self.assertClass("skincare serum 120rb", "Pengeluaran", "Kesehatan & Kebersihan Diri")

    def test_makan(self) -> None:
        self.assertClass("makan malam 65700", "Pengeluaran", "Makanan & Minuman")

    def test_konser_masuk_hiburan_bukan_social(self) -> None:
        self.assertClass("beli tiket konser 450rb", "Pengeluaran", "Lifestyle & Hiburan")

    def test_transport(self) -> None:
        self.assertClass("grab ke kantor 28rb", "Pengeluaran", "Transportasi")

    def test_grab_ke_gym_tetap_transport(self) -> None:
        self.assertClass(
            "Grab dari kos ke gym Imam Bonjol 21000",
            "Pengeluaran",
            "Transportasi",
        )

    def test_grab_ke_gym_sifat_wants(self) -> None:
        out = classify_from_text("Grab dari kos ke gym Imam Bonjol 21000")
        assert out is not None
        self.assertEqual(out["kategori"], "Transportasi")
        self.assertEqual(out["sifat"], "Wants")

    def test_apply_ai_lifestyle_grab_gym_to_transport(self) -> None:
        """AI sering salah ke Lifestyle karena kata gym — paksa Transportasi."""
        parsed = {
            "keterangan": "Grab dari kos ke gym Imam Bonjol",
            "jenis": "Pengeluaran",
            "kategori": "Lifestyle & Hiburan",
            "sifat": "Wants",
        }
        out = apply_context_rules(
            parsed,
            "Grab dari kos ke gym Imam Bonjol 21000",
        )
        self.assertEqual(out["kategori"], "Transportasi")
        self.assertEqual(out["sifat"], "Wants")

    def test_grabbike_ke_gym_tetap_transport(self) -> None:
        self.assertClass(
            "Tgl 22/07/2026 Transportasi grabbike dari cafe linier ke gym Rp 21.700",
            "Pengeluaran",
            "Transportasi",
        )

    def test_ojek_ke_fitness_tetap_transport(self) -> None:
        parsed = {
            "keterangan": "Ojek dari Cafe Linier ke Will Fitness Imbo",
            "jenis": "Pengeluaran",
            "kategori": "Lifestyle & Hiburan",
            "sifat": "Wants",
        }
        out = apply_context_rules(
            parsed,
            "Tgl 22/07/2026 Ojek dari cafe linier ke will fitness imbo Rp 21.700",
        )
        self.assertEqual(out["kategori"], "Transportasi")
        self.assertEqual(out["sifat"], "Wants")

    def test_kos(self) -> None:
        self.assertClass("bayar sewa kos 1500000", "Pengeluaran", "Tempat Tinggal")

    def test_sewa_masuk(self) -> None:
        self.assertClass("terima sewa kontrakan 2jt", "Pemasukan", "Sewa Masuk")

    def test_reksadana(self) -> None:
        self.assertClass("nabung reksadana 500rb", "Saving/Investment", "Investasi & Tabungan")

    def test_headset_lifestyle_bukan_jajan(self) -> None:
        hit = classify_from_text("beli headset 350rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Pengeluaran")
        self.assertEqual(hit["kategori"], "Lifestyle & Hiburan")
        self.assertEqual(hit["sifat"], "Wants")

    def test_grabfood_jajan_bukan_transport(self) -> None:
        hit = classify_from_text(
            "Tanggal 4:7/2026 jajan di grabfood 60k beli kue soesweet bali happy"
        )
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Makanan & Minuman")
        self.assertEqual(hit["sifat"], "Wants")
        self.assertNotEqual(hit["kategori"], "Transportasi")

    def test_beli_aqua_essential_bukan_jajan(self) -> None:
        hit = classify_from_text("beli aqua 1.5L 7k")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Makanan & Minuman")
        self.assertEqual(hit["sifat"], "Need")

    def test_correct_ai_jajan_aqua_to_makan(self) -> None:
        parsed = {
            "keterangan": "beli aqua 1.5L 7k",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "beli aqua 1.5L 7k")
        self.assertEqual(out["kategori"], "Makanan & Minuman")
        self.assertEqual(out["sifat"], "Need")

    def test_donasi_grab_driver_social(self) -> None:
        self.assertClass("donasi ke bapak grab 5k", "Pengeluaran", "Sosial & Keluarga")

    def test_tips_grab_driver_hadiah(self) -> None:
        self.assertClass("memberikan tips ke bapak grab 5k", "Pengeluaran", "Hadiah")

    def test_laundry_kesehatan_essential(self) -> None:
        self.assertClass("laundry/cuci baju 52.500", "Pengeluaran", "Kesehatan & Kebersihan Diri")

    def test_fashion_pakaian(self) -> None:
        self.assertClass("beli baju fashion 250rb", "Pengeluaran", "Pakaian & Aksesoris")

    def test_hadiah_wants(self) -> None:
        self.assertClass("hadiah atas jasa orang 5k", "Pengeluaran", "Hadiah")

    def test_gym_lifestyle_wants(self) -> None:
        self.assertClass(
            "Bayar olahraga gym bulanan + Personal training Rp 455.583",
            "Pengeluaran",
            "Lifestyle & Hiburan",
        )

    def test_konsumsi_meeting_bisnis(self) -> None:
        self.assertClass(
            "Konsumsi meeting untuk take konten bisnis YFD Rp 127.050",
            "Pengeluaran",
            "Bisnis & Karir",
        )

    def test_makan_meeting_kerjaan_future_building(self) -> None:
        """Ekspektasi klien: makan + meeting kerja → Bisnis & Karir."""
        self.assertClass(
            "makan malem sekalian meeting kerjaan 29/07 213.675",
            "Pengeluaran",
            "Bisnis & Karir",
        )

    def test_makan_meeting_untuk_kerja_future_building(self) -> None:
        self.assertClass(
            "makan malem sambil meeting untuk kerja 29/07 213.675",
            "Pengeluaran",
            "Bisnis & Karir",
        )

    def test_apply_rules_meeting_kerja_makan_to_bisnis(self) -> None:
        parsed = {
            "keterangan": "makan malem sekalian meeting kerjaan",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Need",
        }
        out = apply_context_rules(
            parsed,
            "makan malem sekalian meeting kerjaan 29/07 213.675",
        )
        self.assertEqual(out["kategori"], "Bisnis & Karir")
        self.assertEqual(out["sifat"], "Need")

    def test_apply_rules_meeting_bisnis_jajan_to_bisnis(self) -> None:
        parsed = {
            "keterangan": "Konsumsi meeting untuk take konten bisnis YFD",
            "jenis": "Pengeluaran",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
        }
        out = apply_context_rules(
            parsed,
            "Konsumsi meeting untuk take konten bisnis YFD Rp 127.050 neutral",
        )
        self.assertEqual(out["kategori"], "Bisnis & Karir")
        self.assertEqual(out["sifat"], "Need")

    def test_ai_income_jajan_corrected_to_expense(self) -> None:
        parsed = {
            "keterangan": "makan malam 20k",
            "jenis": "Pemasukan",
            "kategori": "Makanan & Minuman",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "makan malam 20k")
        self.assertEqual(out["jenis"], "Pengeluaran")

    def test_admin_bank_komunikasi(self) -> None:
        self.assertClass("admin bank 10 rb", "Pengeluaran", "Komunikasi")
        hit = classify_from_text("admin bank 10 rb")
        assert hit is not None
        self.assertEqual(hit["sifat"], "Need")

    def test_biaya_transfer_komunikasi(self) -> None:
        self.assertClass("biaya transfer BCA 6500", "Pengeluaran", "Komunikasi")

    def test_correct_ai_admin_bank_from_lain_lain(self) -> None:
        parsed = {
            "keterangan": "admin bank 10 rb",
            "jenis": "Pengeluaran",
            "kategori": "Lain-lain",
            "sifat": "Need",
        }
        out = apply_context_rules(parsed, "admin bank 10 rb")
        self.assertEqual(out["kategori"], "Komunikasi")
        self.assertEqual(out["sifat"], "Need")


if __name__ == "__main__":
    unittest.main()
