"""Extract nama / tujuan / estimasi kembali untuk Likuiditas Sosial."""

from __future__ import annotations

import difflib
import re
from datetime import date, timedelta
from typing import Any

_SOCIAL_OPEN_TYPES = {
    "Piutang Keluar",
    "Utang Masuk",
}

_SOCIAL_TYPES = {
    *_SOCIAL_OPEN_TYPES,
    "Piutang Masuk",
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
    "duit",
    "rp",
    "rb",
    "jt",
    "juta",
    "ribu",
    "balik",
    "balikin",
    "kembali",
    "kembalikan",
    "transfer",
    "hutang",
    "utang",
    "pinjaman",
    "piutang",
    "masuk",
    "keluar",
}

_NAME_PATTERNS = (
    re.compile(
        r"\b(?:di\s*pinjam|dipinjam|dipinjami|dipinjemin|pinjamin|pinjami|pinjamkan|pinjemin|"
        r"ngutangin|ngutangi|utangin|hutangin|minjamin|talangin|nombokin)\s+"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:pinjam|pinjem|minjem|minjam|utang|hutang|ngutang)\s+"
        r"(?:duit|uang|dana)?\s*(?:dari|ke|kek|kepada|sama|dengan)\s+"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:transfer|tf|bantu|talangin|bayarkan|bayarin|kirim)\s+(?:ke|kepada|sama)?\s*"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:dari|oleh|ke|kek|kepada|sama|dengan)\s+([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b(?:dibalikin|dikembalikan|dibayar\s+balik)\s+(?:oleh\s+|dari\s+)?"
        r"([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})",
        re.IGNORECASE,
    ),
    re.compile(
        r"\b([A-Za-zÀ-ÿ][\wÀ-ÿ.\-]{1,40})\s+"
        r"(?:balikin|kembalikan|ngembaliin|lunasi|lunasin|bayar\s+balik)\s+"
        r"(?:hutang|utang|uang|duit|pinjaman|piutang)\b",
        re.IGNORECASE,
    ),
)

