"""Daftar kategori & sub-kategori resmi YFD (sesuai dropdown Google Sheet)."""

from __future__ import annotations

import re
from typing import Any, Dict, Iterable

VALID_KATEGORI: tuple[str, ...] = (
    "Makan",
    "Transport",
    "Listrik",
    "Air",
    "Jajan",
    "Social",
    "Gaji",
)

VALID_SUB_KATEGORI: tuple[str, ...] = (
    # Gambar 2
    "Listrik",
    "Pakaian",
    "Servis Kendaraan",
    "Nonton Konser",
    "Pengeluaran lain-lain",
    "Hadiah / Amplop sosial",
    "Popok",
    "Jajan / Makan diluar",
    # Gambar 3
    "Angkutan Umum",
    "Skincare",
    "Mainan Anak",
    "Ulang Tahun keluarga",
    "Vitamin",
    "Alat Kesehatan",
)

# Sub kategori yang boleh per kategori induk (dropdown Sheet).
KATEGORI_SUB_MAP: dict[str, tuple[str, ...]] = {
    "Makan": ("Jajan / Makan diluar",),
    "Jajan": (
        "Jajan / Makan diluar",
        "Skincare",
        "Pakaian",
        "Popok",
        "Mainan Anak",
        "Vitamin",
        "Alat Kesehatan",
        "Pengeluaran lain-lain",
    ),
    "Transport": ("Angkutan Umum", "Servis Kendaraan"),
    "Listrik": ("Listrik",),
    "Air": ("Pengeluaran lain-lain",),
    "Social": (
        "Hadiah / Amplop sosial",
        "Nonton Konser",
        "Ulang Tahun keluarga",
    ),
    "Gaji": ("Pengeluaran lain-lain",),
}

DEFAULT_FALLBACK_KATEGORI = "Jajan"
DEFAULT_FALLBACK_SUB = "Pengeluaran lain-lain"

_KATEGORI_ALIASES: dict[str, str] = {
    "lain-lain": DEFAULT_FALLBACK_KATEGORI,
    "lain lain": DEFAULT_FALLBACK_KATEGORI,
    "other": DEFAULT_FALLBACK_KATEGORI,
    "umum": DEFAULT_FALLBACK_KATEGORI,
    "misc": DEFAULT_FALLBACK_KATEGORI,
    "belanja": DEFAULT_FALLBACK_KATEGORI,
    "beli": DEFAULT_FALLBACK_KATEGORI,
}

_SUB_ALIASES: dict[str, str] = {
    "lain-lain": DEFAULT_FALLBACK_SUB,
    "lain lain": DEFAULT_FALLBACK_SUB,
    "pengeluaran lain lain": DEFAULT_FALLBACK_SUB,
    "other": DEFAULT_FALLBACK_SUB,
    "misc": DEFAULT_FALLBACK_SUB,
    "umum": DEFAULT_FALLBACK_SUB,
}

_WATER_PATTERNS: tuple[re.Pattern[str], ...] = tuple(
    re.compile(pattern, re.IGNORECASE)
    for pattern in (
        r"\bpdam\b",
        r"\btagihan\s+air\b",
        r"\bbayar\s+air\b",
        r"\brekening\s+air\b",
        r"\bbiaya\s+air\b",
        r"\bair\s+pdam\b",
    )
)

_ELECTRONICS_PATTERNS: tuple[re.Pattern[str], ...] = tuple(
    re.compile(pattern, re.IGNORECASE)
    for pattern in (
        r"\bhp\b",
        r"\bhandphone\b",
        r"\bsmartphone\b",
        r"\blaptop\b",
        r"\btablet\b",
        r"\bipad\b",
        r"\biphone\b",
        r"\bandroid\b",
        r"\bgadget\b",
        r"\belektronik\b",
        r"\btv\b",
        r"\btelevisi\b",
        r"\bkamera\b",
        r"\bcharger\b",
        r"\bearphone\b",
        r"\bheadset\b",
    )
)

