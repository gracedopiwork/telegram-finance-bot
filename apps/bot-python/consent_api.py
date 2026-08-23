"""Lapis 2 Informed Consent — status & simpan via Laravel API."""

from __future__ import annotations

import logging
from typing import Any

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)


def fetch_consent_status(telegram_user_id: int) -> tuple[bool, dict[str, Any]]:
    """Return (ok, payload). ok=False on config/network/API error."""
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {
            "error": "config_missing",
            "message": missing_laravel_config_message()
            or "Konfigurasi server belum lengkap.",
        }

    url = f"{app_url}/api/bot/consent"
    try:
        resp = requests.get(
            url,
            params={"telegram_user_id": telegram_user_id},
            headers=headers,
            timeout=12,
        )
        data = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, data
        return False, {
            "error": data.get("error") or "consent_status_failed",
            "message": data.get("message") or resp.text[:300],
        }
    except Exception as exc:
        logger.warning("consent status gagal: %s", exc)
        return False, {"error": "network_error", "message": str(exc)[:200]}


def submit_consent(
    telegram_user_id: int,
    checkbox_ids: list[str],
    method: str = "bot",
) -> tuple[bool, dict[str, Any]]:
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {
            "error": "config_missing",
            "message": missing_laravel_config_message()
            or "Konfigurasi server belum lengkap.",
        }

    url = f"{app_url}/api/bot/consent"
    body = {
        "telegram_user_id": telegram_user_id,
        "method": method,
        "checkbox_ids": checkbox_ids,
    }
    try:
        resp = requests.post(url, json=body, headers=headers, timeout=15)
        data = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, data
        return False, {
            "error": data.get("error") or "consent_submit_failed",
            "message": data.get("message") or resp.text[:300],
        }
    except Exception as exc:
        logger.warning("consent submit gagal: %s", exc)
        return False, {"error": "network_error", "message": str(exc)[:200]}
