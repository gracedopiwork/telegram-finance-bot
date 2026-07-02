"""Aktivasi lisensi bot lewat Laravel (cek order bot + migrasi data FTSA)."""

from __future__ import annotations

import logging
from typing import Any

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)


def activate_license_via_api(
    license_key: str,
    telegram_user_id: int,
    telegram_username: str | None,
) -> tuple[bool, dict[str, Any]]:
    """Return (success, payload). On failure payload has error + message."""
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {
            "error": "config_missing",
            "message": missing_laravel_config_message()
            or "Konfigurasi server belum lengkap (LARAVEL_APP_URL + BOT_INTERNAL_API_TOKEN).",
        }

    url = f"{app_url}/api/bot/activate"
    body: dict[str, Any] = {
        "license_key": license_key.strip(),
        "telegram_user_id": telegram_user_id,
    }
    if telegram_username:
        body["telegram_username"] = telegram_username

    try:
        resp = requests.post(url, json=body, headers=headers, timeout=15)
        data = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, data
        return False, {
            "error": data.get("error") or "activation_failed",
            "message": data.get("message") or data.get("error") or resp.text[:300],
        }
    except Exception as exc:
        logger.warning("license_activate: gagal POST %s — %s", url, exc)
        return False, {"error": "network_error", "message": str(exc)[:200]}
