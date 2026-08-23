"""Penjelasan bucket untuk tombol 'Kenapa masuk bucket ini?' (tanpa dependensi Telegram)."""

from __future__ import annotations

from typing import Any


def format_prescription_bucket(parsed: dict) -> str:
    bucket = parsed.get("bucket")
    jenis = str(parsed.get("jenis") or "").strip()
    kategori = str(parsed.get("kategori") or "").strip()
    if jenis in {
        "Piutang Keluar",
        "Piutang Masuk",
        "Utang Masuk",
        "Utang Keluar",
    } or kategori in {
        "Piutang Keluar",
        "Piutang Masuk",
        "Utang Masuk",
        "Utang Keluar",
    }:
        label = jenis if jenis in {
            "Piutang Keluar",
            "Piutang Masuk",
            "Utang Masuk",
            "Utang Keluar",
        } else kategori
        return f"Likuiditas sosial ({label}) — tidak masuk prescription"
    if jenis == "Pemasukan" and bucket is None:
        return "Tidak masuk prescription (Pemasukan)"
    return str(bucket or "Belum dapat dicek")


def explain_bucket_choice(parsed: dict[str, Any]) -> str:
    """Penjelasan singkat untuk tombol 'Kenapa masuk bucket ini?'."""
    bucket = format_prescription_bucket(parsed)
    jenis = str(parsed.get("jenis") or "").strip()
    kategori = str(parsed.get("kategori") or "").strip()
    sifat = str(parsed.get("sifat") or "").strip()
    notes = str(parsed.get("keterangan") or "").strip()

    if "Likuiditas sosial" in bucket or jenis in {
        "Piutang Keluar",
        "Piutang Masuk",
        "Utang Masuk",
        "Utang Keluar",
    }:
        return (
            f"Ini dicatat sebagai *{jenis or kategori}* (likuiditas sosial).\n"
            "Bukan bucket prescription biasa — tracker utang/piutang antar orang, "
            "supaya sisa kewajiban/tagihan tetap akurat."
        )

    if jenis == "Pemasukan" and ("Tidak masuk" in bucket or not parsed.get("bucket")):
        return (
            "Ini *Pemasukan*. Pemasukan tidak masuk proporsi 4 bucket pengeluaran "
            "di Budget Prescription — bucket dipakai untuk membaca alokasi uang yang keluar."
        )

    lines = [
        f"*Kenapa masuk: {bucket}?*",
        f"Kategori: {kategori or '—'} · Sifat: {sifat or '—'}",
        "",
        "Di YFD, bucket ditentukan dari *fungsi & tujuan* uang — bukan hanya nama barang.",
    ]

    b = str(parsed.get("bucket") or bucket).lower()
    if "essential" in b:
        lines.append(
            "Essential Living = mempertahankan hidup & kemampuan kerja hari ini "
            "(makan, tinggal, transport wajib, kesehatan dasar, dll)."
        )
    elif "future" in b:
        lines.append(
            "Future Building = membangun aset atau kapasitas menghasilkan uang "
            "(investasi, bisnis, pengembangan diri, gym sebagai alat kerja PT/atlet)."
        )
    elif "protection" in b:
        lines.append(
            "Protection = mengurangi risiko finansial besar (asuransi, BPJS, dana darurat)."
        )
    elif "flexible" in b:
        lines.append(
            "Flexible + Social = kualitas hidup & hubungan sosial — bukan berarti buruk, "
            "tapi proporsinya perlu dijaga. Gym pribadi biasanya masuk sini."
        )

    if notes:
        lines.append(f"\nKonteks yang kamu catat: _{notes[:180]}_")
    lines.append("\nDetail lengkap: ketik /panduan atau buka Panduan di web.")
    return "\n".join(lines)
