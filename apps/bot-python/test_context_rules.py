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

    def test_transport_bisnis_tetap_transport(self) -> None:
        hit = classify_from_text(
            "Grab dari kos ke aktivitas networking bisnis di Nusa Dua 45100"
        )
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Pengeluaran")
        self.assertEqual(hit["kategori"], "Transportasi")
        self.assertEqual(hit["sifat"], "Need")

    def test_transport_lifestyle_wants(self) -> None:
        hit = classify_from_text("grab ke mall nongkrong 25rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Transportasi")
        self.assertEqual(hit["sifat"], "Wants")

    def test_piutang_keluar_bukan_kesehatan(self) -> None:
        hit = classify_from_text("Di pinjam Catherine 1 jt buat bayar RS")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Piutang Keluar")
        self.assertEqual(hit["sifat"], "Need")

    def test_utang_ke_orang_adalah_cicilan_hutang(self) -> None:
        self.assertClass("utang ke ayuti 1 juta", "Pengeluaran", "Cicilan & Hutang")
        self.assertClass("hutang ke ayuti 1jt", "Pengeluaran", "Cicilan & Hutang")
        self.assertClass("pinjam ke ayuti 1jt", "Pengeluaran", "Cicilan & Hutang")
        self.assertClass("bayar utang ke ayuti 1jt", "Pengeluaran", "Cicilan & Hutang")

    def test_apply_rules_corrects_ai_piutang_when_utang_ke(self) -> None:
        parsed = {
            "keterangan": "Utang ke Ayuti",
            "jenis": "Piutang Keluar",
            "kategori": "Sosial & Keluarga",
            "sifat": "Need",
        }
        out = apply_context_rules(parsed, "utang ke ayuti 1 juta")
        self.assertEqual(out["jenis"], "Pengeluaran")
        self.assertEqual(out["kategori"], "Cicilan & Hutang")

    def test_ngutangin_tetap_piutang_keluar(self) -> None:
        hit = classify_from_text("ngutangin ayuti 1jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Piutang Keluar")

    def test_apply_rules_corrects_ai_kesehatan_to_piutang(self) -> None:
        parsed = {
            "keterangan": "Dipinjam Catherine untuk bayar RS",
            "jenis": "Pengeluaran",
            "kategori": "Kesehatan & Kebersihan Diri",
            "sifat": "Need",
        }
        out = apply_context_rules(parsed, "Di pinjam Catherine 1 jt buat bayar RS")
        self.assertEqual(out["jenis"], "Piutang Keluar")

    def test_piutang_masuk(self) -> None:
        self.assertClass(
            "Catherine bayar balik pinjaman 1jt",
            "Piutang Masuk",
            "Lain-lain",
        )

    def test_dp_rumah_saving_future(self) -> None:
        hit = classify_from_text("dp rumah 50jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Saving/Investment")
        self.assertEqual(hit["kategori"], "Investasi & Tabungan")

    def test_dp_kendaraan_lifestyle_pengeluaran(self) -> None:
        hit = classify_from_text("dp mobil kedua upgrade gaya hidup 20jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Pengeluaran")
        self.assertEqual(hit["kategori"], "Cicilan & Hutang")
        self.assertEqual(hit["sifat"], "Wants")

    def test_pinjol_cicilan(self) -> None:
        self.assertClass("bayar cicilan pinjol 500rb", "Pengeluaran", "Cicilan & Hutang")

    def test_denda_tilang(self) -> None:
        self.assertClass("bayar tilang 250rb", "Pengeluaran", "Cicilan & Hutang")

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

    def test_traveling_hotel_staycation(self) -> None:
        self.assertClass("bayar hotel staycation Ubud 1.2jt", "Pengeluaran", "Traveling")

    def test_traveling_liburan_wisata(self) -> None:
        hit = classify_from_text("liburan wisata Bali hotel 3jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Traveling")
        self.assertEqual(hit["sifat"], "Wants")

    def test_seminar_pengembangan_diri_pendidikan(self) -> None:
        self.assertClass("bayar seminar sertifikasi 2jt", "Pengeluaran", "Pendidikan")

    def test_workshop_conference_pendidikan(self) -> None:
        self.assertClass("ikut workshop pengembangan diri 500rb", "Pengeluaran", "Pendidikan")

    def test_iuran_idi_pendidikan(self) -> None:
        self.assertClass("bayar iuran IDI tahunan 1jt", "Pengeluaran", "Pendidikan")

    def test_babysitter_tempat_tinggal(self) -> None:
        self.assertClass("gaji babysitter bulanan 2jt", "Pengeluaran", "Tempat Tinggal")

    def test_art_pembantu_tempat_tinggal(self) -> None:
        self.assertClass("bayar gaji pembantu rumah tangga 1.5jt", "Pengeluaran", "Tempat Tinggal")

    def test_pbb_investasi_tempat_tinggal(self) -> None:
        hit = classify_from_text("bayar PBB properti disewakan 5jt")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Tempat Tinggal")
        self.assertEqual(hit["sifat"], "Need")

    def test_fisioterapi_resep_dokter_need(self) -> None:
        hit = classify_from_text("fisioterapi resep dokter 350rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Kesehatan & Kebersihan Diri")
        self.assertEqual(hit["sifat"], "Need")

    def test_qurban_sosial(self) -> None:
        self.assertClass("bayar qurban 3jt", "Pengeluaran", "Sosial & Keluarga")

    def test_tip_hadiah(self) -> None:
        self.assertClass("kasih tip gojek 10rb", "Pengeluaran", "Hadiah")

    def test_fashion_bukan_lifestyle(self) -> None:
        hit = classify_from_text("beli baju fashion 250rb")
        assert hit is not None
        self.assertEqual(hit["kategori"], "Pakaian & Aksesoris")
        self.assertNotEqual(hit["kategori"], "Lifestyle & Hiburan")

    def test_pemasukan_generik_lain_lain_bukan_lainnya(self) -> None:
        hit = classify_from_text("terima uang 100rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["jenis"], "Pemasukan")
        self.assertEqual(hit["kategori"], "Lain-lain")

    def test_starbucks_meeting_bisnis(self) -> None:
        self.assertClass(
            "starbucks meeting klien 85rb",
            "Pengeluaran",
            "Bisnis & Karir",
        )

    def test_kopi_healing_makanan_wants(self) -> None:
        hit = classify_from_text("kopi starbucks healing 65rb")
        self.assertIsNotNone(hit)
        assert hit is not None
        self.assertEqual(hit["kategori"], "Makanan & Minuman")
        self.assertEqual(hit["sifat"], "Wants")

    def test_gym_membership_bukan_transport(self) -> None:
        hit = classify_from_text("bayar membership gym bulanan 450rb")
        assert hit is not None
        self.assertEqual(hit["kategori"], "Lifestyle & Hiburan")

    def test_grab_ke_gym_transport_wants(self) -> None:
        hit = classify_from_text("grab ke gym 21rb")
        assert hit is not None
        self.assertEqual(hit["kategori"], "Transportasi")
        self.assertEqual(hit["sifat"], "Wants")

    def test_apply_ai_lainnya_to_traveling(self) -> None:
        parsed = {
            "keterangan": "hotel staycation",
            "jenis": "Pengeluaran",
            "kategori": "Lainnya",
            "sifat": "Wants",
        }
        out = apply_context_rules(parsed, "bayar hotel staycation 1jt")
        self.assertEqual(out["kategori"], "Traveling")


if __name__ == "__main__":
    unittest.main()
