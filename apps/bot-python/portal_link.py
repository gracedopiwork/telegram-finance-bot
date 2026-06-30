"""Minta link auto-login portal dari Laravel (untuk perintah /web)."""

from __future__ import annotations

import logging

import requests

logger = logging.getLogger(__name__)


def _get_env(name: str) -> str:
    import os

    return (os.getenv(name) or "").strip()


def _resolve_laravel_target() -> tuple[str, dict[str, str]]:
    for key in ("LARAVEL_APP_URL", "APP_URL"):
        url = _get_env(key).rstrip("/")
        if url:
            return url, {}

    app_path = _get_env("LARAVEL_APP_PATH")
    host = _get_env("LARAVEL_APP_HOST")
    if app_path and host:
        return "http://127.0.0.1", {"Host": host}

    return "", {}


def fetch_portal_login_url(telegram_user_id: int) -> tuple[bool, str]:
    """Return (success, url_or_error_message)."""
    token = _get_env("BOT_INTERNAL_API_TOKEN")
    app_url, extra_headers = _resolve_laravel_target()
    if not token or not app_url:
        return False, "Konfigurasi server belum lengkap (LARAVEL_APP_URL + BOT_INTERNAL_API_TOKEN)."

    headers = {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        **extra_headers,
    }
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
