"""Smoke tests for local bucket resolver vs known YFD mapping cases."""

from __future__ import annotations

import unittest

from bucket_resolver import load_mappings, resolve_bucket
from offline_classify import classify_offline


class BucketResolverTests(unittest.TestCase):
    def test_mappings_loaded(self) -> None:
        rows = load_mappings()
        self.assertGreaterEqual(len(rows), 40)

    def test_freelancer_expense_future_building(self) -> None:
        got = resolve_bucket(
            {
                "jenis": "Pengeluaran",
                "kategori": "Bisnis & Karir",
                "sifat": "Need",
                "keterangan": "Pelunasan jasa freelancer IT dan web developer untuk proyek YFD",
            }
        )
        self.assertEqual(got, "Future Building")

    def test_gym_flexible(self) -> None:
        got = resolve_bucket(
            {
                "jenis": "Pengeluaran",
                "kategori": "Lifestyle & Hiburan",
                "sifat": "Wants",
                "keterangan": "Bayar personal trainer di gym",
            }
        )
        self.assertEqual(got, "Flexible + Social")

    def test_emergency_fund_protection(self) -> None:
        got = resolve_bucket(
            {
                "jenis": "Saving/Investment",
                "kategori": "Investasi & Tabungan",
                "sifat": "Need",
                "keterangan": "Top up emergency fund bulanan",
            }
        )
        self.assertEqual(got, "Protection")

    def test_income_excluded(self) -> None:
        got = resolve_bucket(
            {
                "jenis": "Pemasukan",
                "kategori": "Freelance",
                "sifat": "Need",
                "keterangan": "Terima honor freelance",
            }
        )
        self.assertIsNone(got)

    def test_tumbler_ganti_rusak_essential_not_protection(self) -> None:
        got = resolve_bucket(
            {
                "jenis": "Pengeluaran",
                "kategori": "Tempat Tinggal",
                "sifat": "Need",
                "keterangan": "Beli tumbler karena tumbler lama rusak 200 rb",
            }
        )
        self.assertEqual(got, "Essential Living")
        mislabeled = resolve_bucket(
            {
                "jenis": "Pengeluaran",
                "kategori": "Proteksi",
                "sifat": "Need",
                "keterangan": "Beli tumbler ganti yang rusak 200000",
            }
        )
        self.assertEqual(mislabeled, "Essential Living")

    def test_offline_grab_gym_is_transport_flexible(self) -> None:
        parsed = classify_offline("Grab dari kos ke gym Imam Bonjol 21000")
        self.assertEqual(parsed["kategori"], "Transportasi")
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_klarifikasi_sosial_flexible(self) -> None:
        parsed = classify_offline(
            "Tgl 13/09/2026 grab dari kos ke rumah cia PP 30.1k\nKlarifikasi user: Sosial"
        )
        self.assertEqual(parsed["kategori"], "Transportasi")
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_klarifikasi_lifestyle_flexible(self) -> None:
        parsed = classify_offline(
            "Tgl 13/09/2026 grab dari kos ke rumah cia PP 30.1k\nKlarifikasi user: Lifestyle"
        )
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_silahturahmi_flexible(self) -> None:
        parsed = classify_offline(
            "grab dari kos ke rumah cia acara kekeluargaan/silahturahmi PP 30.1k\n"
            "Klarifikasi user: Silahturahmi"
        )
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_rumah_orang_flexible_tanpa_klarifikasi(self) -> None:
        parsed = classify_offline("grab dari kos ke rumah budi 30100")
        self.assertEqual(parsed["kategori"], "Transportasi")
        self.assertEqual(parsed["sifat"], "Wants")
        self.assertEqual(parsed["bucket"], "Flexible + Social")

    def test_grab_wajib_tetap_essential(self) -> None:
        parsed = classify_offline("grab ke kantor 30k\nKlarifikasi user: wajib")
        self.assertEqual(parsed["sifat"], "Need")
        self.assertEqual(parsed["bucket"], "Essential Living")

    def test_bayar_kos_listrik_essential_not_protection(self) -> None:
        from bucket_resolver import resolve_bucket

        parsed = {
            "jenis": "Pengeluaran",
            "kategori": "Tempat Tinggal",
            "keterangan": "Bayar kos dan listrik",
            "sifat": "Need",
        }
        self.assertEqual(resolve_bucket(parsed), "Essential Living")
        # Mislabel Proteksi tetap dikoreksi ke Essential Living.
        parsed["kategori"] = "Proteksi"
        self.assertEqual(resolve_bucket(parsed), "Essential Living")
        # Ride dari kos tidak boleh kena aturan sewa kos.
        ride = classify_offline("Grab dari kos ke gym Imam Bonjol 21000")
        self.assertEqual(ride["bucket"], "Flexible + Social")


if __name__ == "__main__":
    unittest.main()
