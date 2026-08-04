"""Reclassify transaksi tersimpan memakai rules taxonomy (tanpa AI / Telegram).

Input JSON (stdin atau --file):
{
  "rows": [
    {
      "id": 1,
      "type": "Pengeluaran",
      "category": "Jajan",
      "nature": "Wants",
      "is_impulsive": false,
      "notes": "makan malam karena capek 65rb",
      "amount": 65000,
      "mood": "Tired"
    }
  ]
}

Output JSON (stdout):
{
  "rows": [
    {
      "id": 1,
      "category": "Makanan & Minuman",
      "nature": "Wants",
      "is_impulsive": true,
      "changed": true,
      "changes": ["category", "is_impulsive"]
    }
  ]
}
"""

from __future__ import annotations

import argparse
import json
import sys
import time
from pathlib import Path
from typing import Any

from context_rules import apply_context_rules
from impulsive_rules import resolve_impulsif
from nature_rules import refine_sifat_from_context
from transaction_categories import normalize_category_fields, normalize_saving_fields

VALID_JENIS = frozenset({"Pemasukan", "Pengeluaran", "Saving/Investment", "Kewajiban Pajak", "Piutang Keluar", "Piutang Masuk"})
VALID_SIFAT = frozenset({"Need", "Wants"})


def _seed_static_rules() -> None:
    """Pastikan offline tidak bergantung ke API Laravel saat reclassify batch."""
    import category_rules_cache as crc

    crc._cache = dict(crc._STATIC_FALLBACK)
    crc._cache_loaded_at = time.time()


def _normalize_taxonomy(parsed: dict[str, Any]) -> dict[str, Any]:
    jenis = str(parsed.get("jenis", "Pengeluaran")).strip()
    sifat = str(parsed.get("sifat", "Need")).strip()
    sifat_lower = sifat.lower()

    if sifat_lower in {"saving/investement", "saving/investment", "saving", "investasi", "investment"}:
        parsed["jenis"] = "Saving/Investment"
        parsed["sifat"] = "Need"
    elif sifat_lower in {"donation", "donasi", "sedekah", "persembahan"}:
        parsed["jenis"] = "Pengeluaran"
        parsed["kategori"] = "Sosial & Keluarga"
        parsed["sifat"] = "Need"
    elif sifat not in VALID_SIFAT:
        parsed["sifat"] = "Wants" if sifat_lower in {"want", "wants"} else "Need"

    if jenis not in VALID_JENIS:
        jenis_lower = jenis.lower()
        if jenis_lower in {
            "saving/investement",
            "saving/investment",
            "saving",
            "investasi",
            "investment",
            "nabung",
        }:
            parsed["jenis"] = "Saving/Investment"
        else:
            parsed["jenis"] = "Pengeluaran"

    if parsed["sifat"] not in VALID_SIFAT:
        parsed["sifat"] = "Need"

    return parsed


def reclassify_row(row: dict[str, Any]) -> dict[str, Any]:
    source = str(row.get("notes") or "").strip()
    parsed: dict[str, Any] = {
        "jenis": str(row.get("type") or "Pengeluaran").strip(),
        "kategori": str(row.get("category") or "").strip(),
        "sifat": str(row.get("nature") or "Need").strip(),
        "impulsif": "Yes" if bool(row.get("is_impulsive")) else "No",
        "keterangan": source,
        "nominal": int(row.get("amount") or 0),
        "mood": str(row.get("mood") or "Neutral").strip() or "Neutral",
    }

    _normalize_taxonomy(parsed)
    apply_context_rules(parsed, source)
    normalize_category_fields(parsed, source)
    normalize_saving_fields(parsed, source)
    refine_sifat_from_context(parsed, source)
    parsed["impulsif"] = resolve_impulsif(
        parsed,
        source,
        ai_suggested=None,
        trust_ai=False,
    )

    new_category = str(parsed.get("kategori") or row.get("category") or "").strip()
    new_nature = str(parsed.get("sifat") or row.get("nature") or "Need").strip()
    new_impulsive = str(parsed.get("impulsif") or "No").strip().lower() in {"yes", "true", "1"}

    old_category = str(row.get("category") or "").strip()
    old_nature = str(row.get("nature") or "").strip()
    old_impulsive = bool(row.get("is_impulsive"))

    changes: list[str] = []
    if new_category != old_category:
        changes.append("category")
    if new_nature != old_nature:
        changes.append("nature")
    if new_impulsive != old_impulsive:
        changes.append("is_impulsive")

    return {
        "id": row.get("id"),
        "category": new_category,
        "nature": new_nature,
        "is_impulsive": new_impulsive,
        "changed": bool(changes),
        "changes": changes,
    }


def reclassify_batch(payload: dict[str, Any]) -> dict[str, Any]:
    _seed_static_rules()
    rows = payload.get("rows") or []
    if not isinstance(rows, list):
        raise ValueError("payload.rows harus array")

    out = [reclassify_row(row) for row in rows if isinstance(row, dict)]
    return {"rows": out}


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Reclassify bot transactions offline (rules only)")
    parser.add_argument("--file", "-f", help="Path JSON input (default: stdin)")
    args = parser.parse_args(argv)

    if args.file:
        raw = Path(args.file).read_text(encoding="utf-8")
    else:
        raw = sys.stdin.read()

    payload = json.loads(raw or "{}")
    result = reclassify_batch(payload if isinstance(payload, dict) else {"rows": []})
    json.dump(result, sys.stdout, ensure_ascii=False)
    sys.stdout.write("\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
