"""Anthropic Claude — parsing transaksi teks & gambar (ganti Gemini)."""

from __future__ import annotations

import base64
import json
import logging
import os
import re
from typing import Any

from anthropic import Anthropic

logger = logging.getLogger(__name__)

DEFAULT_MODELS = "claude-3-5-haiku-20241022,claude-sonnet-4-20250514"


def _get_env(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise RuntimeError(f"Missing required environment variable: {name}")
    return value


def extract_json(raw_text: str) -> dict[str, Any]:
    cleaned = raw_text.strip()
    cleaned = re.sub(r"^```json\s*|\s*```$", "", cleaned, flags=re.IGNORECASE | re.DOTALL).strip()
    return json.loads(cleaned)


def model_candidates() -> list[str]:
    raw = os.getenv("CLAUDE_MODELS", DEFAULT_MODELS).strip()
    return [m.strip() for m in raw.split(",") if m.strip()]


def _client() -> Anthropic:
    return Anthropic(api_key=_get_env("ANTHROPIC_API_KEY"))


def _generate_json(system: str, user_content: str | list[dict[str, Any]], max_tokens: int = 1024) -> dict[str, Any]:
    client = _client()
    last_error: Exception | None = None

    for model in model_candidates():
        try:
            message = client.messages.create(
                model=model,
                max_tokens=max_tokens,
                system=system,
                messages=[{"role": "user", "content": user_content}],
            )
            text = ""
            for block in message.content:
                if getattr(block, "type", None) == "text":
                    text += block.text
            if not text.strip():
                raise RuntimeError("Claude returned empty response")
            return extract_json(text)
        except Exception as exc:
            last_error = exc
            logger.warning("Claude model %s failed: %s", model, exc)

    raise RuntimeError(f"All Claude models failed: {last_error}")


def analyze_with_claude(text: str, system_prompt: str) -> dict[str, Any]:
    return _generate_json(system_prompt, text)


def extract_transaction_text_from_image(image_bytes: bytes, mime_type: str, instruction: str) -> str:
    b64 = base64.standard_b64encode(image_bytes).decode("ascii")
    media = mime_type if mime_type.startswith("image/") else "image/jpeg"
    content: list[dict[str, Any]] = [
        {
            "type": "image",
            "source": {"type": "base64", "media_type": media, "data": b64},
        },
        {
            "type": "text",
            "text": instruction,
        },
    ]
    client = _client()
    last_error: Exception | None = None

    for model in model_candidates():
        try:
            message = client.messages.create(
                model=model,
                max_tokens=512,
                messages=[{"role": "user", "content": content}],
            )
            text = ""
            for block in message.content:
                if getattr(block, "type", None) == "text":
                    text += block.text
            return text.strip()
        except Exception as exc:
            last_error = exc
            logger.warning("Claude vision model %s failed: %s", model, exc)

    raise RuntimeError(f"Claude vision failed: {last_error}")
