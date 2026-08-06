"""Parse nominal rupiah dari teks transaksi — hindari salah baca rb/k di kata biasa."""

from __future__ import annotations

import re

_JT_PATTERN = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta|milyun|miliar)\b",
    re.IGNORECASE,
)
# "2 jt 5 ratus" / "2 juta 500 rb" / "2 jt setengah"
_COMPOUND_JT_RATUS = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta)\s+(\d+(?:[.,]\d+)?)\s*ratus\b",
    re.IGNORECASE,
)
_COMPOUND_JT_RIBU = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta)\s+(\d+(?:[.,]\d+)?)\s*(?:rb|br|ribu|k)\b",
    re.IGNORECASE,
)
_COMPOUND_JT_HALF = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta)\s+setengah\b",
    re.IGNORECASE,
)
# k/rb menempel pada angka (50k, 50rb). "br" = typo umum untuk "rb".
# "ribu" / "rb" / "br" boleh pakai spasi (50 ribu, 90 br).
_RIBU_ATTACHED = re.compile(
    r"(\d+(?:[.,]\d+)?)(?:rb|br|k)\b",
    re.IGNORECASE,
)
_RIBU_SPACED = re.compile(
    r"(\d+(?:[.,]\d+)?)\s+(?:ribu|rb|br)\b",
    re.IGNORECASE,
)
_GROUPED_AMOUNT = re.compile(r"\d{1,3}(?:[.,]\d{3})+")
_PLAIN_NUMBER = re.compile(r"\d+(?:[.,]\d+)?")
_TRAILING_PLAIN = re.compile(r"(\d{4,7})\s*$")

# Konteks tagihan yang jarang bernilai sangat kecil (tanpa suffix ribu).
_HIGH_COST_HINTS = (
    "listrik",
    "pln",
    "token",
    "tagihan",
    "sewa",
    "kos",
    "kontrakan",
    "kpr",
    "bpjs",
    "asuransi",
    "cicilan",
    "angsuran",
    "kuliah",
    "spp",
    "wifi",
    "indihome",
    "pdam",
)


def _token_to_float(token: str) -> float:
    raw = token.strip()
    # Ribuan ID: 1.200.000 / 65.700
    if re.match(r"^\d{1,3}(\.\d{3})+(,\d+)?$", raw):
        raw = raw.replace(".", "").replace(",", ".")
    elif re.match(r"^\d{1,3}(,\d{3})+(\.\d+)?$", raw):
        raw = raw.replace(",", "")
    elif re.match(r"^\d+\.\d{3}$", raw):
        # Satu pemisah ribuan 3 digit (65.700)
        raw = raw.replace(".", "")
    elif re.match(r"^\d+,\d{1,2}$", raw):
        # Desimal koma: 1,2
        raw = raw.replace(",", ".")
    elif re.match(r"^\d+\.\d{1,2}$", raw):
        # Desimal titik: 1.2 — biarkan
        pass
    else:
        raw = raw.replace(",", ".")
    return float(raw)


def _has_explicit_scale_suffix(text: str, number_token: str) -> bool:
    """True jika angka diikuti suffix ribu/juta yang valid (bukan huruf k di kata lain)."""
    pattern = (
        rf"{re.escape(number_token)}"
        rf"(?:\s*(?:rb|br|ribu|k|jt|juta|milyun|miliar)\b|(?:rb|br|k)\b)"
    )
    return bool(re.search(pattern, text, flags=re.IGNORECASE))


def _parse_trailing_plain_amount(text: str) -> int | None:
    """Angka polos 4–7 digit di akhir teks, tanpa suffix rb/k/jt (mis. makan malam 65700)."""
    match = _TRAILING_PLAIN.search(text.strip())
    if not match:
        return None

    token = match.group(1)
    if _has_explicit_scale_suffix(text, token):
        return None

    return max(1, int(token))


def _parse_ribu_amount(text: str) -> int | None:
    for pattern in (_RIBU_ATTACHED, _RIBU_SPACED):
        match = pattern.search(text)
        if match:
            return max(1, int(round(_token_to_float(match.group(1)) * 1_000)))
    return None


