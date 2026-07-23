"""Daftar kategori resmi YFD — closed list (YFD AI Taxonomy v1.0)."""

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
    is_drinking_water_expense,
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
    "is_drinking_water_expense",
    "normalize_category_fields",
    "normalize_saving_fields",
]

# Closed list selaras docs/YFD_AI_Taxonomy.pdf
OFFICIAL_EXPENSE_CATEGORIES = (
    "Makanan & Minuman",
    "Tempat Tinggal",
    "Transportasi",
    "Komunikasi",
    "Kesehatan & Kebersihan Diri",
    "Pendidikan",
    "Investasi & Tabungan",
    "Proteksi",
    "Lifestyle & Hiburan",
    "Traveling",
    "Sosial & Keluarga",
    "Bisnis & Karir",
    "Hadiah",
    "Cicilan & Hutang",
    "Lain-lain",
)

OFFICIAL_INCOME_CATEGORIES = (
    "Gaji",
    "Bonus",
    "Freelance",
    "Affiliate",
    "Dividen",
    "Bunga Investasi",
    "Cashback",
    "Refund",
    "Penjualan",
    "Sewa Masuk",
    "Transfer Masuk",
    "Lain-lain",
)


def _kategori_aliases() -> dict[str, str]:
    fb = fallback_kategori()
    aliases = {
        "lain-lain": fb,
        "lain lain": fb,
        "lainnya": fb,
        "other": fb,
        "umum": fb,
        "misc": fb,
        "makan": "Makanan & Minuman",
        "makanan": "Makanan & Minuman",
        "makanan & minuman": "Makanan & Minuman",
        "makanan dan minuman": "Makanan & Minuman",
        "jajan": "Makanan & Minuman",
        "minuman": "Makanan & Minuman",
        "transport": "Transportasi",
        "transportasi": "Transportasi",
        "listrik": "Tempat Tinggal",
        "air": "Tempat Tinggal",
        "sewa/tempat tinggal": "Tempat Tinggal",
        "sewa": "Tempat Tinggal",
        "tempat tinggal": "Tempat Tinggal",
        "komunikasi": "Komunikasi",
        "kesehatan": "Kesehatan & Kebersihan Diri",
        "kesehatan & kebersihan diri": "Kesehatan & Kebersihan Diri",
        "laundry": "Kesehatan & Kebersihan Diri",
        "cuci baju": "Kesehatan & Kebersihan Diri",
        "skincare": "Lifestyle & Hiburan",
        "pendidikan": "Pendidikan",
        "saham": "Investasi & Tabungan",
        "reksadana": "Investasi & Tabungan",
        "emas": "Investasi & Tabungan",
        "obligasi": "Investasi & Tabungan",
        "tabungan/investasi": "Investasi & Tabungan",
        "investasi": "Investasi & Tabungan",
        "investasi & tabungan": "Investasi & Tabungan",
        "asuransi": "Proteksi",
        "proteksi": "Proteksi",
        "hiburan": "Lifestyle & Hiburan",
        "lifestyle": "Lifestyle & Hiburan",
        "lifestyle & hiburan": "Lifestyle & Hiburan",
        "subscription": "Lifestyle & Hiburan",
        "elektronik": "Lifestyle & Hiburan",
        "peralatan": "Lifestyle & Hiburan",
        "gadget": "Lifestyle & Hiburan",
        "traveling": "Traveling",
        "liburan": "Traveling",
        "social": "Sosial & Keluarga",
        "sosial": "Sosial & Keluarga",
        "sosial & keluarga": "Sosial & Keluarga",
        "bisnis": "Bisnis & Karir",
        "bisnis & karir": "Bisnis & Karir",
        "jasa": "Bisnis & Karir",
        "hadiah": "Hadiah",
        "cicilan": "Cicilan & Hutang",
        "cicilan & hutang": "Cicilan & Hutang",
        "pajak": "Lain-lain",
        "affiliate": "Affiliate",
        "afiliasi": "Affiliate",
        "komisi": "Affiliate",
        "commission": "Affiliate",
        "referral": "Affiliate",
        "bunga": "Bunga Investasi",
        "bunga investasi": "Bunga Investasi",
        "interest": "Bunga Investasi",
        "cashback": "Cashback",
        "refund": "Refund",
        "freelance": "Freelance",
        "honor": "Freelance",
        "honorarium": "Freelance",
        "bonus": "Bonus",
        "thr": "Bonus",
        "gaji": "Gaji",
        "dividen": "Dividen",
        "penjualan": "Penjualan",
        "sewa masuk": "Sewa Masuk",
        "transfer masuk": "Transfer Masuk",
    }
    rules = get_rules()
    for key, value in (rules.get("aliases") or {}).items():
        if isinstance(key, str) and isinstance(value, str):
            aliases[key.strip().lower()] = value
    return aliases


