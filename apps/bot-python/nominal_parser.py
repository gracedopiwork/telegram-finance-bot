"""Parse nominal rupiah dari teks transaksi — hindari salah baca rb/k di kata biasa."""

from __future__ import annotations

import re

_JT_PATTERN = re.compile(
    r"(\d+(?:[.,]\d+)?)\s*(?:jt|juta|milyun|miliar)\b",
    re.IGNORECASE,
)
# k/rb harus menempel pada angka (50k, 50rb). "ribu" boleh pakai spasi (50 ribu).
_RIBU_ATTACHED = re.compile(
    r"(\d+(?:[.,]\d+)?)(?:rb|k)\b",
    re.IGNORECASE,
)
_RIBU_SPACED = re.compile(
    r"(\d+(?:[.,]\d+)?)\s+ribu\b",
    re.IGNORECASE,
)
_GROUPED_AMOUNT = re.compile(r"\d{1,3}(?:[.,]\d{3})+")
_PLAIN_NUMBER = re.compile(r"\d+(?:[.,]\d+)?")
_TRAILING_PLAIN = re.compile(r"(\d{4,7})\s*$")


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


def _has_explicit_scale_suffix(text: str, number_token: str) -> bool:
    """True jika angka diikuti suffix ribu/juta yang valid (bukan huruf k di kata lain)."""
    pattern = rf"{re.escape(number_token)}(?:\s*(?:rb|ribu|k|jt|juta|milyun|miliar)\b|(?:rb|k)\b)"
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


def parse_nominal_from_text(text: str) -> int:
    """Ekstrak nominal integer dari teks. Raise ValueError jika tidak ada angka valid."""
    cleaned = text.strip()
    if cleaned == "":
        raise ValueError("invalid_nominal")

    lower = cleaned.lower()

    trailing_plain = _parse_trailing_plain_amount(cleaned)
    if trailing_plain is not None:
        return trailing_plain

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