_SUB_KEYWORDS: tuple[tuple[tuple[str, ...], str], ...] = (
    (("ojek", "grab", "gojek", "angkot", "transjakarta", "bus", "mrt", "kereta", "tol", "parkir", "bensin"), "Angkutan Umum"),
    (("servis", "bengkel", "oli", "ban"), "Servis Kendaraan"),
    (("listrik", "pln"), "Listrik"),
    (("tagihan air", "bayar air", "rekening air", "biaya air", "pdam"), "Pengeluaran lain-lain"),
    (("restaurant", "restoran", "makan", "nasi", "sarapan", "lunch", "dinner", "warteg"), "Jajan / Makan diluar"),
    (("kopi", "coffee", "jajan", "snack", "starbucks"), "Jajan / Makan diluar"),
    (("skincare", "skincar"), "Skincare"),
    (("baju", "pakaian", "celana"), "Pakaian"),
    (("popok", "diaper"), "Popok"),
    (("mainan",), "Mainan Anak"),
    (("vitamin", "suplemen"), "Vitamin"),
    (("alat kesehatan", "masker", "termometer"), "Alat Kesehatan"),
    (("konser", "tiket", "bioskop", "nonton"), "Nonton Konser"),
    (("hadiah", "amplop", "ultah", "ulang tahun"), "Hadiah / Amplop sosial"),
    (("gaji", "bonus", "honor", "income"), "Pengeluaran lain-lain"),
    (("hp", "handphone", "smartphone", "laptop", "tablet", "gadget", "elektronik"), "Pengeluaran lain-lain"),
)


def _enum_join(values: Iterable[str]) -> str:
    return " | ".join(f'"{v}"' for v in values)


def _matches_any_pattern(text: str, patterns: Iterable[re.Pattern[str]]) -> bool:
    return any(pattern.search(text) for pattern in patterns)


def is_water_expense(text: str) -> bool:
    return _matches_any_pattern(text, _WATER_PATTERNS)


def is_electronics_expense(text: str) -> bool:
    return _matches_any_pattern(text, _ELECTRONICS_PATTERNS)


def _contains_phrase(text: str, phrases: tuple[str, ...]) -> bool:
    lower = text.lower()
    return any(phrase in lower for phrase in phrases)


def _infer_kategori_from_text(text: str) -> str:
    if _contains_phrase(text, ("gaji", "bonus", "honor", "income")):
        return "Gaji"
    if _contains_phrase(text, ("ojek", "grab", "gojek", "transport", "bensin", "tol", "parkir", "angkot")):
        return "Transport"
    if _contains_phrase(text, ("listrik", "pln")):
        return "Listrik"
    if is_water_expense(text):
        return "Air"
    if _contains_phrase(text, ("hadiah", "amplop", "konser", "ultah", "ulang tahun")):
        return "Social"
    if _contains_phrase(text, ("makan", "restaurant", "restoran", "nasi", "sarapan", "lunch", "dinner")):
        return "Makan"
    if is_electronics_expense(text):
        return "Jajan"
    return DEFAULT_FALLBACK_KATEGORI


def _kategori_from_strong_signals(text: str) -> str | None:
    """Kategori dengan sinyal kata kunci tegas; None jika tidak ada."""
    if is_water_expense(text):
        return "Air"
    if _contains_phrase(text, ("gaji", "bonus", "honor", "income")):
        return "Gaji"
    if _contains_phrase(text, ("listrik", "pln")):
        return "Listrik"
    if _contains_phrase(text, ("ojek", "grab", "gojek", "transport", "bensin", "tol", "parkir", "angkot")):
        return "Transport"
    if _contains_phrase(text, ("hadiah", "amplop", "konser", "ultah", "ulang tahun")):
        return "Social"
    if _contains_phrase(text, ("makan", "restaurant", "restoran", "nasi", "sarapan", "lunch", "dinner")):
        return "Makan"
    if is_electronics_expense(text):
        return "Jajan"
    return None


def _parents_for_sub(sub: str) -> tuple[str, ...]:
    return tuple(parent for parent, subs in KATEGORI_SUB_MAP.items() if sub in subs)


def _resolve_parent_for_sub(sub: str, text: str, current_kategori: str) -> str:
    parents = _parents_for_sub(sub)
    if not parents:
        return current_kategori if current_kategori in VALID_KATEGORI else "Jajan"
    if current_kategori in parents:
        return current_kategori

    lower = text.lower()
    if sub == "Pengeluaran lain-lain":
        if _contains_phrase(lower, ("gaji", "bonus", "honor", "income")) and "Gaji" in parents:
            return "Gaji"
        if is_water_expense(lower) and "Air" in parents:
            return "Air"
        return "Jajan" if "Jajan" in parents else parents[0]

    inferred = _infer_kategori_from_text(lower)
    if inferred in parents:
        return inferred
    return parents[0]


