"""Rule konteks transaksi YFD — deterministik sebelum/sesudah AI.

Prioritas eksklusif (YFD AI Taxonomy v1.3) — yang di atas menang, tidak ditabrak yang bawah:
0) Jenis eksplisit user di awal teks — menang mutlak
1) Likuiditas sosial (4 arah pinjam-meminjam) mengalahkan Pemasukan DAN
   kategori tujuan (kuliah/obat/RS/kerja). Jatuh tempo bukan jenis transaksi.
2) Pemasukan khusus (kecuali skip karena sosial / Pengeluaran eksplisit)
3) DP aset / Saving-Investment / pajak
4) Pengeluaran: bisnis jasa → makeup/skincare → proteksi (asuransi/BPJS saja)
   → hadiah → sosial giving → meeting kerja → traveling → ride/transport
   → gym membership → household durable → kesehatan/pendidikan/tagihan → makan
5) Bucket (sistem, bukan AI): exclude income/tax/sosial → beauty Flexible
   → tumbler/perabot (bukan Protection) → gym Flexible → mapping → legacy

Hadiah vs Likuiditas Sosial: kado/parcel/tip tidak diharapkan kembali.
HP grey area (§2.18) hanya untuk HP user sendiri, bukan hadiah untuk orang lain.
"""

from __future__ import annotations

import re
from typing import Any

from keyword_match import any_keyword

VALID_JENIS = frozenset({
    "Pemasukan",
    "Pengeluaran",
    "Saving/Investment",
    "Kewajiban Pajak",
    "Piutang Keluar",
    "Piutang Masuk",
    "Utang Masuk",
    "Utang Keluar",
})
VALID_SIFAT = frozenset({"Need", "Wants"})

# AI "Lain-lain" / alias = tidak tahu. Bukan keputusan taksonomi.
_DUMP_CATEGORIES = frozenset({
    "",
    "lain-lain",
    "lain lain",
    "lainnya",
    "other",
    "misc",
    "umum",
})


def _is_dump_category(kategori: str) -> bool:
    return str(kategori or "").strip().lower() in _DUMP_CATEGORIES

_EXPLICIT_JENIS_RE = re.compile(
    r"^\s*(pengeluaran|pemasukan|saving(?:\s*/\s*investment)?|kewajiban\s*pajak|"
    r"piutang\s*keluar|piutang\s*masuk|utang\s*masuk|utang\s*keluar|hutang\s*masuk|hutang\s*keluar)\b",
    re.IGNORECASE,
)


def _contains(text: str, phrases: tuple[str, ...]) -> bool:
    return any_keyword(text, phrases)


def _match_any(text: str, patterns: tuple[re.Pattern[str], ...]) -> bool:
    return any(pattern.search(text) for pattern in patterns)


def detect_explicit_jenis(text: str) -> str | None:
    """Jenis yang user tulis eksplisit di awal teks (bukan heuristik kategori)."""
    raw = (text or "").strip()
    if not raw:
        return None
    m = _EXPLICIT_JENIS_RE.match(raw)
    if not m:
        return None
    word = m.group(1).lower().replace(" ", "")
    if word == "pengeluaran":
        return "Pengeluaran"
    if word == "pemasukan":
        return "Pemasukan"
    if word in {"kewajibanpajak", "kewajiban pajak"}:
        return "Kewajiban Pajak"
    if word in {"piutangkeluar", "piutang keluar"}:
        return "Piutang Keluar"
    if word in {"piutangmasuk", "piutang masuk"}:
        return "Piutang Masuk"
    if word in {"utangmasuk", "utang masuk", "hutangmasuk", "hutang masuk"}:
        return "Utang Masuk"
    if word in {"utangkeluar", "utang keluar", "hutangkeluar", "hutang keluar"}:
        return "Utang Keluar"
    return "Saving/Investment"


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
    "komisi gift",
    "tiktok gift",
    "gift tiktok",
    "live gift",
    "komisi live",
    "kreator tiktok",
    "creator tiktok",
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

# Honor/freelance sebagai PEMASUKAN — jangan pakai bare "freelance(r)" saja:
# "bayar jasa freelancer" adalah pengeluaran, bukan income.
_HONOR = (
    "honor",
    "honorarium",
    "honor freelance",
    "honor freelancer",
    "terima freelance",
    "dapat freelance",
    "terima jasa",
    "dapat jasa",
    "dapet jasa",
    "fee freelance",
    "fee project",
    "fee proyek",
    "bayaran project",
    "kontrak kerja lepas",
)

# Sinyal jelas: user MEMBAYAR jasa/freelance (bukan menerima honor).
# Jangan pakai bare "jasa freelance" — "terima jasa freelance" = pemasukan.
_PAYING_FREELANCE = (
    "bayar jasa",
    "bayar freelancer",
    "bayar freelance",
    "bayar ke freelancer",
    "bayar ke freelance",
    "melunasi jasa",
    "pelunasan jasa",
    "melunasi freelancer",
    "pelunasan freelancer",
)

# Arah uang (kata kerja) — lebih penting dari label kategori.
_INCOME_DIRECTION = (
    "terima",
    "dapat",
    "dapet",
    "menerima",
    "uang masuk",
    "dana masuk",
)

_EXPENSE_DIRECTION = (
    "bayar",
    "membayar",
    "pengeluaran",
    "keluarkan",
    "keluarin",
    "melunasi",
    "pelunasan",
    "belanja",
)

_FREELANCE_MARKERS = (
    "freelance",
    "freelancer",
    "freelence",  # typo umum
    "freelanc",
    "honor",
    "honorarium",
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
    ("emergency fund", "Dana darurat"),
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
    "premi asuransi",
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
    "toner",
    "makeup",
    "make up",
    "make-up",
    "lipstik",
    "lipstick",
    "mascara",
    "foundation",
    "bedak",
    "blush",
    "dandan",
    "lip cream",
    "lipcream",
    "makeup cushion",
    "bb cushion",
    "cc cushion",
    "maybelline",
    "maybeline",
    "cushion",
    "parfum",
    "perfume",
    "facial",
    "pelembab",
    "lotion wajah",
    "spa",
    "potong rambut",
    "cukur rambut",
    "cukur",
    "salon rambut",
    "salon kecantikan",
    "creambath",
    "cream bath",
)

_PRINTER_SUPPLIES = (
    "printer",
    "fotocopy",
    "fotokopi",
    "tinta printer",
    "toner printer",
)

_PERSONAL_DRINKWARE = (
    "tumbler",
    "thumbler",
    "termos",
)
_HOUSEHOLD_DURABLES = (
    "rice cooker",
    "penanak nasi",
    "kulkas",
    "mesin cuci",
    "bedcover",
    "gorden",
    "sprei",
    "perabot",
)

_RIDE_SUBSCRIPTION = (
    "subscription grab",
    "grab subscription",
    "langganan grab",
    "grab unlimited",
    "grabunlimited",
    "paket hemat grab",
    "grab paket hemat",
    "grabhemat",
    "subscription gojek",
    "gojek subscription",
    "langganan gojek",
    "gojek unlimited",
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
    "capcut",
    "capcut pro",
)

_WORK_SOFTWARE_CONTEXT = (
    "untuk kerja",
    "kebutuhan kerja",
    "buat kerja",
    "pekerjaan",
    "edit video",
    "video editing",
    "desain kerja",
    "konten kerja",
    "proyek",
    "project",
    "bisnis",
    "usaha",
)


def is_work_software_expense(text: str) -> bool:
    lower = text.lower()
    return _contains(lower, _SUBSCRIPTION) and _contains(lower, _WORK_SOFTWARE_CONTEXT)

_TRANSPORT_PATTERNS = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
        r"\bojek\b",
        # grab ojek/car — jangan cocokkan grabfood / grabmart
        r"\bgrabbike\b",
        r"\bgrabcar\b",
        r"\bgrab\s*bike\b",
        r"\bgrab\s*car\b",
        r"\bgrab(?!food|mart)\b",
        r"\bgojek\b",
        r"\bgojek\s*bike\b",
        r"\bgo\s*ride\b",
        r"\bmaxim\b",
        r"\bangkot\b",
        r"\bbensin\b",
        r"\bpertamax\b",
        r"\bpertalite\b",
        r"\bsolar\b",
        r"\btol\b",
        r"\bparkir\b",
        r"\btransport\b",
        r"\bkereta\b",
        r"\bkrl\b",
        r"\bmrt\b",
        r"\blrt\b",
        r"\btiket\s+pesawat\b",
        r"\bboarding\b",
    )
)

_FOOD_DELIVERY = (
    "grabfood",
    "grab food",
    "grabmart",
    "grab mart",
    "gofood",
    "go food",
    "gojek food",
    "shopeefood",
    "shopee food",
    "foodpanda",
    "maxim food",
)

_SERVIS_KENDARAAN = (
    "servis",
    "bengkel",
    "ganti oli",
    "ban motor",
    "ban mobil",
)

_LISTRIK = (
    "listrik",
    "pln",
    "token listrik",
    "token pln",
    "deterjen",
    "detergen",
    "elpiji",
    "gas elpiji",
    "gas lpg",
    "tabung gas",
)

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

# Air minum / galon / merek — kebutuhan hidup (bukan PDAM, bukan jajan).
_AIR_MINUM_PATTERNS = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
        r"\bair\s+minum\b",
        r"\bair\s+mineral\b",
        r"\bair\s+galon\b",
        r"\bgalon\s+air\b",
        r"\bbeli\s+galon\b",
        r"\baqua\b",
        r"\ble\s*minerale\b",
        r"\bpristine\b",
        r"\bcleo\b",
        r"\bnestle\s+pure\s*life\b",
        r"\bcrystalin[e]?\b",
        r"\bequil\b",
        r"\bair\s+club\b",
        r"\bair\s+vit\b",
        r"\bair\s+ades\b",
        r"\bbeli\s+vit\b",
        r"\bbeli\s+ades\b",
    )
)

_KEBUTUHAN_HIDUP = (
    "kebutuhan hidup",
    "kebutuhan sehari",
    "kebutuhan harian",
    "sembako",
    "kebutuhan pokok",
    "bahan pokok",
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
    "ngopi",
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
    "kue",
    "cake",
    "dessert",
    "donat",
    "croissant",
)

_SOCIAL = (
    "hadiah",
    "kado",
    "parcel",
    "souvenir",
    "amplop",
    "ultah",
    "ulang tahun",
    "sedekah",
    "persembahan",
    "ibadah",
    "donasi",
    "zakat",
    "infaq",
    "infak",
    "wakaf",
    "qurban",
    "kurban",
    "hewan qurban",
    "hewan kurban",
    "perpuluhan",
)

_FAMILY_SUPPORT_RE = (
    re.compile(
        r"\b(?:transfer|kirim|bantu|kasih|kasi)\s+(?:ke|kepada|sama)\s+"
        r"(?:mama|papa|ibu|ayah|bunda|mami|papi|adik|kakak|ortu|nenek|kakek|"
        r"keluarga|orang\s+tua)\b",
        re.IGNORECASE,
    ),
    re.compile(
        r"\bbantu\s+(?:mama|papa|ibu|ayah|bunda|adik|kakak|ortu|keluarga|orang\s+tua)\b",
        re.IGNORECASE,
    ),
)

