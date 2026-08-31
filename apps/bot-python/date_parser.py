"""Ekstrak tanggal transaksi dari teks user (backdating)."""

from __future__ import annotations

import re
from datetime import date, datetime, timedelta, timezone
from zoneinfo import ZoneInfo

DEFAULT_TZ = ZoneInfo("Asia/Jakarta")

_MONTHS: dict[str, int] = {
    "januari": 1,
    "jan": 1,
    "februari": 2,
    "feb": 2,
    "maret": 3,
    "mar": 3,
    "april": 4,
    "apr": 4,
    "mei": 5,
    "juni": 6,
    "jun": 6,
    "juli": 7,
    "jul": 7,
    "agustus": 8,
    "agu": 8,
    "ags": 8,
    "september": 9,
    "sep": 9,
    "sept": 9,
    "oktober": 10,
    "okt": 10,
    "november": 11,
    "nov": 11,
    "desember": 12,
    "des": 12,
}

_DATE_PREFIX = r"(?:tgl|tanggal|tgl\.|tgl:)"
_MONTH_NAMES = "|".join(sorted(_MONTHS.keys(), key=len, reverse=True))


def _safe_date(year: int, month: int, day: int) -> date | None:
    try:
        return date(year, month, day)
    except ValueError:
        return None


def _resolve_year(month: int, day: int, *, today: date) -> int:
    """Tanpa tahun: pakai tahun berjalan; jika jauh di masa depan, geser ke tahun lalu."""
    candidate = _safe_date(today.year, month, day)
    if candidate is None:
        return today.year
    # Izinkan sampai 1 hari ke depan (timezone edge); selain itu anggap tahun lalu.
    if candidate > today + timedelta(days=1):
        prev = _safe_date(today.year - 1, month, day)
        if prev is not None:
            return today.year - 1
    return today.year


def _to_recorded_at(d: date, *, now: datetime | None = None, tz: ZoneInfo = DEFAULT_TZ) -> datetime:
    """Simpan tanggal lokal di tengah hari agar tidak geser hari saat dikonversi UTC."""
    return datetime(d.year, d.month, d.day, 12, 0, 0, tzinfo=tz)


def extract_transaction_date(
    text: str,
    *,
    now: datetime | None = None,
    tz: ZoneInfo = DEFAULT_TZ,
) -> datetime | None:
    """
    Ambil tanggal eksplisit dari catatan.
    Contoh yang didukung:
      - tgl 2/7 beli makan 50k
      - tanggal 02-07-2026 makan 50rb
      - tgl 2 juli makan 50k
      - kemarin beli kopi 20k
      - 2 hari lalu makan 30k
    """
    if not text or not text.strip():
        return None

    local_now = (now or datetime.now(tz)).astimezone(tz)
    today = local_now.date()
    raw = text.strip()
    lower = raw.lower()

    # Relatif
    if re.search(r"\bkemarin\b", lower):
        return _to_recorded_at(today - timedelta(days=1), now=local_now, tz=tz)

    relative = re.search(r"\b(\d{1,2})\s*hari\s*lalu\b", lower)
    if relative:
        days = int(relative.group(1))
        if 1 <= days <= 60:
            return _to_recorded_at(today - timedelta(days=days), now=local_now, tz=tz)

    # tgl 2 juli [2026]
    named = re.search(
        rf"\b{_DATE_PREFIX}\s*[:.]?\s*(\d{{1,2}})\s+({_MONTH_NAMES})(?:\s+(\d{{4}}))?\b",
        lower,
        flags=re.IGNORECASE,
    )
    if named:
        day = int(named.group(1))
        month = _MONTHS[named.group(2).lower()]
        year = int(named.group(3)) if named.group(3) else _resolve_year(month, day, today=today)
        d = _safe_date(year, month, day)
        if d is not None:
            return _to_recorded_at(d, now=local_now, tz=tz)

    # tgl 2/7, tanggal 2-7-2026, tgl 02.07.26
    numeric = re.search(
        rf"\b{_DATE_PREFIX}\s*[:.]?\s*(\d{{1,2}})[/.\-](\d{{1,2}})(?:[/.\-](\d{{2,4}}))?\b",
        lower,
        flags=re.IGNORECASE,
    )
    if numeric:
        day = int(numeric.group(1))
        month = int(numeric.group(2))
        if numeric.group(3):
            year_raw = int(numeric.group(3))
            year = year_raw + 2000 if year_raw < 100 else year_raw
        else:
            year = _resolve_year(month, day, today=today)
        d = _safe_date(year, month, day)
        if d is not None:
            return _to_recorded_at(d, now=local_now, tz=tz)

    # ISO 2026-07-02 (dengan atau tanpa prefix tgl)
    iso = re.search(rf"\b(?:{_DATE_PREFIX}\s*[:.]?\s*)?(\d{{4}})-(\d{{2}})-(\d{{2}})\b", lower)
    if iso:
        d = _safe_date(int(iso.group(1)), int(iso.group(2)), int(iso.group(3)))
        if d is not None:
            return _to_recorded_at(d, now=local_now, tz=tz)

    # AI field YYYY-MM-DD saja (tanpa kata tgl) — dipanggil terpisah via apply_ai_tanggal
    return None


def parse_ai_tanggal(value: object, *, now: datetime | None = None, tz: ZoneInfo = DEFAULT_TZ) -> datetime | None:
    if value is None:
        return None
    text = str(value).strip()
    if not text:
        return None
    match = re.fullmatch(r"(\d{4})-(\d{2})-(\d{2})", text)
    if not match:
        return None
    d = _safe_date(int(match.group(1)), int(match.group(2)), int(match.group(3)))
    if d is None:
        return None
    return _to_recorded_at(d, now=now, tz=tz)


def apply_transaction_date(
    parsed: dict,
    source_text: str = "",
    *,
    now: datetime | None = None,
) -> dict:
    """Isi parsed['recorded_at'] jika user menyebut tanggal. Teks user menang atas field AI."""
    from_text = extract_transaction_date(source_text, now=now)
    from_ai = parse_ai_tanggal(parsed.get("tanggal"), now=now)
    recorded = from_text or from_ai
    if recorded is not None:
        parsed["recorded_at"] = recorded
        # Jangan ikut ke field yang dikirim API selain via argumen recorded_at
        parsed.pop("tanggal", None)
    return parsed


def format_recorded_at_label(value: datetime | None, tz: ZoneInfo = DEFAULT_TZ) -> str:
    if value is None:
        return ""
    local = value.astimezone(tz) if value.tzinfo else value.replace(tzinfo=timezone.utc).astimezone(tz)
    return local.strftime("%d/%m/%Y")
