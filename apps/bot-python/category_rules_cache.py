"""Cache aturan kategori dari admin Laravel — sumber kebenaran taxonomy bot."""

from __future__ import annotations

import logging
import time
from typing import Any

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)

_CACHE_TTL_SECONDS = 300
_cache: dict[str, Any] | None = None
_cache_loaded_at: float = 0.0
_warned_fetch_fail = False

# Fallback statis jika API belum tersedia (sama seperti taxonomy bot lama).
_STATIC_FALLBACK: dict[str, Any] = {
    "version": "static",
    "source": "static",
    "categories": ["Makan", "Transport", "Listrik", "Air", "Jajan", "Social", "Gaji"],
    "sub_categories": [
        "Listrik",
        "Pakaian",
        "Servis Kendaraan",
        "Nonton Konser",
        "Pengeluaran lain-lain",
        "Hadiah / Amplop sosial",
        "Popok",
        "Jajan / Makan diluar",
        "Angkutan Umum",
        "Skincare",
        "Mainan Anak",
        "Ulang Tahun keluarga",
        "Vitamin",
        "Alat Kesehatan",
    ],
    "category_sub_map": {
        "Makan": ["Jajan / Makan diluar"],
        "Jajan": [
            "Jajan / Makan diluar",
            "Skincare",
            "Pakaian",
            "Popok",
            "Mainan Anak",
            "Vitamin",
            "Alat Kesehatan",
            "Pengeluaran lain-lain",
        ],
        "Transport": ["Angkutan Umum", "Servis Kendaraan"],
        "Listrik": ["Listrik"],
        "Air": ["Pengeluaran lain-lain"],
        "Social": [
            "Hadiah / Amplop sosial",
            "Nonton Konser",
            "Ulang Tahun keluarga",
        ],
        "Gaji": ["Pengeluaran lain-lain"],
    },
    "rules": [],
    "fallback_category": "Jajan",
    "fallback_sub": "Pengeluaran lain-lain",
    "natures": ["Need", "Wants"],
    "policy_notes": [
        "Tabel Pemetaan Bucket di admin adalah sumber kebenaran pemetaan kategori → bucket.",
        "AI bot HANYA boleh memakai Kategori yang terdaftar di tabel admin.",
        "Kategori baru harus ditambahkan ke tabel admin dulu sebelum dashboard membacanya.",
    ],
    "strict_categories_only": True,
}


def _is_stale() -> bool:
    if _cache is None:
        return True
    return (time.time() - _cache_loaded_at) > _CACHE_TTL_SECONDS


def refresh(force: bool = False) -> dict[str, Any]:
    """Muat ulang aturan dari Laravel admin."""
    global _cache, _cache_loaded_at, _warned_fetch_fail

    if not force and not _is_stale():
        return _cache or _STATIC_FALLBACK

    headers = auth_headers()
    app_url, _ = resolve_laravel_target()
    if headers is None or not app_url:
        if not _warned_fetch_fail:
            logger.warning(
                "category_rules: %s — pakai fallback statis",
                missing_laravel_config_message() or "no_config",
            )
            _warned_fetch_fail = True
        _cache = dict(_STATIC_FALLBACK)
        _cache_loaded_at = time.time()
        return _cache

    url = f"{app_url}/api/bot/category-rules"
    try:
        resp = requests.get(url, headers=headers, timeout=15)
        if resp.status_code == 200:
            body = resp.json()
            if body.get("ok") and isinstance(body.get("data"), dict):
                _cache = body["data"]
                _cache_loaded_at = time.time()
                logger.info(
                    "category_rules: dimuat dari admin (%s kategori, v=%s)",
                    len(_cache.get("categories", [])),
                    _cache.get("version"),
                )
                return _cache
        logger.warning(
            "category_rules: HTTP %s — %s",
            resp.status_code,
            resp.text[:200],
        )
    except Exception as exc:
        logger.warning("category_rules: gagal GET %s — %s", url, exc)

    if _cache is None:
        _cache = dict(_STATIC_FALLBACK)
    _cache_loaded_at = time.time()
    return _cache


def get_rules() -> dict[str, Any]:
    if _is_stale():
        return refresh()
    return _cache or _STATIC_FALLBACK


def valid_kategori() -> tuple[str, ...]:
    cats = get_rules().get("categories") or _STATIC_FALLBACK["categories"]
    return tuple(str(c) for c in cats)


def valid_sub_kategori() -> tuple[str, ...]:
    subs = get_rules().get("sub_categories") or _STATIC_FALLBACK["sub_categories"]
    return tuple(str(s) for s in subs)


def kategori_sub_map() -> dict[str, tuple[str, ...]]:
    raw = get_rules().get("category_sub_map") or _STATIC_FALLBACK["category_sub_map"]
    return {str(k): tuple(str(s) for s in v) for k, v in raw.items()}


def fallback_kategori() -> str:
    return str(get_rules().get("fallback_category") or "Jajan")


def fallback_sub() -> str:
    return str(get_rules().get("fallback_sub") or "Pengeluaran lain-lain")


def apply_admin_nature(parsed: dict[str, Any]) -> dict[str, Any]:
    """Terapkan sifat dari aturan admin jika kategori cocok."""
    kategori = str(parsed.get("kategori", ""))
    rules = get_rules().get("rules") or []
    matched_nature: str | None = None

    for rule in rules:
        if not isinstance(rule, dict):
            continue
        nature = rule.get("nature")
        if not nature:
            continue
        rule_cat = str(rule.get("category") or "")
        if rule_cat == "*":
            if matched_nature is None:
                matched_nature = str(nature)
            continue
        if rule_cat != kategori:
            continue
        matched_nature = str(nature)
        break

    if matched_nature:
        parsed["sifat"] = matched_nature

    return parsed
