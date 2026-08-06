"""Extract nama / tujuan / estimasi kembali untuk Likuiditas Sosial."""

from __future__ import annotations

import re
from datetime import date, datetime, timedelta
from typing import Any

_SOCIAL_TYPES = {
    "Piutang Keluar",
    "Piutang Masuk",
    "Utang Masuk",
    "Utang Keluar",
}

_SKIP_NAMES = {
    "saya",
    "aku",
    "dia",
    "teman",
    "saudara",
    "keluarga",
    "orang",
    "dulu",
    "nanti",
    "besok",
    "lusa",
    "buat",
    "untuk",
    "dari",
    "ke",
    "sama",
    "uang",
    "rp",
    "rb",
    "jt",
    "juta",
    "ribu",
}

_NAME_PATTERNS = (
    re.compile(
        r"\b(?:di\s*pinjam|dipinjam|dipinjami|pinjamin|pinjami|pinjamkan|ngutangin|ngutangi|utangin|hutangin)\s+"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:pinjam|utang|hutang|ngutang|minjem)\s+(?:dari|ke|kepada|sama)\s+"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:transfer|bantu|talangin|bayarkan)\s+(?:ke|kepada|sama)?\s*"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:dari|oleh|ke|kepada|sama)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
)

_PURPOSE_PATTERNS = (
    re.compile(
        r"\b(?:buat|untuk|kepentingan|keperluan|tujuan)\s+(.+?)(?:"
        r"\.\s*|,\s*|\s+besok|\s+lusa|\s+nanti|\s+minggu|\s+bulan|\s+tanggal|"
        r"\s+di\s+transfer|\s+transfer|\s+kembali|$)",
        re.IGNORECASE,
    ),
)

_RELATIVE_DUE = (
    (re.compile(r"\bbesok\b", re.IGNORECASE), 1),
    (re.compile(r"\blusa\b", re.IGNORECASE), 2),
    (re.compile(r"\bminggu\s+depan\b", re.IGNORECASE), 7),
    (re.compile(r"\b2\s*minggu\b", re.IGNORECASE), 14),
    (re.compile(r"\bbulan\s+depan\b", re.IGNORECASE), 30),
    (re.compile(r"\b30\s*hari\b", re.IGNORECASE), 30),
    (re.compile(r"\b60\s*hari\b", re.IGNORECASE), 60),
    (re.compile(r"\b90\s*hari\b", re.IGNORECASE), 90),
)


def default_due_days(amount: int) -> int:
    if amount < 500_000:
        return 30
    if amount <= 2_000_000:
        return 60
    return 90


def extract_counterparty(text: str) -> str:
    for pattern in _NAME_PATTERNS:
        match = pattern.search(text or "")
        if not match:
            continue
        name = match.group(1).strip(" .,")
        if name and name.lower() not in _SKIP_NAMES and not name.isdigit():
            return name[:120]
    return ""


def extract_purpose(text: str) -> str:
    raw = text or ""
    for pattern in _PURPOSE_PATTERNS:
        match = pattern.search(raw)
        if not match:
            continue
        purpose = re.sub(r"\s+", " ", match.group(1)).strip(" .,")
        if len(purpose) >= 3:
            return purpose[:180]
    return ""


def extract_due_date(text: str, *, amount: int, base: date | None = None) -> date:
    today = base or date.today()
    raw = text or ""

    m = re.search(
        r"\b(?:tgl|tanggal)\s*(\d{1,2})(?:[/\-.](\d{1,2})(?:[/\-.](\d{2,4}))?)?\b",
        raw,
        re.IGNORECASE,
    )
    if m:
        day = int(m.group(1))
        month = int(m.group(2)) if m.group(2) else today.month
        year = int(m.group(3)) if m.group(3) else today.year
        if year < 100:
            year += 2000
        try:
            return date(year, month, day)
        except ValueError:
            pass

    m = re.search(r"\b(\d{1,2})[/\-.](\d{1,2})(?:[/\-.](\d{2,4}))?\b", raw)
    if m and ("jatuh" in raw.lower() or "tempo" in raw.lower() or "kembali" in raw.lower()):
        day = int(m.group(1))
        month = int(m.group(2))
        year = int(m.group(3)) if m.group(3) else today.year
        if year < 100:
            year += 2000
        try:
            return date(year, month, day)
        except ValueError:
            pass

    for pattern, days in _RELATIVE_DUE:
        if pattern.search(raw):
            return today + timedelta(days=days)

    return today + timedelta(days=default_due_days(max(0, amount)))


def enrich_social_liquidity_fields(parsed: dict[str, Any], source_text: str = "") -> dict[str, Any]:
    jenis = str(parsed.get("jenis") or "").strip()
    if jenis not in _SOCIAL_TYPES:
        return parsed

    text = f"{source_text} {parsed.get('keterangan', '')}".strip()
    name = extract_counterparty(text)
    purpose = extract_purpose(text)
    try:
        amount = int(parsed.get("nominal") or 0)
    except (TypeError, ValueError):
        amount = 0

    due = extract_due_date(text, amount=amount)
    if name:
        parsed["sub_kategori"] = name
    if purpose:
        parsed["social_purpose"] = purpose
    parsed["social_expected_back_at"] = due.isoformat()

    # Pastikan keterangan menyimpan konteks untuk extract di Laravel.
    note = str(parsed.get("keterangan") or source_text or "").strip()
    extras: list[str] = []
    if name and name.lower() not in note.lower():
        extras.append(f"ke {name}" if jenis.endswith("Keluar") or jenis == "Utang Keluar" else f"dari {name}")
    if purpose and purpose.lower() not in note.lower():
        extras.append(f"buat {purpose}")
    if extras:
        parsed["keterangan"] = (note + " | " + ", ".join(extras)).strip(" |")

    return parsed


def social_missing_name_question(parsed: dict[str, Any], source_text: str = "") -> str | None:
    jenis = str(parsed.get("jenis") or "").strip()
    if jenis not in {"Piutang Keluar", "Utang Masuk"}:
        return None
    text = f"{source_text} {parsed.get('keterangan', '')}"
    if extract_counterparty(text) or str(parsed.get("sub_kategori") or "").strip() not in {"", "-"}:
        return None
    if jenis == "Piutang Keluar":
        return (
            "Siapa yang meminjam uang ini? Sertakan juga tujuan dan estimasi kembali "
            "(contoh: Grace, kepentingan kerja, kembali besok)."
        )
    return (
        "Dari siapa pinjaman ini? Sertakan juga tujuan dan estimasi bayar "
        "(contoh: Ayuti, biaya RS, bulan depan)."
    )
