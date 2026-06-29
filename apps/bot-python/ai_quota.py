"""Kuota AI parsing per user per bulan (IDR)."""

from __future__ import annotations

import logging
import os
from datetime import datetime
from typing import Any

import mysql.connector

logger = logging.getLogger(__name__)

_MONTH_NAMES_ID = {
    1: "Januari",
    2: "Februari",
    3: "Maret",
    4: "April",
    5: "Mei",
    6: "Juni",
    7: "Juli",
    8: "Agustus",
    9: "September",
    10: "Oktober",
    11: "November",
    12: "Desember",
}


def _env_int(name: str, default: int) -> int:
    raw = (os.getenv(name) or "").strip()
    if not raw:
        return default
    try:
        return int(raw)
    except ValueError:
        logger.warning("Env %s tidak valid (%r), pakai default %s", name, raw, default)
        return default


def quota_enabled() -> bool:
    return _env_int("AI_QUOTA_MONTHLY_IDR", 5000) > 0


def get_quota_config() -> tuple[int, int, int]:
    monthly = _env_int("AI_QUOTA_MONTHLY_IDR", 5000)
    text_cost = _env_int("AI_COST_TEXT_PARSE_IDR", 10)
    vision_cost = _env_int("AI_COST_VISION_PARSE_IDR", 30)
    return monthly, text_cost, vision_cost


def current_usage_month() -> str:
    return datetime.now().strftime("%Y-%m")


def _month_label(usage_month: str) -> str:
    try:
        year, month = usage_month.split("-", 1)
        month_num = int(month)
        return f"{_MONTH_NAMES_ID.get(month_num, month)} {year}"
    except (ValueError, IndexError):
        return usage_month


def _get_db_connection():
    return mysql.connector.connect(
        host=(os.getenv("MYSQL_HOST") or "127.0.0.1").strip(),
        port=int((os.getenv("MYSQL_PORT") or "3306").strip()),
        user=(os.getenv("MYSQL_USER") or "").strip(),
        password=os.getenv("MYSQL_PASSWORD", ""),
        database=(os.getenv("MYSQL_DATABASE") or "").strip(),
    )


def _empty_usage(usage_month: str | None = None) -> dict[str, Any]:
    month = usage_month or current_usage_month()
    return {
        "telegram_user_id": 0,
        "usage_month": month,
        "cost_idr": 0,
        "text_parse_count": 0,
        "vision_parse_count": 0,
        "quota_exhausted_notified_at": None,
    }


