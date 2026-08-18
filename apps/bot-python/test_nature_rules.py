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
        "kategori": "Makanan & Minuman",
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
        "kategori": "Makanan & Minuman",
        "sifat": "Need",
        "mood": "Happy",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"


def test_jajan_brownies_is_wants_not_need():
    text = "28/07 beli brownies karena cape pengen jajan 13000"
    parsed = {
        "keterangan": "Beli brownies",
        "nominal": 13000,
        "jenis": "Pengeluaran",
        "kategori": "Makanan & Minuman",
        "sifat": "Need",
        "mood": "Tired",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"


def test_grab_ke_gym_stays_wants():
    text = "Grab dari kos ke gym Imam Bonjol 21000"
    parsed = {
        "keterangan": "Grab dari kos ke gym Imam Bonjol",
        "nominal": 21000,
        "jenis": "Pengeluaran",
        "kategori": "Transportasi",
        "sifat": "Wants",
        "mood": "Neutral",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"


def test_networking_bisnis_transport_is_need():
    text = "Grab dari kos ke aktivitas networking bisnis di Nusa Dua 45100"
    parsed = {
        "keterangan": text,
        "nominal": 45100,
        "jenis": "Pengeluaran",
        "kategori": "Transportasi",
        "sifat": "Wants",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Need"


def test_makeup_stays_wants_not_forced_need_by_kesehatan():
    text = "beli makeup cushion maybeline 185 rb"
    parsed = {
        "keterangan": "Makeup cushion Maybelline",
        "nominal": 185000,
        "jenis": "Pengeluaran",
        "kategori": "Kesehatan & Kebersihan Diri",
        "sifat": "Wants",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"


def test_tumbler_ganti_rusak_is_need():
    text = "beli tumbler ganti yang sebelumnya rusak 150rb"
    parsed = {
        "keterangan": "Beli tumbler",
        "nominal": 150000,
        "jenis": "Pengeluaran",
        "kategori": "Tempat Tinggal",
        "sifat": "Wants",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Need"


def test_gym_income_tool_is_need():
    text = "membership gym, saya personal trainer"
    parsed = {
        "keterangan": "Membership gym",
        "nominal": 450000,
        "jenis": "Pengeluaran",
        "kategori": "Lifestyle & Hiburan",
        "sifat": "Wants",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Need"


def test_grab_subscription_stays_wants():
    text = "bayar subscription grab untuk dapat paket hemat 14000"
    parsed = {
        "keterangan": "Subscription Grab",
        "nominal": 14000,
        "jenis": "Pengeluaran",
        "kategori": "Transportasi",
        "sifat": "Wants",
    }
    refine_sifat_from_context(parsed, text)
    assert parsed["sifat"] == "Wants"
