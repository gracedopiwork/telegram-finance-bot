"""Daftar kategori resmi YFD — sub-kategori tidak dipakai lagi."""

from __future__ import annotations

import re
from typing import Any, Dict, Iterable

from category_rules_cache import (
    apply_admin_nature,
    fallback_kategori,
    get_rules,
    valid_kategori,
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


def _enum_join(values: Iterable[str]) -> str:
    return " | ".join(f'"{v}"' for v in values)


def _matches_any_pattern(text: str, patterns: Iterable[re.Pattern[str]]) -> bool:
    return any(pattern.search(text) for pattern in patterns)


def is_water_expense(text: str) -> bool:
    return _matches_any_pattern(text, _WATER_PATTERNS)


def is_electronics_expense(text: str) -> bool:
    return _matches_any_pattern(text, _ELECTRONICS_PATTERNS)


_SAVING_LABEL_KEYWORDS: tuple[tuple[str, str], ...] = (
    ("reksadana", "Reksadana"),
    ("reksa dana", "Reksadana"),
    ("saham", "Saham"),
    ("obligasi", "Obligasi"),
    ("emas", "Emas"),
    ("deposito", "Deposito"),
    ("crypto", "Crypto"),
    ("bitcoin", "Crypto"),
    ("dana darurat", "Dana darurat"),
    ("nabung", "Tabungan"),
    ("tabungan", "Tabungan"),
    ("investasi", "Investasi"),
    ("avg down", "Saham"),
    ("sbn", "Obligasi"),
)


def infer_saving_label(text: str) -> str | None:
    lower = text.lower()
    for keyword, label in _SAVING_LABEL_KEYWORDS:
        if keyword in lower:
            return label
    return None


def normalize_saving_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Untuk Saving/Investment: label investasi di sub_kategori + keterangan yang jelas."""
    if str(parsed.get("jenis", "")).strip() != "Saving/Investment":
        return parsed

    combined = f"{parsed.get('keterangan', '')} {source_text}".strip()
    label = infer_saving_label(combined) or "Tabungan/Investasi"
    parsed["sub_kategori"] = label

    keterangan = str(parsed.get("keterangan", "")).strip()
    if keterangan == "" or label.lower() not in keterangan.lower():
        if keterangan:
            parsed["keterangan"] = f"{label}: {keterangan}"
        else:
            parsed["keterangan"] = f"Investasi {label}"

    parsed["kategori"] = fallback_kategori()
    parsed["sifat"] = "Need"
    return parsed


def _contains_phrase(text: str, phrases: tuple[str, ...]) -> bool:
    lower = text.lower()
    return any(phrase in lower for phrase in phrases)


def _title_category(value: str) -> str:
    cleaned = re.sub(r"\s+", " ", value.strip())
    if cleaned == "":
        return ""
    return " ".join(word.capitalize() for word in cleaned.split(" "))


def _infer_kategori_from_text(text: str) -> str:
    if _contains_phrase(text, ("gaji", "bonus", "honor", "income", "dividen", "dividend")):
        return "Gaji" if _contains_phrase(text, ("gaji", "bonus", "honor", "income")) else "Dividen"
    if _contains_phrase(text, ("ojek", "grab", "gojek", "transport", "bensin", "tol", "parkir", "angkot")):
        return "Transport"
    if _contains_phrase(text, ("listrik", "pln")):
        return "Listrik"
    if is_water_expense(text):
        return "Air"
    if _contains_phrase(text, ("hadiah", "amplop", "konser", "ultah", "ulang tahun", "sedekah", "persembahan", "ibadah", "donasi")):
        return "Social"
    if _contains_phrase(text, ("asuransi", "premi", "bpjs")):
        return "Asuransi"
    if _contains_phrase(text, ("skincare", "skin care", "serum", "masker")):
        return "Skincare"
    if _contains_phrase(text, ("subscription", "langganan", "netflix", "spotify", "streaming")):
        return "Subscription"
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
    if _contains_phrase(text, ("dividen", "dividend")) and not _contains_phrase(text, ("reinvest", "re-invest")):
        return "Dividen"
    if _contains_phrase(text, ("listrik", "pln")):
        return "Listrik"
    if _contains_phrase(text, ("ojek", "grab", "gojek", "transport", "bensin", "tol", "parkir", "angkot")):
        return "Transport"
    if _contains_phrase(text, ("hadiah", "amplop", "konser", "ultah", "ulang tahun", "sedekah", "persembahan", "ibadah", "donasi")):
        return "Social"
    if _contains_phrase(text, ("asuransi", "premi", "bpjs")):
        return "Asuransi"
    if _contains_phrase(text, ("skincare", "skin care", "serum", "masker")):
        return "Skincare"
    if _contains_phrase(text, ("subscription", "langganan", "netflix", "spotify", "streaming")):
        return "Subscription"
    if _contains_phrase(text, ("makan", "restaurant", "restoran", "nasi", "sarapan", "lunch", "dinner")):
        return "Makan"
    if is_electronics_expense(text):
        return "Jajan"
    return None


def build_system_prompt_rules() -> str:
    rules_data = get_rules()
    categories = rules_data.get("categories") or list(valid_kategori())
    fb_cat = rules_data.get("fallback_category") or fallback_kategori()

    admin_hints = []
    for rule in rules_data.get("rules") or []:
        if not isinstance(rule, dict):
            continue
        cat = rule.get("category")
        if not cat or cat == "*":
            continue
        reason = (rule.get("reason") or "").strip()
        bucket = (rule.get("bucket") or "").strip()
        hint = f"   - {cat}"
        if bucket:
            hint += f" → {bucket}"
        if reason:
            hint += f" ({reason})"
        admin_hints.append(hint)

    hints_block = "\n".join(admin_hints[:40]) if admin_hints else ""
    policy_lines = rules_data.get("policy_notes") or []
    policy_block = "\n".join(f"   - {line}" for line in policy_lines)
    known_cats = ", ".join(categories[:20])
    if len(categories) > 20:
        known_cats += ", ..."

    return f"""
Anda adalah parser keuangan pribadi.
Ubah input user menjadi JSON VALID dengan schema berikut:
{{
  "keterangan": string,
  "nominal": integer,
  "jenis": "Pemasukan" | "Pengeluaran" | "Saving/Investment",
  "kategori": string,
  "sifat": "Need" | "Wants",
  "mood": "Happy" | "Neutral" | "Sad" | "Stressed" | "Angry" | "Tired",
  "impulsif": "Yes" | "No"
}}

CATATAN:
{policy_block}
   Kategori boleh label deskriptif apa pun (contoh: Skincare, Asuransi, Subscription, Dividen).
   Kategori yang sudah umum: {known_cats}
   Jika tidak yakin → {fb_cat}.

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer rupiah penuh.
   - 50rb / 50 ribu / 50k => 50000 (suffix rb/ribu/k WAJIB menempel pada angka).
   - 1,2jt / 1.2 juta => 1200000.
   - 83800 / 83.800 / 83,800 tanpa suffix => 83800 (BUKAN juta).
   - Huruf k di kata biasa (kemarin, snack, stock) BUKAN penanda ribuan.
3) jenis: Pemasukan | Pengeluaran | Saving/Investment.
   - Pemasukan: gaji, bonus, honor, dividen yang dicairkan.
   - Saving/Investment untuk nabung, saham, reksadana, deposito, avg down, dividen reinvest — BUKAN pengeluaran hidup.
   - Donasi/sedekah/persembahan/ibadah = Pengeluaran + kategori Social.
