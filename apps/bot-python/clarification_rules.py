"""Deteksi transaksi yang perlu ditanya balik sebelum diklasifikasikan.

Grey area §2.18 / §3.3 — AI WAJIB konfirmasi konteks sebelum bucket final.
"""

from __future__ import annotations

from typing import Any

from yfd_taxonomy import grey_area_question


def clarification_question(parsed: dict[str, Any], source_text: str) -> str | None:
    """Return a concise follow-up question when purpose changes the YFD bucket."""
    ai_question = str(parsed.get("clarification_question") or "").strip()
    if parsed.get("needs_clarification") is True and ai_question:
        return ai_question

    text = f"{source_text} {parsed.get('keterangan', '')}".lower()
    category = str(parsed.get("kategori") or "").strip().lower()

    checkers = (
        _is_ambiguous_utang_arah,
        _is_generic_book,
        _is_generic_instrument,
        _is_generic_coffee,
        _is_generic_transport,
        _is_generic_pinjol,
        _is_generic_dp_kendaraan,
        _is_generic_pajak_kendaraan,
        _is_generic_kpr_pbb,
        _is_generic_perabot,
        _is_generic_laptop,
        _is_generic_hp,
        _is_generic_subscription,
        _is_generic_coaching,
        _is_generic_art,
        _is_generic_notaris,
        _is_generic_fashion,
        _is_generic_fisioterapi,
        _is_generic_electronic,  # fallback laptop/HP via kategori elektronik lama
    )

    for checker in checkers:
        key = checker(text, category)
        if key:
            return grey_area_question(key)

    return None


def _has_purpose(text: str, markers: tuple[str, ...]) -> bool:
    return any(marker in text for marker in markers) or "klarifikasi user:" in text


def _is_ambiguous_utang_arah(text: str, category: str) -> str | None:
    """utang/pinjam ke [nama] tanpa sinyal jelas — tanya arah cashflow."""
    del category
    from context_rules import is_ambiguous_utang_ke_person

    return "utang_arah" if is_ambiguous_utang_ke_person(text) else None


def _is_generic_book(text: str, category: str) -> str | None:
    if category != "buku" and "beli buku" not in text:
        return None
    purpose = (
        "pengembangan diri",
        "self development",
        "sekolah",
        "kuliah",
        "buku wajib",
        "belajar",
        "bisnis",
        "keuangan",
        "psychology of money",
        "novel",
        "komik",
        "hiburan",
    )
    return None if _has_purpose(text, purpose) else "buku"


def _is_generic_instrument(text: str, category: str) -> str | None:
    del category
    instruments = (
        "piano",
        "gitar",
        "biola",
        "violin",
        "drum",
        "saxophone",
        "saksofon",
        "ukulele",
        "cello",
    )
    if not any(instrument in text for instrument in instruments):
        return None
    purpose = (
        "belajar",
        "pengembangan diri",
        "self development",
        "profesi",
        "kerja",
        "manggung",
        "studio",
        "sekolah",
        "kuliah",
        "wajib",
        "hobi",
        "hiburan",
        "koleksi",
    )
    return None if _has_purpose(text, purpose) else "alat_musik"


def _is_generic_coffee(text: str, category: str) -> str | None:
    coffee_cats = {"jajan", "makanan & minuman", "makanan dan minuman", "makan", "bisnis & karir"}
    if category not in coffee_cats and not any(
        w in text for w in ("kopi", "coffee", "ngopi", "starbucks")
    ):
        return None
    if not any(w in text for w in ("kopi", "coffee", "ngopi", "starbucks")):
        return None
    purpose = (
        "kerja",
        "meeting",
        "rapat",
        "klien",
        "client",
        "santai",
        "healing",
        "nongkrong",
        "hiburan",
        "produktif",
        "produktivitas",
    )
    return None if _has_purpose(text, purpose) else "kopi"


def _is_generic_transport(text: str, category: str) -> str | None:
    if category not in {"transport", "transportasi"}:
        return None
    if not any(word in text for word in ("grab", "gojek", "ojek", "maxim", "parkir", "bensin", "tiket")):
        return None
    purpose = (
        "kantor",
        "office",
        "klinik",
        "rumah sakit",
        "sekolah",
        "kampus",
        "apotek",
        "supermarket",
        "gym",
        "nongkrong",
        "healing",
        "cafe",
        "kafe",
        "mall",
        "liburan",
        "wisata",
        "bisnis",
        "usaha",
        "networking",
        "klien",
        "client",
        "meeting",
        "rapat",
        "kerja",
        "urusan bisnis",
        "keperluan bisnis",
        "dinas",
        "pitch",
        "investor",
    )
    return None if _has_purpose(text, purpose) else "transport"


def _is_generic_pinjol(text: str, category: str) -> str | None:
    if category not in {"cicilan & hutang", "cicilan"}:
        return None
    if not any(k in text for k in ("pinjol", "pinjaman online", "pinjaman tunai")):
        return None
    purpose = (
        "mendesak",
        "darurat",
        "kebutuhan",
        "konsumsi",
        "hiburan",
        "non esensial",
        "non-esensial",
    )
    return None if _has_purpose(text, purpose) else "pinjol"


