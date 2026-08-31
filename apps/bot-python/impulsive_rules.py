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
    "impulsif",
    "impulsive",
    "impulse",
    "belanja impulsif",
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
    "sebelumnya rusak",
    "yg rusak",
    "yang sebelumnya rusak",
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
    "Minuman",
})

# Jajan/snack singkat sering tanpa kata "spontan" — tanya user bila ambigu.
SNACK_CLARIFY_KEYWORDS = (
    "jajan",
    "jajanan",
    "snack",
    "cemilan",
    "ngemil",
    "dessert",
    "brownies",
    "boba",
    "es krim",
    "ice cream",
    "gorengan",
    "keripik",
    "permen",
    "coklat",
    "chocolate",
    "donat",
    "donut",
    "kue basah",
    "kue kering",
    "croissant",
    "cookies",
    "kue",
    "pastry",
)

# Signal No taxonomy §3.5 — "terencana" menang atas heuristic hadiah=impulsif.
_UNPLANNED_TERENCANA = (
    "tidak terencana",
    "nggak terencana",
    "ngga terencana",
    "gak terencana",
    "ga terencana",
    "belum terencana",
    "tanpa rencana",
    "ga rencanain",
    "gak rencanain",
    "nggak rencanain",
    "tidak rencanain",
)

PLANNED_PURCHASE_KEYWORDS = (
    "terencana",
    "terencan",
    "trencana",
    "direncanakan",
    "di rencanakan",
    "sudah rencanain",
    "udah rencanain",
    "sudah direncanakan",
    "udah direncanakan",
    "sudah rencanakan",
    "udah rencanakan",
    "sudah rencana",
    "udah rencana",
    "ada rencana",
    "rencanain",
    "tabungan buat ini",
    "langganan bulanan",
    "sudah niat",
    "udah niat",
    "planned",
)

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

# Item umum di struk F&B yang sering impulsif meski OCR tidak tulis "jajan".
RECEIPT_DISCRETIONARY_KEYWORDS = SNACK_CLARIFY_KEYWORDS + PREMIUM_SPENDING_KEYWORDS + (
    "kopi",
    "coffee",
    "latte",
    "matcha",
    "cappuccino",
    "espresso",
    "americano",
    "teh",
    "tea",
    "smoothie",
    "milkshake",
    "bubble tea",
    "minuman",
)

NEGATIVE_MOODS_FOR_IMPULSE = frozenset({"Sad", "Stressed", "Angry", "Tired"})

# Belanja kecil (kopi/snack harian) jangan dilabel impulsif hanya karena mood negatif.
MIN_NOMINAL_FOR_IMPULSE = 50_000


def _combined_text(parsed: dict[str, Any], source_text: str) -> str:
    return f"{parsed.get('keterangan', '')} {source_text}".lower()


def is_planned_social(combined: str) -> bool:
    return any(keyword in combined for keyword in PLANNED_SOCIAL_KEYWORDS)


def has_planned_purchase_signal(combined: str) -> bool:
    """§3.5 Signal No: terencana / sudah rencanain. 'tidak terencana' bukan planned."""
    lower = combined.lower()
    if any(keyword in lower for keyword in _UNPLANNED_TERENCANA):
        return False
    return any(keyword in lower for keyword in PLANNED_PURCHASE_KEYWORDS)


def stamp_planned_cue(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    """AI sering drop kata 'terencana' dari keterangan — simpan cue-nya."""
    combined = _combined_text(parsed, source_text)
    if not has_planned_purchase_signal(combined):
        return parsed
    note = str(parsed.get("keterangan") or "").strip()
    if note and not has_planned_purchase_signal(note):
        parsed["keterangan"] = f"{note} (terencana)"
    return parsed


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


def is_discretionary_snack(parsed: dict[str, Any], combined: str) -> bool:
    """Jajan/cemilan — kandidat tanya impulsif bila tanpa sinyal jelas."""
    if any(keyword in combined for keyword in SNACK_CLARIFY_KEYWORDS):
        return True
    kategori = str(parsed.get("kategori") or "").strip().lower()
    return kategori in {"jajan", "cemilan", "snack"}


def is_receipt_discretionary_food(parsed: dict[str, Any], combined: str) -> bool:
    """
    Foto struk F&B jarang punya kata 'jajan'/'spontan'.
    Tanya klarifikasi untuk makanan/minuman diskresioner dari OCR.
    """
    kategori = str(parsed.get("kategori") or "").strip()
    sifat = str(parsed.get("sifat") or "").strip()
    in_food = kategori in FOOD_CATEGORIES or any(
        token in combined
        for token in ("makan", "makanan", "minuman", "resto", "restoran", "cafe", "kafe")
    )
    if not in_food:
        return False

    if sifat == "Wants":
        return True

    # Need + item/merchant yang tipikal jajan/cafe/delivery → tetap tanya.
    return any(keyword in combined for keyword in RECEIPT_DISCRETIONARY_KEYWORDS)


def needs_impulse_clarification(
    parsed: dict[str, Any],
    source_text: str = "",
    *,
    from_receipt: bool = False,
) -> bool:
    """
    True bila belanja jajan/snack (atau F&B dari foto struk) tanpa sinyal jelas.
    Bot sebaiknya tanya user: terencana atau tidak.
    """
    if parsed.get("jenis") != "Pengeluaran":
        return False

    combined = _combined_text(parsed, source_text)

    if has_planned_purchase_signal(combined):
        return False
    if has_explicit_impulse_signal(combined):
        return False
    if is_planned_social(combined):
        return False
    if is_functional_replacement(combined):
        return False
    if is_essential_obligation(parsed, combined):
        return False
    # Comfort emosional sudah cukup jelas → resolve_impulsif = Yes, jangan tanya lagi.
    if is_emotional_comfort_spending(parsed, combined):
        return False

    if from_receipt:
        return is_receipt_discretionary_food(parsed, combined) or is_discretionary_snack(
            parsed, combined
        )

    return is_discretionary_snack(parsed, combined)


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

    if has_planned_purchase_signal(combined):
        return "No"

    if is_planned_social(combined):
        return "No"

    if is_functional_replacement(combined):
        return "No"

    if is_essential_obligation(parsed, combined):
        return "No"

    if has_explicit_impulse_signal(combined):
        return "Yes"

    # Tip/hadiah kecil tanpa kata terencana → Yes. Hadiah bernominal besar jangan dipaksa Yes.
    if is_discretionary_social_giving(combined) and 0 < nominal < 100_000:
        return "Yes"

    # Comfort spending emosional: jajan/snack karena capek → Yes meski nominal kecil.
    # Kopi kecil + healing saja tetap No (lihat guardrail nominal di bawah).
    if is_emotional_comfort_spending(parsed, combined):
        discretionary_snack = any(
            token in combined
            for token in (
                "jajan",
                "snack",
                "cemilan",
                "ngemil",
                "brownies",
                "dessert",
                "comfort food",
                "comfort spending",
            )
        )
        if discretionary_snack or nominal <= 0 or nominal >= MIN_NOMINAL_FOR_IMPULSE:
            return "Yes"

    # Kopi/snack kecil (mis. 15rb) bukan impulsif meski Tired + healing/Wants.
    if nominal > 0 and nominal < MIN_NOMINAL_FOR_IMPULSE:
        return "No"

    if trust_ai and ai_suggested in VALID_IMPULSIF:
        kategori = str(parsed.get("kategori") or "").strip().lower()
        if (
            ai_suggested == "Yes"
            and kategori == "hadiah"
            and not has_explicit_impulse_signal(combined)
        ):
            return "No"
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
