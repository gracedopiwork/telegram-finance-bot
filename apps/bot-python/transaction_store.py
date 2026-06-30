"""Simpan transaksi ke Laravel (MySQL) via API internal."""

from __future__ import annotations

import logging
import threading

import requests

logger = logging.getLogger(__name__)

_warned_missing_config = False


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


def storage_mode() -> str:
    return (_get_env("BOT_TRANSACTION_STORAGE") or "db").lower()


def save_transaction_to_api(
    telegram_user_id: int,
    parsed: dict,
    *,
    source: str = "manual",
) -> tuple[bool, str]:
    """Return (success, error_message)."""
    global _warned_missing_config

    if storage_mode() == "sheet":
        return False, "storage_mode_sheet"

    token = _get_env("BOT_INTERNAL_API_TOKEN")
    app_url, extra_headers = _resolve_laravel_target()
    if not token or not app_url:
        if not _warned_missing_config:
            logger.warning(
                "transaction_store: BOT_INTERNAL_API_TOKEN atau LARAVEL_APP_URL kosong"
            )
            _warned_missing_config = True
        return False, "missing_laravel_config"

    payload = {
        "telegram_user_id": telegram_user_id,
        "type": parsed["jenis"],
        "category": parsed["kategori"],
        "sub_category": parsed["sub_kategori"],
        "amount": int(parsed["nominal"]),
        "nature": parsed["sifat"],
        "mood": parsed["mood"],
        "is_impulsive": str(parsed.get("impulsif", "No")).strip().lower() == "yes",
        "notes": str(parsed.get("keterangan", "")).strip(),
        "source": source,
    }

    headers = {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        **extra_headers,
    }

    url = f"{app_url}/api/bot/transactions"
    try:
        resp = requests.post(url, json=payload, headers=headers, timeout=15)
        if resp.status_code == 200 and resp.json().get("ok"):
            return True, ""
        logger.warning(
            "transaction_store: Laravel HTTP %s — %s",
            resp.status_code,
            resp.text[:300],
        )
        return False, resp.text[:200]
    except Exception as exc:
        logger.warning("transaction_store: gagal POST %s — %s", url, exc)
        return False, str(exc)[:200]
