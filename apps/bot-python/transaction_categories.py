"""Daftar kategori & sub-kategori resmi YFD (sesuai dropdown Google Sheet)."""

from __future__ import annotations

import re
from typing import Any, Dict, Iterable

from category_rules_cache import (
    apply_admin_nature,
    fallback_kategori,
    fallback_sub,
    get_rules,
    kategori_sub_map,
    valid_kategori,
    valid_sub_kategori,
)

def _kategori_aliases() -> dict[str, str]:
    fb = fallback_kategori()
    return {
        "lain-lain": fb,
        "lain lain": fb,
        "other": fb,
        "umum": fb,
        "misc": fb,
        "belanja": fb,
        "beli": fb,
    }


def _sub_aliases() -> dict[str, str]:
    fb = fallback_sub()
    return {
        "lain-lain": fb,
        "lain lain": fb,
        "pengeluaran lain lain": fb,
        "other": fb,
        "misc": fb,
        "umum": fb,
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
    return fallback_kategori()


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
    return tuple(parent for parent, subs in kategori_sub_map().items() if sub in subs)


def _resolve_parent_for_sub(sub: str, text: str, current_kategori: str) -> str:
    parents = _parents_for_sub(sub)
    valid = valid_kategori()
    fb = fallback_kategori()
    if not parents:
        return current_kategori if current_kategori in valid else fb
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
    rules_data = get_rules()
    categories = rules_data.get("categories") or list(valid_kategori())
    subs = rules_data.get("sub_categories") or list(valid_sub_kategori())
    sub_map = rules_data.get("category_sub_map") or kategori_sub_map()
    fb_cat = rules_data.get("fallback_category") or fallback_kategori()
    fb_sub = rules_data.get("fallback_sub") or fallback_sub()

    mapping_lines = []
    for kategori, sub_list in sub_map.items():
        mapping_lines.append(f"   - {kategori}: {', '.join(sub_list)}")

    admin_hints = []
    for rule in rules_data.get("rules") or []:
        if not isinstance(rule, dict):
            continue
        cat = rule.get("category")
        if not cat or cat == "*":
            continue
        reason = (rule.get("reason") or "").strip()
        bucket = (rule.get("bucket") or "").strip()
        sub = rule.get("sub_category") or ""
        hint = f"   - {cat}"
        if sub:
            hint += f" / {sub}"
        if bucket:
            hint += f" → {bucket}"
        if reason:
            hint += f" ({reason})"
        admin_hints.append(hint)

    mapping_block = "\n".join(mapping_lines) if mapping_lines else "   (belum ada — admin Laravel)"
    hints_block = "\n".join(admin_hints[:40]) if admin_hints else ""
    policy_lines = rules_data.get("policy_notes") or []
    policy_block = "\n".join(f"   - {line}" for line in policy_lines)

    return f"""
Anda adalah parser keuangan pribadi.
Ubah input user menjadi JSON VALID dengan schema berikut:
{{
  "keterangan": string,
  "nominal": integer,
  "jenis": "Pemasukan" | "Pengeluaran",
  "kategori": {_enum_join(categories)},
  "sub_kategori": {_enum_join(subs)},
  "sifat": "Need" | "Wants" | "Saving/Investement" | "Donation",
  "mood": "Happy" | "Neutral" | "Sad" | "Stressed" | "Angry" | "Tired",
  "impulsif": "Yes" | "No"
}}

CATATAN (WAJIB — source of truth):
{policy_block}
   DILARANG membuat kategori atau sub_kategori baru di luar enum di atas.
   Jika transaksi tidak cocok dengan kategori manapun → gunakan kategori "{fb_cat}" dan sub_kategori "{fb_sub}".

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer bersih (contoh: 50rb => 50000, 1,2jt => 1200000).
3) jenis: pilih hanya Pemasukan atau Pengeluaran.
4) kategori & sub_kategori: WAJIB persis dari enum (huruf besar/kecil sama). Jangan pernah mengarang label baru.
   Pasangan yang benar:
{mapping_block}
   Petunjuk admin (bucket):
{hints_block}
   Jika tidak yakin kategori/sub → kategori {fb_cat}, sub_kategori "{fb_sub}" (bukan kategori baru).
5) impulsif — WAJIB pertimbangkan konteks & niat, bukan hanya nominal besar:
   "Yes" jika spontan / fomo / euforia gajian / mood negatif + wants.
   "No" jika terencana, tagihan wajib, atau perayaan keluarga.
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
    fb = fallback_sub()
    if fb in allowed:
        return fb
    return allowed[0]


def _allowed_subs_for_kategori(kategori: str) -> tuple[str, ...]:
    subs = kategori_sub_map().get(kategori)
    if subs:
        return subs
    return (fallback_sub(),)


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan kategori/sub_kategori dengan aturan admin Laravel."""
    kategori = _normalize_alias(str(parsed.get("kategori", "")), _kategori_aliases())
    sub = _normalize_alias(str(parsed.get("sub_kategori", "")), _sub_aliases())
    combined = f"{parsed.get('keterangan', '')} {source_text}".strip().lower()
    valid_cats = valid_kategori()
    valid_subs = valid_sub_kategori()
    fb_cat = fallback_kategori()
    fb_sub = fallback_sub()

    strong_kategori = _kategori_from_strong_signals(combined)
    if strong_kategori is not None:
        kategori = strong_kategori
    elif kategori not in valid_cats:
        kategori = fb_cat
        sub = fb_sub
    elif kategori == "Air" and not is_water_expense(combined):
        kategori = _infer_kategori_from_text(combined)

    allowed = _allowed_subs_for_kategori(kategori)

    if sub not in valid_subs:
        detected = _detect_sub_from_text(combined)
        if detected in allowed:
            sub = detected
        elif sub in valid_subs:
            kategori = _resolve_parent_for_sub(sub, combined, kategori)
            allowed = _allowed_subs_for_kategori(kategori)
        else:
            sub = _fallback_sub_for_kategori(kategori)
    elif sub not in allowed:
        detected = _detect_sub_from_text(combined)
        if detected in allowed:
            sub = detected
        elif sub in valid_subs:
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
    return apply_admin_nature(parsed)
