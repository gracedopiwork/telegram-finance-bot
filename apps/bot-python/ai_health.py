"""Laporkan kesehatan pemakaian Gemini ke admin Laravel (fire-and-forget)."""

from __future__ import annotations

import logging
import threading

import requests

logger = logging.getLogger(__name__)


def _get_env(name: str) -> str:
    import os

    return (os.getenv(name) or "").strip()


def _classify_gemini_error(exc: Exception) -> str:
    text = f"{type(exc).__name__}: {exc}".lower()
    if "429" in text or "too many requests" in text or "resource exhausted" in text or "quota" in text:
        return "rate_limit"
    return "error"


def report_ai_event(event: str, detail: str = "") -> None:
    """Kirim event ke Laravel tanpa memblokir bot."""
    token = _get_env("BOT_INTERNAL_API_TOKEN")
    app_url = _get_env("LARAVEL_APP_URL").rstrip("/")
    if not token or not app_url:
        return

    payload = {"event": event}
    if detail:
        payload["detail"] = detail[:500]

    def _send() -> None:
        try:
            requests.post(
                f"{app_url}/api/bot/ai-health",
                json=payload,
                headers={
                    "Authorization": f"Bearer {token}",
                    "Accept": "application/json",
                },
                timeout=8,
            )
        except Exception as exc:
            logger.debug("ai_health: gagal lapor ke Laravel: %s", exc)

    threading.Thread(target=_send, daemon=True).start()


def report_gemini_failure(exc: Exception, context: str = "") -> None:
    event = _classify_gemini_error(exc)
    detail = context or str(exc)[:500]
    report_ai_event(event, detail)
