"""Generate fixtures/mega_transactions.json — 500 offline classification cases.

Usage:
  python build_mega_suite.py
"""

from __future__ import annotations

import json
from pathlib import Path

from offline_classify import classify_offline

OUT = Path(__file__).resolve().parent / "fixtures" / "mega_transactions.json"
EXPECT_KEYS = (
    "jenis",
    "kategori",
    "sifat",
    "bucket",
    "nominal",
    "needs_clarification",
    "impulsif",
    "taxonomy_flags",
)

NAMES = [
    "ayuti", "mama", "papa", "grace", "rina", "budi", "andi", "sari",
    "catherine", "tono", "dewi", "agus", "lina", "rudi", "maya",
]
AMOUNTS = [
    "10rb", "15rb", "25rb", "35rb", "45rb", "50rb", "75rb", "100rb",
    "150rb", "200rb", "250rb", "300rb", "500rb", "750rb", "1jt", "1.5jt",
    "2jt", "2.5jt", "3jt", "5jt", "7.5jt", "10jt", "15jt", "20jt",
]


def _case(cid: str, group: str, text: str, why: str = "", severity: str = "must") -> dict:
    actual = classify_offline(text)
    expected = {k: actual.get(k) for k in EXPECT_KEYS}
    row = {
        "id": cid,
        "group": group,
        "severity": severity,
        "text": text,
        "expected": expected,
    }
    if why:
        row["why"] = why
    return row


