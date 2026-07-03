"""Simpan transaksi ke Laravel (MySQL) via API internal."""

from __future__ import annotations

import logging

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)

_warned_missing_config = False


def save_transaction_to_api(
    telegram_user_id: int,
    parsed: dict,
    *,
    source: str = "manual",
) -> tuple[bool, str]:
    """Return (success, error_message)."""
    global _warned_missing_config

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        if not _warned_missing_config:
            logger.error("transaction_store: %s", missing_laravel_config_message())
            _warned_missing_config = True
        return False, missing_laravel_config_message() or "missing_laravel_config"

    payload = {
        "telegram_user_id": telegram_user_id,
        "type": parsed["jenis"],
        "category": parsed["kategori"],
        "sub_category": "-",
        "amount": int(parsed["nominal"]),
        "nature": parsed["sifat"],
        "mood": parsed["mood"],
        "is_impulsive": str(parsed.get("impulsif", "No")).strip().lower() == "yes",
        "notes": str(parsed.get("keterangan", "")).strip(),
        "source": source,
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
        try:
            data = resp.json()
            err = data.get("error") or data.get("message") or resp.text[:200]
        except Exception:
            err = resp.text[:200]
        return False, str(err)
    except Exception as exc:
        logger.warning("transaction_store: gagal POST %s — %s", url, exc)
        return False, str(exc)[:200]
