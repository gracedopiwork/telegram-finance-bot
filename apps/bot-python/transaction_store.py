"""Simpan transaksi ke Laravel (MySQL) via API internal."""

from __future__ import annotations

import logging
from datetime import datetime, timezone

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)

_warned_missing_config = False


def format_prescription_bucket(parsed: dict) -> str:
    bucket = parsed.get("bucket")
    jenis = str(parsed.get("jenis") or "").strip()
    kategori = str(parsed.get("kategori") or "").strip()
    if jenis in {"Piutang Keluar", "Piutang Masuk"} or kategori in {
        "Piutang Keluar",
        "Piutang Masuk",
    }:
        label = jenis if jenis in {"Piutang Keluar", "Piutang Masuk"} else kategori
        return f"Likuiditas sosial ({label}) — tidak masuk prescription"
    if jenis == "Pemasukan" and bucket is None:
        return "Tidak masuk prescription (Pemasukan)"
    return str(bucket or "Belum dapat dicek")


def _format_recorded_at(recorded_at: datetime | None) -> str:
    if recorded_at is None:
        return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    if recorded_at.tzinfo is None:
        recorded_at = recorded_at.replace(tzinfo=timezone.utc)

    return recorded_at.astimezone(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def _classification_payload(parsed: dict) -> dict:
    payload = {
        "type": parsed["jenis"],
        "category": parsed["kategori"],
        "sub_category": "-",
        "nature": parsed["sifat"],
        "notes": str(parsed.get("keterangan", "")).strip(),
    }
    flags = parsed.get("taxonomy_flags")
    if isinstance(flags, list) and flags:
        payload["taxonomy_flags"] = flags
    return payload


def _response_error(resp: requests.Response) -> str:
    try:
        data = resp.json()
        return str(data.get("error") or data.get("message") or resp.text[:200])
    except Exception:
        return resp.text[:200]


def resolve_transaction_bucket(parsed: dict) -> tuple[bool, dict, str]:
    """Resolve canonical category + prescription bucket without saving."""
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        return False, {}, missing_laravel_config_message() or "missing_laravel_config"

    url = f"{app_url}/api/bot/transactions/preview"
    try:
        resp = requests.post(
            url,
            json=_classification_payload(parsed),
            headers=headers,
            timeout=15,
        )
        data = resp.json() if resp.content else {}
        if resp.status_code == 200 and data.get("ok"):
            return (
                True,
                {
                    "category": str(data.get("category") or parsed["kategori"]),
                    "bucket": data.get("bucket"),
                },
                "",
            )
        logger.warning(
            "transaction_store preview: Laravel HTTP %s — %s",
            resp.status_code,
            resp.text[:300],
        )
        return False, {}, _response_error(resp)
    except Exception as exc:
        logger.warning("transaction_store preview: gagal POST %s — %s", url, exc)
        return False, {}, str(exc)[:200]


def save_transaction_to_api(
    telegram_user_id: int,
    parsed: dict,
    *,
    source: str = "manual",
    recorded_at: datetime | None = None,
) -> tuple[bool, str, dict]:
    """Return (success, error_message, canonical_result)."""
    global _warned_missing_config

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        if not _warned_missing_config:
            logger.error("transaction_store: %s", missing_laravel_config_message())
            _warned_missing_config = True
        return False, missing_laravel_config_message() or "missing_laravel_config", {}

    payload = {
        **_classification_payload(parsed),
        "telegram_user_id": telegram_user_id,
        "amount": int(parsed["nominal"]),
        "mood": parsed["mood"],
        "is_impulsive": str(parsed.get("impulsif", "No")).strip().lower() == "yes",
        "source": source,
        "recorded_at": _format_recorded_at(recorded_at),
    }

    url = f"{app_url}/api/bot/transactions"
    try:
        resp = requests.post(url, json=payload, headers=headers, timeout=15)
        data = resp.json() if resp.content else {}
        if resp.status_code == 200 and data.get("ok"):
            return True, "", {
                "category": str(data.get("category") or parsed["kategori"]),
                "bucket": data.get("bucket"),
            }
        logger.warning(
            "transaction_store: Laravel HTTP %s — %s",
            resp.status_code,
            resp.text[:300],
        )
        return False, _response_error(resp), {}
    except Exception as exc:
        logger.warning("transaction_store: gagal POST %s — %s", url, exc)
        return False, str(exc)[:200], {}