_PURPOSE_PATTERNS = (
    re.compile(
        r"\b(?:buat|untuk|tujuan|keperluan|kepentingan)\s+(.+?)(?:"
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
    (re.compile(r"\bsebulan\s+(?:ke\s+)?depan\b", re.IGNORECASE), 30),
    (re.compile(r"\b1\s*bulan\s+(?:ke\s+)?depan\b", re.IGNORECASE), 30),
    (re.compile(r"\bdua\s+minggu\b", re.IGNORECASE), 14),
    (re.compile(r"\bminggu\s+depan\s+lagi\b", re.IGNORECASE), 7),
    (re.compile(r"\b30\s*hari\b", re.IGNORECASE), 30),
    (re.compile(r"\b60\s*hari\b", re.IGNORECASE), 60),
    (re.compile(r"\b90\s*hari\b", re.IGNORECASE), 90),
)

# Frasa waktu yang sering diketik salah (bulan depam, sebulan kedepan, dll).
_DUE_PHRASE_DAYS: dict[str, int] = {
    "besok": 1,
    "bsk": 1,
    "besoknya": 1,
    "di balikin besok": 1,
    "dibalikin besok": 1,
    "balikin besok": 1,
    "kembali besok": 1,
    "lusa": 2,
    "minggu depan": 7,
    "mingdep": 7,
    "mnggu depan": 7,
    "mingu depan": 7,
    "2 minggu": 14,
    "dua minggu": 14,
    "bulan depan": 30,
    "bln depan": 30,
    "bln depn": 30,
    "bulan depn": 30,
    "bulan depam": 30,
    "bulan depa": 30,
    "bln depam": 30,
    "sebulan ke depan": 30,
    "sebulan kedepan": 30,
    "sebulan depan": 30,
    "1 bulan ke depan": 30,
    "satu bulan ke depan": 30,
    "bulan kedepan": 30,
    "nanti bulan depan": 30,
    "30 hari": 30,
    "60 hari": 60,
    "90 hari": 90,
}

_SKIP_DETAIL_MARKERS = (
    "belum tahu",
    "belum tau",
    "tidak tahu",
    "ga tahu",
    "gak tahu",
    "gatau",
    "ga tau",
    "default",
    "pakai default",
    "skip",
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

    # Prioritaskan balasan klarifikasi: "kepentingan kerja, besok" atau "kebutuhan mendesak".
    m = re.search(
        r"klarifikasi\s+user:\s*(.+)$",
        raw,
        re.IGNORECASE | re.DOTALL,
    )
    if m:
        chunk = re.sub(r"\s+", " ", m.group(1)).strip(" .,")
        if chunk.lower() not in _SKIP_DETAIL_MARKERS and not _looks_like_due_only(chunk):
            purpose_part = re.split(
                r",\s*(?:besok|lusa|minggu|bulan|sebulan|tanggal|tgl|\d{1,2}[/\-.])",
                chunk,
                maxsplit=1,
                flags=re.IGNORECASE,
            )[0].strip(" .,")
            purpose_part = re.sub(
                r"\b(?:kembali|bayar|dilunasi|tempo)?\s*"
                r"(?:besok|lusa|minggu depan|bulan depan|sebulan ke depan|30 hari|60 hari|90 hari)\b",
                "",
                purpose_part,
                flags=re.IGNORECASE,
            ).strip(" .,")
            purpose_part = re.sub(
                r"^(?:buat|untuk|tujuan|keperluan)\s+",
                "",
                purpose_part,
                flags=re.IGNORECASE,
            ).strip(" .,")
            if len(purpose_part) >= 3 and not _looks_like_due_only(purpose_part):
                return purpose_part[:180]
            if len(chunk) >= 3:
                return chunk[:180]

    for pattern in _PURPOSE_PATTERNS:
        match = pattern.search(raw)
        if not match:
            continue
        purpose = re.sub(r"\s+", " ", match.group(1)).strip(" .,")
        if len(purpose) >= 3 and not _looks_like_due_only(purpose):
            return purpose[:180]

    return ""


def _normalize_due_text(text: str) -> str:
    lower = re.sub(r"\s+", " ", (text or "").lower()).strip(" .,")
    lower = lower.replace("kedepan", "ke depan").replace("ke-depan", "ke depan")
    lower = re.sub(r"\bbln\b", "bulan", lower)
    lower = re.sub(r"\bmnggu\b", "minggu", lower)
    lower = re.sub(r"\bmingu\b", "minggu", lower)
    return lower


def _looks_like_due_only(text: str) -> bool:
    """True hanya jika teks itu tanggal/tempo, bukan tujuan + tempo sekaligus."""
    if match_relative_due_days(text) is None:
        return False
    leftover = _normalize_due_text(text)
    for phrase in sorted(_DUE_PHRASE_DAYS.keys(), key=len, reverse=True):
        leftover = leftover.replace(phrase, " ")
    leftover = re.sub(
        r"\b(?:besok|lusa|minggu|bulan|sebulan|depan|hari|kembali|bayar|dilunasi|tempo|tgl|tanggal)\b",
        " ",
        leftover,
        flags=re.IGNORECASE,
    )
    leftover = re.sub(r"[,\d/.\-]+", " ", leftover)
    leftover = re.sub(r"\s+", " ", leftover).strip(" .,")
    return len(leftover) < 3


def match_relative_due_days(text: str) -> int | None:
    """Terima 'bulan depan', 'sebulan ke depan', dan typo dekat (bulan depam)."""
    raw = text or ""
    for pattern, days in _RELATIVE_DUE:
        if pattern.search(raw):
            return days

    lower = _normalize_due_text(raw)
    if not lower:
        return None

    for phrase, days in _DUE_PHRASE_DAYS.items():
        if phrase in lower:
            return days

    if len(lower) <= 40:
        close = difflib.get_close_matches(
            lower,
            list(_DUE_PHRASE_DAYS.keys()) + ["besok", "lusa", "minggu depan", "bulan depan", "sebulan ke depan"],
            n=1,
            cutoff=0.78,
        )
        if close:
            hit = close[0]
            return _DUE_PHRASE_DAYS.get(hit, 30 if "bulan" in hit else 7 if "minggu" in hit else 1)

    # "bulan depam" / "bulan depaan" — fuzzy kata kedua.
    m = re.search(r"\b(sebulan|bulan|minggu|besok|lusa)\s+(\w{3,12})\b", lower)
    if m:
        head, tail = m.group(1), m.group(2)
        if head in {"besok", "lusa"}:
            return 1 if head == "besok" else 2
        if difflib.SequenceMatcher(None, tail, "depan").ratio() >= 0.6 or tail in {"kedepan", "depam", "depa", "depn", "depann"}:
            return 30 if head in {"bulan", "sebulan"} else 7

    m = re.search(r"\bsebulan\b", lower)
    if m and any(k in lower for k in ("depan", "lagi", "ke")):
        return 30

    return None


def has_explicit_due(text: str) -> bool:
    raw = text or ""
    lower = raw.lower()
    if match_relative_due_days(raw) is not None:
        return True
    if re.search(r"\b(?:tgl|tanggal)\s*\d{1,2}\b", raw, re.IGNORECASE):
        return True
    if re.search(r"\b\d{1,2}[/\-.]\d{1,2}(?:[/\-.]\d{2,4})?\b", raw) and any(
        k in lower for k in ("jatuh", "tempo", "kembali", "bayar", "lunas")
    ):
        return True
    if re.search(r"\b(?:kembali|bayar|dilunasi|tempo)\s+(?:besok|lusa|minggu|bulan)", lower):
        return True
    return False


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
    if m and ("jatuh" in raw.lower() or "tempo" in raw.lower() or "kembali" in raw.lower() or "bayar" in raw.lower()):
        day = int(m.group(1))
        month = int(m.group(2))
        year = int(m.group(3)) if m.group(3) else today.year
        if year < 100:
            year += 2000
        try:
            return date(year, month, day)
        except ValueError:
            pass

    relative = match_relative_due_days(raw)
    if relative is not None:
        return today + timedelta(days=relative)

    return today + timedelta(days=default_due_days(max(0, amount)))


def user_skipped_social_details(text: str) -> bool:
    lower = (text or "").lower()
    return any(marker in lower for marker in _SKIP_DETAIL_MARKERS)


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
    parsed["social_due_explicit"] = has_explicit_due(text)

    # Pastikan keterangan menyimpan konteks untuk extract di Laravel.
    note = str(parsed.get("keterangan") or source_text or "").strip()
    extras: list[str] = []
    if name and name.lower() not in note.lower():
        extras.append(f"ke {name}" if jenis.endswith("Keluar") or jenis == "Utang Keluar" else f"dari {name}")
    if purpose and purpose.lower() not in note.lower():
        extras.append(f"buat {purpose}")
    if has_explicit_due(text):
        due_labels = (
            "besok",
            "lusa",
            "minggu depan",
            "2 minggu",
            "sebulan ke depan",
            "bulan depan",
            "30 hari",
            "60 hari",
            "90 hari",
        )
        for label in due_labels:
            if label in text.lower() and label not in note.lower():
                extras.append(label)
                break
        else:
            days = match_relative_due_days(text)
            if days == 30 and "bulan" not in note.lower() and "sebulan" not in note.lower():
                extras.append("bulan depan")
            elif days == 7 and "minggu" not in note.lower():
                extras.append("minggu depan")
            elif days == 1 and "besok" not in note.lower():
                extras.append("besok")
    if extras:
        parsed["keterangan"] = (note + " | " + ", ".join(extras)).strip(" |")

    parsed["keterangan"] = _dedupe_keterangan(str(parsed.get("keterangan") or note))
    return parsed


def _dedupe_keterangan(note: str) -> str:
    """Hindari 'Pinjam dari mama … | buat … Pinjam dari mama …'."""
    text = re.sub(r"\s+", " ", (note or "").strip())
    if not text:
        return text
    parts = [p.strip(" |,") for p in text.split("|")]
    seen: list[str] = []
    for part in parts:
        if not part:
            continue
        key = part.lower()
        duplicate = False
        kept: list[str] = []
        for existing in seen:
            ex = existing.lower()
            if key == ex or key in ex:
                duplicate = True
                break
            if ex in key and len(ex) >= 12:
                continue
            kept.append(existing)
        if duplicate:
            continue
        seen = kept
        seen.append(part)
    merged = " | ".join(seen)
    return re.sub(r"(.{12,}?)\s+\1", r"\1", merged, flags=re.IGNORECASE).strip(" |")


def social_missing_details_question(parsed: dict[str, Any], source_text: str = "") -> str | None:
    """Tanya nama / tujuan / kapan dikembalikan untuk buka piutang atau utang."""
    jenis = str(parsed.get("jenis") or "").strip()
    if jenis not in _SOCIAL_OPEN_TYPES:
        return None

    text = f"{source_text} {parsed.get('keterangan', '')}"
    if user_skipped_social_details(text):
        return None

    missing: list[str] = []
    has_name = bool(extract_counterparty(text)) or str(parsed.get("sub_kategori") or "").strip() not in {"", "-"}
    has_purpose = bool(extract_purpose(text) or str(parsed.get("social_purpose") or "").strip())
    has_due = has_explicit_due(text)

    if not has_name:
        missing.append("nama")
    if not has_purpose:
        missing.append("tujuan")
    if not has_due:
        missing.append("kapan dikembalikan" if jenis == "Piutang Keluar" else "kapan dibayar")

    if not missing:
        return None

    if jenis == "Piutang Keluar":
        example = "Grace, kepentingan kerja, kembali besok"
        who = "Siapa yang meminjam"
    else:
        example = "Ayuti, biaya RS, bulan depan"
        who = "Dari siapa pinjaman ini"

    if missing == ["nama"]:
        return f"{who}? Sertakan juga tujuan dan estimasi waktu (contoh: {example})."
    if set(missing) == {"tujuan", "kapan dikembalikan"} or set(missing) == {"tujuan", "kapan dibayar"}:
        when = "kapan dikembalikan" if jenis == "Piutang Keluar" else "kapan dibayar balik"
        return (
            f"Untuk tracker Likuiditas Sosial, sebutkan tujuan pinjaman dan {when} "
            f"(contoh: kepentingan kerja, kembali besok). Ketik 'default' jika belum pasti."
        )
    if missing == ["tujuan"]:
        return (
            "Untuk apa pinjaman ini? (contoh: kepentingan kerja / beli obat). "
            "Ketik 'default' jika belum pasti."
        )
    if "kapan" in missing[0] or missing == ["kapan dikembalikan"] or missing == ["kapan dibayar"]:
        when = "kapan dikembalikan" if jenis == "Piutang Keluar" else "kapan dibayar balik"
        return (
            f"{when.capitalize()}? (contoh: besok / minggu depan / bulan depan / 7/8/2026). "
            "Ketik 'default' untuk pakai estimasi otomatis."
        )

    label = ", ".join(missing)
    return (
        f"Lengkapi dulu data tracker ({label}). "
        f"Contoh: {example}. Ketik 'default' jika sebagian belum pasti."
    )


# Alias lama
def social_missing_name_question(parsed: dict[str, Any], source_text: str = "") -> str | None:
    return social_missing_details_question(parsed, source_text)
