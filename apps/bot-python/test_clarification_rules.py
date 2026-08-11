"""Tests for transaction clarification before confirmation."""

from __future__ import annotations

import unittest

from clarification_rules import clarification_question


class ClarificationRulesTests(unittest.TestCase):
    def test_generic_book_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Buku", "keterangan": "Beli Buku"},
            "beli buku 500rb",
        )
        self.assertIn("pengembangan diri", question or "")

    def test_book_with_clear_purpose_does_not_ask_again(self) -> None:
        question = clarification_question(
            {"kategori": "Buku", "keterangan": "Buku Pengembangan Diri"},
            "beli buku 500rb\nKlarifikasi user: untuk pengembangan diri",
        )
        self.assertIsNone(question)

    def test_generic_coffee_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Makanan & Minuman", "keterangan": "Beli Kopi"},
            "beli kopi 50rb",
        )
        self.assertIn("meeting kerja", question or "")

    def test_generic_piano_purchase_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Alat Musik", "keterangan": "Beli Piano"},
            "beli piano 20jt",
        )
        self.assertIn("belajar/pengembangan diri", question or "")

    def test_piano_with_clear_hobby_purpose_does_not_ask_again(self) -> None:
        question = clarification_question(
            {"kategori": "Alat Musik", "keterangan": "Piano untuk Hobi"},
            "beli piano 20jt\nKlarifikasi user: untuk hobi",
        )
        self.assertIsNone(question)

    def test_clear_work_laptop_does_not_require_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lifestyle & Hiburan", "keterangan": "Laptop Kerja"},
            "beli laptop untuk kerja 8jt",
        )
        self.assertIsNone(question)

    def test_generic_transport_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Transport", "keterangan": "Grab"},
            "grab 28rb",
        )
        self.assertIn("wajib", question or "")

    def test_business_transport_does_not_require_clarification(self) -> None:
        question = clarification_question(
            {
                "kategori": "Transport",
                "keterangan": "Grab ke aktivitas networking bisnis",
            },
            "grab ke networking bisnis 45rb",
        )
        self.assertIsNone(question)

    def test_generic_pinjol_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Cicilan & Hutang", "keterangan": "Bayar pinjol"},
            "bayar cicilan pinjol 500rb",
        )
        self.assertIn("mendesak", question or "")

    def test_generic_dp_kendaraan_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Investasi & Tabungan", "keterangan": "DP motor"},
            "dp motor 5jt",
        )
        self.assertIn("mobilitas kerja", question or "")

    def test_ai_question_has_priority(self) -> None:
        question = clarification_question(
            {
                "kategori": "Hobi",
                "keterangan": "Kelas Baru",
                "needs_clarification": True,
                "clarification_question": "Kelas ini untuk olahraga atau pengembangan diri?",
            },
            "bayar kelas 1jt",
        )
        self.assertEqual(
            question,
            "Kelas ini untuk olahraga atau pengembangan diri?",
        )

    def test_generic_perabot_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Beli kulkas"},
            "beli kulkas 5jt",
        )
        self.assertIn("rusak", question or "")

    def test_perabot_rusak_no_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Ganti kulkas rusak"},
            "ganti kulkas rusak 5jt",
        )
        self.assertIsNone(question)

    def test_generic_laptop_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lifestyle & Hiburan", "keterangan": "Beli laptop"},
            "beli laptop 12jt",
        )
        self.assertIn("alat kerja", question or "")

    def test_generic_hp_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lifestyle & Hiburan", "keterangan": "Beli HP"},
            "beli hp 8jt",
        )
        self.assertIn("rusak", question or "")

    def test_hadiah_iphone_skips_hp_and_social_clarification(self) -> None:
        text = "Hadiah beli iphone 15 jt buat Keluarga. Terencana"
        question = clarification_question(
            {
                "jenis": "Pengeluaran",
                "kategori": "Hadiah",
                "keterangan": "Hadiah iPhone untuk keluarga",
                "needs_clarification": True,
                "clarification_question": (
                    "HP ini ganti HP utama yang rusak (setara), "
                    "upgrade model terbaru, atau khusus operasional bisnis?"
                ),
            },
            text,
        )
        self.assertIsNone(question)
        social_misclassified = clarification_question(
            {
                "jenis": "Piutang Keluar",
                "kategori": "Lain-lain",
                "keterangan": text,
            },
            text,
        )
        self.assertIsNone(social_misclassified)

    def test_beli_iphone_sendiri_tetap_grey_area(self) -> None:
        question = clarification_question(
            {"kategori": "Lifestyle & Hiburan", "keterangan": "Beli iPhone"},
            "beli iphone 15jt",
        )
        self.assertIn("rusak", question or "")

    def test_hadiah_keeps_impulsif_clarification(self) -> None:
        question = clarification_question(
            {
                "jenis": "Pengeluaran",
                "kategori": "Hadiah",
                "needs_clarification": True,
                "clarification_question": (
                    "Transaksi ini terencana sebelumnya atau spontan?"
                ),
            },
            "hadiah 200rb",
        )
        self.assertIn("terencana", question or "")

    def test_generic_kpr_pbb_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Bayar PBB"},
            "bayar PBB tahunan 3jt",
        )
        self.assertIn("ditinggali", question or "")

    def test_pbb_investasi_no_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "PBB disewakan"},
            "bayar PBB properti disewakan 3jt",
        )
        self.assertIsNone(question)

    def test_generic_subscription_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Bisnis & Karir", "keterangan": "ChatGPT Plus"},
            "langganan chatgpt plus 300rb",
        )
        self.assertIn("bisnis/kerja", question or "")

    def test_netflix_no_subscription_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lifestyle & Hiburan", "keterangan": "Netflix"},
            "netflix bulanan 54rb",
        )
        self.assertIsNone(question)

    def test_generic_coaching_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Pendidikan", "keterangan": "Bayar coaching"},
            "bayar coaching 2jt",
        )
        self.assertIn("penghasilan", question or "")

    def test_generic_art_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Gaji babysitter"},
            "gaji babysitter bulanan 2jt",
        )
        self.assertIn("menunjang kemampuan kerja", question or "")

    def test_art_with_kerja_context_no_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Babysitter agar bisa praktik"},
            "gaji babysitter agar bisa praktik 2jt\nKlarifikasi user: menunjang kerja",
        )
        self.assertIsNone(question)

    def test_generic_notaris_requires_clarification(self) -> None:
        question = clarification_question(
            {
                "kategori": "Biaya Legal, Administrasi & Peristiwa Besar",
                "keterangan": "Biaya notaris",
            },
            "biaya notaris 10jt",
        )
        self.assertIn("aset", question or "")

    def test_generic_fashion_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Pakaian & Aksesoris", "keterangan": "Beli baju"},
            "beli baju 300rb",
        )
        self.assertIn("kerja/sekolah", question or "")

    def test_seragam_no_fashion_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Pakaian & Aksesoris", "keterangan": "Seragam kerja"},
            "beli seragam kerja 250rb",
        )
        self.assertIsNone(question)

    def test_generic_fisioterapi_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Kesehatan & Kebersihan Diri", "keterangan": "Fisioterapi"},
            "fisioterapi 350rb",
        )
        self.assertIn("diresepkan dokter", question or "")

    def test_fisioterapi_resep_no_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Kesehatan & Kebersihan Diri", "keterangan": "Fisioterapi resep dokter"},
            "fisioterapi resep dokter 350rb",
        )
        self.assertIsNone(question)

    def test_generic_pajak_kendaraan_requires_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Cicilan & Hutang", "keterangan": "Bayar STNK"},
            "bayar STNK 3jt",
        )
        self.assertIn("mobilitas kerja", question or "")

    def test_utang_ke_requires_arah_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Cicilan & Hutang", "keterangan": "Utang ke Ayuti"},
            "utang ke ayuti 1 juta",
        )
        self.assertIn("Piutang Keluar", question or "")
        self.assertIn("Utang Masuk", question or "")

    def test_pinjam_ke_no_arah_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lain-lain", "keterangan": "Pinjam ke mama"},
            "pinjem duit ke mama 250k",
        )
        self.assertIsNone(question)

    def test_bayar_utang_no_arah_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Lain-lain", "keterangan": "Bayar utang ke Ayuti"},
            "bayar utang ke ayuti 1jt",
        )
        self.assertIsNone(question)

    def test_utang_after_klarifikasi_no_ask_again(self) -> None:
        question = clarification_question(
            {"kategori": "Lain-lain", "keterangan": "Utang ke Ayuti"},
            "utang ke ayuti 1 juta\nKlarifikasi user: saya pinjamkan",
        )
        self.assertIsNone(question)

    def test_grab_subscription_skips_transport_destination(self) -> None:
        text = "Tgl 8/8/2026 bayar subscription grab untuk dapat paket hemat 14.000"
        question = clarification_question(
            {
                "jenis": "Pengeluaran",
                "kategori": "Transportasi",
                "keterangan": "Subscription Grab",
                "needs_clarification": True,
                "clarification_question": (
                    "Transport ini untuk tujuan wajib (kantor/sekolah/klinik), "
                    "lifestyle/sosial (cafe/mall/healing), atau bisnis/kerja/networking?"
                ),
            },
            text,
        )
        self.assertIsNone(question)

    def test_tumbler_requires_perabot_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Proteksi", "keterangan": "Beli tumbler"},
            "beli tumbler 150rb",
        )
        self.assertIn("rusak", question or "")

    def test_tumbler_ganti_rusak_no_clarification(self) -> None:
        question = clarification_question(
            {"kategori": "Tempat Tinggal", "keterangan": "Ganti tumbler rusak"},
            "beli tumbler ganti yang sebelumnya rusak 150rb",
        )
        self.assertIsNone(question)


if __name__ == "__main__":
    unittest.main()
