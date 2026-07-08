"""Unit tests for context_rules — run: python test_context_rules.py"""

from __future__ import annotations

import unittest

from context_rules import classify_from_text


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


if __name__ == "__main__":
    unittest.main()
