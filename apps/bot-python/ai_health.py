"""Laporkan kesehatan pemakaian Claude ke admin Laravel (fire-and-forget)."""

from __future__ import annotations

import logging
import threading

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)

_warned_missing_config = False


def _classify_ai_error(exc: Exception) -> str:
    text = f"{type(exc).__name__}: {exc}".lower()
    if (
        "429" in text
        or "529" in text
        or "too many requests" in text
        or "rate_limit" in text
        or "overloaded" in text
        or "resource exhausted" in text
        or "quota" in text
    ):
        return "rate_limit"
    return "error"


def log_ai_health_config() -> None:
    """Dipanggil saat bot start — agar masalah env terlihat di journalctl."""
    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers and app_url:
        logger.info("ai_health: siap lapor ke %s/api/bot/ai-health", app_url)
        return
    logger.warning(
        "ai_health: %s — statistik AI tidak akan masuk admin dashboard",
        missing_laravel_config_message() or "konfigurasi Laravel kosong",
    )


def report_ai_event(event: str, detail: str = "") -> None:
    """Kirim event ke Laravel tanpa memblokir bot."""
    global _warned_missing_config

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        if not _warned_missing_config:
            log_ai_health_config()
            _warned_missing_config = True
        return

    payload = {"event": event}
    if detail:
        payload["detail"] = detail[:500]

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


def report_ai_failure(exc: Exception, context: str = "") -> None:
    event = _classify_ai_error(exc)
    detail = context or str(exc)[:500]
    report_ai_event(event, detail)


def report_gemini_failure(exc: Exception, context: str = "") -> None:
    """Alias lama — tetap ada agar import lama tidak pecah."""
    report_ai_failure(exc, context)
