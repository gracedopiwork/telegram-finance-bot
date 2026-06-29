"""Daftar kategori & sub-kategori resmi YFD (sesuai dropdown Google Sheet)."""

from __future__ import annotations

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

_SUB_KEYWORDS: tuple[tuple[str, ...], str] = (
    (("ojek", "grab", "gojek", "angkot", "transjakarta", "bus", "mrt", "kereta", "tol", "parkir", "bensin"), "Angkutan Umum"),
    (("servis", "bengkel", "oli", "ban"), "Servis Kendaraan"),
    (("listrik", "pln"), "Listrik"),
    (("pdam", "air"), "Pengeluaran lain-lain"),
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
)


def _enum_join(values: Iterable[str]) -> str:
    return " | ".join(f'"{v}"' for v in values)


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


def _allowed_subs_for_kategori(kategori: str) -> tuple[str, ...]:
    return KATEGORI_SUB_MAP.get(kategori, ("Pengeluaran lain-lain",))


def normalize_category_fields(parsed: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    """Selaraskan kategori/sub_kategori dengan dropdown Sheet YFD."""
    kategori = str(parsed.get("kategori", "")).strip()
    sub = str(parsed.get("sub_kategori", "")).strip()
    combined = f"{parsed.get('keterangan', '')} {source_text}".lower()

    if kategori not in VALID_KATEGORI:
        if any(word in combined for word in ("gaji", "bonus", "honor", "income")):
            kategori = "Gaji"
        elif any(word in combined for word in ("ojek", "grab", "transport", "bensin", "tol", "parkir")):
            kategori = "Transport"
        elif any(word in combined for word in ("listrik", "pln")):
            kategori = "Listrik"
        elif any(word in combined for word in ("pdam", " air")):
            kategori = "Air"
        elif any(word in combined for word in ("hadiah", "amplop", "konser", "ultah")):
            kategori = "Social"
        elif any(word in combined for word in ("makan", "restaurant", "restoran", "nasi")):
            kategori = "Makan"
        else:
            kategori = "Jajan"

    allowed = _allowed_subs_for_kategori(kategori)

    if sub not in VALID_SUB_KATEGORI or sub not in allowed:
        detected = _detect_sub_from_text(combined)
        if detected in allowed:
            sub = detected
        elif sub in allowed:
            pass
        elif sub in VALID_SUB_KATEGORI:
            # Sub valid tapi tidak cocok induk — pindah kategori jika ada yang cocok
            for parent, subs in KATEGORI_SUB_MAP.items():
                if sub in subs:
                    kategori = parent
                    allowed = subs
                    break
        else:
            sub = allowed[0]

    parsed["kategori"] = kategori
    parsed["sub_kategori"] = sub
    return parsed
