"""Rule konteks transaksi YFD — deterministik sebelum/sesudah AI.

Prioritas:
1) Pemasukan khusus (affiliate, bunga, dividen, refund, dll.)
2) Saving/Investment (beli/nabung instrumen)
3) Pengeluaran (tagihan, konsumsi, sosial, dll.)
"""

from __future__ import annotations

import re
from typing import Any

VALID_JENIS = frozenset({"Pemasukan", "Pengeluaran", "Saving/Investment"})
VALID_SIFAT = frozenset({"Need", "Wants"})


def _contains(text: str, phrases: tuple[str, ...]) -> bool:
    lower = text.lower()
    return any(phrase in lower for phrase in phrases)


def _match_any(text: str, patterns: tuple[re.Pattern[str], ...]) -> bool:
    return any(pattern.search(text) for pattern in patterns)


# ---------------------------------------------------------------------------
# Income rules (paling kritis — sering salah ke Pengeluaran/Saving)
# ---------------------------------------------------------------------------

_AFFILIATE = (
    "affiliate",
    "afiliasi",
    "komisi",
    "commission",
    "referral",
    "cashback seller",
    "shopee affiliate",
    "tiktok affiliate",
    "tokopedia affiliate",
    "lazada affiliate",
    "shopee komisi",
    "tiktok shop komisi",
)

_BUNGA = (
    "bunga investasi",
    "bunga deposito",
    "bunga tabungan",
    "bunga reksa",
    "bunga obligasi",
    "bunga sbn",
    "bunga bank",
    "interest income",
    "investment interest",
    "terima bunga",
    "dapat bunga",
    "hasil bunga",
    "kupon obligasi",
    "kupon sbn",
    "yield investasi",
)

_DIVIDEN = (
    "dividen",
    "dividend",
)

_DIVIDEN_REINVEST = (
    "dividen reinvest",
    "dividend reinvest",
    "reinvest dividen",
    "reinvest dividend",
)

_GAJI = (
    "gaji",
    "payroll",
    "slip gaji",
    "salary",
    "transfer gaji",
    "upah bulanan",
)

_BONUS = (
    "bonus",
    "thr",
    "tunjangan hari raya",
    "insentif",
    "komisi kerja",
    "performance bonus",
)

_HONOR = (
    "honor",
    "honorarium",
    "freelance",
    "freelancer",
    "fee project",
    "fee proyek",
    "jasa konsultasi",
    "bayaran project",
    "pembayaran project",
    "kontrak kerja lepas",
)

_CASHBACK = (
    "cashback",
    "cash back",
    "rebate",
    "poin ditukar",
    "redeem poin",
)

_REFUND = (
    "refund",
    "pengembalian dana",
    "dana dikembalikan",
    "chargeback",
    "batal transaksi balik",
)

_PENJUALAN = (
    "hasil jualan",
    "hasil penjualan",
    "terima dari jualan",
    "jual barang",
    "jual hp",
    "jual motor",
    "jual mobil",
    "omzet",
    "penjualan marketplace",
)

_SEWA_MASUK = (
    "terima sewa",
    "dapat sewa",
    "sewa masuk",
    "hasil sewa",
    "rental income",
    "uang kos masuk",
    "bayaran kos masuk",
)

_TRANSFER_MASUK = (
    "transfer masuk",
    "terima transfer",
    "uang masuk dari",
    "kiriman orang tua",
    "kiriman orangtua",
    "kiriman keluarga",
    "dikirim orang tua",
)

# ---------------------------------------------------------------------------
# Saving / Investment (aksi menempatkan uang, BUKAN hasil)
# ---------------------------------------------------------------------------

_SAVING_LABELS: tuple[tuple[str, str], ...] = (
    ("reksadana", "Reksadana"),
    ("reksa dana", "Reksadana"),
    ("avg down", "Saham"),
    ("saham", "Saham"),
    ("obligasi", "Obligasi"),
    ("sbn", "Obligasi"),
    ("emas antam", "Emas"),
    ("beli emas", "Emas"),
    ("nabung emas", "Emas"),
    ("emas", "Emas"),
    ("deposito", "Deposito"),
    ("crypto", "Crypto"),
    ("bitcoin", "Crypto"),
    ("binance", "Crypto"),
    ("dana darurat", "Dana darurat"),
    ("top up bibit", "Reksadana"),
    ("topup bibit", "Reksadana"),
    ("top up ipot", "Saham"),
    ("topup ipot", "Saham"),
    ("nabung", "Tabungan"),
    ("tabungan", "Tabungan"),
    ("investasi", "Investasi"),
)

