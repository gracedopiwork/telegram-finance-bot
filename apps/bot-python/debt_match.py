"""Cocokkan nama transaksi cicilan ke satu baris Debt Register.

Murni fungsi — tidak menyentuh jaringan, daftar utang selalu diberikan pemanggil.
Itu yang membuat modul ini bisa diuji tanpa kredensial apa pun dan dipakai di
jalur offline (kuota AI habis) persis sama seperti di jalur AI.

Kontrak (Baseline Data IT Guidance v1.0 Section 4):
- cocok   -> transaksi disimpan dengan debt_id terisi
- gagal   -> debt_id None, transaksi TETAP tersimpan normal
- ragu    -> diperlakukan sebagai gagal; salah label lebih buruk daripada tanpa label
"""

from __future__ import annotations

import difflib
import re

# Kata kerja pembayaran & pengisi kalimat: tidak membedakan satu utang dari yang lain,
# jadi dibuang sebelum pencocokan. "byr kk mandiri" -> "kk mandiri".
_NOISE = {
    "byr", "bayar", "bayarin", "membayar", "dibayar", "pembayaran",
    "cicilan", "cicil", "nyicil", "angsuran", "ansuran",
    "setor", "setoran", "pelunasan", "lunasi", "lunas", "tagihan", "tagih",
    "bulan", "bulanan", "ini", "untuk", "buat", "ke", "utk", "utang", "hutang",
}

# Singkatan yang lazim dipakai user Indonesia. Peta ini yang melakukan sebagian
# besar kerja pencocokan — difflib hanya menutup salah ketik kecil.
_ALIASES = {
    "kk": "kartu kredit",
    "cc": "kartu kredit",
    "creditcard": "kartu kredit",
    "kartukredit": "kartu kredit",
    "mtr": "motor",
    "mbl": "mobil",
    "kndrn": "kendaraan",
    "pinjol": "pinjaman online",
}

# Kata yang mengisyaratkan jenis utang, dipakai sebagai penguat kecil saat
# nama utang tidak disebut lengkap (mis. "cicilan kpr").
_TYPE_HINTS = {
    "kpr": "kpr",
    "rumah": "kpr",
    "motor": "kredit_kendaraan",
    "mobil": "kredit_kendaraan",
    "kendaraan": "kredit_kendaraan",
    "kartu": "kartu_kredit",
    "kredit": "kartu_kredit",
    "paylater": "paylater",
    "pinjaman": "pinjol_pinjaman_tunai",
    "pinjol": "pinjol_pinjaman_tunai",
}

_MIN_SCORE = 0.6
# Selisih minimum ke kandidat kedua. Menempelkan pembayaran ke utang yang keliru
# menghasilkan narasi konsistensi yang salah, jadi ragu dibiarkan tanpa label.
_MIN_MARGIN = 0.15
_FUZZY_TOKEN_RATIO = 0.85


def _tokens(text: str) -> list[str]:
    lowered = (text or "").lower()
    lowered = re.sub(r"[^a-z0-9\s]+", " ", lowered)

    out: list[str] = []
    for raw in lowered.split():
        # buang nominal: 1500000, 1.5jt, 280rb, 5k
        if re.fullmatch(r"\d+(jt|rb|k|juta|ribu)?", raw):
            continue
        word = _ALIASES.get(raw, raw)
        for part in word.split():
            if part and part not in _NOISE:
                out.append(part)
    return out


def _token_matches(needle: str, haystack: list[str]) -> bool:
    if needle in haystack:
        return True
    # toleransi salah ketik ringan ("mandiiri" -> "mandiri")
    return any(
        difflib.SequenceMatcher(None, needle, token).ratio() >= _FUZZY_TOKEN_RATIO
        for token in haystack
    )


def score_debt(text_tokens: list[str], debt: dict) -> float:
    """Kemiripan nama saja, 0.0-1.0. Jenis utang ditangani terpisah di match_debt."""
    name_tokens = _tokens(str(debt.get("debt_name") or ""))
    if not text_tokens or not name_tokens:
        return 0.0

    query_hits = sum(1 for token in text_tokens if _token_matches(token, name_tokens))
    if query_hits == 0:
        return 0.0

    # Dua sudut pandang, dirata-rata:
    # - name_cov: berapa banyak nama utang yang disebut user. Tinggi kalau
    #   user menyebut nama lengkap.
    # - query_cov: berapa banyak kata user yang mengenai utang ini. Tinggi
    #   kalau user cuma menyebut satu kata khas ("motor" untuk Kredit Motor Honda).
    # Memakai name_cov saja menghukum nama panjang; query_cov saja terlalu
    # mudah tertarik satu kata umum. Rata-ratanya menahan kedua sisi.
    name_cov = sum(1 for token in name_tokens if _token_matches(token, text_tokens)) / len(name_tokens)
    query_cov = query_hits / len(text_tokens)
    score = (name_cov + query_cov) / 2

    return min(score, 1.0)


def _hinted_types(text_tokens: list[str]) -> set[str]:
    return {_TYPE_HINTS[t] for t in text_tokens if t in _TYPE_HINTS}


def match_debt(text: str, debts: list[dict] | None) -> str | None:
    """debt_id dari satu-satunya kandidat terbaik, atau None kalau ragu."""
    if not debts:
        return None

    text_tokens = _tokens(text)
    if not text_tokens:
        return None

    scored = [(score_debt(text_tokens, d), d) for d in debts]
    scored = [(s, d) for s, d in scored if s >= _MIN_SCORE]
    if not scored:
        return None

    scored.sort(key=lambda pair: pair[0], reverse=True)

    best = scored[0]
    if len(scored) > 1 and (scored[0][0] - scored[1][0]) < _MIN_MARGIN:
        # Kemiripan nama tidak memisahkan kandidat. Jenis utang boleh menengahi,
        # tapi hanya kalau benar-benar membedakan — kalau dua kartu kredit
        # sama-sama cocok, petunjuk "kartu" tidak memilih siapa pun.
        hinted = _hinted_types(text_tokens)
        leaders = [d for s_, d in scored if (scored[0][0] - s_) < _MIN_MARGIN]
        candidates = [d for d in leaders if str(d.get("debt_type") or "") in hinted]
        if len(candidates) != 1:
            return None
        best = (scored[0][0], candidates[0])

    debt_id = str(best[1].get("debt_id") or "")
    return debt_id or None
