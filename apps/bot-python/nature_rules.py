"""Aturan Need vs Wants — kontekstual, bukan sekadar label kategori."""

from __future__ import annotations

from typing import Any

from context_rules import is_discretionary_social_giving

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
    "netflix",
    "spotify",
    "hiburan",
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

    if has_productivity_framing(combined) and (
        has_functional_food_context(combined)
        or str(parsed.get("kategori", "")) in {"Jajan", "Makan", "Minuman", "Elektronik", "Subscription"}
    ):
        parsed["sifat"] = "Need"
        return parsed

    essential_cats = {"Listrik", "Air", "Asuransi", "Transport", "Gaji", "Makan", "Kesehatan", "Komunikasi"}
    if str(parsed.get("kategori", "")) in essential_cats:
        parsed["sifat"] = "Need"

    if parsed.get("sifat") not in VALID_SIFAT:
        parsed["sifat"] = "Need"

    return parsed
