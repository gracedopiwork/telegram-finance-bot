"""YFD AI Taxonomy v1.3 (2 Agustus 2026) — closed vocabulary & grey-area helpers.

Source: docs/YFD_AI_Taxonomy_REVISI UPDATED TANGGAL 2 AGUSTUS 2026.pdf
AI picks kategori/jenis/sifat only. Bucket is system-determined (not AI).
"""

from __future__ import annotations

from typing import Any

# ---------------------------------------------------------------------------
# Closed lists (Layer 1)
# ---------------------------------------------------------------------------

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
    "Pakaian & Aksesoris",
    "Lain-lain",
    "Biaya Legal, Administrasi & Peristiwa Besar",
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

VALID_JENIS = (
    "Pemasukan",
    "Pengeluaran",
    "Saving/Investment",
    "Piutang Keluar",
    "Piutang Masuk",
    "Kewajiban Pajak",
)

VALID_SIFAT = ("Need", "Wants")

VALID_MOOD = ("Happy", "Neutral", "Sad", "Stressed", "Angry", "Tired")

# ---------------------------------------------------------------------------
# Grey area (§2.18 / §3.3) — item key → clarification template
# ---------------------------------------------------------------------------

GREY_AREA_GENERIC_TEMPLATE = (
    "Saya mau pastikan dulu — {item} untuk keperluan kerja/kebutuhan, "
    "atau personal/lifestyle? Ini berpengaruh ke bucket alokasi kamu."
)

# Templates prefer wording from the taxonomy PDF where available.
GREY_AREA_ITEMS: dict[str, dict[str, str]] = {
    "perabot": {
        "label": "peralatan/perabot rumah tangga",
        "question": (
            "Peralatan/perabot ini untuk mengganti yang rusak/belum memadai, "
            "atau menambah koleksi/ikuti tren?"
        ),
    },
    "kopi": {
        "label": "Starbucks/kopi",
        "question": "Kopi/ngopi ini untuk meeting kerja/klien, atau untuk healing/santai?",
    },
    "laptop": {
        "label": "laptop/komputer",
        "question": (
            "Laptop/komputer ini alat kerja utama yang rusak, "
            "atau upgrade karena FOMO/tren?"
        ),
    },
    "hp": {
        "label": "handphone/HP",
        "question": (
            "HP ini ganti HP utama yang rusak (setara), "
            "upgrade model terbaru, atau khusus operasional bisnis?"
        ),
    },
    "dp_kendaraan": {
        "label": "DP kendaraan",
        "question": (
            "DP kendaraan ini untuk mobilitas kerja utama, "
            "atau kendaraan kedua/upgrade gaya hidup?"
        ),
    },
    "pajak_kendaraan": {
        "label": "pajak kendaraan",
        "question": (
            "Pajak/STNK ini untuk kendaraan utama mobilitas kerja, "
            "atau kendaraan kedua/hobi?"
        ),
    },
    "kpr_pbb": {
        "label": "KPR/PBB",
        "question": (
            "Properti ini untuk ditinggali sendiri atau investasi/disewakan?"
        ),
    },
    "subscription": {
        "label": "subscription digital",
        "question": (
            "Subscription ini untuk bisnis/kerja, atau entertainment/lifestyle?"
        ),
    },
    "coaching": {
        "label": "coaching/les",
        "question": (
            "Coaching/les ini untuk skill peningkatan penghasilan/kerja, "
            "atau hobi/enjoyment?"
        ),
    },
    "transport": {
        "label": "transportasi",
        "question": (
            "Transport ini untuk tujuan wajib (kantor/sekolah/klinik), "
            "lifestyle/sosial (cafe/mall/healing), atau bisnis/kerja/networking?"
        ),
    },
    "art": {
        "label": "gaji ART/driver/babysitter",
        "question": (
            "Ini menunjang kemampuan kerja kamu (mis. babysitter agar bisa praktik) "
            "atau kenyamanan tambahan?"
        ),
    },
    "pinjol": {
        "label": "cicilan pinjol/pinjaman tunai",
        "question": (
            "Ini cicilan pinjol/pinjaman tunai untuk kebutuhan mendesak "
            "atau konsumsi non-esensial?"
        ),
    },
    "notaris": {
        "label": "notaris/legal terkait aset",
        "question": (
            "Biaya notaris/legal ini terkait aset apa — rumah tinggal utama, "
            "kendaraan kerja, atau properti/kendaraan investasi?"
        ),
    },
    "fashion": {
        "label": "pakaian/fashion",
        "question": (
            "Pakaian ini untuk kerja/sekolah/kebutuhan wajib, "
            "atau fashion/lifestyle?"
        ),
    },
    "fisioterapi": {
        "label": "fisioterapi/rehab",
        "question": (
            "Fisioterapi/rehab ini diresepkan dokter, "
            "atau pilihan sendiri tanpa resep?"
        ),
    },
    "buku": {
        "label": "buku",
        "question": (
            "Buku ini untuk pengembangan diri/belajar, kebutuhan wajib sekolah, "
            "atau hiburan seperti novel/komik?"
        ),
    },
    "alat_musik": {
        "label": "alat musik",
        "question": (
            "Alat musik ini untuk belajar/pengembangan diri, kebutuhan profesi, "
            "kebutuhan wajib sekolah, atau hobi/hiburan?"
        ),
    },
}


