"""Rule konteks transaksi YFD — deterministik sebelum/sesudah AI.

Prioritas:
0) Jenis eksplisit user ("Pengeluaran"/"Pemasukan" di awal) — menang mutlak
1) Pemasukan khusus (affiliate, bunga, dividen, refund, dll.)
2) Saving/Investment (beli/nabung instrumen)
3) Pengeluaran (tagihan, konsumsi, sosial, dll.)
"""

from __future__ import annotations

import re
from typing import Any

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

_EXPLICIT_JENIS_RE = re.compile(
    r"^\s*(pengeluaran|pemasukan|saving(?:\s*/\s*investment)?|kewajiban\s*pajak|"
    r"piutang\s*keluar|piutang\s*masuk|utang\s*masuk|utang\s*keluar|hutang\s*masuk|hutang\s*keluar)\b",
    re.IGNORECASE,
)


def _contains(text: str, phrases: tuple[str, ...]) -> bool:
    lower = text.lower()
    return any(phrase in lower for phrase in phrases)


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
    "kebutuhan bisnis",
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
)

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

# Likuiditas Sosial §5 — uang KELUAR dipinjamkan ke orang lain
# JANGAN masukkan "utang ke" / "pinjam ke" (itu = saya berhutang).
# JANGAN pakai sinyal pelunasan ("kembalikan uang") — itu Piutang Masuk.
_PIUTANG_KELUAR = (
    "piutang keluar",
    "di pinjam",
    "dipinjam",
    "dipinjami",
    "pinjamin",
    "pinjami",
    "pinjamkan",
    "ngutangin",
    "ngutangi",
    "utangin",
    "hutangin",
    "talang dulu",
    "talangin",
    "bayarkan dulu",
    "lunasin dulu buat",
    "aku yang bayar dulu",
    "nanti dia ganti",
    "nanti diganti",
    "nanti dia bayar balik",
    "nanti bayar balik",
    "sementara aku yang cover",
)

# Utang sosial (KBBI) — terima pinjaman (cash bertambah, bukan Pemasukan)
# Jangan pakai "saya pinjam" mentah — bentrok dengan "saya pinjamkan".
_UTANG_MASUK = (
    "utang masuk",
    "hutang masuk",
    "ngutang dari",
    "utang dari",
    "pinjam dari",
    "pinjam kek",
    "minjem dari",
    "minjem kek",
    "terima pinjaman",
    "dapat pinjaman",
    "dapet pinjaman",
)

_UTANG_MASUK_PATTERNS = (
    re.compile(
        r"\b(?:saya|aku|gue|gw)\s+pinjam(?!kan|i|in)\s+(?:ke|kek|dari|sama|kepada)\b",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:saya|aku|gue|gw)\s+(?:minjem|utang|ngutang|hutang)\s+(?:ke|kek|dari|sama|kepada)\b",
        re.IGNORECASE,
    ),
    re.compile(r"\b(?:pinjam|minjem)\s+kek\b", re.IGNORECASE),
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
    "kembalikan hutang",
    "kembalikan utang",
    "bayar balik utang",
    "bayar balik hutang",
)

# "utang ke X" / "pinjam ke X" tanpa sinyal pinjamkan ATAU terima/bayar → AMBIGU
_AMBIGUOUS_UTANG_PATTERNS = (
    re.compile(r"\b(?:ng)?utang\s+ke\b", re.IGNORECASE),
    re.compile(r"\bhutang\s+ke\b", re.IGNORECASE),
    re.compile(r"\bberhutang\s+(?:ke|pada|sama)\b", re.IGNORECASE),
    re.compile(r"\bberutang\s+(?:ke|pada|sama)\b", re.IGNORECASE),
    re.compile(r"\bpinjam\s+ke\b", re.IGNORECASE),
)

