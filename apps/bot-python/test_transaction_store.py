"""Tests for canonical Laravel bucket preview used by Telegram."""

from __future__ import annotations

import unittest
import sys
import types
from unittest.mock import Mock, patch

# Test environment ringan tidak selalu memasang dependency runtime requests.
try:
    import requests  # noqa: F401
except ModuleNotFoundError:
    requests_stub = types.ModuleType("requests")
    requests_stub.Response = object
    requests_stub.post = Mock()
    sys.modules["requests"] = requests_stub

from transaction_store import (
    format_prescription_bucket,
    resolve_transaction_bucket,
    save_transaction_to_api,
)


def parsed_transaction() -> dict:
    return {
        "keterangan": "Pelunasan jasa freelancer IT untuk proyek YFD",
        "nominal": 5_750_000,
        "jenis": "Pengeluaran",
        "kategori": "Jasa",
        "sifat": "Need",
        "mood": "Neutral",
        "impulsif": "No",
    }


class TransactionStoreTests(unittest.TestCase):
    @patch("transaction_store.resolve_laravel_target", return_value=("https://example.test", ""))
    @patch("transaction_store.auth_headers", return_value={"Authorization": "Bearer token"})
    @patch("transaction_store.requests.post")
    def test_resolve_bucket_preview(self, post: Mock, _headers: Mock, _target: Mock) -> None:
        response = Mock(status_code=200, content=b"{}")
        response.json.return_value = {
            "ok": True,
            "category": "Jasa",
            "bucket": "Future Building",
        }
        post.return_value = response

        ok, result, error = resolve_transaction_bucket(parsed_transaction())

        self.assertTrue(ok)
        self.assertEqual(error, "")
        self.assertEqual(result["category"], "Jasa")
        self.assertEqual(result["bucket"], "Future Building")
        self.assertTrue(post.call_args.args[0].endswith("/api/bot/transactions/preview"))

    @patch("transaction_store.resolve_laravel_target", return_value=("https://example.test", ""))
    @patch("transaction_store.auth_headers", return_value={"Authorization": "Bearer token"})
    @patch("transaction_store.requests.post")
    def test_save_returns_canonical_bucket(self, post: Mock, _headers: Mock, _target: Mock) -> None:
        response = Mock(status_code=200, content=b"{}")
        response.json.return_value = {
            "ok": True,
            "id": 1,
            "category": "Jasa",
            "bucket": "Future Building",
        }
        post.return_value = response

        ok, error, result = save_transaction_to_api(123, parsed_transaction())

        self.assertTrue(ok)
        self.assertEqual(error, "")
        self.assertEqual(result["bucket"], "Future Building")

    def test_telegram_bucket_label_displays_resolved_bucket(self) -> None:
        parsed = parsed_transaction()
        parsed["bucket"] = "Future Building"

        self.assertEqual(format_prescription_bucket(parsed), "Future Building")

    def test_income_bucket_label_explains_exclusion(self) -> None:
        parsed = parsed_transaction()
        parsed["jenis"] = "Pemasukan"
        parsed["kategori"] = "Freelance"
        parsed["bucket"] = None

        self.assertEqual(
            format_prescription_bucket(parsed),
            "Tidak masuk prescription (Pemasukan)",
        )


if __name__ == "__main__":
    unittest.main()
