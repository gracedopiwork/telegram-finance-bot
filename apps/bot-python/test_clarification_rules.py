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
        self.assertIn("kerja/meeting", question or "")

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


if __name__ == "__main__":
    unittest.main()