def grey_area_question(item_key: str) -> str | None:
    """Return taxonomy-aligned clarification question for a grey-area item key."""
    meta = GREY_AREA_ITEMS.get(item_key)
    if not meta:
        return None
    return meta["question"]


def generic_grey_area_question(item_label: str) -> str:
    """Section 3.3 generic confirmation wording."""
    return GREY_AREA_GENERIC_TEMPLATE.format(item=item_label)


def clarification_purpose_markers() -> tuple[str, ...]:
    """Markers that mean the user already answered a purpose question."""
    return ("klarifikasi user:",)


def parsed_already_clarified(parsed: dict[str, Any], source_text: str = "") -> bool:
    text = f"{source_text} {parsed.get('keterangan', '')}".lower()
    return any(m in text for m in clarification_purpose_markers())


def detect_taxonomy_flags(parsed: dict[str, Any], source_text: str = "") -> list[str]:
    """Behavioral flags from taxonomy §2.14 / §5C — not bucket overrides."""
    text = f"{source_text} {parsed.get('keterangan', '')}".lower()
    kategori = str(parsed.get("kategori") or "").strip().lower()
    flags: list[str] = []

    if any(k in text for k in ("pinjol", "pinjaman online", "pinjaman tunai")):
        flags.append("risk_alert")

    if any(
        k in text
        for k in (
            "tilang",
            "denda keterlambatan",
            "denda cicilan",
            "denda kartu kredit",
            "sanksi keterlambatan",
        )
    ) or ("denda" in text and "pajak" not in text):
        flags.append("late_pattern")

    life_event_hits = (
        "mahar",
        "pernikahan",
        "resepsi",
        "duka",
        "pemakaman",
        "tahlilan",
        "dp rumah",
        "dp properti",
        "dp kpr",
        "dp mobil",
        "dp motor",
        "dp kendaraan",
        "uang muka rumah",
        "uang muka mobil",
        "uang muka motor",
    )
    if any(k in text for k in life_event_hits) or "peristiwa besar" in kategori:
        flags.append("life_event")

    # Deduplicate while preserving order
    seen: set[str] = set()
    out: list[str] = []
    for flag in flags:
        if flag not in seen:
            seen.add(flag)
            out.append(flag)
    return out


def attach_taxonomy_flags(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    flags = detect_taxonomy_flags(parsed, source_text)
    if flags:
        parsed["taxonomy_flags"] = flags
    else:
        parsed.pop("taxonomy_flags", None)
    return parsed