def build_texts() -> list[tuple[str, str, str]]:
    """Return list of (group, text, why)."""
    rows: list[tuple[str, str, str]] = []

    # --- Income / saving / tax ---
    income = [
        ("gajian masuk {a}", "gaji"),
        ("THR {a}", "bonus"),
        ("bonus kinerja {a}", "bonus"),
        ("honor freelance {a}", "freelance"),
        ("komisi affiliate {a}", "affiliate"),
        ("dividen saham {a}", "dividen"),
        ("bunga deposito {a}", "bunga"),
        ("cashback ewallet {a}", "cashback"),
        ("refund belanja {a}", "refund"),
        ("omzet toko {a}", "penjualan"),
        ("terima sewa kos {a}", "sewa"),
        ("transfer masuk dari papa {a}", "transfer"),
        ("terima hadiah uang {a}", "hadiah masuk"),
    ]
    for i, (tpl, why) in enumerate(income):
        for a in AMOUNTS[8:16]:
            rows.append(("income", tpl.format(a=a), why))

    saving = [
        "beli reksa dana {a}",
        "nabung emas {a}",
        "topup dana darurat {a}",
        "setor deposito {a}",
        "beli SBN {a}",
        "investasi saham {a}",
    ]
    for tpl in saving:
        for a in ("500rb", "1jt", "2jt", "5jt"):
            rows.append(("saving", tpl.format(a=a), "saving"))

    tax = [
        "bayar PPh 21 {a}",
        "bayar PPh 25 {a}",
        "setor pajak usaha {a}",
    ]
    for tpl in tax:
        for a in ("500rb", "1jt", "2jt"):
            rows.append(("tax", tpl.format(a=a), "pajak"))

    # --- Everyday expenses ---
    food = [
        "nasi padang {a}",
        "makan siang {a}",
        "makan malam {a}",
        "sarapan bubur {a}",
        "gofood ayam geprek {a}",
        "beli galon aqua {a}",
        "jajan es teh {a}",
        "kopi kenangan {a}",
        "starbucks meeting klien {a}",
        "kopi starbucks healing {a}",
        "beli snack indomaret {a}",
        "belanja sayur pasar {a}",
    ]
    for tpl in food:
        for a in ("15rb", "25rb", "35rb", "45rb", "65rb", "85rb"):
            rows.append(("food", tpl.format(a=a), "makanan"))

    home = [
        "bayar kos {a}",
        "token pln {a}",
        "bayar air pdam {a}",
        "beli deterjen {a}",
        "bayar gas elpiji {a}",
        "ganti rice cooker rusak {a}",
        "beli sapu {a}",
        "service AC {a}",
    ]
    for tpl in home:
        for a in ("25rb", "50rb", "150rb", "800rb", "1.8jt"):
            rows.append(("home", tpl.format(a=a), "rumah"))

    ride = [
        "gojek ke kantor {a}",
        "grab ke klinik {a}",
        "grab ke gym {a}",
        "maxim ke cafe {a}",
        "ojek ke rumah sakit {a}",
        "isi bensin {a}",
        "parkir mall {a}",
        "tol dalam kota {a}",
    ]
    for tpl in ride:
        for a in ("12rb", "18rb", "22rb", "35rb", "60rb"):
            rows.append(("ride", tpl.format(a=a), "transport"))

    health = [
        "beli sabun mandi {a}",
        "beli shampoo {a}",
        "beli odol {a}",
        "beli handbody {a}",
        "beli makeup {a}",
        "beli serum wajah {a}",
        "beli lipstik {a}",
        "obat demam {a}",
        "rawat gigi {a}",
        "konsultasi dokter {a}",
        "beli vitamin {a}",
        "laundry kiloan {a}",
    ]
    for tpl in health:
        for a in ("18rb", "35rb", "65rb", "120rb", "350rb"):
            rows.append(("health", tpl.format(a=a), "kesehatan"))

    life = [
        "langganan spotify {a}",
        "langganan netflix {a}",
        "nonton bioskop {a}",
        "membership gym {a}",
        "beli game steam {a}",
        "hobi fotografi {a}",
    ]
    for tpl in life:
        for a in ("55rb", "100rb", "150rb", "280rb"):
            rows.append(("life", tpl.format(a=a), "lifestyle"))

    edu = [
        "bayar UKT {a}",
        "les bahasa inggris {a}",
        "workshop excel {a}",
        "beli buku pelajaran {a}",
        "kursus online coding {a}",
    ]
    for tpl in edu:
        for a in ("150rb", "350rb", "450rb", "2jt"):
            rows.append(("edu", tpl.format(a=a), "pendidikan"))

    protect = [
        "bayar BPJS {a}",
        "premi asuransi kesehatan {a}",
        "asuransi jiwa {a}",
    ]
    for tpl in protect:
        for a in ("80rb", "150rb", "400rb"):
            rows.append(("protect", tpl.format(a=a), "proteksi"))

    social_gift = [
        "sedekah jumat {a}",
        "perpuluhan gereja {a}",
        "transfer ke mama {a}",
        "bantu adik {a}",
        "kasih ke papa {a}",
        "parcel lebaran {a}",
        "beli kado ulang tahun {a}",
        "tip ojol {a}",
        "hadiah beli iphone buat keluarga {a}",
    ]
    for tpl in social_gift:
        for a in ("20rb", "50rb", "200rb", "500rb", "1jt"):
            rows.append(("social_gift", tpl.format(a=a), "sosial/hadiah"))

    wear = [
        "beli baju kerja {a}",
        "beli sepatu kerja {a}",
        "beli kaos santai {a}",
        "beli tas {a}",
        "beli jam tangan {a}",
    ]
    for tpl in wear:
        for a in ("150rb", "250rb", "600rb", "1.2jt"):
            rows.append(("wear", tpl.format(a=a), "pakaian"))

    biz = [
        "iklan instagram bisnis {a}",
        "bayar domain website {a}",
        "alat kerja kantor {a}",
        "meeting klien restoran {a}",
    ]
    for tpl in biz:
        for a in ("100rb", "250rb", "500rb"):
            rows.append(("biz", tpl.format(a=a), "bisnis"))

    # --- Grey area ---
    grey = [
        ("beli tumbler {a}", "grey tumbler"),
        ("beli tumbler karena rusak {a}", "grey resolved need"),
        ("beli tumbler koleksi {a}", "grey resolved want"),
        ("beli iphone {a}", "grey hp"),
        ("ganti HP utama rusak {a}", "grey hp need"),
        ("beli iphone upgrade model terbaru {a}", "grey hp want"),
        ("beli laptop {a}", "grey laptop"),
        ("beli laptop kerja rusak {a}", "grey laptop need"),
        ("kopi starbucks {a}", "grey kopi"),
        ("gaji babysitter {a}", "grey art"),
        ("gaji ART bulanan {a}", "grey art"),
        ("gaji driver {a}", "grey art"),
        ("fisioterapi {a}", "grey fisik"),
        ("beli baju {a}", "grey baju"),
        ("tiket pesawat {a}", "grey travel"),
        ("dp mobil {a}", "grey dp"),
        ("dp motor {a}", "grey dp"),
        ("beli perabot rumah {a}", "grey perabot"),
    ]
    for tpl, why in grey:
        for a in ("150rb", "350rb", "2jt", "4jt", "12jt", "15jt", "20jt"):
            rows.append(("grey", tpl.format(a=a), why))

    # --- Perhutangan / likuiditas sosial ---
    for name in NAMES:
        for a in ("250k", "500k", "1jt", "2jt", "5jt"):
            rows.append(("utang_masuk", f"pinjam ke {name} {a}", "utang masuk"))
            rows.append(("utang_masuk", f"pinjam dari {name} {a}", "utang masuk"))
            rows.append(("utang_masuk", f"ngutang sama {name} {a}", "utang masuk"))
            rows.append(("piutang_keluar", f"pinjamin {name} {a}", "piutang keluar"))
            rows.append(("piutang_keluar", f"pinjamkan {name} {a}", "piutang keluar"))
            rows.append(("piutang_keluar", f"talangin {name} {a}", "piutang keluar"))
            rows.append(("piutang_masuk", f"dibalikin {name} {a}", "piutang masuk"))
            rows.append(("utang_keluar", f"bayar utang ke {name} {a}", "utang keluar"))
            rows.append(("utang_keluar", f"balikin ke {name} {a}", "utang keluar"))
            rows.append(("utang_ambigu", f"utang ke {name} {a}", "ambigu arah"))

    # Planned / impulsive social
    for name in NAMES[:8]:
        rows.append(
            (
                "social_impulse",
                f"pinjamin {name} 500k dadakan buat kerja, minggu depan",
                "dadakan",
            )
        )
        rows.append(
            (
                "social_impulse",
                f"pinjamkan {name} 500k terencana buat kerja, minggu depan",
                "terencana",
            )
        )
        rows.append(
            (
                "social_impulse",
                f"pinjam ke {name} 250k dadakan buat obat, minggu depan",
                "dadakan masuk",
            )
        )
        rows.append(
            (
                "social_impulse",
                f"pinjam ke {name} 250k terencana buat obat, minggu depan",
                "terencana masuk",
            )
        )
        rows.append(
            (
                "social_clarify",
                f"utang ke {name} 1jt\nKlarifikasi user: saya yang berhutang",
                "klarifikasi berhutang",
            )
        )
        rows.append(
            (
                "social_clarify",
                f"utang ke {name} 1jt\nKlarifikasi user: saya pinjamkan, nanti balik",
                "klarifikasi pinjamkan",
            )
        )

    # Funded expense (loan then spend) — still Pengeluaran when spending
    spend_from_loan = [
        "bayar RS {a}",
        "bayar kuliah {a}",
        "beli obat {a}",
        "bayar listrik {a}",
    ]
    for tpl in spend_from_loan:
        for a in ("500rb", "1jt", "2jt"):
            rows.append(("expense", tpl.format(a=a), "pengeluaran biasa"))

    # --- Flags & cicilan ---
    flags = [
        ("bayar cicilan pinjol {a}", "risk_alert"),
        ("bayar cicilan pinjol kebutuhan mendesak {a}", "risk_alert resolved"),
        ("bayar cicilan pinjol konsumsi {a}", "risk_alert konsumsi"),
        ("angsuran kpr {a}", "kpr"),
        ("bayar kartu kredit {a}", "kk"),
        ("cicilan paylater {a}", "paylater"),
        ("denda keterlambatan kartu kredit {a}", "late_pattern"),
        ("bayar denda telat pinjol {a}", "risk+late"),
        ("denda tilang {a}", "late/tilang"),
        ("dp rumah {a}", "life_event"),
        ("mahar pernikahan {a}", "life_event"),
        ("biaya pemakaman {a}", "life_event"),
        ("uang muka mobil {a}", "life_event"),
    ]
    for tpl, why in flags:
        for a in ("50rb", "75rb", "500rb", "2jt", "4jt", "10jt", "50jt"):
            rows.append(("flags", tpl.format(a=a), why))

    # Impulsive expense cues
    impulse = [
        "beli aksesoris lucu {a} spontan",
        "jajan karena capek {a}",
        "beli baju iseng {a}",
        "belanja fomo {a}",
        "makan malam karena stres {a}",
        "beli kopi specialty {a} karena capek",
        "beli kado {a} terencana",
        "langganan capcut untuk kerja karena akun rusak {a}",
    ]
    for tpl in impulse:
        for a in ("15rb", "45rb", "75rb", "95rb", "250rb"):
            rows.append(("impulse", tpl.format(a=a), "impulsif cue"))

    # Travel
    travel = [
        "booking hotel {a}",
        "tiket kereta liburan {a}",
        "sewa mobil wisata {a}",
        "beli oleh-oleh travel {a}",
    ]
    for tpl in travel:
        for a in ("150rb", "250rb", "900rb", "1.8jt"):
            rows.append(("travel", tpl.format(a=a), "traveling"))

    # Deduplicate while preserving order
    seen: set[str] = set()
    unique: list[tuple[str, str, str]] = []
    for group, text, why in rows:
        key = text.strip().lower()
        if key in seen:
            continue
        seen.add(key)
        unique.append((group, text, why))
    return unique