_SAVING_ACTION = (
    "beli",
    "nabung",
    "top up",
    "topup",
    "setor",
    "setoran",
    "avg down",
    "tambah posisi",
    "ikut",
)

# ---------------------------------------------------------------------------
# Expense rules
# ---------------------------------------------------------------------------

_ASURANSI = (
    "asuransi",
    "premi",
    "bpjs",
    "bpjs kesehatan",
    "bpjs ketenagakerjaan",
)

_SKINCARE = (
    "skincare",
    "skin care",
    "serum",
    "masker wajah",
    "moisturizer",
    "sunscreen",
    "toner wajah",
)

_SUBSCRIPTION = (
    "subscription",
    "langganan",
    "netflix",
    "spotify",
    "youtube premium",
    "disney+",
    "disney plus",
    "apple music",
    "icloud",
    "google one",
    "chatGPT plus",
    "chatgpt plus",
    "canva pro",
)

_TRANSPORT = (
    "ojek",
    "grab",
    "gojek",
    "maxim",
    "angkot",
    "bensin",
    "pertamax",
    "pertalite",
    "solar",
    "tol",
    "parkir",
    "transport",
    "kereta",
    "krl",
    "mrt",
    "lrt",
    "tiket pesawat",
    "boarding",
)

_SERVIS_KENDARAAN = (
    "servis",
    "bengkel",
    "ganti oli",
    "ban motor",
    "ban mobil",
)

_LISTRIK = ("listrik", "pln", "token listrik", "token pln")

_AIR_PATTERNS = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
        r"\bpdam\b",
        r"\btagihan\s+air\b",
        r"\bbayar\s+air\b",
        r"\brekening\s+air\b",
        r"\bbiaya\s+air\b",
        r"\bair\s+pdam\b",
    )
)

_MAKAN = (
    "makan",
    "nasi",
    "sarapan",
    "lunch",
    "dinner",
    "restaurant",
    "restoran",
    "warung",
    "kedai",
    "gojek food",
    "gofood",
    "grabfood",
)

_KOPI_JAJAN = (
    "kopi",
    "coffee",
    "starbucks",
    "espresso",
    "americano",
    "kafein",
    "jajan",
    "snack",
    "cemilan",
    "bubble tea",
    "boba",
)

_SOCIAL = (
    "hadiah",
    "amplop",
    "ultah",
    "ulang tahun",
    "konser",
    "sedekah",
    "persembahan",
    "ibadah",
    "donasi",
    "zakat",
    "infaq",
    "infak",
    "wakaf",
)

_KESEHATAN = (
    "obat",
    "apotek",
    "klinik",
    "dokter",
    "rumah sakit",
    "rawat jalan",
    "rawat inap",
    "lab",
    "cek darah",
    "vitamin",
    "suplemen",
)

_PENDIDIKAN = (
    "spp",
    "uang sekolah",
    "kursus",
    "les",
    "bimbel",
    "kuliah",
    "uks",
    "buku pelajaran",
)

_KOMUNIKASI = (
    "pulsa",
    "kuota",
    "paket data",
    "internet rumah",
    "wifi",
    "indihome",
    "biznet",
    "telkomsel",
    "xl ",
    "axis",
)

_SEWA_KELUAR = (
    "bayar sewa",
    "sewa rumah",
    "sewa kost",
    "sewa kos",
    "kontrakan",
    "uang kos",
    "bayar kos",
    "cicilan rumah",
    "kpr",
)

_CICILAN = (
    "cicilan",
    "angsuran",
    "kredit",
    "paylater",
    "shopee paylater",
    "gopay later",
    "kredit motor",
    "kredit mobil",
)

_PAJAK = (
    "pajak",
    "pph",
    "ppn",
    "pbb",
    "stnk",
    "pkb",
    "samsat",
)

_ELEKTRONIK_PATTERNS = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
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


def is_affiliate_income(text: str) -> bool:
    return _contains(text, _AFFILIATE)


def is_interest_income(text: str) -> bool:
    lower = text.lower()
    if _contains(lower, _BUNGA):
        return True
    if "bunga" in lower and any(
        token in lower
        for token in ("investasi", "deposito", "tabungan", "reksa", "obligasi", "sbn", "saham", "bank", "rp", "ribu")
    ):
        return True
    if re.search(r"\bbunga\s+\d", lower) or re.search(r"\d+\s*(?:rb|ribu|jt)?\s*bunga\b", lower):
        return True
    return False