_GIFT_MARKERS = (
    "hadiah",
    "kado",
    "parcel",
    "souvenir",
    "gift",
)

_HIBURAN = (
    "konser",
    "tiket konser",
    "bioskop",
    "nonton",
    "movie",
    "netflix",
    "spotify",
    "gaming",
    "game",
    "playstation",
    "hiburan",
)

_OBLIGATION_SOCIAL = (
    "sedekah",
    "zakat",
    "perpuluhan",
    "infaq",
    "infak",
    "wakaf",
    "persembahan",
    "ibadah",
    "qurban",
    "kurban",
)

_BUSINESS_BUILDING = (
    "konten bisnis",
    "take konten",
    "konsumsi meeting",
    "meeting bisnis",
    "rapat bisnis",
    "proyek yfd",
    "bisnis yfd",
    "untuk bisnis",
    "untuk usaha",
    "modal usaha",
    "website bisnis",
    "website usaha",
    "kebutuhan bisnis",
    "bayar domain",
    "domain website",
    "hosting",
    # Ekspektasi klien: makan/ngopi + meeting kerja → Bisnis & Karir / Future Building
    "meeting kerja",
    "meeting kerjaan",
    "rapat kerja",
    "meeting klien",
    "rapat klien",
    "meeting untuk kerja",
    "rapat untuk kerja",
    "ngopi meeting",
    "kopi meeting",
    "starbucks meeting",
    "makan meeting",
)

_WORK_MEETING_RE = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
        r"\bmeeting\s+kerja(?:an)?\b",
        r"\brapat\s+kerja\b",
        r"\bmeeting\s+klien\b",
        r"\brapat\s+klien\b",
        r"\bmeeting\s+untuk\s+kerja\b",
        r"\brapat\s+untuk\s+kerja\b",
        # "sambil/sekalian meeting … kerja(an|klien|bisnis)"
        r"\b(?:sekalian|sambil|saat|lagi|pas)\s+meeting\b.{0,40}\b(?:kerja(?:an)?|klien|bisnis)\b",
        r"\b(?:kerja(?:an)?|klien|bisnis)\b.{0,40}\b(?:sekalian|sambil|saat|lagi|pas)\s+meeting\b",
    )
)

_TIP_PATTERNS = tuple(
    re.compile(p, re.IGNORECASE)
    for p in (
        r"\btips?\b",
        r"\bmemberikan\s+tips?\b",
        r"\bkasih\s+tips?\b",
        r"\bberi\s+tips?\b",
        r"\buat\s+tips?\b",
        r"\buang\s+rokok\b",
        r"\buang\s+terima\s*kasih\b",
    )
)

# §2.5 kebersihan dasar → Essential Need. Sunscreen tetap di _SKINCARE (Flexible).
_KEBERSIHAN_DASAR = (
    "sabun",
    "sabun mandi",
    "shampo",
    "shampoo",
    "pasta gigi",
    "odol",
    "softex",
    "pembalut",
    "deodoran",
    "deodorant",
    "handbody",
    "hand body",
    "hand & body",
    "hand and body",
    "body lotion",
    "lotion tubuh",
    "lotion badan",
    "lotion tangan",
    "lotion",
    "sikat gigi",
    "cotton bud",
    "cottonbuds",
    "cotton stick",
    "hand sanitizer",
    "handsanitizer",
    "sanitizer",
    "nivea",
    "vaseline",
    "vaselin",
    "body wash",
    "bodywash",
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
    "fisioterapi",
    "fisio",
    "rehab",
    "rehabilitasi",
    "hydrotherapy",
    "minoxidil",
    "masker",
    "rawat gigi",
    "dokter gigi",
    "cabut gigi",
    "tambal gigi",
) + _KEBERSIHAN_DASAR

_FISIOTERAPI_RESEP = (
    "resep dokter",
    "diresepkan",
    "resep dari dokter",
    "anjuran dokter",
    "pasca operasi",
    "pasca-operasi",
    "rehab medis",
)

_FISIOTERAPI_MARKERS = (
    "fisioterapi",
    "fisio",
    "rehab",
    "rehabilitasi",
    "hydrotherapy",
)

_GYM_LIFESTYLE = (
    "gym",
    "fitness",
    "olahraga",
    "personal training",
    "personal trainer",
    "kebugaran",
    "membership gym",
    "kelas yoga",
    "pilates",
    "crossfit",
    "yoga",
    "tenis",
    "padel",
    "coaching tenis",
    "coaching padel",
    "les tenis",
    "les padel",
)

_PENDIDIKAN = (
    "spp",
    "uang sekolah",
    "kursus",
    "les",
    "bimbel",
    "kuliah",
    "uks",
    "ukt",
    "buku pelajaran",
    # Pengembangan diri — selalu kategori Pendidikan (bucket Future Building via mapping)
    "seminar",
    "workshop",
    "sertifikasi",
    "conference",
    "konferensi",
    "pengembangan diri",
    "self development",
    "self-development",
    "iuran organisasi",
    "keanggotaan profesi",
    "asosiasi profesi",
    "iuran idi",
    "bayar idi",
    "iuran idai",
    "public speaking",
    "coaching karier",
    "coaching karir",
)

_TRAVELING = (
    "liburan",
    "staycation",
    "hotel",
    "penginapan",
    "wisata",
    "traveling",
    "travelling",
    "tour",
    "vacation",
    "resort",
    "villa liburan",
    "tiket wisata",
)

# Gaji ART / Driver / Babysitter → Tempat Tinggal (grey area sifat)
_HOUSEHOLD_HELP = (
    "gaji art",
    "bayar art",
    "gaji pembantu",
    "bayar pembantu",
    "pembantu rumah",
    "pembantu rumah tangga",
    "babysitter",
    "baby sitter",
    "gaji babysitter",
    "bayar babysitter",
    "pengasuh anak",
    "gaji pengasuh",
    "sopir pribadi",
    "driver pribadi",
    "gaji driver",
    "bayar driver",
    "gaji sopir",
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
    "tarik tunai",
    "tariktunai",
)

# Biaya admin bank/transfer → Komunikasi (alias taxonomy: biaya admin / komunikasi & administrasi).
# Jangan cocokkan "admin" generik (admin kantor, dll).
_BANK_ADMIN_FEE = (
    "admin bank",
    "biaya admin",
    "admin transfer",
    "biaya transfer",
    "admin mbanking",
    "admin m-banking",
    "admin m banking",
    "admin atm",
    "biaya admin bank",
    "biaya admin transfer",
    "admin bca",
    "admin mandiri",
    "admin bri",
    "admin bni",
    "admin jenius",
    "admin seabank",
    "admin jago",
    "charge transfer",
    "biaya rtgs",
    "biaya kliring",
    "biaya antar bank",
)

_LAUNDRY = (
    "laundry",
    "cuci baju",
    "cuci kiloan",
    "setrika kiloan",
    "dry clean",
    "dryclean",
)

_PAKAIAN = (
    "fashion",
    "baju",
    "celana",
    "sepatu",
    "sandal",
    "tas ",
    "aksesoris",
    "seragam",
    "kaos",
    "jaket",
    "hoodie",
    "dress",
    "rok",
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
    "pinjol",
    "pinjaman online",
    "pinjaman tunai",
)

_DENDA_SANKSI = (
    "tilang",
    "denda keterlambatan",
    "denda cicilan",
    "denda kartu kredit",
    "sanksi keterlambatan",
    "denda non pajak",
)

_DP_ASET = (
    "dp rumah",
    "dp properti",
    "dp kpr",
    "dp ruko",
    "uang muka rumah",
    "uang muka properti",
    "dp mobil",
    "dp motor",
    "dp kendaraan",
    "uang muka mobil",
    "uang muka motor",
)

_DP_LIFESTYLE_HINTS = (
    "kendaraan kedua",
    "mobil kedua",
    "motor kedua",
    "upgrade",
    "gaya hidup",
    "lifestyle",
    "hobi",
)

_PPH_KEWAJIBAN = (
    "pph 21",
    "pph21",
    "pph 25",
    "pph25",
    "pph 29",
    "pph29",
    "pph 28a",
    "pph28a",
    "angsuran pajak",
    "cicilan pajak",
    "kurang bayar pajak",
    "bayar pajak spt",
    "restitusi pajak",
    "lebih bayar pajak",
    "denda pajak",
    "sanksi pajak",
)

_PBB = (
    "pbb",
    "pajak bumi",
    "pajak bangunan",
)

_PBB_INVESTASI = (
    "pbb investasi",
    "pbb disewakan",
    "pbb dikoskan",
    "disewakan",
    "dikoskan",
    "properti investasi",
)

_PAJAK_KENDARAAN = (
    "stnk",
    "pkb",
    "samsat",
    "pajak kendaraan",
    "pajak mobil",
    "pajak motor",
)

# Subjek orang pertama vs nama orang lain (untuk arah cashflow).
_SELF = r"(?:saya|aku|gue|gw|kami|kita)"
_NOT_SELF = r"(?!saya\b|aku\b|gue\b|gw\b|kami\b|kita\b)"
_PERSON = r"([a-zà-ÿ][\wà-ÿ.\-]{1,40})"
_PREP_FROM = r"(?:dari|ke|kek|kepada|sama|dengan|pada)"
# Bukan nama orang: tujuan pinjaman, preposisi, nominal, benda yang dipinjam.
_NOT_LENDER_NAME = (
    r"(?:untuk|buat|karena|kebutuhan|bayar|biaya|kuliah|sekolah|obat|"
    r"rs|rumahsakit|kerja|bisnis|usaha|modal|kepentingan|"
    r"uang|duit|dana|pinjaman|rupiah|rp|"
    r"laptop|hp|charger|buku|mobil|motor|rumah|kos|tiket|makanan|makan|"
    r"baju|sepatu|makeup|skincare|"
    r"di|yang|dulu|dong|aja|lah|sih|nih|ya|bulan|depan|nanti|lagi|"
    r"dari|ke|kek|kepada|sama|dengan|pada)\b"
)
_MONEY = r"(?:duit|uang|dana|pinjaman)?"
_MONEY_WORD = r"(?:duit|uang|dana)"
_AMOUNT_GAP = r"(?:(?:rp\s*)?\d[\d.,]*\s*(?:rb|ribu|k|jt|juta)?\s+)?"

# Likuiditas Sosial §5 — uang KELUAR dipinjamkan ke orang lain
# JANGAN masukkan "utang ke" / "pinjam ke" (itu = saya berhutang).
# JANGAN pakai sinyal pelunasan ("kembalikan uang") — itu Piutang Masuk.
_PIUTANG_KELUAR = (
    "piutang keluar",
    "di pinjam",
    "dipinjam",
    "pinjamin",
    "pinjami",
    "pinjamkan",
    "pinjemin",
    "minjemin",
    "minjamin",
    "minjami",
    "minjamkan",
    "ngutangin",
    "ngutangi",
    "ngutangin",
    "utangin",
    "hutangin",
    "talang dulu",
    "talangin",
    "nombokin",
    "nombok dulu",
    "bayarin dulu",
    "bayarkan dulu",
    "lunasin dulu buat",
    "aku yang bayar dulu",
    "saya yang bayar dulu",
    "aku yang cover",
    "nanti dia ganti",
    "nanti diganti",
    "nanti dia bayar balik",
    "nanti bayar balik",
    "nanti dia balikin",
    "sementara aku yang cover",
    "kasih pinjam",
    "kasi pinjam",
    "kasih utang",
    "kasi utang",
    "aku pinjamin",
    "saya pinjamin",
    "aku pinjamkan",
    "saya pinjamkan",
)

