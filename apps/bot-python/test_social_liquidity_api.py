"""Unit tests for social liquidity list formatting."""

from __future__ import annotations

import unittest

from social_liquidity_api import format_social_list


class SocialLiquidityApiFormatTest(unittest.TestCase):
    def test_format_piutang_with_overdue(self) -> None:
        text = format_social_list(
            "piutang",
            {
                "piutang": {
                    "active": [
                        {
                            "name": "Grace",
                            "amount": 500000,
                            "purpose": "obat",
                            "status_label": "Jatuh tempo (1/8/2026)",
                            "follow_up": "Saatnya ditagih",
                            "is_overdue": True,
                        }
                    ],
                    "active_total": 500000,
                    "overdue_total": 500000,
                },
                "notify": {"enabled": True},
            },
        )
        self.assertIn("Grace", text)
        self.assertIn("Jatuh tempo", text)
        self.assertIn("Notifikasi bot: aktif", text)

    def test_format_empty_utang(self) -> None:
        text = format_social_list("utang", {"utang": {"active": []}, "notify": {"enabled": False}})
        self.assertIn("Belum ada utang aktif", text)


if __name__ == "__main__":
    unittest.main()
