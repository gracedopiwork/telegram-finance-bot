"""Aturan impulsif YFD — AI utama, guardrail untuk kasus jelas."""

from __future__ import annotations

from typing import Any

from context_rules import is_discretionary_social_giving

VALID_IMPULSIF = frozenset({"Yes", "No"})

EXPLICIT_IMPULSIVE_KEYWORDS = (
    "iseng",
    "diskon",
    "tiba-tiba",
    "lapar mata",
    "lucu",
    "gemes",
    "pengen",
    "kepengen",
    "fomo",
    "spontan",
    "nggak niat",
    "ngga niat",
    "tidak niat",
    "tanpa rencana",
    "tidak terencana",
    "nggak terencana",
    "ngga terencana",
    "gak terencana",
)

# Penggantian alat/akun yang sudah tidak berfungsi adalah kebutuhan korektif,
# bukan pembelian impulsif, walau muncul mendadak atau "tidak terencana".
FUNCTIONAL_REPLACEMENT_KEYWORDS = (
    "karena rusak",
    "yang rusak",
    "sudah rusak",
    "udah rusak",
    "tidak bisa dipakai",
    "tidak bisa dipake",
    "nggak bisa dipakai",
    "nggak bisa dipake",
    "ngga bisa dipakai",
    "ngga bisa dipake",
    "gak bisa dipakai",
    "gak bisa dipake",
    "gabisa dipakai",
    "gabisa dipake",
    "tidak berfungsi",
    "sudah tidak aktif",
    "akun sebelumnya",
)

PAYDAY_SPLURGE_KEYWORDS = (
    "habis gajian",
    "abis gajian",
    "baru gajian",
    "baru nerima gaji",
    "baru terima gaji",
    "baru dapat gaji",
    "gajianan",
    "habis gajianan",
    "abis gajianan",
    "setelah gajian",
    "pas gajian",
    "tanggal gajian",
    "hari gajian",
)

REWARD_SPENDING_KEYWORDS = (
    "reward",
    "nge treat",
    "netreat",
    "self treat",
    "self-treat",
    "celebrate",
    "celebratory",
)

# Belanja karena kondisi emosional (comfort spending) — impulsif meski kategori makan = Need.
EMOTIONAL_COMFORT_KEYWORDS = (
    "karena capek",
    "karena lelah",
    "karena cape",
    "karena stres",
    "karena stress",
    "karena sedih",
    "karena cemas",
    "karena bosan",
    "karena lonely",
    "karena kesepian",
    "healing",
    "comfort food",
    "comfort spending",
    "me time",
    "buat tenang",
    "biar tenang",
    "penghibur",
    "ngobatin cape",
    "ngobatin capek",
    "ngobatin lelah",
)

FOOD_CATEGORIES = frozenset({
    "Makanan & Minuman",
    "Makan",
    "Jajan",
})

# Acara sosial terencana — bukan impulsif meski nominal besar / mood Happy.
PLANNED_SOCIAL_KEYWORDS = (
    "ulang tahun",
    "ultah",
    "birthday",
    "pernikahan",
    "wedding",
    "resepsi",
    "syukuran",
    "tasyakuran",
    "aqiqah",
    "khitan",
    "baptis",
    "wisuda",
    "graduation",
    "anniversary",
    "peringatan",
    "pemakaman",
    "duka",
    "arisan rutin",
    "kondangan",
    "undangan",
    "acara keluarga",
    "makan keluarga",
    "dengan keluarga",
    "bareng keluarga",
)

ESSENTIAL_KEYWORDS = (
    "tagihan",
    "cicilan",
    "angsuran",
    "bpjs",
    "sewa",
    "kontrakan",
    "kos-kosan",
    "listrik",
    "pln",
    "pdam",
    "spp",
    "sekolah",
    "kuliah",
    "obat",
    "rumah sakit",
    "rs ",
    "dokter",
)

PREMIUM_SPENDING_KEYWORDS = (
    "restaurant",
    "restoran",
    "cafe",
    "kafe",
    "starbucks",
    "coffee shop",
    "fine dining",
    "gofood",
    "grab food",
    "shopeefood",
    "delivery",
)

NEGATIVE_MOODS_FOR_IMPULSE = frozenset({"Sad", "Stressed", "Angry", "Tired"})

# Belanja kecil (kopi/snack harian) jangan dilabel impulsif hanya karena mood negatif.
MIN_NOMINAL_FOR_IMPULSE = 50_000


def _combined_text(parsed: dict[str, Any], source_text: str) -> str:
    return f"{parsed.get('keterangan', '')} {source_text}".lower()