_PIUTANG_KELUAR_PATTERNS = (
    re.compile(rf"\b{_SELF}\s+(?:yang\s+)?(?:pinjamkan|pinjamin|pinjami|pinjemin|minjamin)\b", re.IGNORECASE),
    re.compile(rf"\b{_SELF}\s+(?:ngutangin|ngutangi|utangin|hutangin|talangin|nombokin)\b", re.IGNORECASE),
    # "di pinjam catherine" / "dipinjam catherine" = mereka pinjam ke kita.
    # Jangan cocokkan dipinjami/dipinjemin (itu kita yang dipinjami = Utang Masuk).
    re.compile(rf"\bdi\s+pinjam\s+{_PERSON}\b", re.IGNORECASE),
    re.compile(rf"\bdipinjam(?!i|in)\s+{_PERSON}\b", re.IGNORECASE),
    re.compile(rf"\b(?:pinjam(?:i|in|kan)|pinjemin|minjamin|minjemin)\s+{_PERSON}\b", re.IGNORECASE),
    re.compile(rf"\bdipinjamkan\s+{_PERSON}\b", re.IGNORECASE),
    re.compile(r"\b(?:kasih|kasi|kasihin)\s+(?:pinjam|utang|hutang)\b", re.IGNORECASE),
    re.compile(
        r"\b(?:transfer|kirim|bantu)\s+(?:ke|kepada|sama)\s+"
        + _PERSON
        + r"\b.*\b(?:nanti|ganti|balik|pinjam)",
        re.IGNORECASE,
    ),
)

# Utang sosial (KBBI) — terima pinjaman (cash bertambah, bukan Pemasukan)
# Jangan pakai "saya pinjam" mentah — bentrok dengan "saya pinjamkan".
_UTANG_MASUK = (
    "utang masuk",
    "hutang masuk",
    "ngutang dari",
    "ngutang sama",
    "ngutang ke",
    "utang dari",
    "utang sama",
    "hutang dari",
    "hutang sama",
    "pinjam dari",
    "pinjam kek",
    "pinjam sama",
    "pinjam duit ke",
    "pinjam uang ke",
    "minjem dari",
    "minjem kek",
    "minjem sama",
    "minjam dari",
    "minjam sama",
    "terima pinjaman",
    "dapat pinjaman",
    "dapet pinjaman",
    "nerima pinjaman",
    "kasih utang sama saya",
    "aku yang utang",
    "saya yang utang",
    "aku yang hutang",
    "saya yang berhutang",
)

_UTANG_MASUK_PATTERNS = (
    re.compile(
        rf"\b{_SELF}\s+pinjam(?!kan|i|in)\s+(?:{_MONEY}\s+)?{_AMOUNT_GAP}{_PREP_FROM}\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b{_SELF}\s+(?:minjem|minjam|utang|ngutang|hutang|berhutang|berutang)\s+{_PREP_FROM}\b",
        re.IGNORECASE,
    ),
    # "pinjam ke mama" / "pinjem duit ke mama" = kita yang berhutang.
    re.compile(
        rf"\bpinjam(?!kan|i|in)\s+(?:(?:duit|uang|dana)\s+)?{_AMOUNT_GAP}{_PREP_FROM}\b",
        re.IGNORECASE,
    ),
    # "saya pinjam uang ayuti" / "pinjam uang ayuti" tanpa ke/dari.
    re.compile(
        rf"\b{_SELF}\s+pinjam(?!kan|i|in)\s+{_MONEY_WORD}\s+"
        rf"(?!{_NOT_LENDER_NAME}){_PERSON}\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\bpinjam(?!kan|i|in)\s+{_MONEY_WORD}\s+"
        rf"(?!{_NOT_LENDER_NAME}){_PERSON}\b",
        re.IGNORECASE,
    ),
    # "saya pinjam ayuti 5jt" / "saya pinjam 5jt ayuti"
    re.compile(
        rf"\b{_SELF}\s+pinjam(?!kan|i|in)\s+{_AMOUNT_GAP}(?!{_NOT_LENDER_NAME}){_PERSON}\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:ngutang|minjam|minjem)\s+{_PREP_FROM}\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:utang|hutang)\s+(?:dari|sama|dengan)\b",
        re.IGNORECASE,
    ),
    re.compile(rf"\b{_SELF}\s+(?:yang\s+)?(?:berhutang|berutang|ngutang)\b", re.IGNORECASE),
    re.compile(r"\b(?:terima|nerima|dapat|dapet)\s+(?:uang\s+)?pinjaman\b", re.IGNORECASE),
    re.compile(rf"\b(?:dipinjami|dipinjemin)\s+(?:oleh\s+)?{_PERSON}\b", re.IGNORECASE),
    re.compile(rf"\b{_SELF}\s+dipinjami\b", re.IGNORECASE),
    re.compile(r"\b(?:pinjam|minjam|minjem)\s+kek\b", re.IGNORECASE),
    # "ayuti kasih pinjam" = mereka yang meminjami kita.
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+(?:kasih|kasi|kasihin)\s+pinjam\b",
        re.IGNORECASE,
    ),
)

# Utang sosial — bayar balik ke orang (cash turun; bukan Cicilan bank/pinjol)
_UTANG_KELUAR = (
    "utang keluar",
    "hutang keluar",
    "bayar utang",
    "bayar hutang",
    "lunasi utang",
    "lunasi hutang",
    "melunasi utang",
    "melunasi hutang",
    "cicil utang",
    "cicil hutang",
    "nyicil utang",
    "nyicil hutang",
    "lunasin utang",
    "lunasin hutang",
    "balikin utang ke",
    "balikin hutang ke",
    "kembalikan hutang ke",
    "kembalikan utang ke",
    "bayar balik utang",
    "bayar balik hutang",
    "nyicil hutang ke",
    "cicil utang ke",
    "saya balikin",
    "aku balikin",
    "saya kembalikan",
    "aku kembalikan",
    "saya mengembalikan",
)

_UTANG_KELUAR_PATTERNS = (
    re.compile(
        rf"\b{_SELF}\s+(?:mengembalikan|kembalikan|balikin|ngembalikan|ngembaliin)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:mengembalikan|kembalikan|balikin)\s+(?:uang|duit|dana)\b.*\b(?:yang\s+)?{_SELF}\s+pinjam\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\byang\s+{_SELF}\s+pinjam\b.*\b(?:mengembalikan|kembalikan|balikin)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:bayar|lunasi|lunasin|balikin|kembalikan|mengembalikan|nyicil|cicil)\s+"
        rf"(?:utang|hutang|pinjaman)\s+(?:ke|kepada|sama)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b{_SELF}\s+(?:bayar|lunasi|lunasin)\s+(?:utang|hutang|pinjaman)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\bkembalikan\s+(?:uang|duit|dana).*\byang\s+{_SELF}\s+pinjam\b",
        re.IGNORECASE,
    ),
)

# "utang ke X" tanpa sinyal pinjamkan ATAU terima/bayar → AMBIGU
# "pinjam ke" / "ngutang ke" = kita berhutang (lihat _UTANG_MASUK_PATTERNS).
_AMBIGUOUS_UTANG_PATTERNS = (
    re.compile(r"\butang\s+ke\b", re.IGNORECASE),
    re.compile(r"\bhutang\s+ke\b", re.IGNORECASE),
    re.compile(r"\bberhutang\s+(?:ke|pada|sama)\b", re.IGNORECASE),
    re.compile(r"\bberutang\s+(?:ke|pada|sama)\b", re.IGNORECASE),
)

# Piutang Masuk — orang lain melunasi yang sebelumnya kita pinjamkan
_PIUTANG_MASUK = (
    "piutang masuk",
    "dibayar balik",
    "dibayarin balik",
    "bayar balik",
    "transfer balik",
    "tf balik",
    "ganti uang dari",
    "ganti duit dari",
    "cicil hutang dari",
    "ngembalikan pinjaman",
    "mengembalikan pinjaman",
    "mengembalikan uang",
    "kembalikan uang",
    "kembalikan pinjaman",
    "balikin pinjaman",
    "balikin uang",
    "balikin duit",
    "ngembaliin uang",
    "ngembaliin duit",
    "pelunasan piutang",
    "terima pelunasan",
    "uang dikembalikan",
    "uang dibalikin",
    "duit dibalikin",
    "sudah dikembalikan",
    "udah dikembalikan",
    "udah dibalikin",
    "sudah balik",
    "udah balik",
    "udah lunas",
    "sudah lunas dari",
)

_PIUTANG_MASUK_PATTERNS = (
    re.compile(r"\b(?:meng)?kembalikan\s+(?:uang|duit|dana|pinjaman|piutang)\b", re.IGNORECASE),
    re.compile(r"\b(?:balikin|ngembaliin|ngembalikan)\s+(?:uang|duit|dana|pinjaman|piutang)\b", re.IGNORECASE),
    # Nama orang + kembalikan — jangan cocokkan "saya/aku kembalikan" (itu utang kita).
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+(?:meng)?kembalikan\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+(?:balikin|ngembaliin|ngembalikan)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+(?:bayar|bayarin)\s+balik\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+(?:lunasi|lunasin|melunasi)\s+(?:hutang|utang|pinjaman|piutang)\b",
        re.IGNORECASE,
    ),
    re.compile(r"\bdikembalikan\b", re.IGNORECASE),
    re.compile(r"\bdibalikin\b", re.IGNORECASE),
    re.compile(r"\bdibayar\s+balik\b", re.IGNORECASE),
    # "Ayuti balikin hutang" = orang lain lunasi ke kita (Piutang Masuk).
    re.compile(
        rf"\b{_NOT_SELF}{_PERSON}\s+"
        r"(?:balikin|kembalikan|mengembalikan|ngembaliin)\s+(?:hutang|utang|pinjaman|uang|duit)\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:dibalikin|dikembalikan|dibayar\s+balik)\s+(?:oleh\s+|dari\s+)?(?!ke\b){_PERSON}\b",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:uang|duit|pinjaman)\s+(?:yang\s+)?(?:di)?pinjam(?:kan)?\s+sebelumnya\b",
        re.IGNORECASE,
    ),
    re.compile(
        rf"\b(?:transfer|tf|kirim)\s+balik\s+(?:dari\s+)?{_PERSON}\b",
        re.IGNORECASE,
    ),
)

_LEGAL_EVENT = (
    "notaris",
    "balik nama",
    "ajb",
    "mahar",
    "pernikahan",
    "resepsi",
    "duka",
    "pemakaman",
    "tahlilan",
    "biaya legal",
)

_PAJAK = (
    "pajak",
    "pph",
    "ppn",
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
        r"\bearbuds?\b",
        r"\bheadset\b",
        r"\bairpods?\b",
        r"\bpowerbank\b",
        r"\bpower\s*bank\b",
        r"\bmouse\b",
        r"\bkeyboard\b",
        r"\bspeaker\b",
        r"\bmonitor\b",
        r"\bssd\b",
        r"\bflashdisk\b",
        r"\bharddisk\b",
        r"\bsmartwatch\b",
        r"\bkabel\s*data\b",
    )
)