def _is_generic_dp_kendaraan(text: str, category: str) -> str | None:
    del category
    if not any(
        k in text
        for k in ("dp mobil", "dp motor", "dp kendaraan", "uang muka mobil", "uang muka motor")
    ):
        return None
    purpose = (
        "kerja",
        "mobilitas",
        "kantor",
        "kendaraan kedua",
        "mobil kedua",
        "motor kedua",
        "upgrade",
        "lifestyle",
        "gaya hidup",
        "hobi",
    )
    return None if _has_purpose(text, purpose) else "dp_kendaraan"


def _is_generic_pajak_kendaraan(text: str, category: str) -> str | None:
    if category not in {"cicilan & hutang", "cicilan"}:
        return None
    if not any(k in text for k in ("stnk", "pkb", "samsat", "pajak kendaraan", "pajak mobil", "pajak motor")):
        return None
    purpose = (
        "kerja",
        "mobilitas",
        "utama",
        "kedua",
        "hobi",
        "lifestyle",
    )
    return None if _has_purpose(text, purpose) else "pajak_kendaraan"


def _is_generic_kpr_pbb(text: str, category: str) -> str | None:
    is_pbb = any(k in text for k in ("pbb", "pajak bumi", "pajak bangunan"))
    is_kpr = "kpr" in text
    if not is_pbb and not is_kpr:
        return None
    if category and category not in {
        "tempat tinggal",
        "cicilan & hutang",
        "cicilan",
        "investasi & tabungan",
        "",
    }:
        return None
    purpose = (
        "ditinggali",
        "tinggal sendiri",
        "rumah tinggal",
        "investasi",
        "disewakan",
        "dikoskan",
        "disewa",
    )
    return None if _has_purpose(text, purpose) else "kpr_pbb"


def _is_generic_perabot(text: str, category: str) -> str | None:
    perabot = (
        "perabot",
        "furniture",
        "furnitur",
        "kulkas",
        "mesin cuci",
        "ac ",
        " air conditioner",
        "rice cooker",
        "bedcover",
        "gorden",
        "panci",
        "piring",
        "sofa",
        "lemari",
        "kasur",
        "springbed",
    )
    if category not in {"tempat tinggal", ""} and not any(k in text for k in perabot):
        return None
    if not any(k in text for k in perabot):
        return None
    # Habis pakai murni bukan grey area
    if any(k in text for k in ("detergen", "sabun cuci", "tissue", "plastik sampah", "cairan pel")):
        return None
    purpose = (
        "rusak",
        "tidak layak",
        "belum memadai",
        "ganti",
        "koleksi",
        "tren",
        "upgrade",
        "tambah",
    )
    return None if _has_purpose(text, purpose) else "perabot"


def _is_generic_laptop(text: str, category: str) -> str | None:
    if not any(w in text for w in ("laptop", "komputer", "notebook", "macbook")):
        return None
    if category in {"makanan & minuman", "transportasi", "traveling"}:
        return None
    purpose = (
        "kerja",
        "kantor",
        "produktif",
        "rusak",
        "pecah",
        "mati",
        "fomo",
        "upgrade",
        "tren",
        "bisnis",
        "alat kerja",
    )
    return None if _has_purpose(text, purpose) else "laptop"


def _is_generic_hp(text: str, category: str) -> str | None:
    if not any(w in text for w in ("hp", "handphone", "smartphone", "iphone", "android")):
        return None
    # Hindari false positive di kata lain
    if "hp" in text and not any(
        w in text for w in ("hp ", " hp", "beli hp", "ganti hp", "handphone", "smartphone", "iphone")
    ):
        if "handphone" not in text and "smartphone" not in text and "iphone" not in text:
            # bare "hp" still ok if word boundary-ish
            if "hp" not in text.split() and not any(
                t.startswith("hp") for t in text.replace(",", " ").split()
            ):
                return None
    if category in {"makanan & minuman", "transportasi"}:
        return None
    purpose = (
        "kerja",
        "kantor",
        "rusak",
        "pecah",
        "mati",
        "fomo",
        "upgrade",
        "bisnis",
        "setara",
        "utama",
        "model terbaru",
    )
    return None if _has_purpose(text, purpose) else "hp"


def _is_generic_subscription(text: str, category: str) -> str | None:
    subs = (
        "chatgpt",
        "claude",
        "notion",
        "canva",
        "figma",
        "domain",
        "hosting",
        "subscription",
        "langganan",
    )
    lifestyle_clear = ("netflix", "spotify", "disney", "youtube premium", "gaming")
    if any(k in text for k in lifestyle_clear):
        return None
    if not any(k in text for k in subs):
        return None
    if category in {"lifestyle & hiburan"} and any(k in text for k in ("netflix", "spotify")):
        return None
    purpose = (
        "kerja",
        "bisnis",
        "usaha",
        "entertainment",
        "hiburan",
        "lifestyle",
        "hobi",
    )
    return None if _has_purpose(text, purpose) else "subscription"