# Piutang Masuk — orang lain melunasi yang sebelumnya kita pinjamkan
_PIUTANG_MASUK = (
    "piutang masuk",
    "dibayar balik",
    "bayar balik",
    "transfer balik",
    "ganti uang dari",
    "cicil hutang dari",
    "ngembalikan pinjaman",
    "mengembalikan pinjaman",
    "mengembalikan uang",
    "kembalikan uang",
    "kembalikan pinjaman",
    "balikin pinjaman",
    "balikin uang",
    "pelunasan piutang",
    "terima pelunasan",
    "uang dikembalikan",
    "sudah dikembalikan",
    "udah dikembalikan",
    "sudah balik",
    "udah balik",
)

_PIUTANG_MASUK_PATTERNS = (
    re.compile(r"\b(?:meng)?kembalikan\s+(?:uang|pinjaman|piutang)\b", re.IGNORECASE),
    re.compile(r"\bbalikin\s+(?:uang|pinjaman|piutang)\b", re.IGNORECASE),
    # Nama orang + kembalikan — jangan cocokkan "saya/aku kembalikan" (itu utang kita).
    re.compile(
        r"\b(?!saya\b|aku\b|gue\b|gw\b|kami\b|kita\b)([a-zà-ÿ][\wà-ÿ.\-]{1,40})\s+(?:meng)?kembalikan\b",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?!saya\b|aku\b|gue\b|gw\b|kami\b|kita\b)([a-zà-ÿ][\wà-ÿ.\-]{1,40})\s+balikin\b",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?!saya\b|aku\b|gue\b|gw\b|kami\b|kita\b)([a-zà-ÿ][\wà-ÿ.\-]{1,40})\s+bayar\s+balik\b",
        re.IGNORECASE,
    ),
    re.compile(r"\bdikembalikan\b", re.IGNORECASE),
    re.compile(
        r"\b(?:uang|pinjaman)\s+(?:yang\s+)?dipinjam\s+sebelumnya\b",
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
    )
    return _contains(text, markers)


def has_essential_living_intent(text: str) -> bool:
    return _contains(text, _KEBUTUHAN_HIDUP)


def is_food_delivery(text: str) -> bool:
    return _contains(text, _FOOD_DELIVERY)


def is_social_giving(text: str) -> bool:
    """Donasi, sedekah, tip driver/ojek, amplop — bukan makan/transport."""
    lower = text.lower()
    if _contains(lower, _SOCIAL):
        return True
    return _match_any(lower, _TIP_PATTERNS)


def is_discretionary_social_giving(text: str) -> bool:
    """Hadiah/tip/donasi spontan — Wants & cenderung impulsif (bukan zakat/sedekah wajib)."""
    lower = text.lower()
    if _contains(lower, _OBLIGATION_SOCIAL):
        return False
    if _contains(lower, ("hadiah",)):
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


def is_transport_ride(text: str) -> bool:
    """Ojek/bensin/dll — bukan GrabFood/GoFood."""
    if is_food_delivery(text):
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


def is_receivable_repay(text: str) -> bool:
    """Pelunasan piutang — Piutang Masuk (orang lain mengembalikan ke kita)."""
    lower = _normalize_typos(text)
    # Kita yang meminjam / akan mengembalikan → Utang Masuk/Keluar, bukan Piutang Masuk.
    if re.search(
        r"\b(?:saya|aku|gue|gw)\s+pinjam(?!kan|i|in)\b",
        lower,
    ):
        return False
    if re.search(
        r"\b(?:saya|aku|gue|gw)\s+(?:minjem|utang|ngutang|hutang)\b",
        lower,
    ):
        return False
    if re.search(
        r"\b(?:saya|aku|gue|gw)\s+(?:akan\s+|mau\s+|rencana\s+)?(?:kembalikan|balikin|bayar\s+balik)\b",
        lower,
    ):
        return False
    if re.search(r"\brencana\b.*\b(?:saya|aku)\s+(?:kembalikan|balikin)\b", lower):
        return False
    # "nanti dia bayar balik" = janji saat meminjamkan → bukan pelunasan.
    if re.search(r"\bnanti\s+(?:dia\s+)?(?:bayar\s+balik|ganti|balikin|kembali)", lower):
        return False
    # Bayar utang kita ke orang lain = Utang Keluar, bukan Piutang Masuk.
    if re.search(r"\b(?:bayar|lunasi|lunasin|balikin|kembalikan)\s+(?:utang|hutang)\s+ke\b", lower):
        return False
    if _contains(lower, _PIUTANG_MASUK):
        return True
    return any(p.search(lower) for p in _PIUTANG_MASUK_PATTERNS)


