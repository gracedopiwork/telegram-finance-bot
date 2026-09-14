"""Daftar kategori resmi YFD — closed list (YFD AI Taxonomy v1.8)."""

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
from yfd_taxonomy import (
    OFFICIAL_EXPENSE_CATEGORIES,
    OFFICIAL_INCOME_CATEGORIES,
    VALID_JENIS,
)

# Re-export untuk kompatibilitas import lama di bot.py / tests.
__all__ = [
    "OFFICIAL_EXPENSE_CATEGORIES",
    "OFFICIAL_INCOME_CATEGORIES",
    "VALID_JENIS",
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
        "dry clean": "Kesehatan & Kebersihan Diri",
        "skincare": "Kesehatan & Kebersihan Diri",
        "serum": "Kesehatan & Kebersihan Diri",
        "make up": "Kesehatan & Kebersihan Diri",
        "makeup": "Kesehatan & Kebersihan Diri",
        "kecantikan": "Kesehatan & Kebersihan Diri",
        "perawatan & kecantikan": "Kesehatan & Kebersihan Diri",
        "dandan": "Kesehatan & Kebersihan Diri",
        "lipstik": "Kesehatan & Kebersihan Diri",
        "parfum": "Kesehatan & Kebersihan Diri",
        "tumbler": "Tempat Tinggal",
        "thumbler": "Tempat Tinggal",
        "termos": "Tempat Tinggal",
        "perabot": "Tempat Tinggal",
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
        "pakaian": "Pakaian & Aksesoris",
        "pakaian & aksesoris": "Pakaian & Aksesoris",
        "fashion": "Pakaian & Aksesoris",
        "baju": "Pakaian & Aksesoris",
        "sepatu": "Pakaian & Aksesoris",
        "tas": "Pakaian & Aksesoris",
        "aksesoris": "Pakaian & Aksesoris",
        "seragam": "Pakaian & Aksesoris",
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
    jenis_list = " | ".join(f'"{j}"' for j in VALID_JENIS)

    return f"""
Anda adalah parser keuangan pribadi dengan TAXONOMY TERTUTUP (YFD AI Taxonomy v1.8).
Ubah input user menjadi JSON VALID dengan schema berikut:
{{
  "keterangan": string,
  "nominal": integer,
  "jenis": {jenis_list},
  "kategori": string,
  "sifat": "Need" | "Wants" | null,
  "mood": "Happy" | "Neutral" | "Sad" | "Stressed" | "Angry" | "Tired",
  "impulsif": "Yes" | "No" | null,
  "tanggal": "YYYY-MM-DD" | null,
  "needs_clarification": boolean,
  "clarification_question": string | null
}}

SCOPE AI (§3.1) — BOLEH:
  - Pilih SATU kategori dari closed list 17 pengeluaran / 12 pemasukan.
  - Tentukan jenis (8 jenis resmi), mood (wajib semua jenis).
  - sifat Need/Wants HANYA untuk jenis Pengeluaran; selain itu isi null.
  - impulsif Yes/No untuk Pengeluaran, Piutang Keluar, dan Utang Masuk; selain itu isi null
    (Piutang Masuk / Utang Keluar / Pemasukan / Kewajiban Pajak / Saving — tidak dievaluasi).
  - Untuk grey area: set needs_clarification=true + pertanyaan singkat (bahasa Indonesia).

SCOPE AI (§3.2) — DILARANG:
  - Membuat kategori baru di luar closed list.
  - Menentukan Bucket (Essential Living / Future Building / Protection / Flexible + Social).
    Bucket dihitung sistem dari mapping + sifat — BUKAN oleh Anda.
  - Mengasumsikan konteks grey area tanpa konfirmasi.
  - Menentukan impulsif dari nominal (Impulsif ≠ nominal besar).
  - Mengisi Need/Want untuk jenis selain Pengeluaran.

CATATAN:
{policy_block}
   KATEGORI adalah CLOSED LIST. Anda TIDAK BOLEH membuat kategori baru.
   Pengeluaran / Saving — pilih SATU dari: {expense_list}
   Pemasukan — pilih SATU dari: {income_list}
   Jika ragu, pakai "{fb_cat}" (target Lain-lain < 2%).
   Nama fallback = "Lain-lain" (bukan "Lainnya").

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer rupiah penuh.
   - 50rb / 50 ribu / 50k => 50000 (suffix rb/ribu/k WAJIB menempel pada angka).
   - Typo umum: "90 br" / "90br" = "90 rb" => 90000 (bukan 90).
   - 1,2jt / 1.2 juta => 1200000.
   - 2 jt 5 ratus / 2 juta 5 ratus => 2500000 (bukan 2000000).
   - 2 jt 500rb / 2 jt setengah => 2500000.
   - 83800 / 83.800 / 83,800 / 5000 tanpa suffix => nilai apa adanya (BUKAN juta).
   - Huruf k di kata biasa (kemarin, snack, stock) BUKAN penanda ribuan.
3) jenis (8 jenis resmi — Likuiditas Sosial 4 arah):
   - Pemasukan: terima, dapat, dapet, uang masuk, cair (hasil). BUKAN terima pinjaman sosial.
   - Pengeluaran: bayar, belanja, keluarin, konsumsi.
   - Saving/Investment: beli/nabung saham, reksadana, deposito, emas, crypto, dana darurat, DP rumah/properti, DP kendaraan kebutuhan.
   - Kewajiban Pajak: PPh 25 angsuran, PPh 29 kurang bayar, PPh 28A restitusi/lebih bayar, denda pajak SPT — TIDAK masuk 4 bucket.
     * PBB / STNK / pajak kendaraan = Pengeluaran (bukan Kewajiban Pajak).
   - Piutang Keluar: pinjamin / pinjamkan / ngutangin / di pinjam X / talangin / kasih pinjam / bayarkan dulu / nanti dia ganti.
   - Piutang Masuk: dibalikin X / X balikin hutang / X bayar balik / transfer balik dari X / uang dikembalikan — BUKAN Utang Keluar, BUKAN Pemasukan.
   - Utang Masuk: saya pinjam / pinjam ke/dari/sama / pinjam uang [nama] (tanpa ke/dari) / ngutang / terima pinjaman — cash naik, BUKAN Pemasukan, BUKAN Pengeluaran.
     * "saya pinjam uang ayuti 5jt" = Utang Masuk meski tanpa kata ke/dari.
     * Tujuan pinjaman (kuliah, obat, RS, kerja) dan jatuh tempo (kembali bulan depan) BUKAN jenis. Jangan jadi Pendidikan/Kesehatan.
   - Utang Keluar: bayar/lunasi/balikin utang ke X / mengembalikan uang [nama] yang saya pinjam — BUKAN Pengeluaran 4-bucket.
     * "mengembalikan uang mama 2.5jt yang saya pinjam" = Utang Keluar (pelunasan). BUKAN Piutang Masuk, BUKAN Piutang Keluar.
     * Pelunasan: JANGAN tanya tujuan pinjaman / kapan dikembalikan. Tracker sudah punya data itu.
     * Cicilan lembaga (pinjol/KPR/kartu kredit/paylater) tetap Pengeluaran + Cicilan & Hutang.
   - PRINSIP: Likuiditas Sosial hanya mengubah kas & piutang/hutang. Belanja dari dana pinjaman (mis. bayar RS / bayar kuliah) tetap Pengeluaran + bucket — itu transaksi TERPISAH, bukan catatan pinjamnya.
   - Hadiah/kado/parcel/tip yang DIBERI = Pengeluaran + Hadiah. BUKAN Likuiditas Sosial.
     Beli gadget sebagai hadiah tidak perlu grey area HP rusak vs upgrade.
   - Terima hadiah uang = Pemasukan (bukan kategori Hadiah).
   - Komisi gift TikTok / live gift / komisi afiliasi = Pemasukan + Affiliate.
     BUKAN Pengeluaran Hadiah (itu kado yang DIBERI).
   - Pinjam/utang/talangin/nanti dia ganti tetap Likuiditas Sosial meski ada kata keluarga.
   - "utang ke [nama]" tanpa konteks = AMBIGU → needs_clarification (meminjamkan vs berhutang).
   - Hasil investasi (bunga/dividen cair) = Pemasukan, BUKAN Saving/Investment.
   - Donasi/sedekah/zakat/qurban = Pengeluaran + Sosial & Keluarga (bukan Piutang).
4) sifat: HANYA untuk Pengeluaran — nilai Need atau Wants. Jenis lain → sifat=null.
   - Need: kebutuhan hidup/kerja fungsional, tagihan, proteksi, cicilan.
   - Wants: diskresioner, reward, hiburan, jajan, belanja gaya hidup.
5) kategori (WAJIB dari closed list):
   - Makanan & Minuman: makan harian, jajan, kopi, boba, GoFood/GrabFood.
     * Starbucks/kopi + meeting kerja/klien → Bisnis & Karir (Need).
     * Starbucks/kopi healing/santai → Makanan & Minuman (Wants).
   - Tempat Tinggal: kos, KPR tinggal, listrik, air, gas, PBB, ART/driver/babysitter,
     peralatan/perabot rumah (kulkas, rice cooker, tumbler/termos, panci, piring).
     * §2.18: rusak/tidak layak/belum memadai → Need (Essential Living).
     * §2.18: tambah koleksi/tren → Wants (Flexible + Social). BUKAN Proteksi.
   - Transportasi: ojek/grab/parkir/bensin (bukan pesanan makanan); bucket mengikuti tujuan perjalanan.
     * Grab ke gym = Transportasi Wants (bukan Lifestyle). Membership gym = Lifestyle.
     * Langganan Grab/Gojek (subscription/paket hemat) = Transportasi Wants — BUKAN grey area tujuan ride,
       BUKAN Likuiditas Sosial.
     * Gym/olahraga berbayar = Flexible + Social, KECUALI fisik latihan = alat penghasil uang
       (kamu personal trainer / atlet profesional) → Future Building. WAJIB klarifikasi.
   - Komunikasi: pulsa, kuota, internet HP, biaya admin bank/ATM/transfer.
   - Kesehatan & Kebersihan Diri: dokter, obat, sabun, handbody, deodoran, sikat gigi, laundry (semua laundry = Need);
     skincare/makeup/dandan/parfum/facial/toner/spa = Wants (BUKAN Lain-lain); fisioterapi resep dokter = Need (tanpa resep = grey area).
   - Pendidikan: SPP/UKT ATAU seminar/workshop/sertifikasi/conference/pengembangan diri /
     iuran organisasi profesi (IDI). Pengembangan diri SELALU Future Building di sistem
     (Need atau Wants — bucket sama).
   - Investasi & Tabungan: saham, reksadana, emas, dana darurat (jenis Saving/Investment).
   - Proteksi: BPJS, premi asuransi jiwa/kesehatan/kendaraan/aset.
   - Lifestyle & Hiburan: Netflix, konser, gym/pilates berbayar, gadget upgrade.
     * Gym untuk kebugaran pribadi = Wants. Gym alat kerja fisik (PT/atlet) = Need + klarifikasi.
   - Traveling: liburan, hotel, staycation, wisata.
   - Sosial & Keluarga: donasi, sedekah, zakat, qurban, bantu keluarga (bukan hadiah barang).
   - Bisnis & Karir: modal usaha, tools kerja, marketing, software bisnis, konsumsi meeting kerja.
   - Hadiah: kado, parcel, tip/tips, hadiah beli barang untuk orang lain. BUKAN piutang/utang.
   - Cicilan & Hutang (§2.14):
     * Cicilan produktif / KPR investasi → Future Building / Need.
     * KPR rumah tinggal / cicilan konsumtif / paylater → Essential Living / Need.
     * DP rumah/properti → Saving/Investment / Future Building (Baseline Aset).
     * DP kendaraan kebutuhan → Saving/Investment / Future Building.
     * DP kendaraan lifestyle → Pengeluaran / Flexible + Social / Wants.
     * Pajak kendaraan (STNK/PKB) → ikut fungsi kendaraan.
     * Pinjol/pinjaman tunai = grey area — WAJIB klarifikasi mendesak vs konsumsi (+ Risk Alert di sistem).
     * Denda/tilang non-pajak → Essential Living / Need (denda pajak → Kewajiban Pajak).
     * DP HP/gadget TIDAK masuk Baseline Aset.
   - Pakaian & Aksesoris: fashion, baju, sepatu, seragam, tas (bukan Lifestyle).
   - Biaya Legal, Administrasi & Peristiwa Besar: notaris, balik nama, mahar/pernikahan, biaya duka
     (BUKAN admin bank). Notaris bucket ikut aset induk — WAJIB klarifikasi aset.
   - Lain-lain: hanya jika benar-benar tidak cocok (target < 2%).
6) {examples}
7) impulsif (§3.5) — TERPISAH dari sifat Need/Wants; JANGAN pakai nominal sebagai dasar:
   Signal Yes: "tiba-tiba", "spontan", "kalap", "stres jadi beli", "lihat Instagram langsung beli",
   "ga rencanain sih", FOMO.
   Signal No: "terencana", "sudah rencanain", "tabungan buat ini", "meeting klien", "langganan bulanan".
   Jika tidak ada signal → impulsif = "No". JANGAN tanya terencana vs spontan.
   Essential + Need + Impulsif Yes adalah VALID (§3.6) — bukan kontradiksi.
8) tanggal: opsional. Isi YYYY-MM-DD HANYA jika user menyebut tanggal transaksi secara eksplisit
   (kata tgl/tanggal/kemarin/hari lalu, atau pola DD/MM). Format Indonesia = hari dulu, bulan kedua
   (contoh "1/9" = 1 September = 2026-09-01, BUKAN 9 Januari). Jika ragu atau tidak disebut → null.
   JANGAN mengarang tanggal dari "hari ini" atau format Amerika MM/DD.
9) Balas HANYA JSON murni, tanpa markdown.
10) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {{"error":"invalid_input"}}
11) Grey area (§2.18 / §3.3) — WAJIB konfirmasi sebelum final:
   perabot rusak vs koleksi; kopi meeting vs healing; laptop kerja vs FOMO; HP rusak vs upgrade;
   DP/pajak kendaraan kerja vs lifestyle; KPR/PBB tinggal vs investasi; subscription bisnis vs hiburan;
   coaching skill vs hobi; transport tujuan; ART menunjang kerja vs kenyamanan; pinjol mendesak vs konsumsi;
   notaris aset induk; pakaian kerja vs fashion; fisioterapi resep vs pilihan sendiri.
   JANGAN klarifikasi HP/laptop rusak vs upgrade jika transaksi adalah hadiah/kado.
   JANGAN tanya tujuan ride (kantor/lifestyle/bisnis) untuk langganan Grab/Gojek/paket hemat.
   JANGAN pakai pertanyaan Likuiditas Sosial kecuali jenis memang Piutang/Utang.
   Makeup/skincare/dandan/cushion = Kesehatan & Kebersihan Diri / Wants.
   JANGAN klarifikasi Need vs Wants, JANGAN tanya terencana vs spontan,
   JANGAN tanya Likuiditas Sosial untuk makeup/skincare.
   Tumbler/termos/kulkas/perabot = Tempat Tinggal, grey area rusak vs koleksi (BUKAN Proteksi, BUKAN Lifestyle).
   Set needs_clarification=true dan clarification_question singkat (bahasa Indonesia taxonomy).
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