def is_planned_social(combined: str) -> bool:
    return any(keyword in combined for keyword in PLANNED_SOCIAL_KEYWORDS)


def is_essential_obligation(parsed: dict[str, Any], combined: str) -> bool:
    if parsed.get("jenis") != "Pengeluaran":
        return False
    kategori = str(parsed.get("kategori", ""))
    if kategori in {"Listrik", "Air", "Gaji"}:
        return True
    sifat = str(parsed.get("sifat", ""))
    if sifat == "Need" and any(keyword in combined for keyword in ESSENTIAL_KEYWORDS):
        return True
    return any(keyword in combined for keyword in ESSENTIAL_KEYWORDS)


def is_functional_replacement(combined: str) -> bool:
    return any(keyword in combined for keyword in FUNCTIONAL_REPLACEMENT_KEYWORDS)


def has_explicit_impulse_signal(combined: str) -> bool:
    return (
        any(keyword in combined for keyword in EXPLICIT_IMPULSIVE_KEYWORDS)
        or any(keyword in combined for keyword in PAYDAY_SPLURGE_KEYWORDS)
        or any(keyword in combined for keyword in REWARD_SPENDING_KEYWORDS)
    )


def is_emotional_comfort_spending(parsed: dict[str, Any], combined: str) -> bool:
    """Makan/belanja karena capek/sedih/stres = comfort spending impulsif."""
    if not any(keyword in combined for keyword in EMOTIONAL_COMFORT_KEYWORDS):
        return False

    kategori = str(parsed.get("kategori", ""))
    sifat = str(parsed.get("sifat", ""))
    if kategori in FOOD_CATEGORIES or sifat == "Wants":
        return True

    # Teks jelas tentang makan/jajan meski label kategori masih lama/salah.
    return any(
        token in combined
        for token in (
            "makan",
            "makanan",
            "jajan",
            "kopi",
            "boba",
            "snack",
            "gofood",
            "grabfood",
            "cafe",
            "resto",
            "restoran",
        )
    )


def resolve_impulsif(
    parsed: dict[str, Any],
    source_text: str = "",
    *,
    ai_suggested: str | None = None,
    trust_ai: bool = False,
) -> str:
    """
    Urutan keputusan:
    1) Guardrail wajib (acara sosial / tagihan / penggantian rusak) — override AI
    2) Sinyal impulsif kuat (spontan / pasca-gajian / comfort spending emosional)
    3) Keputusan AI — nominal kecil tetap No kecuali sinyal eksplisit
    4) Heuristik fallback sempit
    """
    if parsed.get("jenis") != "Pengeluaran":
        return "No"

    combined = _combined_text(parsed, source_text)
    nominal = int(parsed.get("nominal", 0) or 0)

    if is_planned_social(combined):
        return "No"

    if is_discretionary_social_giving(combined):
        return "Yes"

    if is_essential_obligation(parsed, combined):
        return "No"

    if is_functional_replacement(combined):
        return "No"

    if has_explicit_impulse_signal(combined):
        return "Yes"

    # Kopi/snack kecil (mis. 15rb) bukan impulsif meski Tired + healing/Wants.
    if nominal > 0 and nominal < MIN_NOMINAL_FOR_IMPULSE:
        return "No"

    # "makan malam 100rb karena capek" → Yes, override AI yang sering bilang Need/No.
    if is_emotional_comfort_spending(parsed, combined):
        return "Yes"

    if trust_ai and ai_suggested in VALID_IMPULSIF:
        return ai_suggested

    return infer_impulsif_fallback(parsed, combined)


def infer_impulsif_fallback(parsed: dict[str, Any], combined: str) -> str:
    """Parser tanpa AI — heuristik konservatif."""
    mood = str(parsed.get("mood", "Neutral"))
    nominal = int(parsed.get("nominal", 0) or 0)
    kategori = str(parsed.get("kategori", ""))
    sifat = str(parsed.get("sifat", ""))
    is_food_out = kategori in FOOD_CATEGORIES
    is_premium = any(keyword in combined for keyword in PREMIUM_SPENDING_KEYWORDS)

    if nominal > 0 and nominal < MIN_NOMINAL_FOR_IMPULSE:
        return "No"

    if mood in NEGATIVE_MOODS_FOR_IMPULSE:
        if sifat == "Wants" and nominal >= MIN_NOMINAL_FOR_IMPULSE:
            return "Yes"
        if is_food_out and (is_premium or nominal >= 100_000):
            return "Yes"

    return "No"