def _is_generic_coaching(text: str, category: str) -> str | None:
    if not any(k in text for k in ("coaching", "les privat", "les ", "mentoring", "kelas ")):
        return None
    # Gym/olahraga berbayar bukan grey ke Essential — selalu Flexible; skip
    if any(k in text for k in ("gym", "pilates", "yoga", "tenis", "padel", "renang", "fitness")):
        return None
    if category in {"lifestyle & hiburan"}:
        return None
    purpose = (
        "kerja",
        "karir",
        "karier",
        "penghasilan",
        "skill",
        "bisnis",
        "hobi",
        "enjoyment",
        "hiburan",
        "pengembangan diri",
    )
    return None if _has_purpose(text, purpose) else "coaching"


def _is_generic_art(text: str, category: str) -> str | None:
    helpers = (
        "art ",
        " gaji art",
        "pembantu",
        "babysitter",
        "baby sitter",
        "pengasuh",
        "sopir pribadi",
        "driver pribadi",
        "gaji driver",
        "gaji pembantu",
        "gaji babysitter",
    )
    # "art" alone is noisy — require household-help context
    hit = any(k in text for k in helpers) or (
        "art" in text
        and any(k in text for k in ("gaji", "bayar", "pembantu", "rumah tangga"))
    )
    if not hit and category != "tempat tinggal":
        return None
    if not hit:
        return None
    purpose = (
        "kerja",
        "praktik",
        "bisa kerja",
        "menunjang",
        "kenyamanan",
        "nyaman",
        "lifestyle",
    )
    return None if _has_purpose(text, purpose) else "art"


def _is_generic_notaris(text: str, category: str) -> str | None:
    if not any(k in text for k in ("notaris", "balik nama", "ajb", "biaya legal")):
        return None
    if any(k in text for k in ("mahar", "pernikahan", "duka", "pemakaman")):
        return None
    purpose = (
        "rumah tinggal",
        "ditinggali",
        "investasi",
        "disewakan",
        "kendaraan",
        "aset",
        "properti",
        "ruko",
    )
    return None if _has_purpose(text, purpose) else "notaris"


def _is_generic_fashion(text: str, category: str) -> str | None:
    if category not in {"pakaian & aksesoris", "pakaian", "fashion"}:
        if not any(k in text for k in ("baju", "sepatu", "tas", "fashion", "seragam", "pakaian")):
            return None
    # Fashion lifestyle default is clear Wants — only ask when ambiguous work/school grey
    workish = any(
        k in text
        for k in (
            "seragam",
            "sepatu kerja",
            "tas kerja",
            "sepatu sekolah",
            "tas sekolah",
            "kerja",
            "sekolah",
            "wisuda",
            "jas sidang",
            "bridesmaid",
            "mukena",
            "sajadah",
            "olahraga",
        )
    )
    lifestyle_clear = any(k in text for k in ("fashion", "konser", "lifestyle", "tren", "brand"))
    if lifestyle_clear and not workish:
        return None
    if not workish and category in {"pakaian & aksesoris", "pakaian"}:
        # Generic baju without purpose — ask
        if any(k in text for k in ("baju", "sepatu", "tas", "pakaian", "kaos")):
            purpose = (
                "kerja",
                "sekolah",
                "wajib",
                "fashion",
                "lifestyle",
                "hobi",
                "olahraga",
                "ibadah",
                "wisuda",
                "sidang",
            )
            return None if _has_purpose(text, purpose) else "fashion"
        return None
    if workish:
        purpose = (
            "kerja",
            "sekolah",
            "wajib",
            "fashion",
            "lifestyle",
            "hobi",
            "olahraga",
            "ibadah",
            "wisuda",
            "sidang",
            "seragam",
        )
        # seragam kerja already clear enough
        if any(k in text for k in ("seragam", "jas sidang", "wisuda", "bridesmaid")):
            return None
        return None if _has_purpose(text, purpose) else "fashion"
    return None


def _is_generic_fisioterapi(text: str, category: str) -> str | None:
    if not any(k in text for k in ("fisioterapi", "fisio", "rehab", "rehabilitasi", "hydrotherapy")):
        return None
    if category in {"lifestyle & hiburan"} and "gym" in text:
        return None
    purpose = (
        "resep dokter",
        "diresepkan",
        "dokter",
        "pasca operasi",
        "pilihan sendiri",
        "tanpa resep",
        "sendiri",
    )
    return None if _has_purpose(text, purpose) else "fisioterapi"


def _is_generic_electronic(text: str, category: str) -> str | None:
    """Legacy kategori 'Elektronik' — map ke laptop/HP grey questions."""
    if category != "elektronik":
        return None
    if any(w in text for w in ("laptop", "komputer", "macbook")):
        return _is_generic_laptop(text, category)
    if any(w in text for w in ("hp", "handphone", "iphone", "smartphone")):
        return _is_generic_hp(text, category)
    return None
