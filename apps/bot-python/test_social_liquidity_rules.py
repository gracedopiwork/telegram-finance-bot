"""Pinjam-meminjam: 4 arah likuiditas sosial, tanpa tabrakan tujuan/jatuh tempo."""

from __future__ import annotations

import unittest

from context_rules import (
    apply_context_rules,
    classify_from_text,
    detect_social_liquidity_jenis,
    is_ambiguous_utang_ke_person,
)
from offline_classify import classify_offline


def _hit(text: str) -> dict[str, str]:
    found = classify_from_text(text)
    assert found is not None, f"no rule for: {text}"
    return found


class SocialLiquidityRulesTests(unittest.TestCase):
    def assertArah(self, text: str, jenis: str) -> None:
        self.assertEqual(detect_social_liquidity_jenis(text), jenis, msg=text)
        hit = _hit(text)
        self.assertEqual(hit["jenis"], jenis, msg=text)
        self.assertEqual(hit["kategori"], "Lain-lain", msg=text)
        parsed = classify_offline(text)
        self.assertEqual(parsed["jenis"], jenis, msg=text)
        self.assertIsNone(parsed.get("bucket"), msg=text)

    def test_utang_masuk_variasi_pinjam(self) -> None:
        samples = (
            "saya pinjam uang ayuti 5 jt",
            "pinjam uang ayuti 5 jt",
            "saya pinjam ayuti 5 jt",
            "saya pinjam 5jt ke ayuti",
            "pinjam ke mama 250k",
            "pinjem duit ke mama 250k",
            "minjem ke kakak 1jt",
            "minjam uang ke mama 250k",
            "ngutang sama mama 250k",
            "ngutang ke mama 250k",
            "ngutang dari mama 250k",
            "pinjam dari ayuti 1jt",
            "pinjam kek mama 250k",
            "saya pinjam ke mama 250k",
            "aku ngutang sama mama 250k",
            "terima pinjaman dari mama 250k",
            "dapet pinjaman mama 250k",
            "saya yang berhutang ke mama 250k",
            "saya dipinjami ayuti 1jt",
            "dipinjami oleh ayuti 1jt",
            "ayuti kasih pinjam 1jt",
        )
        for text in samples:
            with self.subTest(text=text):
                self.assertArah(text, "Utang Masuk")

    def test_utang_masuk_tujuan_tidak_jadi_pengeluaran(self) -> None:
        samples = (
            "saya pinjam uang ayuti 5 jt buat bayar kuliah",
            "saya pinjam uang ayuti 5 jt, kebutuhan bayar uang kuliah, kembali bulan depan",
            "pinjam dari ayuti 1jt buat biaya RS, bulan depan",
            "saya pinjam ke ayuti 1jt buat kerja, besok saya kembalikan",
            "saya pinjam kek ayuti 1 jt untuk keperluan bekerja, rencana besok saya kembalikan",
            "pinjam uang mama 2jt buat obat",
        )
        for text in samples:
            with self.subTest(text=text):
                self.assertArah(text, "Utang Masuk")
                self.assertNotEqual(_hit(text)["kategori"], "Pendidikan")
                self.assertNotEqual(_hit(text)["kategori"], "Kesehatan & Kebersihan Diri")

    def test_klarifikasi_tujuan_kuliah_tetap_utang_masuk(self) -> None:
        text = (
            "saya pinjam uang ayuti 5 jt\n"
            "Klarifikasi user: kebutuhan bayar uang kuliah, kembali bulan depan"
        )
        self.assertArah(text, "Utang Masuk")
        out = apply_context_rules(
            {
                "keterangan": "Pinjam uang Ayuti untuk bayar kuliah",
                "jenis": "Pengeluaran",
                "kategori": "Pendidikan",
                "sifat": "Need",
            },
            text,
        )
        self.assertEqual(out["jenis"], "Utang Masuk")
        self.assertEqual(out["kategori"], "Lain-lain")

    def test_ai_kesehatan_tidak_menimpa_pinjam(self) -> None:
        text = "saya pinjam uang ayuti 5 jt buat obat"
        out = apply_context_rules(
            {
                "keterangan": "Pinjam uang Ayuti untuk obat",
                "jenis": "Pengeluaran",
                "kategori": "Kesehatan & Kebersihan Diri",
                "sifat": "Need",
            },
            text,
        )
        self.assertEqual(out["jenis"], "Utang Masuk")

    def test_piutang_keluar_variasi(self) -> None:
        samples = (
            "pinjamin ayuti 500k",
            "pinjamkan ayuti 500k",
            "ngutangin ayuti 500k",
            "di pinjam ayuti 500k",
            "dipinjam catherine 500k",
            "talangin mama 500k",
            "kasih pinjam ayuti 500k",
            "aku pinjamin ayuti 500k",
            "saya pinjamkan ayuti 500k",
            "bayarkan dulu buat ayuti 500k",
            "talangin mama 500k buat obat, minggu depan",
            "Di pinjam Catherine 1 jt buat bayar RS",
            "pinjamin grace 500rb buat obat",
            "transfer ke mama 500rb nanti balik",
        )
        for text in samples:
            with self.subTest(text=text):
                self.assertArah(text, "Piutang Keluar")

    def test_piutang_masuk_variasi(self) -> None:
        samples = (
            "dibalikin ayuti 500k",
            "dikembalikan ayuti 500k",
            "ayuti balikin hutang 500k",
            "ayuti kembalikan hutang 500k",
            "ayuti balikin uang 500k",
            "ayuti bayar balik 500k",
            "ayuti lunasi hutang 500k",
            "transfer balik dari ayuti 500k",
            "tf balik dari ayuti 500k",
            "uang dibalikin ayuti 500k",
            "catherine bayar balik pinjaman 500k",
            "grace kembalikan uang 500k",
            "Grace kembalikan uang yang dipinjam sebelumnya 2700000",
        )
        for text in samples:
            with self.subTest(text=text):
                self.assertArah(text, "Piutang Masuk")

    def test_utang_keluar_variasi(self) -> None:
        samples = (
            "mengembalikan uang mama 2.500.000 yang sudah saya pinjam",
            "mengembalikan uang mama 2.500.000 yang saya pinjam",
            "kembalikan ke mama 2 jt 5 ratus",
            "balikin ke mama 2500000",
            "bayar ke mama 2.5jt",
            "bayar mama 2.5jt",
            "cicil ke mama 2.5jt",
            "bayar utang ke ayuti 500k",
            "bayar hutang ke mama 500k",
            "lunasi hutang ke ayuti 500k",
            "lunasin utang ke mama 500k",
            "balikin utang ke ayuti 500k",
            "saya kembalikan uang ke ayuti 500k",
            "aku balikin hutang ke mama 500k",
            "saya mengembalikan uang ayuti yang saya pinjam 500k",
            "nyicil hutang ke ayuti 500k",
        )
        for text in samples:
            with self.subTest(text=text):
                self.assertArah(text, "Utang Keluar")

    def test_utang_ke_tanpa_sinyal_ambigu(self) -> None:
        self.assertIsNone(detect_social_liquidity_jenis("utang ke ayuti 1 juta"))
        self.assertTrue(is_ambiguous_utang_ke_person("utang ke ayuti 1 juta"))
        self.assertIsNone(classify_from_text("utang ke ayuti 1 juta"))

    def test_klarifikasi_utang_ke_arah(self) -> None:
        lend = apply_context_rules(
            {
                "keterangan": "Utang ke Ayuti",
                "jenis": "Pengeluaran",
                "kategori": "Cicilan & Hutang",
                "sifat": "Need",
            },
            "utang ke ayuti 1 juta\nKlarifikasi user: saya pinjamkan, nanti balik",
        )
        self.assertEqual(lend["jenis"], "Piutang Keluar")
        borrow = apply_context_rules(
            {
                "keterangan": "Utang ke Ayuti",
                "jenis": "Piutang Keluar",
                "kategori": "Sosial & Keluarga",
                "sifat": "Need",
            },
            "utang ke ayuti 1 juta\nKlarifikasi user: saya yang berhutang",
        )
        self.assertEqual(borrow["jenis"], "Utang Masuk")

    def test_bukan_likuiditas_sosial(self) -> None:
        cases = (
            ("bayar kuliah 5 jt", "Pengeluaran", "Pendidikan"),
            ("obat demam 45rb", "Pengeluaran", "Kesehatan & Kebersihan Diri"),
            ("transfer ke mama 500rb", "Pengeluaran", "Sosial & Keluarga"),
            ("bantu adik 1jt", "Pengeluaran", "Sosial & Keluarga"),
            ("bayar cicilan pinjol 500rb", "Pengeluaran", "Cicilan & Hutang"),
            ("beli makeup 139.5k", "Pengeluaran", "Kesehatan & Kebersihan Diri"),
            ("kado ulang tahun iphone 15jt", "Pengeluaran", "Hadiah"),
            ("pinjam laptop 2jt", "Pengeluaran", "Lifestyle & Hiburan"),
        )
        for text, jenis, kategori in cases:
            with self.subTest(text=text):
                self.assertIsNone(detect_social_liquidity_jenis(text))
                parsed = classify_offline(text)
                self.assertEqual(parsed["jenis"], jenis, msg=text)
                self.assertEqual(parsed["kategori"], kategori, msg=text)

    def test_empat_arah_satu_pasang_orang(self) -> None:
        self.assertArah("pinjamin grace 500rb buat obat", "Piutang Keluar")
        self.assertArah("grace balikin uang 500rb", "Piutang Masuk")
        self.assertArah("pinjam dari ayuti 1jt", "Utang Masuk")
        self.assertArah("bayar utang ke ayuti 1jt", "Utang Keluar")


if __name__ == "__main__":
    unittest.main()