_ELEKTRONIK_NEED_HINTS = (
    "rusak",
    "pecah",
    "mati",
    "ganti hp",
    "ganti handphone",
    "untuk kerja",
    "laptop kerja",
    "laptop produktif",
    "alat kerja",
    "modal kerja",
    "kebutuhan kerja",
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


def is_drinking_water_expense(text: str) -> bool:
    """Beli aqua/air minum/galon — Essential Living, bukan jajan & bukan tagihan PDAM."""
    if is_water_expense(text):
        return False
    return _match_any(text, _AIR_MINUM_PATTERNS)


def is_bank_admin_fee(text: str) -> bool:
    """Biaya admin bank/ATM/transfer — kategori Komunikasi (Need / Essential)."""
    return _contains(text, _BANK_ADMIN_FEE)


def is_traveling_expense(text: str) -> bool:
    """Liburan/hotel/staycation/wisata → Traveling / Wants."""
    return _contains(text, _TRAVELING)


def is_household_help_expense(text: str) -> bool:
    """Gaji ART/driver/babysitter → Tempat Tinggal (grey area sifat)."""
    return _contains(text, _HOUSEHOLD_HELP)


def is_fisioterapi_expense(text: str) -> bool:
    return _contains(text, _FISIOTERAPI_MARKERS)


def fisioterapi_sifat(text: str) -> str:
    """Resep dokter → Need; tanpa konteks resep tetap Need default (klarifikasi terpisah)."""
    lower = text.lower()
    if _contains(lower, _FISIOTERAPI_RESEP):
        return "Need"
    if any(k in lower for k in ("tanpa resep", "pilihan sendiri", "sendiri aja", "healing")):
        return "Wants"
    return "Need"


def is_self_development_expense(text: str) -> bool:
    """Seminar/workshop/sertifikasi/iuran organisasi → Pendidikan (bucket Future Building)."""
    markers = (
        "seminar",
        "workshop",
        "sertifikasi",
        "conference",
        "konferensi",
        "pengembangan diri",
        "self development",
        "self-development",
        "iuran organisasi",
        "keanggotaan profesi",
        "asosiasi profesi",
        "iuran idi",
        "bayar idi",
        "iuran idai",
        "public speaking",
        "coaching karier",
        "coaching karir",
        "psychology of money",
        "buku finansial",
        "buku financial",
    )
    return _contains(text, markers)


def has_essential_living_intent(text: str) -> bool:
    return _contains(text, _KEBUTUHAN_HIDUP)


def is_food_delivery(text: str) -> bool:
    return _contains(text, _FOOD_DELIVERY)


def is_family_support_expense(text: str) -> bool:
    """Transfer/bantu keluarga tanpa janji kembali → Sosial, bukan piutang."""
    lower = _normalize_typos(text)
    if (
        is_lending_to_others(lower)
        or is_social_debt_borrow(lower)
        or is_social_debt_repay(lower)
        or is_receivable_repay(lower)
    ):
        return False
    return _match_any(lower, _FAMILY_SUPPORT_RE)


def is_social_giving(text: str) -> bool:
    """Donasi, sedekah, tip driver/ojek, amplop, bantu keluarga — bukan makan/transport."""
    lower = text.lower()
    if _contains(lower, _SOCIAL):
        return True
    if is_family_support_expense(lower):
        return True
    return _match_any(lower, _TIP_PATTERNS)


def is_discretionary_social_giving(text: str) -> bool:
    """Hadiah/tip/donasi spontan — Wants & cenderung impulsif (bukan zakat/sedekah wajib)."""
    lower = text.lower()
    if _contains(lower, _OBLIGATION_SOCIAL):
        return False
    if _contains(lower, _GIFT_MARKERS):
        return True
    if _match_any(lower, _TIP_PATTERNS):
        return True
    if "donasi" in lower:
        return True
    return False


def is_business_building_expense(text: str) -> bool:
    """Biaya bisnis/konten ATAU konsumsi meeting kerja → Future Building."""
    lower = text.lower()
    if _contains(lower, _BUSINESS_BUILDING):
        return True
    return _match_any(lower, _WORK_MEETING_RE)


def is_ride_subscription(text: str) -> bool:
    """Langganan ojek (Grab Unlimited / paket hemat) — bukan ride sekali jalan."""
    return _contains(text.lower(), _RIDE_SUBSCRIPTION)


def is_personal_drinkware(text: str) -> bool:
    """Tumbler/termos = item pribadi, bukan kos/listrik (bukan Tempat Tinggal)."""
    return _contains(text.lower(), _PERSONAL_DRINKWARE)


def is_household_durable(text: str) -> bool:
    """Perabot rumah ATAU tumbler — grey area rusak vs koleksi."""
    return is_personal_drinkware(text) or _contains(text.lower(), _HOUSEHOLD_DURABLES)


def household_durable_sifat(text: str) -> str:
    lower = text.lower()
    if _contains(lower, ("koleksi", "ikuti tren", "ikut tren", "upgrade", "fomo", "tambah koleksi")):
        return "Wants"
    if _contains(
        lower,
        (
            "rusak",
            "pecah",
            "bocor",
            "tidak layak",
            "belum memadai",
            "ganti yang",
            "ganti yg",
            "mengganti",
            "sebelumnya rusak",
        ),
    ):
        return "Need"
    return "Wants"


def is_transport_ride(text: str) -> bool:
    """Ojek/bensin/dll — bukan GrabFood/GoFood / langganan paket."""
    if is_food_delivery(text):
        return False
    if is_ride_subscription(text):
        return False
    return _match_any(text, _TRANSPORT_PATTERNS)


def is_dp_asset_payment(text: str) -> bool:
    return _contains(text.lower(), _DP_ASET)


def is_dp_lifestyle_vehicle(text: str) -> bool:
    lower = text.lower()
    if not any(k in lower for k in ("dp mobil", "dp motor", "dp kendaraan", "uang muka mobil", "uang muka motor")):
        return False
    return _contains(lower, _DP_LIFESTYLE_HINTS)


def is_pinjol_or_cash_loan(text: str) -> bool:
    lower = text.lower()
    return any(k in lower for k in ("pinjol", "pinjaman online", "pinjaman tunai"))


def _source_without_klarifikasi(text: str) -> str:
    idx = (text or "").lower().find("klarifikasi user:")
    return text[:idx] if idx >= 0 else (text or "")


def _klarifikasi_only(text: str) -> str:
    idx = (text or "").lower().find("klarifikasi user:")
    return text[idx:] if idx >= 0 else ""


def _first_pattern_hit(lower: str, patterns: tuple[re.Pattern[str], ...]) -> int | None:
    best: int | None = None
    for rx in patterns:
        m = rx.search(lower)
        if m and (best is None or m.start() < best):
            best = m.start()
    return best


def _first_phrase_hit(lower: str, phrases: tuple[str, ...]) -> int | None:
    best: int | None = None
    for phrase in phrases:
        token = phrase.lower().strip()
        if not token:
            continue
        i = lower.find(token)
        if i >= 0 and (best is None or i < best):
            # "dipinjam" jangan menelan "dipinjami" (Utang Masuk).
            if token == "dipinjam" and re.match(r"dipinjami|dipinjemin", lower[i:]):
                continue
            best = i
    return best


def _earliest_social_jenis(lower: str) -> str | None:
    """Jenis likuiditas sosial dari sinyal paling awal. Satu teks = satu arah."""
    candidates: list[tuple[int, int, str]] = []
    groups = (
        ("Piutang Masuk", _PIUTANG_MASUK_PATTERNS, _PIUTANG_MASUK, 0),
        ("Utang Keluar", _UTANG_KELUAR_PATTERNS, _UTANG_KELUAR, 1),
        ("Utang Masuk", _UTANG_MASUK_PATTERNS, _UTANG_MASUK, 2),
        ("Piutang Keluar", _PIUTANG_KELUAR_PATTERNS, _PIUTANG_KELUAR, 3),
    )
    for jenis, patterns, phrases, tie in groups:
        pos = _first_pattern_hit(lower, patterns)
        phrase_pos = _first_phrase_hit(lower, phrases)
        if pos is None or (phrase_pos is not None and phrase_pos < pos):
            pos = phrase_pos
        if pos is not None:
            candidates.append((pos, tie, jenis))
    if not candidates:
        return None
    return min(candidates)[2]


_LEND_CLAR = (
    "pinjamkan",
    "meminjamkan",
    "piutang",
    "nanti balik",
    "nanti kembali",
    "nanti diganti",
    "dia yang utang",
    "dia yang hutang",
    "saya yang pinjamin",
    "saya pinjamkan",
    "aku pinjamkan",
    "aku pinjamin",
)
_BORROW_CLAR = (
    "berhutang",
    "berutang",
    "saya yang utang",
    "saya yang hutang",
    "utang saya",
    "hutang saya",
    "menerima pinjaman",
    "terima pinjaman",
    "pinjam dari",
    "utang masuk",
    "hutang masuk",
    "saya yang berhutang",
    "aku yang utang",
    "aku yang hutang",
)


def _resolve_ambiguous_utang_ke(source: str, clar: str) -> str | None:
    if not any(p.search(source) for p in _AMBIGUOUS_UTANG_PATTERNS):
        return None
    if any(k in clar for k in _LEND_CLAR):
        return "Piutang Keluar"
    if any(k in clar for k in _BORROW_CLAR):
        return "Utang Masuk"
    return None


def detect_social_liquidity_jenis(text: str) -> str | None:
    """Satu arah likuiditas sosial, atau None.

    Kata kerja paling awal yang menang. Tujuan (kuliah/obat/RS) dan jatuh tempo
    (kembali bulan depan) tidak mengubah jenis. Pinjol/lembaga = bukan sosial.
    """
    raw = text or ""
    if not raw.strip():
        return None
    if is_pinjol_or_cash_loan(raw):
        return None
    source = _normalize_typos(_source_without_klarifikasi(raw))
    full = _normalize_typos(raw)
    clar = _normalize_typos(_klarifikasi_only(raw))
    hit = _earliest_social_jenis(source)
    if hit:
        return hit
    hit = _earliest_social_jenis(full)
    if hit:
        return hit
    return _resolve_ambiguous_utang_ke(source, clar)


def is_receivable_repay(text: str) -> bool:
    """Pelunasan piutang — Piutang Masuk (orang lain mengembalikan ke kita)."""
    return detect_social_liquidity_jenis(text) == "Piutang Masuk"


def is_lending_to_others(text: str) -> bool:
    """Uang keluar dipinjamkan — Piutang Keluar (§5.1)."""
    return detect_social_liquidity_jenis(text) == "Piutang Keluar"


def is_social_debt_borrow(text: str) -> bool:
    """Terima pinjaman sosial — Utang Masuk (cash bertambah; bukan Pemasukan)."""
    return detect_social_liquidity_jenis(text) == "Utang Masuk"


def is_social_debt_repay(text: str) -> bool:
    """Bayar balik pinjaman sosial — Utang Keluar (bukan Cicilan bank/pinjol)."""
    return detect_social_liquidity_jenis(text) == "Utang Keluar"


def is_explicit_debt_repayment(text: str) -> bool:
    """Alias lama — sekarang = Utang Keluar sosial."""
    return is_social_debt_repay(text)


def is_ambiguous_utang_ke_person(text: str) -> bool:
    """utang ke [nama] tanpa sinyal jelas → wajib klarifikasi.

    "pinjam ke X" / "ngutang sama X" dianggap Utang Masuk (kita yang berhutang).
    "utang ke X" tetap dua makna (meminjamkan vs berhutang).
    """
    lower = _normalize_typos(text)
    if "klarifikasi user:" in lower:
        return False
    if detect_social_liquidity_jenis(text):
        return False
    return any(p.search(lower) for p in _AMBIGUOUS_UTANG_PATTERNS)


def is_personal_debt_to_others(text: str) -> bool:
    """Deprecated alias — gunakan is_social_debt_repay / is_social_debt_borrow."""
    return is_social_debt_repay(text) or is_social_debt_borrow(text)


def is_received_money_gift(text: str) -> bool:
    """Hadiah uang yang DITERIMA = Pemasukan (taxonomy §5 contoh), bukan kategori Hadiah."""
    lower = _normalize_typos(text)
    if _contains(lower, ("beli", "bayar", "belanja", "kasih hadiah", "kasi hadiah", "kasih kado")):
        return False
    if detect_cashflow_direction(text) == "Pengeluaran":
        return False
    return _contains(
        lower,
        (
            "terima hadiah",
            "nerima hadiah",
            "dapat hadiah",
            "dapet hadiah",
            "hadiah uang",
            "uang hadiah",
        ),
    )


def is_gift_expense(text: str) -> bool:
    """Hadiah/kado/parcel yang DIBERI = Pengeluaran Hadiah, bukan pinjaman/utang."""
    lower = _normalize_typos(text)
    if not _contains(lower, _GIFT_MARKERS):
        return False
    if is_received_money_gift(lower):
        return False
    # Komisi gift TikTok / affiliate = Pemasukan, bukan kado yang diberi.
    if is_affiliate_income(lower):
        return False
    if _contains(
        lower,
        (
            "komisi gift",
            "tiktok gift",
            "gift tiktok",
            "live gift",
            "komisi live",
            "kreator",
            "creator",
        ),
    ):
        return False
    if (
        is_lending_to_others(lower)
        or is_social_debt_borrow(lower)
        or is_social_debt_repay(lower)
        or is_receivable_repay(lower)
    ):
        return False
    return True


def is_beauty_care_expense(text: str) -> bool:
    """Makeup/skincare/dandan = Kesehatan Wants, bukan likuiditas sosial / grey area."""
    lower = _normalize_typos(text)
    if _contains(lower, _PRINTER_SUPPLIES):
        return False
    if not _contains(lower, _SKINCARE):
        return False
    if is_gift_expense(lower):
        return False
    if (
        is_lending_to_others(lower)
        or is_social_debt_borrow(lower)
        or is_social_debt_repay(lower)
        or is_receivable_repay(lower)
    ):
        return False
    return True


def is_denda_sanksi(text: str) -> bool:
    lower = text.lower()
    if _contains(lower, ("denda pajak", "sanksi pajak")):
        return False
    return _contains(lower, _DENDA_SANKSI) or (
        "denda" in lower and "pajak" not in lower
    )


_LEISURE_TRANSPORT_DEST = (
    "ke gym",
    "ke fitness",
    "ke cafe",
    "ke mall",
    "ke bioskop",
    "ke konser",
    "ke yoga",
    "ke pilates",
    "ke spa",
    "nongkrong",
    "healing",
    "wisata",
    "staycation",
    "ke will fitness",
    "dari kos ke gym",
    "dari cafe",
)

_BUSINESS_TRANSPORT_DEST = (
    "networking bisnis",
    "networking",
    "ketemu client",
    "ketemu klien",
    "ketemu bisnis",
    "ketemu calon klien",
    "meeting client",
    "meeting klien",
    "meeting bisnis",
    "meeting kerja",
    "meeting kerjaan",
    "rapat kerja",
    "rapat klien",
    "rapat client",
    "klien bisnis",
    "client bisnis",
    "urusan bisnis",
    "keperluan bisnis",
    "keperluan usaha",
    "perjalanan bisnis",
    "perjalanan dinas",
    "dinas luar",
    "kerja training",
    "training kerja",
    "untuk bisnis",
    "tujuan bisnis",
    "ke bisnis",
    "buat bisnis",
    "ke klien",
    "ke client",
    "ke meeting",
    "ke rapat",
    "pitch client",
    "pitch klien",
    "investor meeting",
    "acara bisnis",
    "event bisnis",
)


def is_business_transport_destination(text: str) -> bool:
    """Tujuan ride bisnis/kerja → Future Building / Need."""
    lower = text.lower()
    if not is_transport_ride(lower):
        return False
    return _contains(lower, _BUSINESS_TRANSPORT_DEST)


def is_leisure_transport_destination(text: str) -> bool:
    """Tujuan ride yang Flexible + Social / Wants (bukan Essential ke kantor)."""
    lower = text.lower()
    if not is_transport_ride(lower):
        return False
    if is_business_transport_destination(lower):
        return False
    if _contains(lower, _LEISURE_TRANSPORT_DEST):
        return True
    # "grab … gym/fitness" tanpa kata "ke" eksplisit
    if _contains(lower, ("gym", "fitness", "pilates", "yoga", "crossfit")):
        return True
    return False


def transport_ride_sifat(text: str) -> str:
    if is_business_transport_destination(text):
        return "Need"
    return "Wants" if is_leisure_transport_destination(text) else "Need"


def is_electronics_expense(text: str) -> bool:
    return _match_any(text, _ELEKTRONIK_PATTERNS)


def electronics_sifat(text: str) -> str:
    """Need jika perbaikan/alat kerja; selain itu Wants (bukan jajan)."""
    lower = text.lower()
    if any(hint in lower for hint in _ELEKTRONIK_NEED_HINTS):
        return "Need"
    return "Wants"


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


def _normalize_typos(text: str) -> str:
    """Normalisasi typo/slang umum sebelum matching keyword."""
    lower = text.lower()
    lower = lower.replace("freelence", "freelance").replace("freelanc ", "freelance ")
    replacements = (
        (r"\bpinjem\b", "pinjam"),
        (r"\bminjem\b", "pinjam"),
        (r"\bminjam\b", "pinjam"),
        (r"\bnyicil\b", "cicil"),
        (r"\bnyicilin\b", "cicil"),
        (r"\blunasin\b", "lunasi"),
        (r"\bbayarin\b", "bayarkan"),
        (r"\bngutangin\b", "ngutangin"),
        (r"\bngutangi\b", "ngutangin"),
        (r"\bdibalikin\b", "dikembalikan"),
        (r"\bdikembaliin\b", "dikembalikan"),
        (r"\bngembaliin\b", "kembalikan"),
        (r"\bngembalikan\b", "kembalikan"),
        (r"\bbalikin\b", "kembalikan"),
        (r"\bkek\b", "ke"),
        (r"\bkepada\b", "ke"),
    )
    for pattern, repl in replacements:
        lower = re.sub(pattern, repl, lower)
    return lower


def detect_cashflow_direction(text: str) -> str | None:
    """
    Deteksi arah uang dari kata kerja user.
    terima/dapat → Pemasukan; bayar/pengeluaran/melunasi → Pengeluaran.
    """
    explicit = detect_explicit_jenis(text)
    if explicit in {"Pemasukan", "Pengeluaran"}:
        return explicit

    lower = _normalize_typos(text)
    has_in = _contains(lower, _INCOME_DIRECTION)
    has_out = _contains(lower, _EXPENSE_DIRECTION)

    if has_in and not has_out:
        return "Pemasukan"
    if has_out and not has_in:
        return "Pengeluaran"
    if has_in and has_out:
        # Keduanya ada: utamakan kata yang muncul lebih dulu.
        first_in = min(
            (lower.find(p) for p in _INCOME_DIRECTION if p in lower),
            default=10**9,
        )
        first_out = min(
            (lower.find(p) for p in _EXPENSE_DIRECTION if p in lower),
            default=10**9,
        )
        return "Pemasukan" if first_in <= first_out else "Pengeluaran"
    return None


def _has_freelance_marker(text: str) -> bool:
    return _contains(_normalize_typos(text), _FREELANCE_MARKERS) or "jasa" in _normalize_typos(
        text
    )


def _is_honor_income(text: str) -> bool:
    """Honor/freelance masuk HANYA jika bukan pembayaran ke freelancer."""
    lower = _normalize_typos(text)
    if detect_cashflow_direction(text) == "Pengeluaran":
        return False
    if _contains(lower, _PAYING_FREELANCE):
        return False
    if detect_explicit_jenis(text) == "Pengeluaran":
        return False
    if _contains(lower, _HONOR):
        return True
    # "terima jasa freelence" / "dapat freelance 6jt"
    if detect_cashflow_direction(text) == "Pemasukan" and _has_freelance_marker(lower):
        return True
    return False


def classify_from_text(text: str) -> dict[str, str] | None:
    """Kembalikan {jenis, kategori, sifat} jika rule kuat cocok, else None."""
    lower = _normalize_typos(text).strip()
    if not lower:
        return None

    explicit = detect_explicit_jenis(text)
    direction = detect_cashflow_direction(text)
    # User tulis "Pengeluaran …" → jangan pernah paksa Pemasukan dari keyword kategori.
    skip_income = explicit == "Pengeluaran" or direction == "Pengeluaran"
    social_jenis = detect_social_liquidity_jenis(lower)
    if social_jenis:
        # Terima/bayar pinjaman orang = likuiditas sosial, bukan Pemasukan/Pengeluaran.
        skip_income = True
    # User tulis "Pemasukan …" → tetap izinkan income rules (dan skip expense force).
    force_income_only = explicit == "Pemasukan"

    # ---- Pemasukan (prioritas tinggi, kecuali jenis eksplisit Pengeluaran) ----
    if not skip_income:
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
        if _contains(lower, _GAJI) and not is_household_help_expense(lower):
            return {"jenis": "Pemasukan", "kategori": "Gaji", "sifat": "Need"}
        if _contains(lower, _BONUS):
            return {"jenis": "Pemasukan", "kategori": "Bonus", "sifat": "Need"}
        if _is_honor_income(lower):
            return {"jenis": "Pemasukan", "kategori": "Freelance", "sifat": "Need"}
        if _contains(lower, _PENJUALAN):
            return {"jenis": "Pemasukan", "kategori": "Penjualan", "sifat": "Need"}
        if _contains(lower, _SEWA_MASUK):
            return {"jenis": "Pemasukan", "kategori": "Sewa Masuk", "sifat": "Need"}
        if _contains(lower, _TRANSFER_MASUK):
            return {"jenis": "Pemasukan", "kategori": "Transfer Masuk", "sifat": "Need"}
        if is_received_money_gift(lower):
            return {"jenis": "Pemasukan", "kategori": "Lain-lain", "sifat": "Need"}
        # Kata kerja masuk jelas, tapi kategori belum ketemu → Pemasukan generik.
        if direction == "Pemasukan":
            return {"jenis": "Pemasukan", "kategori": "Lain-lain", "sifat": "Need"}

    # Likuiditas sosial sebelum kategori tujuan (kuliah/obat/makeup) dan sebelum
    # "Pemasukan …" eksplisit — terima pinjaman bukan pendapatan.
    if social_jenis:
        return {"jenis": social_jenis, "kategori": "Lain-lain", "sifat": "Need"}
    if is_ambiguous_utang_ke_person(lower):
        return None

    if force_income_only:
        return None

    # ---- DP aset (taxonomy 2.14) — sebelum saving generik ----
    if is_dp_lifestyle_vehicle(lower) or (
        explicit == "Pengeluaran"
        and any(k in lower for k in ("dp mobil", "dp motor", "dp kendaraan", "uang muka mobil", "uang muka motor"))
    ):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Cicilan & Hutang",
            "sifat": "Wants",
        }
    if is_dp_asset_payment(lower) and explicit != "Pengeluaran" and direction != "Pengeluaran":
        return {
            "jenis": "Saving/Investment",
            "kategori": "Investasi & Tabungan",
            "sifat": "Need",
        }

    # ---- Saving / Investment ----
    if explicit != "Pengeluaran" and direction != "Pengeluaran" and (
        is_saving_action(lower)
        or (
            infer_saving_label(lower) is not None
            and _contains(
                lower,
                _SAVING_ACTION + ("saham", "reksa", "deposito", "crypto", "bitcoin", "sbn", "obligasi"),
            )
        )
    ):
        label = infer_saving_label(lower) or "Investasi & Tabungan"
        # "investasi" generik tanpa aksi beli/nabung & tanpa instrumen jelas → jangan paksa.
        if label == "Investasi" and not _contains(lower, _SAVING_ACTION):
            pass
        else:
            return {"jenis": "Saving/Investment", "kategori": "Investasi & Tabungan", "sifat": "Need"}

    # Dividen reinvest = saving
    if explicit != "Pengeluaran" and direction != "Pengeluaran" and _contains(lower, _DIVIDEN_REINVEST):
        return {"jenis": "Saving/Investment", "kategori": "Investasi & Tabungan", "sifat": "Need"}

    # ---- Pengeluaran ----
    # Bayar jasa/freelancer dulu — sebelum _MAKAN ("nasi" di dalam "melunasi").
    if _contains(lower, _PAYING_FREELANCE) or (
        direction == "Pengeluaran" and _has_freelance_marker(lower)
    ):
        return {"jenis": "Pengeluaran", "kategori": "Bisnis & Karir", "sifat": "Need"}

    # Makeup/skincare sebelum Proteksi — "premium" jangan kena kata "premi".
    if _contains(lower, _SKINCARE) and not _contains(lower, _PRINTER_SUPPLIES):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan & Kebersihan Diri", "sifat": "Wants"}

    if _contains(lower, _ASURANSI):
        return {"jenis": "Pengeluaran", "kategori": "Proteksi", "sifat": "Need"}
    # Hadiah/kado = belanja, bukan pinjaman sosial & bukan grey area gadget.
    if is_gift_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Hadiah", "sifat": "Wants"}
    # Tip/donasi ke driver (grab/gojek) — Sosial, bukan Transport/Makan.
    if is_social_giving(lower):
        if is_gift_expense(lower) or _contains(lower, _GIFT_MARKERS + ("tip", "tips")):
            return {"jenis": "Pengeluaran", "kategori": "Hadiah", "sifat": "Wants"}
        return {"jenis": "Pengeluaran", "kategori": "Sosial & Keluarga", "sifat": "Wants"}
    if is_business_building_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Bisnis & Karir", "sifat": "Need"}
    # Traveling: hotel/staycation/liburan/wisata (bukan grab/ojek lokal).
    if is_traveling_expense(lower) and not is_business_transport_destination(lower):
        local_ride_only = is_transport_ride(lower) and not _contains(
            lower,
            ("tiket pesawat", "tiket kereta", "tiket bus", "boarding", "hotel", "staycation", "penginapan"),
        )
        if not local_ride_only:
            # "grab ke hotel" = ride lokal → biarkan jatuh ke Transportasi di bawah
            if not (
                is_transport_ride(lower)
                and _contains(lower, ("grab", "gojek", "ojek", "maxim", "grabbike", "grabcar"))
            ):
                return {"jenis": "Pengeluaran", "kategori": "Traveling", "sifat": "Wants"}
    # Langganan ojek (Grab Unlimited) — bukan ride sekali jalan / bukan grey area tujuan.
    if is_ride_subscription(lower):
        return {"jenis": "Pengeluaran", "kategori": "Transportasi", "sifat": "Wants"}
    # Ride Grab/ojek dulu — "grab ke gym" tetap Transportasi (bucket Flexible),
    # bukan Lifestyle. Bayar membership gym tanpa ride → Lifestyle di bawah.
    if _contains(lower, _SERVIS_KENDARAAN) or is_transport_ride(lower):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Transportasi",
            "sifat": transport_ride_sifat(lower),
        }
    if _contains(lower, _GYM_LIFESTYLE):
        return {"jenis": "Pengeluaran", "kategori": "Lifestyle & Hiburan", "sifat": "Wants"}
    if _contains(lower, _HIBURAN):
        return {"jenis": "Pengeluaran", "kategori": "Lifestyle & Hiburan", "sifat": "Wants"}
    if _contains(lower, _SUBSCRIPTION):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Bisnis & Karir" if is_work_software_expense(lower) else "Lifestyle & Hiburan",
            "sifat": "Need" if is_work_software_expense(lower) else "Wants",
        }
    if is_household_help_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    if is_household_durable(lower):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Tempat Tinggal",
            "sifat": household_durable_sifat(lower),
        }
    if _contains(lower, _LISTRIK) or is_water_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    # Air minum/galon sebelum jajan — "beli aqua" sering salah jadi Jajan.
    if is_drinking_water_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Need"}
    if is_fisioterapi_expense(lower):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Kesehatan & Kebersihan Diri",
            "sifat": fisioterapi_sifat(lower),
        }
    if _contains(lower, _KESEHATAN):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan & Kebersihan Diri", "sifat": "Need"}
    if is_self_development_expense(lower) or _contains(lower, _PENDIDIKAN):
        return {"jenis": "Pengeluaran", "kategori": "Pendidikan", "sifat": "Need"}
    if "wifi rumah" in lower or "internet rumah" in lower:
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    if is_bank_admin_fee(lower) or _contains(lower, _KOMUNIKASI):
        return {"jenis": "Pengeluaran", "kategori": "Komunikasi", "sifat": "Need"}
    if _contains(lower, _LAUNDRY):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan & Kebersihan Diri", "sifat": "Need"}
    if _contains(lower, _PAKAIAN):
        work_wear = any(
            k in lower
            for k in ("seragam", "sepatu kerja", "tas kerja", "tas sekolah", "sepatu sekolah")
        )
        return {
            "jenis": "Pengeluaran",
            "kategori": "Pakaian & Aksesoris",
            "sifat": "Need" if work_wear else "Wants",
        }
    if is_denda_sanksi(lower):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan & Hutang", "sifat": "Need"}
    if _contains(lower, _CICILAN) or is_pinjol_or_cash_loan(lower):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan & Hutang", "sifat": "Need"}
    if _contains(lower, _SEWA_KELUAR):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    if _contains(lower, _PPH_KEWAJIBAN) or (
        _contains(lower, _PAJAK)
        and not _contains(lower, _PBB)
        and not _contains(lower, _PAJAK_KENDARAAN)
    ):
        return {"jenis": "Kewajiban Pajak", "kategori": "Lain-lain", "sifat": "Need"}
    if _contains(lower, _PBB):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    if _contains(lower, _PAJAK_KENDARAAN):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan & Hutang", "sifat": "Need"}
    if _contains(lower, _LEGAL_EVENT):
        sifat_legal = "Wants" if any(k in lower for k in ("mahar", "pernikahan", "resepsi", "duka", "pemakaman", "tahlilan")) else "Need"
        return {
            "jenis": "Pengeluaran",
            "kategori": "Biaya Legal, Administrasi & Peristiwa Besar",
            "sifat": sifat_legal,
        }
    if _contains(lower, _SOCIAL):
        return {"jenis": "Pengeluaran", "kategori": "Sosial & Keluarga", "sifat": "Wants"}
    if has_essential_living_intent(lower):
        if _contains(lower, _KOPI_JAJAN) and not is_drinking_water_expense(lower):
            return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Need"}
        return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Need"}
    # Jajan dulu (kata "jajan"/kue), baru makan/delivery — hindari GrabFood → Transport.
    if _contains(lower, _KOPI_JAJAN):
        return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Wants"}
    if is_food_delivery(lower) or _contains(lower, _MAKAN):
        return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Need"}
    if is_electronics_expense(lower):
        return {
            "jenis": "Pengeluaran",
            "kategori": "Lifestyle & Hiburan",
            "sifat": electronics_sifat(lower),
        }

    # User tulis "Pengeluaran …" tanpa kategori spesifik → jenis tetap Pengeluaran.
    if explicit == "Pengeluaran" or direction == "Pengeluaran":
        return {"jenis": "Pengeluaran", "kategori": "Lain-lain", "sifat": "Need"}

    return None