def is_dividend_income(text: str) -> bool:
    if _contains(text, _DIVIDEN_REINVEST):
        return False
    return _contains(text, _DIVIDEN)


def is_water_expense(text: str) -> bool:
    return _match_any(text, _AIR_PATTERNS)


def is_electronics_expense(text: str) -> bool:
    return _match_any(text, _ELEKTRONIK_PATTERNS)


def infer_saving_label(text: str) -> str | None:
    lower = text.lower()
    # Hasil investasi = pemasukan, jangan jadi saving.
    if is_interest_income(lower) or is_dividend_income(lower) or is_affiliate_income(lower):
        return None
    for keyword, label in _SAVING_LABELS:
        if keyword in lower:
            return label
    return None


def is_saving_action(text: str) -> bool:
    lower = text.lower()
    if is_interest_income(lower) or is_dividend_income(lower) or is_affiliate_income(lower):
        return False
    label = infer_saving_label(lower)
    if label is None:
        return False
    # Ada lemak instrument: anggap saving kecuali ingin hasil (sudah difilter di atas).
    if label in {"Saham", "Reksadana", "Obligasi", "Emas", "Deposito", "Crypto", "Dana darurat"}:
        return True
    if _contains(lower, _SAVING_ACTION):
        return True
    if any(k in lower for k in ("nabung", "tabungan", "investasi", "deposito")):
        return True
    return False


def classify_from_text(text: str) -> dict[str, str] | None:
    """Kembalikan {jenis, kategori, sifat} jika rule kuat cocok, else None."""
    lower = text.lower().strip()
    if not lower:
        return None

    # ---- Pemasukan (prioritas tertinggi) ----
    if is_affiliate_income(lower):
        return {"jenis": "Pemasukan", "kategori": "Affiliate", "sifat": "Need"}
    if is_interest_income(lower):
        return {"jenis": "Pemasukan", "kategori": "Bunga Investasi", "sifat": "Need"}
    if is_dividend_income(lower):
        return {"jenis": "Pemasukan", "kategori": "Dividen", "sifat": "Need"}
    if _contains(lower, _REFUND):
        return {"jenis": "Pemasukan", "kategori": "Refund", "sifat": "Need"}
    if _contains(lower, _CASHBACK):
        return {"jenis": "Pemasukan", "kategori": "Cashback", "sifat": "Need"}
    if _contains(lower, _GAJI):
        return {"jenis": "Pemasukan", "kategori": "Gaji", "sifat": "Need"}
    if _contains(lower, _BONUS):
        return {"jenis": "Pemasukan", "kategori": "Bonus", "sifat": "Need"}
    if _contains(lower, _HONOR):
        return {"jenis": "Pemasukan", "kategori": "Freelance", "sifat": "Need"}
    if _contains(lower, _PENJUALAN):
        return {"jenis": "Pemasukan", "kategori": "Penjualan", "sifat": "Need"}
    if _contains(lower, _SEWA_MASUK):
        return {"jenis": "Pemasukan", "kategori": "Sewa Masuk", "sifat": "Need"}
    if _contains(lower, _TRANSFER_MASUK):
        return {"jenis": "Pemasukan", "kategori": "Transfer Masuk", "sifat": "Need"}

    # ---- Saving / Investment ----
    if is_saving_action(lower) or (
        infer_saving_label(lower) is not None and _contains(lower, _SAVING_ACTION + ("saham", "reksa", "deposito", "crypto", "bitcoin", "sbn", "obligasi"))
    ):
        label = infer_saving_label(lower) or "Tabungan/Investasi"
        # "investasi" generik tanpa aksi beli/nabung & tanpa instrumen jelas → jangan paksa.
        if label == "Investasi" and not _contains(lower, _SAVING_ACTION):
            pass
        else:
            return {"jenis": "Saving/Investment", "kategori": label, "sifat": "Need"}

    # Dividen reinvest = saving
    if _contains(lower, _DIVIDEN_REINVEST):
        return {"jenis": "Saving/Investment", "kategori": "Saham", "sifat": "Need"}

    # ---- Pengeluaran ----
    if _contains(lower, _ASURANSI):
        return {"jenis": "Pengeluaran", "kategori": "Asuransi", "sifat": "Need"}
    if _contains(lower, _SKINCARE):
        return {"jenis": "Pengeluaran", "kategori": "Skincare", "sifat": "Wants"}
    if _contains(lower, _SUBSCRIPTION):
        return {"jenis": "Pengeluaran", "kategori": "Subscription", "sifat": "Wants"}
    if _contains(lower, _LISTRIK):
        return {"jenis": "Pengeluaran", "kategori": "Listrik", "sifat": "Need"}
    if is_water_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Air", "sifat": "Need"}
    if _contains(lower, _SERVIS_KENDARAAN) or _contains(lower, _TRANSPORT):
        return {"jenis": "Pengeluaran", "kategori": "Transport", "sifat": "Need"}
    if _contains(lower, _KESEHATAN):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan", "sifat": "Need"}
    if _contains(lower, _PENDIDIKAN):
        return {"jenis": "Pengeluaran", "kategori": "Pendidikan", "sifat": "Need"}
    if _contains(lower, _KOMUNIKASI):
        return {"jenis": "Pengeluaran", "kategori": "Komunikasi", "sifat": "Need"}
    if _contains(lower, _SEWA_KELUAR):
        return {"jenis": "Pengeluaran", "kategori": "Sewa/Tempat Tinggal", "sifat": "Need"}
    if _contains(lower, _CICILAN):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan", "sifat": "Need"}
    if _contains(lower, _PAJAK):
        return {"jenis": "Pengeluaran", "kategori": "Pajak", "sifat": "Need"}
    if _contains(lower, _SOCIAL):
        return {"jenis": "Pengeluaran", "kategori": "Social", "sifat": "Need"}
    if _contains(lower, _MAKAN):
        return {"jenis": "Pengeluaran", "kategori": "Makan", "sifat": "Need"}
    if _contains(lower, _KOPI_JAJAN):
        return {"jenis": "Pengeluaran", "kategori": "Jajan", "sifat": "Wants"}
    if is_electronics_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Jajan", "sifat": "Wants"}

    return None


