"""Parse nominal rupiah dari teks transaksi — hindari salah baca rb/k di kata biasa."""

from __future__ import annotations

import re

_JT_PATTERN = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta|milyun|miliar)\b",
    re.IGNORECASE,
)
_RIBU_PATTERN = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:rb|ribu|k)\b",
    re.IGNORECASE,
)
_GROUPED_AMOUNT = re.compile(r"\d{1,3}(?:[.,]\d{3})+")
_PLAIN_NUMBER = re.compile(r"\d+(?:[.,]\d+)?")


def _token_to_float(token: str) -> float:
    raw = token.strip()
    if re.match(r"^\d{1,3}(\.\d{3})+(,\d+)?$", raw):
        raw = raw.replace(".", "").replace(",", ".")
    elif re.match(r"^\d{1,3}(,\d{3})+(\.\d+)?$", raw):
        raw = raw.replace(",", "")
    elif re.match(r"^\d+\.\d{3}$", raw):
        raw = raw.replace(".", "")
    else:
        raw = raw.replace(".", "").replace(",", ".")
    return float(raw)


def parse_nominal_from_text(text: str) -> int:
    """Ekstrak nominal integer dari teks. Raise ValueError jika tidak ada angka valid."""
    cleaned = text.strip()
    if cleaned == "":
        raise ValueError("invalid_nominal")

    lower = cleaned.lower()

    jt_match = _JT_PATTERN.search(lower)
    if jt_match:
        return max(1, int(round(_token_to_float(jt_match.group(1)) * 1_000_000)))

    ribu_match = _RIBU_PATTERN.search(lower)
    if ribu_match:
        return max(1, int(round(_token_to_float(ribu_match.group(1)) * 1_000)))

    grouped = list(_GROUPED_AMOUNT.finditer(cleaned))
    if grouped:
        token = grouped[-1].group()
        return max(1, int(round(_token_to_float(token))))

    plain_matches = list(_PLAIN_NUMBER.finditer(cleaned))
    if not plain_matches:
        raise ValueError("invalid_nominal")

    # Angka polos di akhir kalimat biasanya nominal (contoh: "... indomaret 83800").
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

    larger = max(ai_nominal, parsed)
    smaller = min(ai_nominal, parsed)
    if smaller == 0:
        return parsed

    # AI sering salah skala (83800 → 83jt) — percayai parser teks jika beda >= 5x.
    if larger / smaller >= 5:
        return parsed

    if ai_nominal == parsed:
        return ai_nominal

    # Beda kecil: tetap pakai parser teks (lebih konsisten dengan input user).
    return parsed
