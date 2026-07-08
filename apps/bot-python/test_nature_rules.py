"""Tests for contextual Need/Wants refinement."""

from nature_rules import refine_sifat_from_context


def test_coffee_for_productivity_is_need():
    text = (
        "Beli kopi starbuck 69k soalnya butuh supaya produktif, "
        "kerja ngantuk banget, abis nonton piala dunia kemarin malam."
    )
    parsed = {
        "keterangan": text,
        "nominal": 69000,
        "jenis": "Pengeluaran",
        "kategori": "Jajan",
        "sifat": "Wants",
        "mood": "Tired",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Need"


def test_reward_treat_stays_wants():
    text = "beli dessert reward diri habis lembur"
    parsed = {
        "keterangan": text,
        "nominal": 45000,
        "jenis": "Pengeluaran",
        "kategori": "Jajan",
        "sifat": "Need",
        "mood": "Happy",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"
