"""Resolve prescription bucket locally — mirror Laravel CategoryBucketService.

Loads apps/admin-laravel/config/category_bucket_mappings_defaults.php so golden
tests stay aligned with admin mapping without calling the API.
"""

from __future__ import annotations

import json
import re
import subprocess
from functools import lru_cache
from pathlib import Path
from typing import Any

REPO_ROOT = Path(__file__).resolve().parents[2]
MAPPINGS_PHP = REPO_ROOT / "apps" / "admin-laravel" / "config" / "category_bucket_mappings_defaults.php"
BUCKETS_PHP = REPO_ROOT / "apps" / "admin-laravel" / "config" / "category_buckets.php"

JENIS_TO_TX_TYPE = {
    "Pemasukan": "income",
    "Saving/Investment": "saving",
    "Kewajiban Pajak": "tax",
    "Piutang Keluar": "receivable_out",
    "Piutang Masuk": "receivable_in",
    "Utang Masuk": "payable_in",
    "Utang Keluar": "payable_out",
    "Pengeluaran": "expense",
}

EXCLUDED_JENIS = {
    "Pemasukan",
    "Kewajiban Pajak",
    "Piutang Keluar",
    "Piutang Masuk",
    "Utang Masuk",
    "Utang Keluar",
}

_KV_RE = re.compile(
    r"'(\w+)'\s*=>\s*(?:'((?:\\'|[^'])*)'|(\d+)|null)",
)
_COMMENT_RE = re.compile(r"(?<!:)//.*?$", re.M)


def _php_include_json(path: Path) -> Any | None:
    try:
        proc = subprocess.run(
            ["php", "-r", "echo json_encode(include $argv[1], JSON_UNESCAPED_UNICODE);", str(path)],
            capture_output=True,
            text=True,
            timeout=20,
            check=False,
        )
    except (OSError, subprocess.TimeoutExpired):
        return None
    if proc.returncode != 0 or not proc.stdout.strip():
        return None
    try:
        return json.loads(proc.stdout)
    except json.JSONDecodeError:
        return None


def _parse_php_assoc_rows(text: str) -> list[dict[str, Any]]:
    body = _COMMENT_RE.sub("", text)
    rows: list[dict[str, Any]] = []
    for chunk in re.findall(r"\[([^\[\]]+)\]", body):
        if "'bucket'" not in chunk and '"bucket"' not in chunk:
            continue
        row: dict[str, Any] = {}
        for key, str_val, num_val in _KV_RE.findall(chunk):
            if num_val:
                row[key] = int(num_val)
            elif str_val == "" and f"'{key}' => null" in chunk.replace(" ", ""):
                row[key] = None
            else:
                row[key] = str_val.replace("\\'", "'")
        if "bucket" in row:
            rows.append(row)
    return rows


def _parse_php_string_lists(text: str) -> dict[str, list[str]]:
    body = _COMMENT_RE.sub("", text)
    out: dict[str, list[str]] = {}
    for key, inner in re.findall(r"'(\w+)'\s*=>\s*\[(.*?)\],", body, flags=re.S):
        vals = [v.replace("\\'", "'") for v in re.findall(r"'((?:\\'|[^'])*)'", inner)]
        if vals:
            out[key] = vals
    return out


@lru_cache(maxsize=1)
def load_mappings() -> list[dict[str, Any]]:
    data = _php_include_json(MAPPINGS_PHP)
    if isinstance(data, list) and data:
        rows = [row for row in data if isinstance(row, dict) and row.get("bucket")]
        return sorted(rows, key=lambda r: (int(r.get("sort_order") or 0), r.get("category") or ""))
    text = MAPPINGS_PHP.read_text(encoding="utf-8")
    rows = _parse_php_assoc_rows(text)
    return sorted(rows, key=lambda r: (int(r.get("sort_order") or 0), r.get("category") or ""))


@lru_cache(maxsize=1)
def load_legacy_keywords() -> dict[str, list[str]]:
    data = _php_include_json(BUCKETS_PHP)
    if isinstance(data, dict) and data:
        out: dict[str, list[str]] = {}
        for key, val in data.items():
            if isinstance(val, list):
                out[key] = [str(x) for x in val]
        return out
    return _parse_php_string_lists(BUCKETS_PHP.read_text(encoding="utf-8"))


def _keywords_list(mapping: dict[str, Any]) -> list[str]:
    raw = mapping.get("match_keywords")
    if not raw:
        return []
    return [part.strip().lower() for part in str(raw).split(",") if part.strip()]


def _category_keys_match(a: str, b: str) -> bool:
    if a == b:
        return True
    compact_a = re.sub(r"\s+", "", a)
    compact_b = re.sub(r"\s+", "", b)
    return bool(compact_a) and compact_a == compact_b


def _contains_any(haystack: str, keywords: list[str]) -> bool:
    return any(k and k.lower() in haystack for k in keywords)


def mapping_transaction_type(jenis: str) -> str:
    return JENIS_TO_TX_TYPE.get(jenis, "expense")


