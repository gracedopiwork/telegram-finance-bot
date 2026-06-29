"""Laporkan kesehatan pemakaian Gemini ke admin Laravel (fire-and-forget)."""

from __future__ import annotations

import logging
import threading

import requests

logger = logging.getLogger(__name__)

_warned_missing_config = False


def _get_env(name: str) -> str:
    import os

    return (os.getenv(name) or "").strip()


def _classify_gemini_error(exc: Exception) -> str:
    text = f"{type(exc).__name__}: {exc}".lower()
    if "429" in text or "too many requests" in text or "resource exhausted" in text or "quota" in text:
        return "rate_limit"
    return "error"


def _resolve_laravel_target() -> tuple[str, dict[str, str]]:
    """URL dasar + header tambahan (mis. Host untuk loopback)."""
    for key in ("LARAVEL_APP_URL", "APP_URL"):
        url = _get_env(key).rstrip("/")
        if url:
            return url, {}

    app_path = _get_env("LARAVEL_APP_PATH")
    host = _get_env("LARAVEL_APP_HOST")
    if app_path and host:
        return "http://127.0.0.1", {"Host": host}

    return "", {}


def log_ai_health_config() -> None:
    """Dipanggil saat bot start — agar masalah env terlihat di journalctl."""
    token = _get_env("BOT_INTERNAL_API_TOKEN")
    app_url, _ = _resolve_laravel_target()
    if token and app_url:
        logger.info("ai_health: siap lapor ke %s/api/bot/ai-health", app_url)
        return

    missing = []
    if not token:
        missing.append("BOT_INTERNAL_API_TOKEN")
    if not app_url:
        missing.append("LARAVEL_APP_URL (atau LARAVEL_APP_PATH + LARAVEL_APP_HOST)")
    logger.warning(
        "ai_health: %s kosong — statistik AI tidak akan masuk admin dashboard",
        ", ".join(missing),
    )


def report_ai_event(event: str, detail: str = "") -> None:
    """Kirim event ke Laravel tanpa memblokir bot."""
    global _warned_missing_config

    token = _get_env("BOT_INTERNAL_API_TOKEN")
    app_url, extra_headers = _resolve_laravel_target()
    if not token or not app_url:
        if not _warned_missing_config:
            log_ai_health_config()
            _warned_missing_config = True
        return

    payload = {"event": event}
    if detail:
        payload["detail"] = detail[:500]

    headers = {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        **extra_headers,
    }

    def _send() -> None:
        url = f"{app_url}/api/bot/ai-health"
        try:
            resp = requests.post(url, json=payload, headers=headers, timeout=12)
            if resp.status_code != 200:
                logger.warning(
                    "ai_health: Laravel balas HTTP %s untuk %s: %s",
                    resp.status_code,
                    url,
                    resp.text[:300],
                )
        except Exception as exc:
            logger.warning("ai_health: gagal POST %s — %s", url, exc)

    threading.Thread(target=_send, daemon=True).start()


def report_gemini_failure(exc: Exception, context: str = "") -> None:
    event = _classify_gemini_error(exc)
    detail = context or str(exc)[:500]
    report_ai_event(event, detail)
