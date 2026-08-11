"""Aturan Need vs Wants — kontekstual, bukan sekadar label kategori."""

from __future__ import annotations

from typing import Any

from context_rules import (
    household_durable_sifat,
    is_beauty_care_expense,
    is_business_transport_destination,
    is_discretionary_social_giving,
    is_dp_lifestyle_vehicle,
    is_household_durable,
    is_leisure_transport_destination,
    is_ride_subscription,
)

VALID_SIFAT = frozenset({"Need", "Wants"})

PRODUCTIVITY_NEED_KEYWORDS = (
    "produktif",
    "produktivitas",
    "biar fokus",
    "supaya fokus",
    "agar fokus",
    "biar bisa kerja",
    "supaya bisa kerja",
    "butuh supaya",
    "biar kerja",
    "untuk kerja",
    "kebutuhan kerja",
    "saat kerja",
    "di kantor",
    "meeting",
    "rapat",
    "konsentrasi",
    "fokus kerja",
    "ngantuk kerja",
    "kerja ngantuk",
    "ngantuk banget",
    "gak bisa kerja",
    "ga bisa kerja",
    "tidak bisa kerja",
    "biar melek",
    "supaya melek",
    "biar bangun",
    "tetap terjaga",
)

FUNCTIONAL_FOOD_KEYWORDS = (
    "kopi",
    "coffee",
    "starbucks",
    "espresso",
    "americano",
    "caffeine",
    "kafein",
    "sarapan",
    "makan siang",
    "makan malam",
    "nasi",
    "makan",
)

DISCRETIONARY_WANTS_KEYWORDS = (
    "reward",
    "nge treat",
    "self treat",
    "celebrate",
    "iseng",
    "shopping",
    "belanja baju",
    "skincare",
    "makeup",
    "make up",
    "dandan",
    "sunscreen",
    "lipstik",
    "lipstick",
    "cushion",
    "netflix",
    "spotify",
    "hiburan",
    "jajan",
    "ngemil",
    "snack",
    "cemilan",
    "brownies",
    "dessert",
    "boba",
    "pengen",
    "kepengen",
    "healing",
    "santai",
)


def _combined_text(parsed: dict[str, Any], source_text: str) -> str:
    return f"{parsed.get('keterangan', '')} {source_text}".lower()


def has_productivity_framing(combined: str) -> bool:
    return any(keyword in combined for keyword in PRODUCTIVITY_NEED_KEYWORDS)


def has_functional_food_context(combined: str) -> bool:
    return any(keyword in combined for keyword in FUNCTIONAL_FOOD_KEYWORDS)


def has_discretionary_framing(combined: str) -> bool:
    return any(keyword in combined for keyword in DISCRETIONARY_WANTS_KEYWORDS)


def refine_sifat_from_context(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    """
    Selaraskan Need/Wants dari niat user — hindari Wants otomatis untuk jajan premium
    bila user menyatakan fungsi kerja/produktivitas.
    """
    if parsed.get("jenis") != "Pengeluaran":
        return parsed

    combined = _combined_text(parsed, source_text)

    if is_discretionary_social_giving(combined):
        parsed["sifat"] = "Wants"
        return parsed

    if has_discretionary_framing(combined) and not has_productivity_framing(combined):
        parsed["sifat"] = "Wants"
        return parsed

    # Meeting coffee / produktivitas → Need (closed kategori Makanan atau Bisnis).
    if has_productivity_framing(combined) and (
        has_functional_food_context(combined)
        or str(parsed.get("kategori", "")) in {
            "Makanan & Minuman",
            "Bisnis & Karir",
            "Lifestyle & Hiburan",
            # Legacy aliases (secondary)
            "Jajan",
            "Makan",
            "Minuman",
            "Elektronik",
            "Subscription",
        }
    ):
        parsed["sifat"] = "Need"
        return parsed

    # Closed-list essential categories (primary).
    essential_cats = {
        "Tempat Tinggal",
        "Transportasi",
        "Komunikasi",
        "Kesehatan & Kebersihan Diri",
        "Makanan & Minuman",
        "Cicilan & Hutang",
        "Proteksi",
        # Legacy aliases (secondary)
        "Listrik",
        "Air",
        "Asuransi",
        "Transport",
        "Gaji",
        "Makan",
        "Kesehatan",
    }
    kategori = str(parsed.get("kategori", ""))
    if is_beauty_care_expense(combined):
        parsed["sifat"] = "Wants"
        return parsed
    if is_household_durable(combined):
        parsed["sifat"] = household_durable_sifat(combined)
        return parsed
    if is_dp_lifestyle_vehicle(combined):
        parsed["sifat"] = "Wants"
        return parsed
    if any(
        k in combined
        for k in ("seragam", "sepatu kerja", "tas kerja", "tas sekolah", "sepatu sekolah")
    ):
        parsed["sifat"] = "Need"
        return parsed
    if kategori in essential_cats:
        if kategori in {"Makan", "Makanan & Minuman", "Minuman"} and has_discretionary_framing(
            combined
        ):
            parsed["sifat"] = "Wants"
        elif kategori in {"Transport", "Transportasi"}:
            if is_ride_subscription(combined):
                parsed["sifat"] = "Wants"
            elif is_leisure_transport_destination(combined):
                parsed["sifat"] = "Wants"
            elif is_business_transport_destination(combined):
                parsed["sifat"] = "Need"
            else:
                parsed["sifat"] = "Need"
        else:
            parsed["sifat"] = "Need"

    # Traveling / Lifestyle / Sosial / Hadiah default Wants unless already set Need.
    wants_cats = {
        "Traveling",
        "Lifestyle & Hiburan",
        "Sosial & Keluarga",
        "Hadiah",
        "Pakaian & Aksesoris",
    }
    if kategori in wants_cats and parsed.get("sifat") not in VALID_SIFAT:
        parsed["sifat"] = "Wants"

    if parsed.get("sifat") not in VALID_SIFAT:
        parsed["sifat"] = "Need"

    return parsed