4) sifat: HANYA Need atau Wants — dengarkan NIAT & FUNGSI user, bukan sekadar merek/harga/kategori.
   - Need: kebutuhan hidup atau kerja yang user jelaskan fungsional (mis. kopi supaya produktif/fokus kerja, transport ke kantor, obat, tagihan).
   - Wants: belanja diskresioner, reward diri, hiburan, atau jajan tanpa kebutuhan fungsional yang user nyatakan.
   - JANGAN otomatis Wants hanya karena premium/jajan/starbucks — jika user bilang \"butuh supaya produktif\", \"biar bisa kerja\", \"ngantuk kerja\" → pertimbangkan Need.
   - Skincare, subscription hiburan, belanja iseng → biasanya Wants.
5) kategori: pilih label paling sesuai dari input (Skincare, Asuransi, Subscription, Makan, Transport, dll).
   Petunjuk bucket (referensi):
{hints_block}
6) Konteks bucket (contoh):
   - Premi asuransi / BPJS → kategori Asuransi, sifat Need
   - Skincare / skin care → kategori Skincare, sifat Wants
   - Netflix / langganan → kategori Subscription, sifat Wants
   - Dividen dicairkan → jenis Pemasukan, kategori Dividen
7) impulsif — terpisah dari sifat Need/Wants:
   "Yes" jika spontan / fomo / mood negatif + belanja diskresioner, atau premium di luar kebutuhan terencana.
   "No" jika terencana, tagihan wajib, perayaan keluarga, atau kebutuhan kerja yang user jelaskan meski mood lelah.
   Boleh impulsif=Yes sambil sifat=Need (contoh: kopi premium mendadak saat ngantuk kerja).
8) Balas HANYA JSON murni, tanpa markdown.
9) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {{"error":"invalid_input"}}
"""


def _normalize_alias(value: str, aliases: dict[str, str]) -> str:
    key = value.strip().lower()
    return aliases.get(key, value.strip())


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan kategori dengan aturan admin Laravel."""
    if str(parsed.get("jenis", "")).strip() == "Saving/Investment":
        parsed["kategori"] = fallback_kategori()
        parsed.pop("sub_kategori", None)
        return apply_admin_nature(parsed)

    kategori = _normalize_alias(str(parsed.get("kategori", "")), _kategori_aliases())
    combined = f"{parsed.get('keterangan', '')} {source_text}".strip().lower()
    valid_cats = valid_kategori()
    fb_cat = fallback_kategori()

    strong_kategori = _kategori_from_strong_signals(combined)
    if strong_kategori is not None:
        kategori = strong_kategori
    elif kategori not in valid_cats:
        inferred = _infer_kategori_from_text(combined)
        if inferred in valid_cats:
            kategori = inferred
        elif kategori.strip():
            kategori = _title_category(kategori)
        else:
            kategori = inferred if inferred else fb_cat
    elif kategori == "Air" and not is_water_expense(combined):
        kategori = _infer_kategori_from_text(combined)
        if kategori not in valid_cats and kategori != fb_cat:
            kategori = _title_category(kategori) if str(parsed.get("kategori", "")).strip() else fb_cat

    parsed["kategori"] = _title_category(kategori) or fb_cat
    parsed.pop("sub_kategori", None)
    return apply_admin_nature(parsed)