def build_system_prompt_rules() -> str:
    mapping_lines = []
    for kategori, subs in KATEGORI_SUB_MAP.items():
        mapping_lines.append(f"   - {kategori}: {', '.join(subs)}")
    mapping_block = "\n".join(mapping_lines)

    return f"""
Anda adalah parser keuangan pribadi.
Ubah input user menjadi JSON VALID dengan schema berikut:
{{
  "keterangan": string,
  "nominal": integer,
  "jenis": "Pemasukan" | "Pengeluaran",
  "kategori": {_enum_join(VALID_KATEGORI)},
  "sub_kategori": {_enum_join(VALID_SUB_KATEGORI)},
  "sifat": "Need" | "Wants" | "Saving/Investement" | "Donation",
  "mood": "Happy" | "Neutral" | "Sad" | "Stressed" | "Angry" | "Tired",
  "impulsif": "Yes" | "No"
}}

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer bersih (contoh: 50rb => 50000, 1,2jt => 1200000).
3) jenis: pilih hanya Pemasukan atau Pengeluaran.
4) kategori & sub_kategori: WAJIB persis dari enum (huruf besar/kecil sama).
   Pasangan yang benar:
{mapping_block}
   Contoh: makan/restoran → kategori Makan, sub_kategori "Jajan / Makan diluar".
   Contoh: ojek/transport → kategori Transport, sub_kategori "Angkutan Umum".
   Contoh: beli hp/laptop/gadget → kategori Jajan, sub_kategori "Pengeluaran lain-lain".
   Contoh: tagihan air/pdam → kategori Air, sub_kategori "Pengeluaran lain-lain".
   Jika tidak yakin kategori/sub → kategori Jajan, sub_kategori "Pengeluaran lain-lain".
5) impulsif: "Yes" jika pembelian spontan (iseng, kepengen, diskon, tiba-tiba) ATAU
   perilaku belanja premium saat mood negatif (Sad/Stressed/Angry/Tired).
   Bisa tetap "Need" untuk sifat, tetapi impulsif "Yes" bila ada alternatif lebih murah.
6) Balas HANYA JSON murni, tanpa markdown dan tanpa teks tambahan.
7) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {{"error":"invalid_input"}}
"""


def _detect_sub_from_text(text: str) -> str | None:
    lower = text.lower()
    for keywords, sub in _SUB_KEYWORDS:
        if any(keyword in lower for keyword in keywords):
            return sub
    return None


def _normalize_alias(value: str, aliases: dict[str, str]) -> str:
    key = value.strip().lower()
    return aliases.get(key, value.strip())


def _fallback_sub_for_kategori(kategori: str) -> str:
    allowed = _allowed_subs_for_kategori(kategori)
    if DEFAULT_FALLBACK_SUB in allowed:
        return DEFAULT_FALLBACK_SUB
    return allowed[0]


def _allowed_subs_for_kategori(kategori: str) -> tuple[str, ...]:
    return KATEGORI_SUB_MAP.get(kategori, (DEFAULT_FALLBACK_SUB,))


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan kategori/sub_kategori dengan dropdown Sheet YFD."""
    kategori = _normalize_alias(str(parsed.get("kategori", "")), _KATEGORI_ALIASES)
    sub = _normalize_alias(str(parsed.get("sub_kategori", "")), _SUB_ALIASES)
    combined = f"{parsed.get('keterangan', '')} {source_text}".strip().lower()

    strong_kategori = _kategori_from_strong_signals(combined)
    if strong_kategori is not None:
        kategori = strong_kategori
    elif kategori not in VALID_KATEGORI:
        kategori = DEFAULT_FALLBACK_KATEGORI
        sub = DEFAULT_FALLBACK_SUB
    elif kategori == "Air" and not is_water_expense(combined):
        kategori = _infer_kategori_from_text(combined)

    allowed = _allowed_subs_for_kategori(kategori)

    if sub not in VALID_SUB_KATEGORI:
        detected = _detect_sub_from_text(combined)
        if detected in allowed:
            sub = detected
        elif sub in VALID_SUB_KATEGORI:
            kategori = _resolve_parent_for_sub(sub, combined, kategori)
            allowed = _allowed_subs_for_kategori(kategori)
        else:
            sub = _fallback_sub_for_kategori(kategori)
    elif sub not in allowed:
        detected = _detect_sub_from_text(combined)
        if detected in allowed:
            sub = detected
        elif sub in VALID_SUB_KATEGORI:
            kategori = _resolve_parent_for_sub(sub, combined, kategori)
            allowed = _allowed_subs_for_kategori(kategori)
        else:
            sub = _fallback_sub_for_kategori(kategori)
    elif sub == "Pengeluaran lain-lain" and kategori not in _parents_for_sub(sub):
        kategori = _resolve_parent_for_sub(sub, combined, kategori)
        allowed = _allowed_subs_for_kategori(kategori)

    if sub not in allowed:
        detected = _detect_sub_from_text(combined)
        sub = detected if detected in allowed else _fallback_sub_for_kategori(kategori)

    parsed["kategori"] = kategori
    parsed["sub_kategori"] = sub
    return parsed
