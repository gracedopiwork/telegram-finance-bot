"""Unit tests for onboarding helpers."""

from __future__ import annotations

import unittest

from bucket_explain import explain_bucket_choice


class ExplainBucketTest(unittest.TestCase):
    def test_flexible_bucket(self) -> None:
        text = explain_bucket_choice(
            {
                "jenis": "Pengeluaran",
                "kategori": "Lifestyle & Hiburan",
                "sifat": "Wants",
                "bucket": "Flexible + Social",
                "keterangan": "membership gym bulanan",
            }
        )
        self.assertIn("Flexible + Social", text)
        self.assertIn("kualitas hidup", text.lower())

    def test_social_liquidity(self) -> None:
        text = explain_bucket_choice(
            {
                "jenis": "Piutang Keluar",
                "kategori": "Lain-lain",
                "sifat": "Need",
                "bucket": None,
                "keterangan": "pinjamin Grace 500rb",
            }
        )
        self.assertIn("likuiditas sosial", text.lower())


if __name__ == "__main__":
    unittest.main()
