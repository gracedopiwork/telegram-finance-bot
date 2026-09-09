import unittest

from ambiguous_suite import evaluate_cases, load_cases


class DebtTransactionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.cases = load_cases("utang")

    def test_fixture_has_unique_ids(self):
        ids = [c["id"] for c in self.cases]
        self.assertEqual(len(ids), len(set(ids)))

    def test_covers_the_main_debt_types(self):
        groups = {c.get("group") for c in self.cases}
        for expected in {"kpr", "kartu_kredit", "kendaraan", "paylater"}:
            self.assertIn(expected, groups)

    def test_must_cases_match_taxonomy(self):
        """Pencocokan Debt Register hanya menambah label — kategori & bucket tetap."""
        result = evaluate_cases(self.cases, pack="utang")
        if result["must_fail"]:
            lines = []
            for row in result["must_fail"]:
                parts = ", ".join(f"{k}: want={w!r} got={g!r}" for k, (w, g) in row["diffs"].items())
                lines.append(f"{row['id']} | {row['text']} | {parts}")
            self.fail(f"{len(lines)} kasus wajib gagal:\n" + "\n".join(lines))


if __name__ == "__main__":
    unittest.main()