def apply_context_rules(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    """
    Setelah AI parse: hormati AI, kecuali arah uang dari kata kerja jelas salah.

    Rule:
    - hormati jenis eksplisit user ("Pengeluaran …" / "Pemasukan …"),
    - koreksi jenis jika kata kerja arah jelas (terima→masuk, bayar→keluar),
    - koreksi dump kategori (Elektronik vs Jajan, GrabFood vs Transport),
    - isi field yang AI biarkan kosong.
    """
    combined = f"{source_text} {parsed.get('keterangan', '')}".strip()
    explicit = detect_explicit_jenis(source_text) or detect_explicit_jenis(combined)
    direction = detect_cashflow_direction(source_text) or detect_cashflow_direction(combined)
    lower_combined = _normalize_typos(combined)

    # Jenis yang user tulis sendiri di awal pesan.
    if explicit and str(parsed.get("jenis") or "").strip() != explicit:
        parsed["jenis"] = explicit

    # Komisi/affiliate (termasuk gift TikTok) = Pemasukan — jangan kena aturan kado.
    if explicit != "Pengeluaran" and is_affiliate_income(combined):
        parsed["jenis"] = "Pemasukan"
        parsed["kategori"] = "Affiliate"
        parsed["sifat"] = "Need"
        parsed.pop("sub_kategori", None)
        return parsed

    social_jenis = detect_social_liquidity_jenis(lower_combined)
    if social_jenis:
        parsed["jenis"] = social_jenis
        parsed["kategori"] = "Lain-lain"
        if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
            parsed["sifat"] = "Need"
        parsed.pop("sub_kategori", None)
        return parsed

    # Hadiah yang diberi (§4 Hadiah / Flexible) — jangan timpa pinjam/utang yang sudah jelas.
    if is_gift_expense(combined):
        parsed["jenis"] = "Pengeluaran"
        parsed["kategori"] = "Hadiah"
        parsed["sifat"] = "Wants"
        parsed.pop("sub_kategori", None)
    elif is_beauty_care_expense(combined):
        parsed["jenis"] = "Pengeluaran"
        parsed["kategori"] = "Kesehatan & Kebersihan Diri"
        parsed["sifat"] = "Wants"
        parsed.pop("sub_kategori", None)
        parsed["needs_clarification"] = False
        parsed["clarification_question"] = None
    elif is_household_durable(combined):
        parsed["jenis"] = "Pengeluaran"
        parsed["kategori"] = "Tempat Tinggal"
        parsed["sifat"] = household_durable_sifat(combined)
        parsed.pop("sub_kategori", None)
    elif is_ride_subscription(combined):
        parsed["jenis"] = "Pengeluaran"
        parsed["kategori"] = "Transportasi"
        parsed["sifat"] = "Wants"
        parsed.pop("sub_kategori", None)
    # Kata kerja arah uang menang jika AI salah (terima ≠ pengeluaran).
    elif (
        direction
        and str(parsed.get("jenis") or "").strip() != direction
        and str(parsed.get("jenis") or "").strip()
        not in {
            "Kewajiban Pajak",
            "Saving/Investment",
            "Piutang Keluar",
            "Piutang Masuk",
            "Utang Masuk",
            "Utang Keluar",
        }
    ):
        parsed["jenis"] = direction
        kat_l = str(parsed.get("kategori") or "").strip().lower()
        if direction == "Pemasukan" and _has_freelance_marker(combined):
            if kat_l in {
                "",
                "jajan",
                "belanja",
                "makan",
                "makanan & minuman",
                "makanan dan minuman",
                "lain-lain",
                "lain lain",
                "jasa",
                "lainnya",
                "lifestyle & hiburan",
            }:
                parsed["kategori"] = "Freelance"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
        elif direction == "Pengeluaran" and (
            _contains(_normalize_typos(combined), _PAYING_FREELANCE)
            or _has_freelance_marker(combined)
        ):
            if kat_l in {
                "",
                "freelance",
                "gaji",
                "bonus",
                "honor",
                "jajan",
                "makan",
                "makanan & minuman",
                "jasa",
            }:
                parsed["kategori"] = "Bisnis & Karir"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)

    ai_jenis = str(parsed.get("jenis") or "").strip()
    ai_kat = str(parsed.get("kategori") or "").strip()
    ai_kat_l = ai_kat.lower()

    # AI sudah lengkap → jangan timpa dengan rule income/freelance generik.
    if ai_jenis and ai_kat:
        # Jaga supaya kategori konsumsi tidak masuk jenis Pemasukan.
        _consumption_cats = {
            "jajan",
            "makan",
            "makanan & minuman",
            "makanan dan minuman",
            "minuman",
            "hiburan",
            "lifestyle & hiburan",
            "social",
            "sosial & keluarga",
            "transport",
            "transportasi",
            "traveling",
            "hadiah",
        }
        if ai_jenis == "Pemasukan" and ai_kat_l in _consumption_cats:
            if detect_cashflow_direction(combined) != "Pemasukan":
                parsed["jenis"] = "Pengeluaran"
                ai_jenis = "Pengeluaran"

        # Layer 1 keyword > AI dump. "Lain-lain" berarti model tidak tahu,
        # bukan izin menimpa taksonomi yang sudah ada di classify_from_text.
        if _is_dump_category(ai_kat_l):
            rule_hit = classify_from_text(combined)
            rule_kat = str((rule_hit or {}).get("kategori") or "").strip()
            if rule_hit and rule_kat and not _is_dump_category(rule_kat):
                parsed["jenis"] = rule_hit["jenis"]
                parsed["kategori"] = rule_kat
                parsed["sifat"] = rule_hit["sifat"]
                parsed.pop("sub_kategori", None)
                return parsed

        if (
            ai_jenis == "Pengeluaran"
            and _contains(combined.lower(), _SKINCARE)
            and not _contains(combined.lower(), _PRINTER_SUPPLIES)
        ):
            parsed["kategori"] = "Kesehatan & Kebersihan Diri"
            parsed["sifat"] = "Wants"
            parsed.pop("sub_kategori", None)
            return parsed

        # Donasi/tip/hadiah spontan sering salah jadi Makan/Transport/Need.
        if ai_jenis == "Pengeluaran" and is_social_giving(combined):
            if is_gift_expense(combined) or _contains(combined.lower(), _GIFT_MARKERS + ("tip", "tips")):
                parsed["kategori"] = "Hadiah"
            else:
                parsed["kategori"] = "Sosial & Keluarga"
            parsed["sifat"] = "Wants"
            parsed.pop("sub_kategori", None)
            return parsed

        # Qurban → Sosial & Keluarga (bukan Lain-lain / Lifestyle).
        if ai_jenis == "Pengeluaran" and _contains(combined.lower(), ("qurban", "kurban")):
            parsed["kategori"] = "Sosial & Keluarga"
            parsed["sifat"] = "Wants"
            parsed.pop("sub_kategori", None)
            return parsed

        # ART/driver/babysitter → Tempat Tinggal.
        if ai_jenis == "Pengeluaran" and is_household_help_expense(combined):
            parsed["kategori"] = "Tempat Tinggal"
            if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
                parsed["sifat"] = "Need"
            parsed.pop("sub_kategori", None)
            return parsed

        # Traveling (hotel/liburan/staycation) — bukan Lifestyle.
        if (
            ai_jenis == "Pengeluaran"
            and is_traveling_expense(combined)
            and not is_business_transport_destination(combined)
            and not (
                is_transport_ride(combined)
                and _contains(combined.lower(), ("grab", "gojek", "ojek", "maxim", "grabbike", "grabcar"))
            )
        ):
            if ai_kat_l in {
                "lifestyle & hiburan",
                "lain-lain",
                "lain lain",
                "lainnya",
                "transport",
                "transportasi",
                "makanan & minuman",
                "",
            } or "traveling" not in ai_kat_l:
                parsed["kategori"] = "Traveling"
                parsed["sifat"] = "Wants"
                parsed.pop("sub_kategori", None)
                return parsed

        # Pengembangan diri / iuran organisasi → Pendidikan.
        if ai_jenis == "Pengeluaran" and is_self_development_expense(combined):
            if ai_kat_l in {
                "lifestyle & hiburan",
                "lain-lain",
                "lain lain",
                "lainnya",
                "bisnis & karir",
                "makanan & minuman",
                "",
            } or "pendidikan" not in ai_kat_l:
                parsed["kategori"] = "Pendidikan"
                if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
                    parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed

        # Fashion → Pakaian & Aksesoris (bukan Lifestyle).
        if ai_jenis == "Pengeluaran" and _contains(combined.lower(), _PAKAIAN):
            work_wear = any(
                k in combined.lower()
                for k in ("seragam", "sepatu kerja", "tas kerja", "tas sekolah", "sepatu sekolah")
            )
            if work_wear:
                parsed["kategori"] = "Pakaian & Aksesoris"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed
            if ai_kat_l in {
                "lifestyle & hiburan",
                "hiburan",
                "lain-lain",
                "lain lain",
                "lainnya",
                "jajan",
            }:
                parsed["kategori"] = "Pakaian & Aksesoris"
                parsed["sifat"] = "Wants"
                parsed.pop("sub_kategori", None)
                return parsed

        # Wifi rumah → Tempat Tinggal (bukan Komunikasi).
        if ai_jenis == "Pengeluaran" and ("wifi rumah" in combined.lower() or "internet rumah" in combined.lower()):
            parsed["kategori"] = "Tempat Tinggal"
            parsed["sifat"] = "Need"
            parsed.pop("sub_kategori", None)
            return parsed

        # Laundry → Kesehatan & Kebersihan Diri.
        if ai_jenis == "Pengeluaran" and _contains(combined.lower(), _LAUNDRY):
            parsed["kategori"] = "Kesehatan & Kebersihan Diri"
            parsed["sifat"] = "Need"
            parsed.pop("sub_kategori", None)
            return parsed

        # Fisioterapi → Kesehatan.
        if ai_jenis == "Pengeluaran" and is_fisioterapi_expense(combined):
            parsed["kategori"] = "Kesehatan & Kebersihan Diri"
            parsed["sifat"] = fisioterapi_sifat(combined)
            parsed.pop("sub_kategori", None)
            return parsed

        # Alias AI "Lainnya" → closed list "Lain-lain".
        if ai_kat_l in {"lainnya", "other", "misc", "umum"}:
            parsed["kategori"] = "Lain-lain"

        # Grab/ojek lokal → Transportasi. Tiket pesawat/kereta + liburan tetap Traveling.
        if ai_jenis == "Pengeluaran" and is_transport_ride(combined):
            if not is_food_delivery(combined) and not is_social_giving(combined):
                local_ride = _contains(
                    combined.lower(),
                    ("grab", "gojek", "ojek", "maxim", "grabbike", "grabcar"),
                )
                if is_traveling_expense(combined) and not local_ride:
                    parsed["kategori"] = "Traveling"
                    parsed["sifat"] = "Wants"
                    parsed.pop("sub_kategori", None)
                    return parsed
                parsed["kategori"] = "Transportasi"
                parsed["sifat"] = transport_ride_sifat(combined)
                parsed.pop("sub_kategori", None)
                return parsed

        # Gym/olahraga berbayar → Lifestyle & Hiburan (bukan Essential).
        # Hanya jika BUKAN ongkos ride ke lokasi gym.
        if (
            ai_jenis == "Pengeluaran"
            and _contains(combined.lower(), _GYM_LIFESTYLE)
            and not is_transport_ride(combined)
        ):
            parsed["kategori"] = "Lifestyle & Hiburan"
            parsed["sifat"] = "Wants"
            parsed.pop("sub_kategori", None)
            return parsed

        # Konsumsi meeting kerja/bisnis/konten → Bisnis & Karir (Future Building).
        # Ekspektasi klien: "makan + meeting kerja" bukan Essential Living.
        if ai_jenis == "Pengeluaran" and is_business_building_expense(combined):
            if ai_kat_l in {
                "jajan", "makan", "makanan & minuman", "makanan dan minuman",
                "lainnya", "lain-lain", "lain lain", "belanja",
                "lifestyle & hiburan", "kopi", "minuman",
            }:
                parsed["kategori"] = "Bisnis & Karir"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed

        # Biaya admin bank/transfer → Komunikasi (bukan Lain-lain / Legal Administrasi).
        if ai_jenis == "Pengeluaran" and is_bank_admin_fee(combined):
            if ai_kat_l in {
                "",
                "lain-lain",
                "lain lain",
                "lainnya",
                "other",
                "misc",
                "umum",
                "biaya legal, administrasi & peristiwa besar",
                "biaya legal",
                "administrasi",
                "cicilan & hutang",
                "investasi & tabungan",
            } or "komunikasi" not in ai_kat_l:
                parsed["kategori"] = "Komunikasi"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed

        # Koreksi dump kategori saja (bukan jenis).
        if ai_kat_l == "subscription" and is_work_software_expense(combined):
            parsed["kategori"] = "Bisnis & Karir"
            parsed["sifat"] = "Need"
            parsed.pop("sub_kategori", None)
            return parsed

        if is_electronics_expense(combined) and ai_kat_l in {
            "jajan",
            "belanja",
            "lain-lain",
            "lain lain",
            "other",
            "misc",
            "makan",
            "makanan & minuman",
            "umum",
        }:
            parsed["kategori"] = "Lifestyle & Hiburan"
            parsed["sifat"] = electronics_sifat(combined)
            parsed.pop("sub_kategori", None)
            return parsed

        # Air minum/galon sering salah jadi Jajan/Wants → Makanan & Minuman / Need.
        if ai_jenis == "Pengeluaran" and is_drinking_water_expense(combined):
            if ai_kat_l in {
                "jajan",
                "belanja",
                "minuman",
                "makan",
                "makanan & minuman",
                "makanan dan minuman",
                "lain-lain",
                "lain lain",
                "other",
                "misc",
                "umum",
                "lainnya",
                "lifestyle & hiburan",
            } or str(parsed.get("sifat") or "").strip() != "Need":
                parsed["kategori"] = "Makanan & Minuman"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed

        if has_essential_living_intent(combined) and ai_kat_l in {
            "jajan",
            "belanja",
            "minuman",
            "lain-lain",
            "lain lain",
            "other",
            "misc",
            "umum",
            "lainnya",
        }:
            parsed["kategori"] = "Makanan & Minuman"
            parsed["sifat"] = "Need"
            parsed.pop("sub_kategori", None)
            return parsed

        if ai_kat_l in {"transport", "transportasi"} and (
            is_food_delivery(combined)
            or _contains(combined.lower(), _KOPI_JAJAN)
            or _contains(combined.lower(), _MAKAN)
        ):
            parsed["kategori"] = "Makanan & Minuman"
            parsed["sifat"] = "Need" if not _contains(combined.lower(), _KOPI_JAJAN) else "Wants"
            parsed.pop("sub_kategori", None)
            return parsed

        # AI sering memberi "Jajan" untuk makan utama.
        if ai_kat_l == "jajan":
            if (
                _contains(combined.lower(), _MAKAN)
                and not _contains(combined.lower(), _KOPI_JAJAN)
                and not is_food_delivery(combined)
            ):
                parsed["kategori"] = "Makanan & Minuman"
                parsed["sifat"] = "Need"
                parsed.pop("sub_kategori", None)
                return parsed
            parsed["kategori"] = "Makanan & Minuman"
            parsed["sifat"] = "Wants"
            return parsed

        if ai_kat_l in {"makan", "makanan & minuman", "makanan dan minuman"} and ai_jenis == "Pengeluaran":
            if _contains(combined.lower(), _KOPI_JAJAN) and not has_essential_living_intent(combined):
                parsed["sifat"] = "Wants"
            return parsed

        return parsed

    hit = classify_from_text(combined)
    if hit is None:
        return parsed

    # AI kosong sebagian → isi dari rule sebagai cadangan saja.
    if not ai_jenis:
        parsed["jenis"] = hit["jenis"]
    if not ai_kat:
        parsed["kategori"] = hit["kategori"]
        parsed["sifat"] = hit["sifat"]
        parsed.pop("sub_kategori", None)
    return parsed


