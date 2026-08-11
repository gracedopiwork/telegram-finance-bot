"""Paket uji kasus ambigu — dipakai unittest dan perintah /uji di Telegram."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

from offline_classify import classify_offline

_FIXTURES = Path(__file__).resolve().parent / "fixtures"
COMPARE_KEYS = ("jenis", "kategori", "sifat", "bucket", "nominal", "needs_clarification")

PACKS: dict[str, dict[str, str]] = {
    "ambigu": {
        "title": "Uji ambigu",
        "file": "ambiguous_transactions.json",
        "ok_msg": "Semua kasus ambigu sesuai taksonomi.",
    },
    "cakup": {
        "title": "Uji cakupan (data baru)",
        "file": "coverage_transactions.json",
        "ok_msg": "Semua kasus cakupan sesuai taksonomi.",
    },
    "sosial": {
        "title": "Uji piutang & utang",
        "file": "social_liquidity_transactions.json",
        "ok_msg": "Semua kasus piutang/utang sesuai 4 arah likuiditas sosial.",
    },
}

FIXTURE = _FIXTURES / PACKS["ambigu"]["file"]


def load_cases(pack: str = "ambigu") -> list[dict[str, Any]]:
    meta = PACKS.get(pack) or PACKS["ambigu"]
    path = _FIXTURES / meta["file"]
    data = json.loads(path.read_text(encoding="utf-8"))
    cases = list(data.get("cases") or [])
    if not cases:
        raise AssertionError(f"fixture kosong: {path.name}")
    return cases


def case_diffs(case: dict[str, Any]) -> dict[str, tuple[Any, Any]]:
    expected = dict(case.get("expected") or {})
    actual = classify_offline(str(case["text"]))
    diffs: dict[str, tuple[Any, Any]] = {}
    for key in COMPARE_KEYS:
        if key not in expected:
            continue
        got = actual.get(key)
        want = expected.get(key)
        if key == "needs_clarification":
            got = bool(actual.get("needs_clarification"))
            want = bool(want)
        if got != want:
            diffs[key] = (want, got)
    return diffs


def evaluate_cases(
    cases: list[dict[str, Any]] | None = None,
    pack: str = "ambigu",
) -> dict[str, Any]:
    rows: list[dict[str, Any]] = []
    for case in cases or load_cases(pack):
        actual = classify_offline(str(case["text"]))
        diffs = case_diffs(case)
        rows.append(
            {
                "id": case["id"],
                "group": case.get("group") or "-",
                "severity": case.get("severity") or "watch",
                "text": case["text"],
                "why": case.get("why") or "",
                "ok": not diffs,
                "diffs": diffs,
                "actual": {
                    "jenis": actual.get("jenis"),
                    "kategori": actual.get("kategori"),
                    "sifat": actual.get("sifat"),
                    "bucket": actual.get("bucket"),
                    "nominal": actual.get("nominal"),
                    "ask": actual.get("clarification_question") or "",
                },
            }
        )
    must = [r for r in rows if r["severity"] == "must"]
    watch = [r for r in rows if r["severity"] != "must"]
    return {
        "rows": rows,
        "total": len(rows),
        "passed": sum(1 for r in rows if r["ok"]),
        "must_total": len(must),
        "must_passed": sum(1 for r in must if r["ok"]),
        "watch_total": len(watch),
        "watch_passed": sum(1 for r in watch if r["ok"]),
        "must_fail": [r for r in must if not r["ok"]],
        "watch_fail": [r for r in watch if not r["ok"]],
    }


def _fmt_got(row: dict[str, Any]) -> str:
    a = row["actual"]
    bucket = a["bucket"] if a["bucket"] is not None else "-"
    line = f"{a['jenis']} / {a['kategori']} / {a['sifat']} / {bucket}"
    if a.get("ask"):
        line += f" | tanya: {a['ask']}"
    return line


def _fmt_want(row: dict[str, Any]) -> str:
    parts = [f"{k}={w!r} (dapat {g!r})" for k, (w, g) in row["diffs"].items()]
    return ", ".join(parts)


def format_report(result: dict[str, Any] | None = None, pack: str = "ambigu") -> str:
    result = result or evaluate_cases(pack=pack)
    title = PACKS.get(pack, PACKS["ambigu"])["title"]
    ok_msg = PACKS.get(pack, PACKS["ambigu"])["ok_msg"]
    lines = [
        f"{title}: {result['passed']}/{result['total']} sesuai taksonomi",
        f"Wajib lulus: {result['must_passed']}/{result['must_total']}",
        f"Lubang sisa: {result['watch_total'] - result['watch_passed']}/{result['watch_total']} masih Lain-lain/salah",
        "",
    ]
    if result["must_fail"]:
        lines.append("GAGAL WAJIB:")
        for row in result["must_fail"]:
            lines.append(f"- {row['id']} \"{row['text']}\"")
            lines.append(f"  {_fmt_want(row)}")
        lines.append("")
    if result["watch_fail"]:
        lines.append("MASIH LUBANG (boleh gagal dulu):")
        for row in result["watch_fail"]:
            lines.append(f"- {row['id']} \"{row['text']}\"")
            lines.append(f"  dapat: {_fmt_got(row)}")
            lines.append(f"  {_fmt_want(row)}")
        lines.append("")
    if not result["must_fail"] and not result["watch_fail"]:
        lines.append(ok_msg)
    return "\n".join(lines).strip()


def format_telegram_chunks(pack: str = "ambigu", limit: int = 3500) -> list[str]:
    text = format_report(pack=pack)
    if len(text) <= limit:
        return [text]
    chunks: list[str] = []
    buf: list[str] = []
    size = 0
    for line in text.split("\n"):
        add = len(line) + 1
        if buf and size + add > limit:
            chunks.append("\n".join(buf))
            buf = [line]
            size = add
        else:
            buf.append(line)
            size += add
    if buf:
        chunks.append("\n".join(buf))
    return chunks