def get_user_usage(telegram_user_id: int) -> dict[str, Any]:
    usage_month = current_usage_month()
    try:
        conn = _get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT telegram_user_id, usage_month, cost_idr, text_parse_count,
                   vision_parse_count, quota_exhausted_notified_at
            FROM user_ai_usage
            WHERE telegram_user_id = %s AND usage_month = %s
            LIMIT 1
            """,
            (telegram_user_id, usage_month),
        )
        row = cursor.fetchone()
        cursor.close()
        conn.close()
    except Exception as exc:  # pragma: no cover - db guard
        logger.warning("Gagal baca kuota AI user %s: %s", telegram_user_id, exc)
        return _empty_usage(usage_month)

    if not row:
        return _empty_usage(usage_month)

    return row


def _parse_cost(kind: str) -> int:
    _, text_cost, vision_cost = get_quota_config()
    return vision_cost if kind == "vision" else text_cost


def has_ai_quota(telegram_user_id: int, kind: str = "text") -> bool:
    if not quota_enabled():
        return True

    monthly, _, _ = get_quota_config()
    usage = get_user_usage(telegram_user_id)
    needed = _parse_cost(kind)
    return int(usage["cost_idr"]) + needed <= monthly


def remaining_quota_idr(telegram_user_id: int) -> int:
    monthly, _, _ = get_quota_config()
    usage = get_user_usage(telegram_user_id)
    return max(0, monthly - int(usage["cost_idr"]))


def record_ai_usage(telegram_user_id: int, kind: str = "text") -> None:
    if not quota_enabled():
        return

    usage_month = current_usage_month()
    cost = _parse_cost(kind)
    count_column = "vision_parse_count" if kind == "vision" else "text_parse_count"

    try:
        conn = _get_db_connection()
        cursor = conn.cursor()
        cursor.execute(
            f"""
            INSERT INTO user_ai_usage
                (telegram_user_id, usage_month, cost_idr, text_parse_count, vision_parse_count, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                cost_idr = cost_idr + VALUES(cost_idr),
                {count_column} = {count_column} + 1,
                updated_at = NOW()
            """,
            (
                telegram_user_id,
                usage_month,
                cost,
                1 if kind == "text" else 0,
                1 if kind == "vision" else 0,
            ),
        )
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as exc:  # pragma: no cover - db guard
        logger.warning("Gagal catat kuota AI user %s: %s", telegram_user_id, exc)


def should_notify_quota_exhausted(telegram_user_id: int) -> bool:
    if not quota_enabled():
        return False

    usage = get_user_usage(telegram_user_id)
    if usage.get("quota_exhausted_notified_at"):
        return False

    monthly, _, _ = get_quota_config()
    return int(usage["cost_idr"]) >= monthly


def mark_quota_exhausted_notified(telegram_user_id: int) -> None:
    usage_month = current_usage_month()
    try:
        conn = _get_db_connection()
        cursor = conn.cursor()
        cursor.execute(
            """
            INSERT INTO user_ai_usage
                (telegram_user_id, usage_month, cost_idr, quota_exhausted_notified_at, created_at, updated_at)
            VALUES (%s, %s, 0, NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                quota_exhausted_notified_at = COALESCE(quota_exhausted_notified_at, NOW()),
                updated_at = NOW()
            """,
            (telegram_user_id, usage_month),
        )
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as exc:  # pragma: no cover - db guard
        logger.warning("Gagal tandai notifikasi kuota user %s: %s", telegram_user_id, exc)


def format_quota_exhausted_notice() -> str:
    monthly, _, _ = get_quota_config()
    return (
        f"Kuota AI parsing bulan ini sudah habis (plafon Rp {monthly:,}).\n"
        "Catatan tetap bisa disimpan dengan *mode biasa* — cek preview sebelum simpan.\n"
        "Kuota reset tanggal 1 bulan depan."
    )


def format_vision_quota_blocked() -> str:
    return (
        "Kuota AI bulan ini sudah habis, jadi foto struk belum bisa diproses.\n"
        "Ketik manual ya, contoh: `makan siang 35rb`"
    )


def format_quota_status(telegram_user_id: int) -> str:
    monthly, text_cost, vision_cost = get_quota_config()
    usage_month = current_usage_month()
    month_label = _month_label(usage_month)

    if not quota_enabled():
        return "Kuota AI: tidak dibatasi (pengaturan server)."

    usage = get_user_usage(telegram_user_id)
    used = int(usage["cost_idr"])
    remaining = max(0, monthly - used)
    approx_text = remaining // text_cost if text_cost > 0 else 0
    approx_vision = remaining // vision_cost if vision_cost > 0 else 0

    lines = [
        f"Kuota AI {month_label}:",
        f"Terpakai: Rp {used:,} / Rp {monthly:,}",
        f"Sisa: Rp {remaining:,}",
    ]
    if remaining > 0:
        lines.append(f"±{approx_text} parse teks atau ±{approx_vision} foto struk lagi")
    else:
        lines.append("Mode: *biasa* (tanpa AI penuh) sampai reset bulan depan")
    lines.append(
        f"Detail: {int(usage['text_parse_count'])} parse teks · "
        f"{int(usage['vision_parse_count'])} foto struk"
    )
    lines.append("Reset: tanggal 1 bulan depan.")
    return "\n".join(lines)
