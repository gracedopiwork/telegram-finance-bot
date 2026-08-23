"""Onboarding status API (Welcome → Kenalan / Langsung → Home)."""

from __future__ import annotations

import logging
from typing import Any

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)


def fetch_onboarding_status(telegram_user_id: int) -> tuple[bool, dict[str, Any]]:
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {
            "error": "config_missing",
            "message": missing_laravel_config_message() or "Konfigurasi server belum lengkap.",
        }

    url = f"{app_url}/api/bot/onboarding"
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
            "error": data.get("error") or "onboarding_status_failed",
            "message": data.get("message") or resp.text[:300],
        }
    except Exception as exc:
        logger.warning("onboarding status gagal: %s", exc)
        return False, {"error": "network_error", "message": str(exc)[:200]}


def set_onboarding_step(telegram_user_id: int, step: str) -> tuple[bool, dict[str, Any]]:
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {
            "error": "config_missing",
            "message": missing_laravel_config_message() or "Konfigurasi server belum lengkap.",
        }

    url = f"{app_url}/api/bot/onboarding"
    try:
        resp = requests.post(
            url,
            json={"telegram_user_id": telegram_user_id, "step": step},
            headers=headers,
            timeout=12,
        )
        data = resp.json() if resp.headers.get("content-type", "").startswith("application/json") else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, data
        return False, {
            "error": data.get("error") or "onboarding_step_failed",
            "message": data.get("message") or resp.text[:300],
        }
    except Exception as exc:
        logger.warning("onboarding step gagal: %s", exc)
        return False, {"error": "network_error", "message": str(exc)[:200]}
