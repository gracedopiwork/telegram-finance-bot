"""Unit tests for onboarding helpers."""

from __future__ import annotations

import unittest

from bucket_explain import explain_bucket_choice
from onboarding_flow import home_keyboard


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


class HomeMenuTest(unittest.TestCase):
    def test_home_keyboard_has_ubah_nama(self) -> None:
        labels = [
            btn.text
            for row in home_keyboard().inline_keyboard
            for btn in row
        ]
        self.assertIn("✏️ Ubah Nama", labels)
        self.assertIn("onb:go:nama", [
            btn.callback_data
            for row in home_keyboard().inline_keyboard
            for btn in row
        ])


if __name__ == "__main__":
    unittest.main()
