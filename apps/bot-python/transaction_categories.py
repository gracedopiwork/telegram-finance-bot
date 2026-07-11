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
from context_rules import (
    apply_context_rules,
    classify_from_text,
    infer_saving_label,
    is_affiliate_income,
    is_electronics_expense,
    is_interest_income,
    is_water_expense,
    prompt_context_examples,
)

# Re-export untuk kompatibilitas import lama di bot.py / tests.
__all__ = [
    "apply_context_rules",
    "build_system_prompt_rules",
    "classify_from_text",
    "infer_saving_label",
    "is_affiliate_income",
    "is_electronics_expense",
    "is_interest_income",
    "is_water_expense",
    "normalize_category_fields",
    "normalize_saving_fields",
]


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
        "affiliate": "Affiliate",
        "afiliasi": "Affiliate",
        "komisi": "Affiliate",
        "commission": "Affiliate",
        "referral": "Affiliate",
        "bunga": "Bunga Investasi",
        "bunga investasi": "Bunga Investasi",
        "interest": "Bunga Investasi",
        "interest income": "Bunga Investasi",
        "cashback": "Cashback",
        "refund": "Refund",
        "freelance": "Freelance",
        "honor": "Freelance",
        "honorarium": "Freelance",
        "bonus": "Bonus",
        "thr": "Bonus",
    }


def _enum_join(values: Iterable[str]) -> str:
    return " | ".join(f'"{v}"' for v in values)


def _contains_phrase(text: str, phrases: tuple[str, ...]) -> bool:
    lower = text.lower()
    return any(phrase in lower for phrase in phrases)


def _title_category(value: str) -> str:
    cleaned = re.sub(r"\s+", " ", value.strip())
    if cleaned == "":
        return ""
    return " ".join(word.capitalize() for word in cleaned.split(" "))


def _normalize_alias(value: str, aliases: dict[str, str]) -> str:
    key = value.strip().lower()
    return aliases.get(key, value.strip())


def normalize_saving_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan Saving/Investment; income khusus diarahkan lewat context rules dulu."""
    apply_context_rules(parsed, source_text)

    if str(parsed.get("jenis", "")).strip() != "Saving/Investment":
        return parsed

    combined = f"{parsed.get('keterangan', '')} {source_text}".strip()
    label = infer_saving_label(combined) or str(parsed.get("kategori") or "Tabungan/Investasi")
    parsed["kategori"] = label
    parsed.pop("sub_kategori", None)

    keterangan = str(parsed.get("keterangan", "")).strip()
    if keterangan == "" or label.lower() not in keterangan.lower():
        if keterangan:
            parsed["keterangan"] = f"{label}: {keterangan}"
        else:
            parsed["keterangan"] = f"Investasi {label}"

    parsed["sifat"] = "Need"
    return parsed


def _infer_kategori_from_text(text: str) -> str:
    hit = classify_from_text(text)
    if hit is not None:
        return hit["kategori"]
    return fallback_kategori()


def _kategori_from_strong_signals(text: str) -> str | None:
    hit = classify_from_text(text)
    if hit is not None:
        return hit["kategori"]
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
    examples = prompt_context_examples().strip()

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
  "impulsif": "Yes" | "No",
  "tanggal": "YYYY-MM-DD" | null
}}

CATATAN:
{policy_block}
   Kategori boleh label deskriptif (Affiliate, Bunga Investasi, Saham, Skincare, dll).
   Kategori yang sudah umum: {known_cats}
   Jika tidak yakin → {fb_cat}.

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer rupiah penuh.
   - 50rb / 50 ribu / 50k => 50000 (suffix rb/ribu/k WAJIB menempel pada angka).
   - 1,2jt / 1.2 juta => 1200000.
   - 83800 / 83.800 / 83,800 / 5000 tanpa suffix => nilai apa adanya (BUKAN juta).
   - Huruf k di kata biasa (kemarin, snack, stock) BUKAN penanda ribuan.
3) jenis: Pemasukan | Pengeluaran | Saving/Investment.
   - Pemasukan: gaji, bonus, honor/freelance, affiliate/komisi, bunga investasi, dividen cair, cashback, refund, hasil sewa, hasil jualan.
   - Saving/Investment: beli/nabung saham, reksadana, deposito, emas, crypto, dana darurat — BUKAN hasil investasi.
   - Hasil investasi (bunga/dividen cair) = Pemasukan, BUKAN Saving/Investment.
   - Donasi/sedekah/zakat = Pengeluaran + kategori Social.
4) sifat: HANYA Need atau Wants — dengarkan NIAT & FUNGSI user.
   - Need: kebutuhan hidup/kerja fungsional, tagihan, proteksi, cicilan, hasil/pemasukan.
   - Wants: diskresioner, reward, hiburan, jajan tanpa kebutuhan fungsional.
5) kategori: pilih label paling sesuai.
   Petunjuk bucket (referensi):
{hints_block}
6) {examples}
7) impulsif — terpisah dari sifat Need/Wants:
   "Yes" jika spontan / fomo / mood negatif + belanja diskresioner.
   "No" jika terencana, tagihan wajib, atau kebutuhan kerja yang dijelaskan.
8) tanggal: opsional. Isi YYYY-MM-DD HANYA jika user menyebut tanggal transaksi
   (contoh: "tgl 2/7", "tanggal 2 juli", "kemarin", "2 hari lalu").
   Format Indonesia biasanya hari/bulan. Jika tidak ada tanggal eksplisit → null.
9) Balas HANYA JSON murni, tanpa markdown.
10) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {{"error":"invalid_input"}}
"""


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan kategori: context rules dulu, lalu alias/admin."""
    apply_context_rules(parsed, source_text)

    if str(parsed.get("jenis", "")).strip() == "Saving/Investment":
        combined = f"{parsed.get('keterangan', '')} {source_text}".strip()
        label = infer_saving_label(combined) or str(parsed.get("kategori") or "Tabungan/Investasi")
        parsed["kategori"] = label
        parsed.pop("sub_kategori", None)
        parsed["sifat"] = "Need"
        return apply_admin_nature(parsed)

    if str(parsed.get("jenis", "")).strip() == "Pemasukan":
        # Jaga kategori pemasukan yang sudah di-set context rules.
        kategori = str(parsed.get("kategori", "")).strip()
        if kategori:
            parsed["kategori"] = _title_category(kategori)
            parsed.pop("sub_kategori", None)
            if str(parsed.get("sifat", "")).strip() not in {"Need", "Wants"}:
                parsed["sifat"] = "Need"
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
