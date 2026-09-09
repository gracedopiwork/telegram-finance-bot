"""Cache daftar utang per user untuk pencocokan transaksi cicilan.

Menggabungkan dua pola yang sudah ada di repo ini: struktur TTL + fallback diam
dari category_rules_cache.py, dan parameter telegram_user_id dari
social_liquidity_api.py (Debt Register bersifat per user, bukan global).

Prinsip Section 4: kegagalan apa pun mengembalikan daftar kosong, sehingga
transaksi tetap tersimpan tanpa debt_id. Utang yang tidak ke-link jauh lebih
baik daripada transaksi yang gagal dicatat.
"""

from __future__ import annotations

import logging
import time
from typing import Any

from laravel_api import auth_headers, resolve_laravel_target

logger = logging.getLogger(__name__)

_CACHE_TTL_SECONDS = 60
_cache: dict[int, tuple[list[dict[str, Any]], float]] = {}
_warned_fetch_fail = False


def _is_stale(loaded_at: float) -> bool:
    return (time.time() - loaded_at) > _CACHE_TTL_SECONDS


def fetch_debts(telegram_user_id: int, *, force: bool = False) -> list[dict[str, Any]]:
    """Daftar utang user. Selalu list — kosong kalau apa pun gagal."""
    global _warned_fetch_fail

    if not telegram_user_id:
        return []

    cached = _cache.get(int(telegram_user_id))
    if cached and not force and not _is_stale(cached[1]):
        return cached[0]

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        if not _warned_fetch_fail:
            logger.warning("debt_register: konfigurasi Laravel belum lengkap, pencocokan utang dilewati")
            _warned_fetch_fail = True
        _cache[int(telegram_user_id)] = ([], time.time())
        return []

    import requests

    url = f"{app_url}/api/bot/debt-register"
    try:
        resp = requests.get(
            url,
            params={"telegram_user_id": int(telegram_user_id)},
            headers=headers,
            timeout=10,
        )
        if resp.status_code == 200:
            body = resp.json()
            data = body.get("data") if isinstance(body, dict) else None
            if body.get("ok") and isinstance(data, dict) and isinstance(data.get("debts"), list):
                debts = [d for d in data["debts"] if isinstance(d, dict) and d.get("debt_id")]
                _cache[int(telegram_user_id)] = (debts, time.time())
                return debts
        logger.warning("debt_register: HTTP %s — %s", resp.status_code, resp.text[:200])
    except Exception as exc:  # noqa: BLE001 - kegagalan apa pun tidak boleh menahan transaksi
        logger.warning("debt_register: gagal GET %s — %s", url, exc)

    # Stempel waktu tetap dipasang supaya kegagalan tidak memicu request tiap pesan.
    _cache[int(telegram_user_id)] = ([], time.time())
    return []


def invalidate(telegram_user_id: int | None = None) -> None:
    if telegram_user_id is None:
        _cache.clear()
    else:
        _cache.pop(int(telegram_user_id), None)
