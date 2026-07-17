"""Tests for mood keyword detection — especially informal spelling like 'cape'."""

from __future__ import annotations

import unittest

from bot import detect_mood_in_text, normalize_mood


class MoodDetectionTests(unittest.TestCase):
    def test_cape_is_tired(self) -> None:
        self.assertEqual(detect_mood_in_text("beli kopi starbucks 100k cape healing"), "Tired")

    def test_capek_is_tired(self) -> None:
        self.assertEqual(detect_mood_in_text("capek banget belanja 50k"), "Tired")

    def test_kecapean_is_tired(self) -> None:
        self.assertEqual(detect_mood_in_text("kecapean kerja beli snack 20k"), "Tired")

    def test_normalize_cape_alias(self) -> None:
        self.assertEqual(normalize_mood("cape"), "Tired")
        self.assertEqual(normalize_mood("capek"), "Tired")

    def test_escape_not_tired(self) -> None:
        # word-boundary: 'escape' jangan kena 'cape'
        self.assertIsNone(detect_mood_in_text("escape room ticket 150k"))


if __name__ == "__main__":
    unittest.main()