def apply_context_rules(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    """Override jenis/kategori/sifat jika rule konteks cocok."""
    combined = f"{parsed.get('keterangan', '')} {source_text}".strip()
    hit = classify_from_text(combined)
    if hit is None:
        # Juga cek label kategori mentah dari AI.
        kategori = str(parsed.get("kategori", "")).strip().lower()
        if kategori:
            hit = classify_from_text(kategori)
    if hit is None:
        return parsed

    parsed["jenis"] = hit["jenis"]
    parsed["kategori"] = hit["kategori"]
    parsed["sifat"] = hit["sifat"]
    parsed.pop("sub_kategori", None)
    return parsed


def prompt_context_examples() -> str:
    """Contoh untuk system prompt AI — mengurangi salah konteks."""
    return """
Contoh klasifikasi WAJIB diikuti:
- "dapat shopee affiliate 50rb" → Pemasukan / Affiliate / Need
- "terima bunga investasi sebesar 5000" → Pemasukan / Bunga Investasi / Need (BUKAN Saving)
- "dividen BBCA cair 200rb" → Pemasukan / Dividen / Need
- "beli saham BBCA 1jt" → Saving/Investment / Saham / Need
- "nabung reksadana 500rb" → Saving/Investment / Reksadana / Need
- "dividen reinvest" → Saving/Investment / Saham / Need
- "cashback marketplace 20rb" → Pemasukan / Cashback / Need
- "refund tiket 100rb" → Pemasukan / Refund / Need
- "gaji bulan ini 8jt" → Pemasukan / Gaji / Need
- "honor freelance 1.5jt" → Pemasukan / Freelance / Need
- "bayar BPJS 150rb" → Pengeluaran / Asuransi / Need
- "netflix bulanan 54rb" → Pengeluaran / Subscription / Wants
- "skincare serum 120rb" → Pengeluaran / Skincare / Wants
- "makan malam 65.700" → Pengeluaran / Makan / Need
- "grab ke kantor 28rb" → Pengeluaran / Transport / Need
- "bayar sewa kos 1.5jt" → Pengeluaran / Sewa/Tempat Tinggal / Need
- "terima sewa kontrak 2jt" → Pemasukan / Sewa Masuk / Need
- "obat demam 45rb" → Pengeluaran / Kesehatan / Need
- "pulsa 50rb" → Pengeluaran / Komunikasi / Need
- "cicilan motor 900rb" → Pengeluaran / Cicilan / Need
"""
