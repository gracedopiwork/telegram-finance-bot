"""URL + auth untuk API internal Laravel (transaksi, portal link, AI health)."""

from __future__ import annotations

import logging
import os

logger = logging.getLogger(__name__)


def get_env(name: str) -> str:
    return (os.getenv(name) or "").strip()


def resolve_laravel_target() -> tuple[str, dict[str, str]]:
    """URL dasar API + header tambahan (mis. Host untuk loopback nginx)."""
    for key in ("LARAVEL_APP_URL", "APP_URL"):
        url = get_env(key).rstrip("/")
        if url:
            return url, {}

    app_path = get_env("LARAVEL_APP_PATH")
    host = get_env("LARAVEL_APP_HOST")
    if app_path and host:
        return "http://127.0.0.1", {"Host": host}

    return "", {}


def missing_laravel_fields() -> list[str]:
    missing: list[str] = []
    if not get_env("BOT_INTERNAL_API_TOKEN"):
        missing.append("BOT_INTERNAL_API_TOKEN")
    if not resolve_laravel_target()[0]:
        missing.append("LARAVEL_APP_URL (atau LARAVEL_APP_PATH + LARAVEL_APP_HOST)")
    return missing


def missing_laravel_config_message() -> str:
    fields = missing_laravel_fields()
    if not fields:
        return ""
    return "Env bot belum lengkap — kosong: " + ", ".join(fields)


def log_laravel_api_config() -> None:
    """Dipanggil saat bot start agar masalah env terlihat di log/journalctl."""
    fields = missing_laravel_fields()
    if not fields:
        app_url, _ = resolve_laravel_target()
        logger.info("laravel_api: siap — %s/api/bot/*", app_url)
        return
    logger.error(
        "laravel_api: %s — simpan transaksi & /web tidak akan jalan sampai .env bot diperbaiki",
        missing_laravel_config_message(),
    )


def auth_headers() -> dict[str, str] | None:
    token = get_env("BOT_INTERNAL_API_TOKEN")
    app_url, extra = resolve_laravel_target()
    if not token or not app_url:
        return None
    return {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        **extra,
    }
