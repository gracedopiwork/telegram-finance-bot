"""Minta link auto-login portal dari Laravel (untuk perintah /web)."""

from __future__ import annotations

import logging

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)


def fetch_portal_login_url(telegram_user_id: int) -> tuple[bool, str]:
    """Return (success, url_or_error_message)."""
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, missing_laravel_config_message() or (
            "Konfigurasi server belum lengkap (LARAVEL_APP_URL + BOT_INTERNAL_API_TOKEN)."
        )

    url = f"{app_url}/api/bot/portal-link"

    try:
        resp = requests.post(
            url,
            json={"telegram_user_id": telegram_user_id},
            headers=headers,
            timeout=12,
        )
        data = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {}
        if resp.status_code == 200 and data.get("ok") and data.get("url"):
            return True, str(data["url"])
        message = data.get("message") or data.get("error") or resp.text[:200]
        return False, str(message)
    except Exception as exc:
        logger.warning("portal_link: gagal POST %s — %s", url, exc)
        return False, str(exc)[:200]