def is_lending_to_others(text: str) -> bool:
    """Uang keluar dipinjamkan — Piutang Keluar (§5.1)."""
    lower = _normalize_typos(text)
    if is_receivable_repay(lower):
        return False
    # "saya pinjam ke X" = kita berhutang, bukan meminjamkan.
    if any(p.search(lower) for p in _UTANG_MASUK_PATTERNS) or _contains(lower, _UTANG_MASUK):
        if not _contains(lower, _PIUTANG_KELUAR):
            return False
        if re.search(r"\b(?:saya|aku|gue|gw)\s+pinjam(?!kan|i|in)\b", lower):
            return False
        if re.search(r"\b(?:saya|aku|gue|gw)\s+(?:minjem|utang|ngutang)\b", lower):
            return False
    if "klarifikasi user:" in lower and any(
        k in lower
        for k in (
            "pinjamkan",
            "meminjamkan",
            "piutang",
            "nanti balik",
            "nanti kembali",
            "nanti diganti",
            "dia yang utang",
            "dia yang hutang",
            "saya yang pinjamin",
        )
    ):
        return True
    if _contains(lower, _PIUTANG_KELUAR):
        return True
    return False


def is_social_debt_borrow(text: str) -> bool:
    """Terima pinjaman sosial — Utang Masuk (cash bertambah; bukan Pemasukan)."""
    lower = _normalize_typos(text)
    if is_social_debt_repay(lower):
        return False
    # Pinjam oleh kita menang atas sinyal "kembalikan" di rencana pelunasan.
    if any(p.search(lower) for p in _UTANG_MASUK_PATTERNS):
        return True
    if is_receivable_repay(lower):
        return False
    if is_lending_to_others(lower) and "klarifikasi user:" not in lower:
        if _contains(lower, _PIUTANG_KELUAR):
            return False
    if "klarifikasi user:" in lower and any(
        k in lower
        for k in (
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
        )
    ):
        return True
    return _contains(lower, _UTANG_MASUK)


def is_social_debt_repay(text: str) -> bool:
    """Bayar balik pinjaman sosial — Utang Keluar (bukan Cicilan bank/pinjol)."""
    lower = _normalize_typos(text)
    if is_pinjol_or_cash_loan(lower) or _contains(lower, _CICILAN):
        if not _contains(lower, _UTANG_KELUAR):
            return False
        if any(k in lower for k in ("pinjol", "pinjaman online", "kartu kredit", "paylater", "kpr", "cicilan motor", "cicilan mobil")):
            return False
    if "klarifikasi user:" in lower and any(
        k in lower
        for k in (
            "bayar hutang",
            "bayar utang",
            "utang keluar",
            "hutang keluar",
            "lunasi",
            "bayar balik hutang",
            "bayar balik utang",
        )
    ):
        return True
    return _contains(lower, _UTANG_KELUAR)


def is_explicit_debt_repayment(text: str) -> bool:
    """Alias lama — sekarang = Utang Keluar sosial."""
    return is_social_debt_repay(text)


def is_ambiguous_utang_ke_person(text: str) -> bool:
    """utang/pinjam ke [nama] tanpa sinyal jelas → wajib klarifikasi."""
    lower = _normalize_typos(text)
    if "klarifikasi user:" in lower:
        return False
    # "saya pinjam ke X" jelas Utang Masuk.
    if any(p.search(lower) for p in _UTANG_MASUK_PATTERNS):
        return False
    if (
        is_receivable_repay(lower)
        or is_lending_to_others(lower)
        or is_social_debt_borrow(lower)
        or is_social_debt_repay(lower)
    ):
        return False
    return any(p.search(lower) for p in _AMBIGUOUS_UTANG_PATTERNS)


