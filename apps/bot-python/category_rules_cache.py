"""Cache aturan kategori dari admin Laravel — sumber kebenaran taxonomy bot."""

from __future__ import annotations

import logging
import time
from typing import Any

import requests

from laravel_api import auth_headers, missing_laravel_config_message, resolve_laravel_target

logger = logging.getLogger(__name__)

_CACHE_TTL_SECONDS = 60
_cache: dict[str, Any] | None = None
_cache_loaded_at: float = 0.0
_warned_fetch_fail = False

# Fallback statis jika API belum tersedia — YFD AI Taxonomy v1.3 closed list.
_STATIC_FALLBACK: dict[str, Any] = {
    "version": "static",
    "source": "static",
    "categories": [
        "Makanan & Minuman",
        "Tempat Tinggal",
        "Transportasi",
        "Komunikasi",
        "Kesehatan & Kebersihan Diri",
        "Pendidikan",
        "Investasi & Tabungan",
        "Proteksi",
        "Lifestyle & Hiburan",
        "Traveling",
        "Sosial & Keluarga",
        "Bisnis & Karir",
        "Hadiah",
        "Cicilan & Hutang",
        "Pakaian & Aksesoris",
        "Lain-lain",
        "Biaya Legal, Administrasi & Peristiwa Besar",
        "Gaji",
        "Bonus",
        "Freelance",
        "Affiliate",
        "Dividen",
        "Bunga Investasi",
        "Cashback",
        "Refund",
        "Penjualan",
        "Sewa Masuk",
        "Transfer Masuk",
    ],
    "sub_categories": [],
    "category_sub_map": {},
    "rules": [],
    "fallback_category": "Lain-lain",
    "fallback_sub": "-",
    "natures": ["Need", "Wants"],
    "policy_notes": [
        "Taxonomy tertutup (YFD AI Taxonomy v1.3): AI HANYA memilih dari 17 kategori resmi (+ kategori pemasukan).",
        "AI tidak boleh membuat kategori baru. Jika ragu → Lain-lain.",
        "Layer 1 = Kategori (closed list). Layer 2 = Bucket (otomatis dari mapping sistem).",
        "Jenis Kewajiban Pajak (PPh 25/29/28A) dikecualikan dari 4 bucket.",
        "Gym/olahraga berbayar → Lifestyle & Hiburan / Flexible + Social / Wants.",
        "Grab/ojek ke gym/cafe → tetap Transportasi / Wants; bucket Flexible + Social (bukan kategori Lifestyle).",
        "Laundry → Kesehatan & Kebersihan Diri / Essential. Fashion → Pakaian & Aksesoris.",
        "Makan/ngopi + meeting kerja/klien → Bisnis & Karir / Future Building.",
        "Notaris/mahar/duka → Biaya Legal, Administrasi & Peristiwa Besar.",
    ],
    "strict_categories_only": True,
    "aliases": {},
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
    return str(get_rules().get("fallback_category") or "Lain-lain")


def fallback_sub() -> str:
    return str(get_rules().get("fallback_sub") or "-")


def apply_admin_nature(parsed: dict[str, Any]) -> dict[str, Any]:
    """Terapkan sifat dari aturan admin; utamakan rule yang keyword-nya cocok."""
    kategori = str(parsed.get("kategori", ""))
    notes = str(parsed.get("keterangan", "")).lower()
    rules = get_rules().get("rules") or []
    keyword_nature: str | None = None
    category_nature: str | None = None
    wildcard_nature: str | None = None

    for rule in rules:
        if not isinstance(rule, dict):
            continue
        nature = rule.get("nature")
        if not nature:
            continue
        rule_cat = str(rule.get("category") or "")
        keywords = rule.get("keywords") or []
        hit_keyword = any(
            isinstance(kw, str) and kw.strip() and kw.strip().lower() in notes
            for kw in keywords
        )
        if rule_cat == "*":
            if wildcard_nature is None:
                wildcard_nature = str(nature)
            continue
        if rule_cat != kategori:
            continue
        if hit_keyword and keyword_nature is None:
            keyword_nature = str(nature)
        if category_nature is None and not keywords:
            category_nature = str(nature)
        elif category_nature is None and not hit_keyword:
            category_nature = str(nature)

    matched = keyword_nature or category_nature or wildcard_nature
    if matched:
        existing = str(parsed.get("sifat") or "").strip()
        if keyword_nature or existing not in {"Need", "Wants"}:
            parsed["sifat"] = matched

    return parsed
