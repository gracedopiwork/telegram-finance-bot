import unittest

from debt_match import match_debt

THREE = [
    {"debt_id": "D1", "debt_name": "KPR BCA", "debt_type": "kpr"},
    {"debt_id": "D2", "debt_name": "Kartu Kredit Mandiri", "debt_type": "kartu_kredit"},
    {"debt_id": "D3", "debt_name": "Kredit Motor Honda", "debt_type": "kredit_kendaraan"},
]

TWO_CARDS = [
    {"debt_id": "A", "debt_name": "Kartu Kredit Mandiri", "debt_type": "kartu_kredit"},
    {"debt_id": "B", "debt_name": "Kartu Kredit BCA", "debt_type": "kartu_kredit"},
]


class DebtMatchTests(unittest.TestCase):
    def test_spec_section_8_naming_patterns(self):
        """Pola penamaan umum yang disebut spec, bukan cuma nama persis."""
        self.assertEqual(match_debt("byr kk mandiri", THREE), "D2")
        self.assertEqual(match_debt("cicilan kpr", THREE), "D1")
        self.assertEqual(match_debt("bayar motor", THREE), "D3")

    def test_full_names_with_amounts(self):
        self.assertEqual(match_debt("bayar kartu kredit mandiri 1.5jt", THREE), "D2")
        self.assertEqual(match_debt("cicilan kpr bca 4.200.000", THREE), "D1")

    def test_tolerates_small_typos(self):
        self.assertEqual(match_debt("angsuran mandiiri", THREE), "D2")

    def test_non_debt_transactions_do_not_match(self):
        for text in ["beli kopi 25rb", "bayar listrik", "makan siang", "gaji bulan ini 8jt"]:
            self.assertIsNone(match_debt(text, THREE), text)

    def test_distinguishes_two_debts_of_the_same_type(self):
        self.assertEqual(match_debt("byr kk mandiri", TWO_CARDS), "A")
        self.assertEqual(match_debt("bayar kk bca", TWO_CARDS), "B")

    def test_ambiguous_input_returns_none(self):
        """Salah menempelkan pembayaran lebih buruk daripada tidak menempelkan."""
        self.assertIsNone(match_debt("bayar kartu kredit", TWO_CARDS))

    def test_empty_register_and_empty_text(self):
        self.assertIsNone(match_debt("byr kk mandiri", []))
        self.assertIsNone(match_debt("byr kk mandiri", None))
        self.assertIsNone(match_debt("", THREE))
        self.assertIsNone(match_debt("bayar cicilan", THREE))

    def test_rows_without_an_id_are_never_returned(self):
        self.assertIsNone(match_debt("kpr bca", [{"debt_id": "", "debt_name": "KPR BCA"}]))


if __name__ == "__main__":
    unittest.main()
