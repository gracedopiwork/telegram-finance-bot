"""Offline bot classification — same rules as Telegram fallback + finalize.

Does not call Claude or Laravel. Used by the 100-transaction golden suite.
"""

from __future__ import annotations

from typing import Any

from bucket_resolver import resolve_bucket
from clarification_rules import clarification_question
from context_rules import apply_context_rules, classify_from_text, infer_saving_label
from impulsive_rules import resolve_impulsif, stamp_planned_cue
from nature_rules import refine_sifat_from_context
from nominal_parser import parse_nominal_from_text
from yfd_taxonomy import VALID_JENIS, VALID_SIFAT, attach_taxonomy_flags


def format_prescription_bucket(parsed: dict[str, Any]) -> str:
    bucket = parsed.get("bucket")
    jenis = str(parsed.get("jenis") or "").strip()
    if jenis in {"Piutang Keluar", "Piutang Masuk", "Utang Masuk", "Utang Keluar"}:
        return f"Likuiditas sosial ({jenis}) — tidak masuk prescription"
    if jenis == "Pemasukan" and bucket is None:
        return "Tidak masuk prescription (Pemasukan)"
    if jenis == "Kewajiban Pajak" and bucket is None:
        return "Tidak masuk prescription (Kewajiban Pajak)"
    return str(bucket or "Belum dapat dicek")


def normalize_taxonomy(parsed: dict[str, Any]) -> dict[str, Any]:
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
        elif jenis_lower in {"kewajiban pajak", "pajak", "tax", "pph", "pph 21", "pph 25"}:
            parsed["jenis"] = "Kewajiban Pajak"
        elif jenis_lower in {"piutang keluar", "piutang out", "receivable out", "pinjaman keluar"}:
            parsed["jenis"] = "Piutang Keluar"
        elif jenis_lower in {"piutang masuk", "piutang in", "receivable in", "pelunasan piutang"}:
            parsed["jenis"] = "Piutang Masuk"
        elif jenis_lower in {
            "utang masuk",
            "hutang masuk",
            "payable in",
            "pinjaman masuk",
            "terima pinjaman",
        }:
            parsed["jenis"] = "Utang Masuk"
        elif jenis_lower in {
            "utang keluar",
            "hutang keluar",
            "payable out",
            "bayar utang sosial",
            "bayar hutang sosial",
        }:
            parsed["jenis"] = "Utang Keluar"
        else:
            parsed["jenis"] = "Pengeluaran"

    if parsed.get("sifat") not in VALID_SIFAT:
        parsed["sifat"] = "Need"
    return parsed


def classify_offline(text: str) -> dict[str, Any]:
    """Classify a Telegram-style note the same way the bot does without AI."""
    source = text.strip()
    hit = classify_from_text(source)
    parsed: dict[str, Any] = {
        "keterangan": source,
        "nominal": parse_nominal_from_text(source) or 0,
        "jenis": hit["jenis"] if hit else "Pengeluaran",
        "kategori": hit["kategori"] if hit else "Lain-lain",
        "sifat": hit["sifat"] if hit else "Wants",
        "mood": "Neutral",
        "impulsif": "No",
    }
    apply_context_rules(parsed, source)
    normalize_taxonomy(parsed)
    if str(parsed.get("jenis") or "").strip() == "Saving/Investment":
        label = infer_saving_label(source) or str(parsed.get("kategori") or "Investasi & Tabungan")
        if label != "Investasi":
            parsed["kategori"] = "Investasi & Tabungan"
        parsed["sifat"] = "Need"
    refine_sifat_from_context(parsed, source)
    attach_taxonomy_flags(parsed, source)
    stamp_planned_cue(parsed, source)
    parsed["impulsif"] = resolve_impulsif(parsed, source)
    question = clarification_question(parsed, source)
    parsed["clarification_question"] = question
    parsed["needs_clarification"] = bool(question)
    parsed["bucket"] = resolve_bucket(parsed)
    parsed["bucket_label"] = format_prescription_bucket(parsed)
    parsed["rule_hit"] = hit is not None
    return parsed