_HOUSEHOLD_ITEMS = (
    "tumbler",
    "termos",
    "kulkas",
    "rice cooker",
    "mesin cuci",
    "gorden",
    "sprei",
    "piring",
    "perabot",
)
_HOUSEHOLD_REPAIR = (
    "rusak",
    "pecah",
    "bocor",
    "ganti yang",
    "ganti yg",
    "mengganti",
    "belum memadai",
    "tidak layak",
    "sebelumnya rusak",
)
_HOUSEHOLD_LIFESTYLE = (
    "koleksi",
    "ikuti tren",
    "ikut tren",
    "upgrade",
    "fomo",
    "tambah koleksi",
)


def resolve_household_durable(
    *,
    jenis: str,
    sifat: str,
    notes: str,
    kategori: str,
) -> str | None:
    if jenis != "Pengeluaran":
        return None
    combined = f"{notes} {kategori}".strip().lower()
    if not any(item in combined for item in _HOUSEHOLD_ITEMS):
        return None
    repair = any(k in combined for k in _HOUSEHOLD_REPAIR)
    lifestyle = any(k in combined for k in _HOUSEHOLD_LIFESTYLE)
    if repair or sifat == "Need":
        if lifestyle and not repair:
            return "Flexible + Social"
        return "Essential Living"
    return "Flexible + Social"


def resolve_from_mappings(
    *,
    jenis: str,
    kategori: str,
    sifat: str,
    notes: str,
) -> str | None:
    category = kategori.strip().lower()
    nature = sifat.strip()
    combined = f"{notes} {kategori}".strip().lower()
    tx_type = mapping_transaction_type(jenis)

    for mapping in load_mappings():
        map_type = str(mapping.get("transaction_type") or "expense")
        if map_type not in {tx_type, "transfer"}:
            continue
        map_nature = mapping.get("nature")
        if map_nature and str(map_nature) != nature:
            continue

        map_category = str(mapping.get("category") or "").strip().lower()
        map_sub = str(mapping.get("sub_category") or "").strip().lower()
        wildcard = map_category == "*"
        category_match = wildcard or (map_category != "" and _category_keys_match(map_category, category))
        sub_match = map_sub in {"", "-"}

        keywords = _keywords_list(mapping)
        has_keywords = bool(keywords)
        keyword_match = any(k in combined for k in keywords)

        if wildcard and not has_keywords:
            return str(mapping["bucket"])
        if keyword_match and (wildcard or category_match):
            return str(mapping["bucket"])
        if map_category and not wildcard and category_match and sub_match and not has_keywords:
            return str(mapping["bucket"])

    return None


def resolve_legacy(
    *,
    jenis: str,
    kategori: str,
    sifat: str,
    notes: str,
) -> str | None:
    if jenis in EXCLUDED_JENIS:
        return None
    category = kategori.strip().lower()
    if category in {
        "piutang keluar",
        "piutang masuk",
        "utang masuk",
        "utang keluar",
        "hutang masuk",
        "hutang keluar",
    }:
        return None

    cfg = load_legacy_keywords()
    combined = f"{notes} {kategori}".strip().lower()

    if _contains_any(combined, cfg.get("protection_keywords", [])):
        return "Protection"
    if jenis == "Saving/Investment":
        return "Future Building"
    if _contains_any(combined, cfg.get("future_building_context_keywords", [])):
        return "Future Building"
    if category in {"transport", "transportasi"} and _contains_any(
        combined, cfg.get("transport_flexible_keywords", [])
    ):
        return "Flexible + Social"
    if _contains_any(combined, cfg.get("essential_context_keywords", [])):
        return "Essential Living"
    if category in {"social", "sosial & keluarga", "hadiah", "lifestyle & hiburan", "traveling"}:
        return "Flexible + Social"
    if _contains_any(
        combined,
        [
            "gym",
            "pilates",
            "yoga",
            "crossfit",
            "personal trainer",
            "coaching tenis",
            "coaching padel",
            "les renang",
            "tenis",
            "renang",
            "padel",
        ],
    ):
        return "Flexible + Social"
    if _contains_any(combined, cfg.get("future_building_keywords", [])):
        return "Future Building"
    if sifat == "Wants" or _contains_any(combined, cfg.get("flexible_keywords", [])):
        return "Flexible + Social"
    if category in {c.lower() for c in cfg.get("essential_categories", [])}:
        return "Essential Living"
    return "Essential Living"


def normalize_bucket(bucket: str | None) -> str | None:
    if bucket in {None, "Transfer (Excluded)", "Income"}:
        return None
    return bucket


def resolve_bucket(parsed: dict[str, Any]) -> str | None:
    """Return one of the 4 prescription buckets, or None if excluded."""
    jenis = str(parsed.get("jenis") or "").strip()
    if jenis in EXCLUDED_JENIS:
        return None
    kategori = str(parsed.get("kategori") or "")
    if kategori.lower() in {
        "piutang keluar",
        "piutang masuk",
        "utang masuk",
        "utang keluar",
        "hutang masuk",
        "hutang keluar",
    }:
        return None

    notes = str(parsed.get("keterangan") or parsed.get("notes") or "")
    sifat = str(parsed.get("sifat") or "")
    household = resolve_household_durable(jenis=jenis, sifat=sifat, notes=notes, kategori=kategori)
    if household is not None:
        return household
    from_map = resolve_from_mappings(
        jenis=jenis,
        kategori=kategori,
        sifat=sifat,
        notes=notes,
    )
    if from_map is not None:
        return normalize_bucket(from_map)
    return resolve_legacy(jenis=jenis, kategori=kategori, sifat=sifat, notes=notes)