def _enum_join(values: Iterable[str]) -> str:
    return " | ".join(f'"{v}"' for v in values)


def _title_category(value: str) -> str:
    cleaned = re.sub(r"\s+", " ", value.strip())
    if cleaned == "":
        return ""
    # Pertahankan & dan Title Case ringan.
    return " ".join(
        word if word == "&" else (word[:1].upper() + word[1:] if word else word)
        for word in cleaned.split(" ")
    )


def _normalize_alias(value: str, aliases: dict[str, str]) -> str:
    key = value.strip().lower()
    key = key.replace("_", " ").replace("-", " ")
    key = re.sub(r"\s+", " ", key).strip()
    return aliases.get(key, value.strip())


def normalize_saving_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan Saving/Investment; income khusus diarahkan lewat context rules dulu."""
    apply_context_rules(parsed, source_text)

    if str(parsed.get("jenis", "")).strip() != "Saving/Investment":
        return parsed

    combined = f"{parsed.get('keterangan', '')} {source_text}".strip()
    label = infer_saving_label(combined) or str(parsed.get("kategori") or "Investasi & Tabungan")
    parsed["kategori"] = _canonicalize_category(label, "Saving/Investment")
    parsed.pop("sub_kategori", None)

    keterangan = str(parsed.get("keterangan", "")).strip()
    if keterangan == "" or "investasi" not in keterangan.lower():
        if keterangan:
            parsed["keterangan"] = f"{parsed['kategori']}: {keterangan}"
        else:
            parsed["keterangan"] = f"Investasi {parsed['kategori']}"

    parsed["sifat"] = "Need"
    return parsed


def _infer_kategori_from_text(text: str) -> str:
    hit = classify_from_text(text)
    if hit:
        return hit["kategori"]
    return fallback_kategori()


def _canonicalize_category(value: str, jenis: str = "") -> str:
    aliases = _kategori_aliases()
    mapped = _normalize_alias(value, aliases)
    mapped = _title_category(mapped)
    allowed = set(valid_kategori()) | set(OFFICIAL_EXPENSE_CATEGORIES) | set(OFFICIAL_INCOME_CATEGORIES)

    # Exact / case-insensitive match
    for cat in allowed:
        if cat.lower() == mapped.lower():
            return cat

    if jenis == "Pemasukan":
        return "Gaji" if "Gaji" in allowed else fallback_kategori()
    if jenis == "Saving/Investment":
        return "Investasi & Tabungan"
    return fallback_kategori() or "Lain-lain"


def build_system_prompt_rules() -> str:
    rules_data = get_rules()
    expense_cats = list(OFFICIAL_EXPENSE_CATEGORIES)
    income_cats = list(OFFICIAL_INCOME_CATEGORIES)
    fb_cat = rules_data.get("fallback_category") or fallback_kategori() or "Lain-lain"

    policy_lines = rules_data.get("policy_notes") or []
    policy_block = "\n".join(f"   - {line}" for line in policy_lines)
    examples = prompt_context_examples().strip()
    expense_list = ", ".join(expense_cats)
    income_list = ", ".join(income_cats)

    return f"""
Anda adalah parser keuangan pribadi dengan TAXONOMY TERTUTUP (YFD AI Taxonomy v1.0).
Ubah input user menjadi JSON VALID dengan schema berikut:
{{
  "keterangan": string,
  "nominal": integer,
  "jenis": "Pemasukan" | "Pengeluaran" | "Saving/Investment",
  "kategori": string,
  "sifat": "Need" | "Wants",
  "mood": "Happy" | "Neutral" | "Sad" | "Stressed" | "Angry" | "Tired",
  "impulsif": "Yes" | "No",
  "tanggal": "YYYY-MM-DD" | null,
  "needs_clarification": boolean,
  "clarification_question": string | null
}}

CATATAN:
{policy_block}
   KATEGORI adalah CLOSED LIST. Anda TIDAK BOLEH membuat kategori baru.
   Pengeluaran / Saving — pilih SATU dari: {expense_list}
   Pemasukan — pilih SATU dari: {income_list}
   Jika ragu, pakai "{fb_cat}".
   Bucket ditentukan sistem (bukan Anda).

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer rupiah penuh.
   - 50rb / 50 ribu / 50k => 50000 (suffix rb/ribu/k WAJIB menempel pada angka).
   - Typo umum: "90 br" / "90br" = "90 rb" => 90000 (bukan 90).
   - 1,2jt / 1.2 juta => 1200000.
   - 83800 / 83.800 / 83,800 / 5000 tanpa suffix => nilai apa adanya (BUKAN juta).
   - Huruf k di kata biasa (kemarin, snack, stock) BUKAN penanda ribuan.