def is_personal_debt_to_others(text: str) -> bool:
    """Deprecated alias — gunakan is_social_debt_repay / is_social_debt_borrow."""
    return is_social_debt_repay(text) or is_social_debt_borrow(text)


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
    """Normalisasi typo umum sebelum matching keyword."""
    lower = text.lower()
    return lower.replace("freelence", "freelance").replace("freelanc ", "freelance ")


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
        # Kata kerja masuk jelas, tapi kategori belum ketemu → Pemasukan generik.
        if direction == "Pemasukan":
            return {"jenis": "Pemasukan", "kategori": "Lain-lain", "sifat": "Need"}

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

    if _contains(lower, _ASURANSI):
        return {"jenis": "Pengeluaran", "kategori": "Proteksi", "sifat": "Need"}
    # Tip/donasi ke driver (grab/gojek) — Sosial, bukan Transport/Makan.
    if is_social_giving(lower):
        if _contains(lower, ("hadiah", "kado", "parcel", "tip", "tips")):
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
    if _contains(lower, _SKINCARE):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan & Kebersihan Diri", "sifat": "Wants"}
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
    if _contains(lower, _LISTRIK) or is_water_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    # Air minum/galon sebelum jajan — "beli aqua" sering salah jadi Jajan.
    if is_drinking_water_expense(lower):
        return {"jenis": "Pengeluaran", "kategori": "Makanan & Minuman", "sifat": "Need"}
    # Hutang sosial: bayar balik → Utang Keluar; terima pinjaman → Utang Masuk
    if is_social_debt_repay(lower):
        return {"jenis": "Utang Keluar", "kategori": "Lain-lain", "sifat": "Need"}
    if is_social_debt_borrow(lower):
        return {"jenis": "Utang Masuk", "kategori": "Lain-lain", "sifat": "Need"}
    # Frasa ambigu "utang ke X" → jangan tebak di layer keyword (klarifikasi dulu)
    if is_ambiguous_utang_ke_person(lower):
        return None
    # Piutang: pelunasan dulu, baru pinjamkan (hindari "dipinjam" di teks kembalikan)
    if is_receivable_repay(lower):
        return {"jenis": "Piutang Masuk", "kategori": "Lain-lain", "sifat": "Need"}
    if is_lending_to_others(lower):
        return {"jenis": "Piutang Keluar", "kategori": "Lain-lain", "sifat": "Need"}
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
    if is_bank_admin_fee(lower) or _contains(lower, _KOMUNIKASI):
        return {"jenis": "Pengeluaran", "kategori": "Komunikasi", "sifat": "Need"}
    if _contains(lower, _LAUNDRY):
        return {"jenis": "Pengeluaran", "kategori": "Kesehatan & Kebersihan Diri", "sifat": "Need"}
    if _contains(lower, _PAKAIAN):
        return {"jenis": "Pengeluaran", "kategori": "Pakaian & Aksesoris", "sifat": "Wants"}
    if _contains(lower, _SEWA_KELUAR):
        return {"jenis": "Pengeluaran", "kategori": "Tempat Tinggal", "sifat": "Need"}
    if is_denda_sanksi(lower):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan & Hutang", "sifat": "Need"}
    if _contains(lower, _CICILAN) or is_pinjol_or_cash_loan(lower):
        return {"jenis": "Pengeluaran", "kategori": "Cicilan & Hutang", "sifat": "Need"}
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

    # Likuiditas Sosial: Hutang + Piutang (bukan Pemasukan/Pengeluaran prescription).
    if is_social_debt_repay(lower_combined):
        parsed["jenis"] = "Utang Keluar"
        parsed["kategori"] = "Lain-lain"
        if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
            parsed["sifat"] = "Need"
        parsed.pop("sub_kategori", None)
    elif is_social_debt_borrow(lower_combined):
        parsed["jenis"] = "Utang Masuk"
        parsed["kategori"] = "Lain-lain"
        if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
            parsed["sifat"] = "Need"
        parsed.pop("sub_kategori", None)
    elif is_receivable_repay(lower_combined):
        parsed["jenis"] = "Piutang Masuk"
        parsed["kategori"] = "Lain-lain"
        if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
            parsed["sifat"] = "Need"
    elif is_lending_to_others(lower_combined):
        parsed["jenis"] = "Piutang Keluar"
        if not str(parsed.get("kategori") or "").strip() or str(parsed.get("kategori") or "").strip().lower() in {
            "sosial & keluarga",
            "cicilan & hutang",
            "lain-lain",
            "lainnya",
            "pemasukan",
        }:
            parsed["kategori"] = "Lain-lain"
        if str(parsed.get("sifat") or "").strip() not in {"Need", "Wants"}:
            parsed["sifat"] = "Need"
    # Kata kerja arah uang menang jika AI salah (terima ≠ pengeluaran).
    elif direction and str(parsed.get("jenis") or "").strip() != direction:
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

        # Donasi/tip/hadiah spontan sering salah jadi Makan/Transport/Need.
        if ai_jenis == "Pengeluaran" and is_social_giving(combined):
            if _contains(combined.lower(), ("hadiah", "kado", "parcel", "tip", "tips")):
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

        # Grab/ojek (termasuk ke gym/cafe) → SELALU Transportasi.
        # Klien: semua Grab masuk kategori Transport; bucket/sifat ikut tujuan.
        if ai_jenis == "Pengeluaran" and is_transport_ride(combined):
            if not is_food_delivery(combined) and not is_social_giving(combined):
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
- "makan malam 65.700" → Pengeluaran / Makanan & Minuman / Need
- "grab ke kantor 28rb" → Pengeluaran / Transportasi / Need
- "grab dari kos ke gym 21rb" → Pengeluaran / Transportasi / Wants (bucket Flexible; BUKAN Lifestyle)
- "grab ke networking bisnis 45rb" → Pengeluaran / Transportasi / Need (bucket Future Building)
- "Di pinjam Catherine 1 jt buat bayar RS" → Piutang Keluar (BUKAN Kesehatan; likuiditas sosial)
- "Grace kembalikan uang yang dipinjam sebelumnya 2,7jt" → Piutang Masuk (pelunasan; BUKAN Piutang Keluar / BUKAN Pemasukan)
- "utang ke Ayuti 1jt" → AMBIGU — tanya: meminjamkan (Piutang Keluar) atau berhutang/terima pinjaman (Utang Masuk)
- "pinjam dari Ayuti 1jt" / "ngutang dari Ayuti 1jt" → Utang Masuk (likuiditas naik; BUKAN Pemasukan)
- "bayar utang ke Ayuti 1jt" → Utang Keluar (bayar balik; BUKAN Pengeluaran 4-bucket)
- "pinjamin Ayuti 1jt" / "ngutangin Ayuti 1jt" → Piutang Keluar (saya yang meminjamkan)
- "bayar cicilan pinjol 500rb" → Pengeluaran / Cicilan & Hutang (lembaga; tetap prescription)
- "grabbike ke fitness 21rb" → Pengeluaran / Transportasi / Wants (BUKAN Lifestyle)
- "jajan di grabfood 60k beli kue" → Pengeluaran / Makanan & Minuman / Wants (BUKAN Transportasi)
- "gofood nasi padang 45rb" → Pengeluaran / Makanan & Minuman / Need (BUKAN Transportasi)
- "bayar sewa kos 1.5jt" → Pengeluaran / Tempat Tinggal / Need
- "obat demam 45rb" → Pengeluaran / Kesehatan & Kebersihan Diri / Need
- "pulsa 50rb" → Pengeluaran / Komunikasi / Need
- "admin bank 10 rb" → Pengeluaran / Komunikasi / Need (biaya admin/transfer; BUKAN Lain-lain)
- "biaya transfer BCA 6.500" → Pengeluaran / Komunikasi / Need
- "cicilan motor 900rb" → Pengeluaran / Cicilan & Hutang / Need
- "beli headset 350rb" → Pengeluaran / Lifestyle & Hiburan / Wants
- "kopi susu 28rb" → Pengeluaran / Makanan & Minuman / Wants
- "beli aqua 1.5L 7k" → Pengeluaran / Makanan & Minuman / Need
- "donasi ke bapak grab 5k" → Pengeluaran / Sosial & Keluarga / Wants
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