def _parse_compound_jt_amount(text: str) -> int | None:
    """Gabungan juta + pecahan: 2 jt 5 ratus → 2.500.000."""
    match = _COMPOUND_JT_RATUS.search(text)
    if match:
        millions = _token_to_float(match.group(1)) * 1_000_000
        hundreds = _token_to_float(match.group(2)) * 100_000
        return max(1, int(round(millions + hundreds)))

    match = _COMPOUND_JT_RIBU.search(text)
    if match:
        millions = _token_to_float(match.group(1)) * 1_000_000
        thousands = _token_to_float(match.group(2)) * 1_000
        return max(1, int(round(millions + thousands)))

    match = _COMPOUND_JT_HALF.search(text)
    if match:
        return max(1, int(round(_token_to_float(match.group(1)) * 1_000_000 + 500_000)))

    return None


def parse_nominal_from_text(text: str) -> int:
    """Ekstrak nominal integer dari teks. Raise ValueError jika tidak ada angka valid."""
    cleaned = text.strip()
    if cleaned == "":
        raise ValueError("invalid_nominal")

    lower = cleaned.lower()

    trailing_plain = _parse_trailing_plain_amount(cleaned)
    if trailing_plain is not None:
        return trailing_plain

    compound = _parse_compound_jt_amount(lower)
    if compound is not None:
        return compound

    jt_match = _JT_PATTERN.search(lower)
    if jt_match:
        return max(1, int(round(_token_to_float(jt_match.group(1)) * 1_000_000)))

    ribu_amount = _parse_ribu_amount(lower)
    if ribu_amount is not None:
        return ribu_amount

    grouped = list(_GROUPED_AMOUNT.finditer(cleaned))
    if grouped:
        token = grouped[-1].group()
        return max(1, int(round(_token_to_float(token))))

    plain_matches = list(_PLAIN_NUMBER.finditer(cleaned))
    if not plain_matches:
        raise ValueError("invalid_nominal")

    return max(1, int(round(_token_to_float(plain_matches[-1].group()))))


def reconcile_nominal(ai_nominal: int, source_text: str) -> int:
    """Selaraskan nominal AI dengan parser deterministik dari teks asli user."""
    if ai_nominal <= 0:
        try:
            return parse_nominal_from_text(source_text)
        except ValueError:
            return ai_nominal

    try:
        parsed = parse_nominal_from_text(source_text)
    except ValueError:
        return ai_nominal

    if parsed <= 0:
        return ai_nominal

    for factor in (1_000, 1_000_000):
        if ai_nominal == parsed * factor:
            return parsed

    larger = max(ai_nominal, parsed)
    smaller = min(ai_nominal, parsed)
    if smaller == 0:
        return parsed

    # AI sering salah skala (65700 → 65,7 jt) — percayai parser teks jika beda >= 5x.
    if larger / smaller >= 5:
        return parsed

    if ai_nominal == parsed:
        return ai_nominal

    return parsed


def nominal_sanity_warning(
    amount: int,
    source_text: str = "",
    kategori: str = "",
) -> str | None:
    """Peringatan jika nominal terlihat tidak masuk akal untuk konteksnya."""
    if amount <= 0:
        return None

    combined = f"{source_text} {kategori}".lower()
    high_cost = any(hint in combined for hint in _HIGH_COST_HINTS)

    if high_cost and amount < 1_000:
        scaled = f"Rp{amount * 1_000:,}"
        return (
            f"⚠️ Nominal Rp{amount:,} terlihat terlalu kecil untuk tagihan ini.\n"
            f"Kalau maksudnya {amount}rb, seharusnya {scaled}. Cek lagi ya."
        )

    if amount < 100 and not high_cost:
        return (
            f"⚠️ Nominal Rp{amount:,} sangat kecil. "
            "Kalau maksudnya ribuan, tulis mis. 90rb / 90 ribu."
        )

    return None