3) jenis: Pemasukan | Pengeluaran | Saving/Investment.
   - UTAMA: lihat KATA KERJA arah uang dulu.
     * Pemasukan: terima, dapat, dapet, uang masuk, cair (hasil).
     * Pengeluaran: bayar, pengeluaran, melunasi, pelunasan, belanja, keluarin.
   - Saving/Investment: beli/nabung saham, reksadana, deposito, emas, crypto, dana darurat.
   - Hasil investasi (bunga/dividen cair) = Pemasukan, BUKAN Saving/Investment.
   - Donasi/sedekah/zakat = Pengeluaran + kategori Sosial & Keluarga.
4) sifat: HANYA Need atau Wants.
   - Need: kebutuhan hidup/kerja fungsional, tagihan, proteksi, cicilan, hasil/pemasukan.
   - Wants: diskresioner, reward, hiburan, jajan, belanja gaya hidup.
5) kategori (WAJIB dari closed list):
   - Makanan & Minuman: makan harian, jajan, kopi, boba, GoFood/GrabFood.
   - Tempat Tinggal: kos, KPR tinggal, listrik, air, gas, laundry rumah.
   - Transportasi: ojek/grab ride, bensin, parkir (bukan pesanan makanan).
   - Komunikasi: pulsa, kuota.
   - Kesehatan & Kebersihan Diri: dokter, obat, sabun, shampo (bukan gym).
   - Pendidikan: SPP/UKT ATAU seminar/kursus/pengembangan diri.
   - Investasi & Tabungan: saham, reksadana, emas, dana darurat (jenis Saving/Investment).
   - Proteksi: BPJS, premi asuransi.
   - Lifestyle & Hiburan: Netflix, konser, gym, fashion, gadget, skincare premium.
   - Traveling: liburan, hotel, wisata.
   - Sosial & Keluarga: donasi, sedekah, bantu keluarga.
   - Bisnis & Karir: modal usaha, tools kerja, marketing, software bisnis.
   - Hadiah: kado, parcel, tip.
   - Cicilan & Hutang: cicilan, paylater, kartu kredit.
   - Lain-lain: hanya jika benar-benar tidak cocok (maksimal jarang).
6) {examples}
7) impulsif — terpisah dari sifat Need/Wants:
   "Yes" jika spontan / tidak terencana / fomo / mood negatif + belanja diskresioner
   dengan nominal bermakna (umumnya >= Rp50.000), ATAU belanja makan karena emosi
   (contoh: "makan malam 100rb karena capek" = Yes meski sifat Need).
   "No" jika terencana, tagihan wajib, atau belanja kecil sehari-hari.
8) tanggal: opsional. Isi YYYY-MM-DD HANYA jika user menyebut tanggal transaksi.
9) Balas HANYA JSON murni, tanpa markdown.
10) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {{"error":"invalid_input"}}
11) Grey area (kopi meeting vs healing, HP rusak vs upgrade, subscription kerja vs hiburan):
   - Set needs_clarification=true dan tanya konteks singkat ke user.
12) JANGAN menentukan bucket. Sistem yang menghitung bucket.
"""


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Paksa kategori ke closed list resmi YFD."""
    apply_admin_nature(parsed)
    jenis = str(parsed.get("jenis", "")).strip()
    combined = f"{parsed.get('keterangan', '')} {source_text}".strip()

    if jenis == "Saving/Investment":
        label = infer_saving_label(combined) or str(parsed.get("kategori") or "Investasi & Tabungan")
        parsed["kategori"] = _canonicalize_category(label, jenis)
        parsed.pop("sub_kategori", None)
        return parsed

    if jenis == "Pemasukan":
        kategori = str(parsed.get("kategori", "")).strip()
        if not kategori:
            kategori = _infer_kategori_from_text(combined)
        parsed["kategori"] = _canonicalize_category(kategori, jenis)
        parsed.pop("sub_kategori", None)
        return parsed

    kategori = str(parsed.get("kategori", "")).strip()
    if not kategori:
        kategori = _infer_kategori_from_text(combined)
    parsed["kategori"] = _canonicalize_category(kategori, jenis or "Pengeluaran")
    parsed.pop("sub_kategori", None)
    return parsed
