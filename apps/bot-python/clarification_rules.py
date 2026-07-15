"""Deteksi transaksi yang perlu ditanya balik sebelum diklasifikasikan."""

from __future__ import annotations

from typing import Any


def clarification_question(parsed: dict[str, Any], source_text: str) -> str | None:
    """Return a concise follow-up question when purpose changes the YFD bucket."""
    ai_question = str(parsed.get("clarification_question") or "").strip()
    if parsed.get("needs_clarification") is True and ai_question:
        return ai_question

    text = f"{source_text} {parsed.get('keterangan', '')}".lower()
    category = str(parsed.get("kategori") or "").strip().lower()

    if _is_generic_book(text, category):
        return (
            "Buku ini untuk pengembangan diri/belajar, kebutuhan wajib sekolah, "
            "atau hiburan seperti novel/komik?"
        )

    if _is_generic_instrument(text):
        return (
            "Alat musik ini untuk belajar/pengembangan diri, kebutuhan profesi, "
            "kebutuhan wajib sekolah, atau hobi/hiburan?"
        )

    if _is_generic_coffee(text, category):
        return "Kopi/ngopi ini untuk kebutuhan kerja/meeting atau untuk santai/healing?"

    if _is_generic_electronic(text, category):
        return (
            "Perangkat ini untuk kerja, mengganti perangkat utama yang rusak, "
            "atau upgrade pribadi?"
        )

    return None


def _is_generic_book(text: str, category: str) -> bool:
    if category != "buku" and "beli buku" not in text:
        return False
    purpose_markers = (
        "pengembangan diri",
        "self development",
        "sekolah",
        "kuliah",
        "buku wajib",
        "belajar",
        "bisnis",
        "keuangan",
        "psychology of money",
        "novel",
        "komik",
        "hiburan",
        "klarifikasi user:",
    )
    return not any(marker in text for marker in purpose_markers)


def _is_generic_instrument(text: str) -> bool:
    instruments = (
        "piano",
        "gitar",
        "biola",
        "violin",
        "drum",
        "saxophone",
        "saksofon",
        "ukulele",
        "cello",
    )
    if not any(instrument in text for instrument in instruments):
        return False
    purpose_markers = (
        "belajar",
        "pengembangan diri",
        "self development",
        "profesi",
        "kerja",
        "manggung",
        "studio",
        "sekolah",
        "kuliah",
        "wajib",
        "hobi",
        "hiburan",
        "koleksi",
        "klarifikasi user:",
    )
    return not any(marker in text for marker in purpose_markers)


def _is_generic_coffee(text: str, category: str) -> bool:
    if category != "jajan" or not any(word in text for word in ("kopi", "coffee", "ngopi")):
        return False
    purpose_markers = (
        "kerja",
        "meeting",
        "rapat",
        "klien",
        "santai",
        "healing",
        "nongkrong",
        "hiburan",
        "klarifikasi user:",
    )
    return not any(marker in text for marker in purpose_markers)


def _is_generic_electronic(text: str, category: str) -> bool:
    if category != "elektronik":
        return False
    if not any(word in text for word in ("laptop", "komputer", "hp", "handphone", "iphone")):
        return False
    purpose_markers = (
        "kerja",
        "kantor",
        "produktif",
        "rusak",
        "pecah",
        "mati",
        "fomo",
        "upgrade",
        "pribadi",
        "klarifikasi user:",
    )
    return not any(marker in text for marker in purpose_markers)