def main() -> None:
    texts = build_texts()
    if len(texts) < 500:
        raise SystemExit(f"Generator hanya menghasilkan {len(texts)} teks unik; butuh >= 500")

    # Round-robin antar group supaya perhutangan/grey/flags tidak terpotong oleh income/food.
    by_group: dict[str, list[tuple[str, str, str]]] = {}
    for group, text, why in texts:
        by_group.setdefault(group, []).append((group, text, why))

    selected: list[tuple[str, str, str]] = []
    pointers = {g: 0 for g in by_group}
    groups = list(by_group.keys())
    while len(selected) < 500:
        progressed = False
        for g in groups:
            idx = pointers[g]
            bucket = by_group[g]
            if idx >= len(bucket):
                continue
            selected.append(bucket[idx])
            pointers[g] = idx + 1
            progressed = True
            if len(selected) >= 500:
                break
        if not progressed:
            break

    if len(selected) < 500:
        raise SystemExit(f"Setelah round-robin hanya {len(selected)} kasus; butuh 500")

    cases = []
    for i, (group, text, why) in enumerate(selected, start=1):
        cases.append(_case(f"M{i:03d}", group, text, why=why))

    payload = {
        "version": 1,
        "how_to": "Telegram: /uji5    atau    python test_mega_transactions.py --report\nRegenerate: python build_mega_suite.py",
        "notes": "500 kasus snapshot dari classify_offline (grey area, perhutangan, flags, cakupan harian).",
        "cases": cases,
    }
    OUT.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    counts: dict[str, int] = {}
    for c in cases:
        counts[c["group"]] = counts.get(c["group"], 0) + 1
    print(f"Wrote {len(cases)} cases -> {OUT}")
    print("Groups:", dict(sorted(counts.items(), key=lambda x: (-x[1], x[0]))))


if __name__ == "__main__":
    main()
