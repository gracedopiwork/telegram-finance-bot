from datetime import datetime
from zoneinfo import ZoneInfo

from date_parser import apply_transaction_date, extract_transaction_date, format_recorded_at_label

TZ = ZoneInfo("Asia/Jakarta")
NOW = datetime(2026, 7, 11, 15, 30, tzinfo=TZ)


def test_tgl_slash_without_year():
    dt = extract_transaction_date("tgl 2/7 beli makan 50k", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2026-07-02"


def test_tanggal_with_year():
    dt = extract_transaction_date("tanggal 02-06-2026 jajan 20rb", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2026-06-02"


def test_named_month():
    dt = extract_transaction_date("tgl 2 juli makan 50k", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2026-07-02"


def test_kemarin():
    dt = extract_transaction_date("kemarin beli kopi 18000", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2026-07-10"


def test_days_ago():
    dt = extract_transaction_date("3 hari lalu makan 30k", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2026-07-08"


def test_future_month_uses_previous_year():
    # Sekarang Juli; tgl 15/12 tanpa tahun → Des tahun lalu
    dt = extract_transaction_date("tgl 15/12 beli hadiah 100k", now=NOW)
    assert dt is not None
    assert dt.astimezone(TZ).date().isoformat() == "2025-12-15"


def test_apply_prefers_text_over_ai():
    parsed = {"tanggal": "2026-01-01", "keterangan": "makan"}
    apply_transaction_date(parsed, "tgl 2/7 makan 50k", now=NOW)
    assert parsed["recorded_at"].astimezone(TZ).date().isoformat() == "2026-07-02"
    assert "tanggal" not in parsed


def test_label():
    dt = extract_transaction_date("tgl 2/7 makan 50k", now=NOW)
    assert format_recorded_at_label(dt) == "02/07/2026"


def test_tgl_with_year_when_today_is_next_month():
    """Kasus: catat 1 Sep, teks 'Tgl 31/08/2026' harus tetap 31 Agustus."""
    now = datetime(2026, 9, 1, 0, 43, tzinfo=TZ)
    dt = extract_transaction_date(
        "Tgl 31/08/2026 grab dari the meru klinik ke kos 26.4k",
        now=now,
    )
    assert dt is not None
    local = dt.astimezone(TZ)
    assert local.date().isoformat() == "2026-08-31"
    assert local.hour == 12
    assert format_recorded_at_label(dt) == "31/08/2026"


def test_ignore_ai_tanggal_when_user_did_not_mention_date():
    """Kasus Catherina: AI mengarang 2026-01-09 (MM/DD) untuk 'Terima gaji 5k'."""
    now = datetime(2026, 9, 1, 18, 40, tzinfo=TZ)
    parsed = {"tanggal": "2026-01-09", "keterangan": "Terima gaji"}
    apply_transaction_date(parsed, "Terima gaji 5k", now=now)
    assert "recorded_at" not in parsed
    assert "tanggal" not in parsed


def test_bare_indonesian_date_day_first():
    """'1/9 terima gaji' = 1 September, bukan 9 Januari."""
    now = datetime(2026, 9, 1, 18, 40, tzinfo=TZ)
    parsed = {"tanggal": "2026-01-09", "keterangan": "Terima gaji"}
    apply_transaction_date(parsed, "1/9 terima gaji 5k", now=now)
    assert parsed["recorded_at"].astimezone(TZ).date().isoformat() == "2026-09-01"
    assert format_recorded_at_label(parsed["recorded_at"]) == "01/09/2026"


def test_slash_dates_mid_sentence_are_day_first_not_ai():
    """Kasus Catherina: '9/1' dan '1/9' di tengah teks harus beda (bukan sama-sama 9 Jan)."""
    now = datetime(2026, 9, 1, 18, 46, tzinfo=TZ)

    parsed_a = {"tanggal": "2026-01-09"}
    apply_transaction_date(parsed_a, "terima gaji 1/9 5k", now=now)
    assert parsed_a["recorded_at"].astimezone(TZ).date().isoformat() == "2026-09-01"

    parsed_b = {"tanggal": "2026-01-09"}
    apply_transaction_date(parsed_b, "terima gaji 9/1 5k", now=now)
    assert parsed_b["recorded_at"].astimezone(TZ).date().isoformat() == "2026-01-09"

    assert format_recorded_at_label(parsed_a["recorded_at"]) == "01/09/2026"
    assert format_recorded_at_label(parsed_b["recorded_at"]) == "09/01/2026"


def test_ai_tanggal_with_iso_in_text():
    now = datetime(2026, 9, 1, 18, 40, tzinfo=TZ)
    parsed = {"tanggal": "2026-08-15", "keterangan": "gaji"}
    apply_transaction_date(parsed, "gaji tanggal 2026-08-15 sebesar 5k", now=now)
    assert parsed["recorded_at"].astimezone(TZ).date().isoformat() == "2026-08-15"
