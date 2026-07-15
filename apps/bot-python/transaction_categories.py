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
        "elektronik": "Elektronik",
        "gadget": "Elektronik",
        "headset": "Elektronik",
        "earphone": "Elektronik",
        "laptop": "Elektronik",
        "hp": "Elektronik",
        "handphone": "Elektronik",
        "tumbler": "Peralatan",
        "botol minum": "Peralatan",
        "peralatan": "Peralatan",
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


def _kategori_from_strong_signals(text: str, current: str = "") -> str | None:
    """Jangan timpa kategori AI. Hanya koreksi dump Jajan → Elektronik."""
    current_l = current.strip().lower()
    if is_electronics_expense(text) and current_l in {
        "",
        "jajan",
        "belanja",
        "lain-lain",
        "lain lain",
        "other",
        "misc",
        "umum",
    }:
        return "Elektronik"
    return None


def build_system_prompt_rules() -> str:
    rules_data = get_rules()
    categories = list(rules_data.get("categories") or list(valid_kategori()))
    # Pastikan AI selalu tahu label Elektronik meski belum di-sync admin.
    for required in ("Elektronik", "Jajan", "Makan", "Transport"):
        if required not in categories:
            categories.insert(0, required)
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
    known_cats = ", ".join(categories[:24])
    if len(categories) > 24:
        known_cats += ", ..."
    examples = prompt_context_examples().strip()

    return f"""
Anda adalah parser keuangan pribadi dengan TAXONOMY TERBUKA.
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
   Anda BOLEH dan DIANJURKAN membuat label kategori baru yang deskriptif (Bahasa Indonesia, Title Case).
   Contoh label baru yang valid: Peralatan, Fashion, Hobi, Perawatan Rumah, Buku, dll.
   Daftar yang sudah ada HANYA referensi (bukan batasan): {known_cats}
   Jangan memaksa ke {fb_cat} kecuali memang snack/kopi/cemilan.
   Sistem akan otomatis mendaftarkan kategori baru ke rule admin.

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer rupiah penuh.
   - 50rb / 50 ribu / 50k => 50000 (suffix rb/ribu/k WAJIB menempel pada angka).
   - Typo umum: "90 br" / "90br" = "90 rb" => 90000 (bukan 90).
   - 1,2jt / 1.2 juta => 1200000.
   - 83800 / 83.800 / 83,800 / 5000 tanpa suffix => nilai apa adanya (BUKAN juta).
   - Huruf k di kata biasa (kemarin, snack, stock) BUKAN penanda ribuan.
   - Tagihan (listrik/sewa/BPJS/cicilan) jarang di bawah Rp1000 — jika user tulis "90 br/rb", pakai skala ribuan.
3) jenis: Pemasukan | Pengeluaran | Saving/Investment.
   - UTAMA: lihat KATA KERJA arah uang dulu.
     * Pemasukan: terima, dapat, dapet, uang masuk, cair (hasil).
     * Pengeluaran: bayar, pengeluaran, melunasi, pelunasan, belanja, keluarin.
   - Jika user menulis "Pengeluaran" / "Pemasukan" / "Saving" di AWAL pesan, jenis WAJIB mengikuti itu.
   - "terima jasa freelance/freelence 6jt" = Pemasukan / Freelance (BUKAN Pengeluaran, BUKAN Jajan).
   - "bayar/melunasi jasa freelancer" = Pengeluaran / Jasa (BUKAN Pemasukan), meskipun ada kata freelance.
   - Pemasukan lain: gaji, bonus, honor yang DITERIMA, affiliate/komisi, bunga investasi, dividen cair, cashback, refund, hasil sewa, hasil jualan.
   - Saving/Investment: beli/nabung saham, reksadana, deposito, emas, crypto, dana darurat — BUKAN hasil investasi.
   - Hasil investasi (bunga/dividen cair) = Pemasukan, BUKAN Saving/Investment.
   - Donasi/sedekah/zakat = Pengeluaran + kategori Social.
   - Typo "freelence" = freelance.
4) sifat: HANYA Need atau Wants — dengarkan NIAT & FUNGSI user.
   - Need: kebutuhan hidup/kerja fungsional, tagihan, proteksi, cicilan, hasil/pemasukan.
   - Wants: diskresioner, reward, hiburan, jajan, belanja gaya hidup tanpa urgensi.
5) kategori: tentukan dari MAKNA barang/jasa — bebas label baru.
   - Utamakan label yang tepat; reuse kategori lama HANYA jika benar-benar cocok.
   - Jajan HANYA: kopi, boba, snack, cemilan, kue, dessert ringan.
   - GrabFood / GoFood / ShopeeFood = Makan atau Jajan sesuai isinya — BUKAN Transport.
   - Transport = ojek/grab ride/bensin/parkir, bukan pesanan makanan.
   - Elektronik: headset, earphone, HP, laptop, charger, gadget — BUKAN Jajan.
   - Tumbler / botol minum / peralatan dapur → Peralatan (BUKAN Jajan).
   - Baju/sepatu → Fashion. Buku → Buku. dll.
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
11) Pertanyaan balik:
   - JANGAN menebak jika informasi penting belum jelas.
   - Set needs_clarification=true jika informasi yang hilang dapat mengubah jenis,
     kategori, sifat, impulsif, atau bucket prescription.
   - Isi clarification_question dengan satu pertanyaan singkat dan spesifik.
   - Contoh ambigu: "freelance 6jt" (menerima atau membayar), "beli buku 500rb"
     (belajar/wajib/hiburan), "beli kopi 50rb" (kerja/healing), "beli laptop 8jt"
     (kerja/ganti rusak/upgrade pribadi), "bayar kelas 1jt" (olahraga atau self-development).
   - Tanyakan HANYA informasi yang belum ada; jangan meminta ulang nominal/keterangan
     yang sudah jelas.
   - Jika input memuat "Klarifikasi user:", gunakan jawabannya. Jika jawabannya masih
     belum cukup jelas, boleh ajukan satu pertanyaan yang lebih spesifik.
   - Jangan gunakan klarifikasi untuk mood; bot memiliki alur pilihan mood terpisah.
   - Tanggal transaksi bersifat opsional dan tidak perlu ditanyakan.
   - Jika tidak ambigu: needs_clarification=false dan clarification_question=null.
"""


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Hormati kategori AI; rule hanya koreksi kasus kritis / kosong."""
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

    strong_kategori = _kategori_from_strong_signals(combined, kategori)
    if strong_kategori is not None:
        kategori = strong_kategori
    elif not kategori.strip():
        inferred = _infer_kategori_from_text(combined)
        kategori = inferred if inferred else fb_cat
    elif kategori not in valid_cats:
        # Biarkan label deskriptif dari AI (auto-register di Laravel).
        kategori = _title_category(kategori)
    elif kategori == "Air" and not is_water_expense(combined):
        inferred = _infer_kategori_from_text(combined)
        if inferred and inferred != "Air":
            kategori = inferred if inferred in valid_cats else _title_category(str(parsed.get("kategori", "")) or inferred)

    parsed["kategori"] = _title_category(kategori) or fb_cat
    parsed.pop("sub_kategori", None)
    return apply_admin_nature(parsed)
