"""Ambil daftar piutang/utang sosial dari Laravel untuk command bot."""

from __future__ import annotations

import logging
from typing import Any

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)


def fetch_social_liquidity(telegram_user_id: int, kind: str = "all") -> tuple[bool, dict[str, Any], str]:
    import requests

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {}, missing_laravel_config_message() or "missing_laravel_config"

    url = f"{app_url}/api/bot/social-liquidity"
    try:
        resp = requests.get(
            url,
            params={"telegram_user_id": telegram_user_id, "kind": kind},
            headers=headers,
            timeout=15,
        )
        data = resp.json() if resp.content else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, data, ""
        err = str(data.get("error") or data.get("message") or resp.text[:200])
        logger.warning("social_liquidity API HTTP %s — %s", resp.status_code, err)
        return False, {}, err
    except Exception as exc:
        logger.warning("social_liquidity API gagal: %s", exc)
        return False, {}, str(exc)[:200]


def format_social_list(kind: str, payload: dict[str, Any]) -> str:
    """kind: piutang | utang"""
    block = payload.get(kind) or {}
    rows = list(block.get("active") or [])
    title = "Piutang aktif (kami pinjamkan)" if kind == "piutang" else "Utang aktif (kami pinjam)"
    if not rows:
        empty = (
            "Belum ada piutang aktif.\nContoh catat: Di pinjam Grace 500rb buat obat, minggu depan."
            if kind == "piutang"
            else "Belum ada utang aktif.\nContoh catat: Pinjam dari Ayuti 1jt buat RS, bulan depan."
        )
        return f"{title}\n\n{empty}"

    lines = [title, ""]
    overdue_n = 0
    for i, row in enumerate(rows, start=1):
        name = str(row.get("name") or "—")
        amount = int(row.get("amount") or 0)
        purpose = str(row.get("purpose") or "—")
        due = str(row.get("due_label") or "—")
        status = str(row.get("status_label") or row.get("status") or "")
        follow = str(row.get("follow_up") or "")
        mark = "⚠ " if row.get("is_overdue") else ""
        if row.get("is_overdue"):
            overdue_n += 1
        lines.append(
            f"{i}. {mark}{name} — Rp{amount:,}\n"
            f"   Tujuan: {purpose}\n"
            f"   Jatuh tempo: {due}\n"
            f"   Status: {status}\n"
            f"   Tindak lanjut: {follow}"
        )

    total = int(block.get("active_total") or 0)
    overdue_total = int(block.get("overdue_total") or 0)
    lines.append("")
    lines.append(f"Total aktif: Rp{total:,}")
    if overdue_n:
        lines.append(f"Jatuh tempo: {overdue_n} item (Rp{overdue_total:,}) — saatnya {'ditagih' if kind == 'piutang' else 'dibayar'}.")

    notify = payload.get("notify") or {}
    if notify.get("enabled"):
        lines.append("Notifikasi bot: aktif (cek otomatis tiap hari jam 09:00 WIB).")
    else:
        lines.append("Notifikasi jatuh tempo: belum aktif di server (TELEGRAM_BOT_TOKEN).")

    return "\n".join(lines)