def prompt_context_examples() -> str:
    """Contoh untuk system prompt AI — mengurangi salah konteks."""
    return """
Contoh klasifikasi WAJIB diikuti (kategori = closed list YFD AI Taxonomy):
- "dapat shopee affiliate 50rb" → Pemasukan / Affiliate / Need
- "komisi gift dari TikTok 200rb" → Pemasukan / Affiliate / Need (BUKAN Pengeluaran Hadiah)
- "terima bunga investasi sebesar 5000" → Pemasukan / Bunga Investasi / Need (BUKAN Saving)
- "dividen BBCA cair 200rb" → Pemasukan / Dividen / Need
- "beli saham BBCA 1jt" → Saving/Investment / Investasi & Tabungan / Need
- "nabung reksadana 500rb" → Saving/Investment / Investasi & Tabungan / Need
- "cashback marketplace 20rb" → Pemasukan / Cashback / Need
- "gaji bulan ini 8jt" → Pemasukan / Gaji / Need
- "honor freelance 1.5jt" → Pemasukan / Freelance / Need
- "Pengeluaran melunasi jasa freelancer IT Rp 5.750.000" → Pengeluaran / Bisnis & Karir / Need
- "bayar BPJS 150rb" → Pengeluaran / Proteksi / Need
- "netflix bulanan 54rb" → Pengeluaran / Lifestyle & Hiburan / Wants
- "langganan capcut untuk kerja edit video 95k" → Pengeluaran / Bisnis & Karir / Need
- "skincare serum 120rb" → Pengeluaran / Kesehatan & Kebersihan Diri / Wants
- "beli makeup / skin care 139.5k" → Pengeluaran / Kesehatan & Kebersihan Diri / Wants (BUKAN Lain-lain)
- "beli tumbler 150rb" → Pengeluaran / Tempat Tinggal — WAJIB klarifikasi rusak vs koleksi (BUKAN Proteksi)
- "beli tumbler karena tumbler lama rusak 200rb" → Pengeluaran / Tempat Tinggal / Need (Essential Living)
- "bayar subscription grab paket hemat 14.000" → Pengeluaran / Transportasi / Wants (BUKAN grey area tujuan; BUKAN Likuiditas Sosial)
- "makan malam 65.700" → Pengeluaran / Makanan & Minuman / Need
- "grab ke kantor 28rb" → Pengeluaran / Transportasi / Need
- "grab dari kos ke gym 21rb" → Pengeluaran / Transportasi / Wants (bucket Flexible; BUKAN Lifestyle)
- "grab ke networking bisnis 45rb" → Pengeluaran / Transportasi / Need (bucket Future Building)
- "saya pinjam uang ayuti 5 jt" → Utang Masuk (BUKAN Pengeluaran; tanpa ke/dari tetap kita yang utang)
- "saya pinjam uang ayuti 5 jt" + tujuan kuliah / kembali bulan depan → TETAP Utang Masuk (tujuan & jatuh tempo bukan jenis; BUKAN Pendidikan)
- "bayar kuliah 5 jt" (tanpa kata pinjam) → Pengeluaran / Pendidikan
- "Di pinjam Catherine 1 jt buat bayar RS" → Piutang Keluar (BUKAN Kesehatan; likuiditas sosial)
- "Grace kembalikan uang yang dipinjam sebelumnya 2,7jt" → Piutang Masuk (pelunasan; BUKAN Piutang Keluar / BUKAN Pemasukan)
- "utang ke Ayuti 1jt" → AMBIGU — tanya: meminjamkan (Piutang Keluar) atau berhutang/terima pinjaman (Utang Masuk)
- "pinjam/pinjem/minjem ke mama" / "ngutang sama/dari mama" / "terima pinjaman dari mama" → Utang Masuk
- "dibalikin ayuti" / "Ayuti balikin/kembalikan/bayar balik hutang" / "transfer balik dari ayuti" → Piutang Masuk
- "pinjamin/ngutangin/talangin/kasih pinjam X" → Piutang Keluar
- "bayar/lunasi/balikin utang ke X" / "saya kembalikan yang saya pinjam" → Utang Keluar
- "pinjam dari Ayuti 1jt" / "ngutang dari Ayuti 1jt" → Utang Masuk (likuiditas naik; BUKAN Pemasukan)
- "bayar utang ke Ayuti 1jt" → Utang Keluar (bayar balik; BUKAN Pengeluaran 4-bucket)
- "pinjamin Ayuti 1jt" / "ngutangin Ayuti 1jt" → Piutang Keluar (saya yang meminjamkan)
- "bayar cicilan pinjol 500rb" → Pengeluaran / Cicilan & Hutang (lembaga; tetap prescription)
- "grabbike ke fitness 21rb" → Pengeluaran / Transportasi / Wants (BUKAN Lifestyle)
- "jajan di grabfood 60k beli kue" → Pengeluaran / Makanan & Minuman / Wants (BUKAN Transportasi)
- "gofood nasi padang 45rb" → Pengeluaran / Makanan & Minuman / Need (BUKAN Transportasi)
- "bayar sewa kos 1.5jt" → Pengeluaran / Tempat Tinggal / Need
- "obat demam 45rb" → Pengeluaran / Kesehatan & Kebersihan Diri / Need
- "beli handbody / sabun / shampo / deodoran / pasta gigi" → Pengeluaran / Kesehatan & Kebersihan Diri / Need (kebersihan dasar; BUKAN Lain-lain; BUKAN perawatan kecantikan)
- "pulsa 50rb" → Pengeluaran / Komunikasi / Need
- "admin bank 10 rb" → Pengeluaran / Komunikasi / Need (biaya admin/transfer; BUKAN Lain-lain)
- "biaya transfer BCA 6.500" → Pengeluaran / Komunikasi / Need
- "cicilan motor 900rb" → Pengeluaran / Cicilan & Hutang / Need
- "beli headset 350rb" → Pengeluaran / Lifestyle & Hiburan / Wants
- "kopi susu 28rb" → Pengeluaran / Makanan & Minuman / Wants
- "beli aqua 1.5L 7k" → Pengeluaran / Makanan & Minuman / Need
- "donasi ke bapak grab 5k" → Pengeluaran / Sosial & Keluarga / Wants
- "Hadiah beli iphone 15jt buat keluarga. Terencana" → Pengeluaran / Hadiah / Wants (BUKAN Likuiditas Sosial; BUKAN grey area HP)
- "beli iphone 15jt" → Pengeluaran / Lifestyle & Hiburan — WAJIB klarifikasi HP rusak vs upgrade vs bisnis
- "terima hadiah uang 1jt" → Pemasukan / Lain-lain (BUKAN kategori Hadiah)
- "kasih tip gojek 10rb" → Pengeluaran / Hadiah / Wants
- "beli tiket konser 450rb" → Pengeluaran / Lifestyle & Hiburan / Wants
- "bayar olahraga gym bulanan + personal training 455rb" → Pengeluaran / Lifestyle & Hiburan / Wants
- "konsumsi meeting untuk take konten bisnis YFD 127rb" → Pengeluaran / Bisnis & Karir / Need → Future Building
- "makan malem sekalian meeting kerjaan 213rb" → Pengeluaran / Bisnis & Karir / Need → Future Building
- "makan malem sambil meeting untuk kerja 213rb" → Pengeluaran / Bisnis & Karir / Need → Future Building
- "laundry/cuci baju 52.500" → Pengeluaran / Kesehatan & Kebersihan Diri / Need
- "beli baju fashion 250rb" → Pengeluaran / Pakaian & Aksesoris / Wants
- "hotel staycation Ubud 1.2jt" → Pengeluaran / Traveling / Wants
- "bayar seminar sertifikasi 2jt" → Pengeluaran / Pendidikan / Need (bucket Future Building)
- "iuran IDI tahunan 1jt" → Pengeluaran / Pendidikan / Need (Future Building)
- "gaji babysitter bulanan 2jt" → Pengeluaran / Tempat Tinggal (grey area sifat)
- "bayar qurban 3jt" → Pengeluaran / Sosial & Keluarga / Wants
- "fisioterapi resep dokter 350rb" → Pengeluaran / Kesehatan & Kebersihan Diri / Need
- "PBB rumah disewakan 5jt" → Pengeluaran / Tempat Tinggal / Need (bucket Future Building)
- "starbucks meeting klien 85rb" → Pengeluaran / Bisnis & Karir / Need (Future Building)
- "kopi starbucks healing 65rb" → Pengeluaran / Makanan & Minuman / Wants
"""
