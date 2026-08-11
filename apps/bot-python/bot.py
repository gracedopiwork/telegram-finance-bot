import json
import logging
import os
import re
import tempfile
from datetime import datetime
from typing import Any, Dict

import mysql.connector
from dotenv import load_dotenv
from telegram import InlineKeyboardButton, InlineKeyboardMarkup, Update
from telegram.ext import (
    ApplicationBuilder,
    CallbackQueryHandler,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)

from ai_health import log_ai_health_config, report_ai_event, report_ai_failure
from claude_ai import analyze_with_claude as claude_parse_json
from claude_ai import extract_transaction_text_from_image as claude_extract_image_text
from laravel_api import log_laravel_api_config
from license_activate import activate_license_via_api
from nominal_parser import parse_nominal_from_text, reconcile_nominal, nominal_sanity_warning
from ai_quota import (
    format_quota_exhausted_notice,
    format_quota_status,
    format_vision_quota_blocked,
    has_ai_quota,
    mark_quota_exhausted_notified,
    record_ai_usage,
    should_notify_quota_exhausted,
)
from portal_link import fetch_portal_login_url
from category_rules_cache import get_rules, refresh as refresh_category_rules
from clarification_rules import clarification_question
from social_meta import enrich_social_liquidity_fields, social_missing_details_question
from social_liquidity_api import fetch_social_liquidity, format_social_list
from transaction_store import (
    format_prescription_bucket,
    resolve_transaction_bucket,
    save_transaction_to_api,
)
from transaction_categories import (
    apply_context_rules,
    build_system_prompt_rules,
    classify_from_text,
    normalize_category_fields,
    normalize_saving_fields,
)
from date_parser import apply_transaction_date, format_recorded_at_label
from impulsive_rules import (
    PAYDAY_SPLURGE_KEYWORDS,
    REWARD_SPENDING_KEYWORDS,
    resolve_impulsif,
    stamp_planned_cue,
)
from nature_rules import refine_sifat_from_context
from yfd_taxonomy import attach_taxonomy_flags

load_dotenv()

logging.basicConfig(
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)


def get_system_prompt() -> str:
    return build_system_prompt_rules()

HELP_TEXT = (
    "Input belum terbaca.\n"
    "Contoh format yang benar:\n"
    "- makan malam 50rb\n"
    "- beli kopi 18000 karena ngantuk\n"
    "- nabung 200000\n"
    "- tgl 2/7 beli makan 50k  (catat ke tanggal 2 Juli)"
)
CATAT_HELP_TEXT = (
    "Gunakan `/catat <catatan>` atau kirim teks biasa.\n"
    "Contoh: `/catat makan malam 50rb`\n"
    "Backdate: `/catat tgl 2/7 beli makan 50k`"
)
ACTIVATE_HELP_TEXT = (
    "Lisensi belum aktif.\n"
    "Gunakan kode yang sama dengan di halaman setelah pembayaran (atau email).\n"
    "Aktifkan dengan:\n"
    "`/activate KODE-LISENSI-ANDA`"
)

VALID_JENIS = {"Pemasukan", "Pengeluaran", "Saving/Investment", "Kewajiban Pajak", "Piutang Keluar", "Piutang Masuk", "Utang Masuk", "Utang Keluar"}
VALID_SIFAT = {"Need", "Wants"}
VALID_MOOD = {"Happy", "Neutral", "Sad", "Stressed", "Angry", "Tired"}
VALID_IMPULSIF = {"Yes", "No"}
PENDING_NAME_USERS: set[int] = set()
PENDING_MOOD_WAIT: Dict[int, Dict[str, Any]] = {}
PENDING_CONFIRMATIONS: Dict[int, Dict[str, Any]] = {}
PENDING_CLARIFICATIONS: Dict[int, Dict[str, Any]] = {}

MOOD_PROMPT_TEXT = (
    "Pilih mood kamu saat transaksi ini:\n"
    "😊 Happy — senang, bersyukur\n"
    "😐 Neutral — biasa saja\n"
    "😢 Sad — sedih, kecewa\n"
    "😨 Stressed — cemas, overwhelmed\n"
    "😡 Angry — marah, frustrasi\n"
    "😴 Tired — lelah, burnout"
)

LEGACY_MOOD_MAP = {
    "Sedih": "Sad",
    "Senang": "Happy",
    "Biasa Saja": "Neutral",
    "Sangat Senang": "Happy",
}

MOOD_ALIASES = {
    "happy": "Happy",
    "senang": "Happy",
    "bahagia": "Happy",
    "excited": "Happy",
    "bersyukur": "Happy",
    "neutral": "Neutral",
    "biasa saja": "Neutral",
    "biasa": "Neutral",
    "sad": "Sad",
    "sedih": "Sad",
    "kecewa": "Sad",
    "kesepian": "Sad",
    "stressed": "Stressed",
    "cemas": "Stressed",
    "overthinking": "Stressed",
    "overwhelmed": "Stressed",
    "angry": "Angry",
    "marah": "Angry",
    "frustrasi": "Angry",
    "frustrated": "Angry",
    "tired": "Tired",
    "ngantuk": "Tired",
    "lelah": "Tired",
    "burnout": "Tired",
    "capek": "Tired",
    "cape": "Tired",
    "capekk": "Tired",
    "kecapean": "Tired",
    "tured": "Tired",
}

MOOD_KEYWORDS: Dict[str, tuple[str, ...]] = {
    "Happy": (
        "sangat senang",
        "bahagia",
        "excited",
        "bersyukur",
        "senang banget",
        "happy",
        "habis gajian",
        "abis gajian",
        "baru gajian",
        "gajianan",
    ),
    "Neutral": ("biasa saja", "biasa aja", "lumayan", "neutral"),
    "Sad": ("sedih banget", "kecewa", "kesepian", "sedih", "sad"),
    "Stressed": ("overwhelmed", "overthinking", "cemas", "stress", "stressed", "panik"),
    "Angry": ("frustrasi", "marah", "kesal", "angry", "jengkel"),
    "Tired": ("burnout", "ngantuk", "lelah", "kecapean", "capek", "cape", "tired"),
}


def normalize_mood(text: str) -> str | None:
    if not text:
        return None
    cleaned = text.strip()
    if cleaned in VALID_MOOD:
        return cleaned
    if cleaned in LEGACY_MOOD_MAP:
        return LEGACY_MOOD_MAP[cleaned]
    return MOOD_ALIASES.get(cleaned.lower())


def extract_forced_mood(text: str) -> str | None:
    mood_match = re.search(r"\bmood\s*:\s*(.+)$", text, flags=re.IGNORECASE | re.MULTILINE)
    if not mood_match:
        return None
    return normalize_mood(mood_match.group(1).strip())


def detect_mood_in_text(text: str) -> str | None:
    base = re.sub(r"\bmood\s*:\s*.+$", "", text, flags=re.IGNORECASE | re.MULTILINE).strip().lower()
    if not base:
        return None
    for mood, keywords in MOOD_KEYWORDS.items():
        for keyword in keywords:
            # Kata pendek (mis. cape, sad) pakai word-boundary agar tidak false-positive.
            if len(keyword) <= 4:
                if re.search(rf"(?<!\w){re.escape(keyword)}(?!\w)", base):
                    return mood
            elif keyword in base:
                return mood
    return None


def normalize_taxonomy(parsed: Dict[str, Any]) -> Dict[str, Any]:
    """Selaraskan jenis & sifat dengan taxonomy YFD (Need/Wants + jenis v1.3)."""
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
        if jenis_lower in {"saving/investement", "saving/investment", "saving", "investasi", "investment", "nabung"}:
            parsed["jenis"] = "Saving/Investment"
        elif jenis_lower in {"kewajiban pajak", "pajak", "tax", "pph", "pph 25", "pph 29", "pph 28a"}:
            parsed["jenis"] = "Kewajiban Pajak"
        elif jenis_lower in {"piutang keluar", "piutang out", "receivable out", "pinjaman keluar"}:
            parsed["jenis"] = "Piutang Keluar"
        elif jenis_lower in {"piutang masuk", "piutang in", "receivable in", "pelunasan piutang"}:
            parsed["jenis"] = "Piutang Masuk"
        elif jenis_lower in {"utang masuk", "hutang masuk", "payable in", "pinjaman masuk", "terima pinjaman"}:
            parsed["jenis"] = "Utang Masuk"
        elif jenis_lower in {"utang keluar", "hutang keluar", "payable out", "bayar utang sosial", "bayar hutang sosial"}:
            parsed["jenis"] = "Utang Keluar"
        else:
            parsed["jenis"] = "Pengeluaran"

    if parsed["sifat"] not in VALID_SIFAT:
        parsed["sifat"] = "Need"

    return parsed


def finalize_parsed_transaction(
    parsed: Dict[str, Any],
    source_text: str = "",
    *,
    trust_ai_impulsif: bool = False,
) -> Dict[str, Any]:
    if source_text.strip():
        try:
            current = int(parsed.get("nominal", 0) or 0)
            parsed["nominal"] = reconcile_nominal(current, source_text.strip())
        except (TypeError, ValueError):
            pass

    normalize_taxonomy(parsed)
    apply_context_rules(parsed, source_text)
    normalize_category_fields(parsed, source_text)
    normalize_saving_fields(parsed, source_text)
    refine_sifat_from_context(parsed, source_text)
    attach_taxonomy_flags(parsed, source_text)
    stamp_planned_cue(parsed, source_text)
    ai_val = str(parsed.get("impulsif", "")).strip() if trust_ai_impulsif else None
    parsed["impulsif"] = resolve_impulsif(
        parsed,
        source_text,
        ai_suggested=ai_val,
        trust_ai=trust_ai_impulsif,
    )
    apply_transaction_date(parsed, source_text)
    enrich_social_liquidity_fields(parsed, source_text)
    return parsed


def build_mood_keyboard() -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton("😊 Happy", callback_data="mood:Happy"),
            InlineKeyboardButton("😐 Neutral", callback_data="mood:Neutral"),
        ],
        [
            InlineKeyboardButton("😢 Sad", callback_data="mood:Sad"),
            InlineKeyboardButton("😨 Stressed", callback_data="mood:Stressed"),
        ],
        [
            InlineKeyboardButton("😡 Angry", callback_data="mood:Angry"),
            InlineKeyboardButton("😴 Tired", callback_data="mood:Tired"),
        ],
    ]
    return InlineKeyboardMarkup(buttons)


async def prompt_mood_selection(message, user_id: int, *, intro: str) -> None:
    await message.reply_text(intro + "\n\n" + MOOD_PROMPT_TEXT, reply_markup=build_mood_keyboard())


async def queue_mood_from_source_text(
    message,
    user_id: int,
    source_text: str,
    *,
    intro: str,
    basic_mode: bool = False,
) -> None:
    PENDING_MOOD_WAIT[user_id] = {
        "mode": "source_text",
        "source_text": source_text.strip(),
        "basic_mode": basic_mode,
    }
    await prompt_mood_selection(message, user_id, intro=intro)


async def queue_mood_from_parsed(
    message,
    user_id: int,
    parsed: Dict[str, Any],
    greeting_name: str,
    *,
    intro: str,
    source_text: str = "",
    basic_mode: bool = False,
) -> None:
    PENDING_MOOD_WAIT[user_id] = {
        "mode": "parsed",
        "parsed": parsed,
        "greeting_name": greeting_name,
        "source_text": source_text.strip(),
        "basic_mode": basic_mode,
    }
    await prompt_mood_selection(message, user_id, intro=intro)


def build_confirmation_keyboard() -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton("✅ Benar, simpan", callback_data="confirm:yes"),
            InlineKeyboardButton("✏️ Salah, ulangi", callback_data="confirm:no"),
        ]
    ]
    return InlineKeyboardMarkup(buttons)


def get_env(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise RuntimeError(f"Environment variable '{name}' belum diisi.")
    return value

def get_db_connection():
    return mysql.connector.connect(
        host=get_env("MYSQL_HOST"),
        port=int(get_env("MYSQL_PORT", required=False) or "3306"),
        user=get_env("MYSQL_USER"),
        password=os.getenv("MYSQL_PASSWORD", ""),
        database=get_env("MYSQL_DATABASE"),
    )


def is_license_required() -> bool:
    return get_env("LICENSE_REQUIRED", required=False).lower() in {"1", "true", "yes"}


def get_user_license(user_id: int) -> Dict[str, Any] | None:
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT id, license_key, status, expires_at, assigned_user_id
        FROM licenses
        WHERE assigned_user_id = %s
        ORDER BY id DESC
        LIMIT 1
        """,
        (user_id,),
    )
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def is_license_active_for_user(user_id: int) -> bool:
    if not is_license_required():
        return True

    try:
        row = get_user_license(user_id)
    except Exception as exc:  # pragma: no cover - external db guard
        logger.warning("Gagal cek lisensi user %s: %s", user_id, exc)
        return False

    if not row:
        return False
    if row["status"] != "active":
        return False
    expires_at = row["expires_at"]
    if expires_at and datetime.utcnow() > expires_at:
        return False
    return True


def activate_license_for_user(license_key: str, user_id: int, username: str | None) -> tuple[str, int]:
    key = license_key.strip().upper()
    if not key:
        raise ValueError("empty_key")

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT id, status, expires_at, assigned_user_id
        FROM licenses
        WHERE license_key = %s
        LIMIT 1
        """,
        (key,),
    )
    row = cursor.fetchone()
    if not row:
        cursor.close()
        conn.close()
        raise ValueError("license_not_found")

    if row["status"] != "active":
        cursor.close()
        conn.close()
        raise ValueError("license_not_active")

    expires_at = row["expires_at"]
    if expires_at and datetime.utcnow() > expires_at:
        cursor.close()
        conn.close()
        raise ValueError("license_expired")

    assigned_user_id = row["assigned_user_id"]
    if assigned_user_id and assigned_user_id != user_id:
        cursor.close()
        conn.close()
        raise ValueError("license_used_by_other_user")

    if not assigned_user_id:
        cursor.execute(
            """
            UPDATE licenses
            SET assigned_user_id = %s, assigned_username = %s, activated_at = NOW()
            WHERE id = %s
            """,
            (user_id, username, row["id"]),
        )

    cursor.execute(
        """
        INSERT INTO license_activations (license_id, telegram_user_id, telegram_username)
        VALUES (%s, %s, %s)
        """,
        (row["id"], user_id, username),
    )
    conn.commit()
    license_id = int(row["id"])
    cursor.close()
    conn.close()
    return key, license_id


def set_user_display_name(user_id: int, display_name: str) -> None:
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        """
        UPDATE licenses
        SET assigned_username = %s
        WHERE assigned_user_id = %s
        """,
        (display_name, user_id),
    )
    conn.commit()
    cursor.close()
    conn.close()


def get_user_display_name(user_id: int) -> str | None:
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT assigned_username
        FROM licenses
        WHERE assigned_user_id = %s
        ORDER BY id DESC
        LIMIT 1
        """,
        (user_id,),
    )
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    if not row:
        return None
    value = (row.get("assigned_username") or "").strip()
    return value or None


def extract_json(raw_text: str) -> Dict[str, Any]:
    cleaned = raw_text.strip()
    cleaned = re.sub(r"^```json\s*|\s*```$", "", cleaned, flags=re.IGNORECASE | re.DOTALL).strip()
    return json.loads(cleaned)


def normalize_ai_result(data: Dict[str, Any], source_text: str = "") -> Dict[str, Any]:
    if data.get("error") == "invalid_input":
        raise ValueError("invalid_input")

    required_keys = ["keterangan", "nominal", "jenis", "kategori", "sifat", "mood", "impulsif"]
    if any(key not in data for key in required_keys):
        raise ValueError("missing_keys")

    ai_nominal = int(data["nominal"])
    if ai_nominal <= 0:
        raise ValueError("invalid_nominal")

    combined = f"{source_text} {data.get('keterangan', '')}".strip()
    data["nominal"] = reconcile_nominal(ai_nominal, combined)
    if data["nominal"] <= 0:
        raise ValueError("invalid_nominal")

    data["keterangan"] = str(data["keterangan"]).strip()
    normalize_taxonomy(data)
    normalize_category_fields(data)
    normalize_saving_fields(data, data["keterangan"])

    if data["jenis"] not in VALID_JENIS:
        raise ValueError("invalid_jenis")
    if not str(data.get("kategori", "")).strip():
        raise ValueError("invalid_kategori")
    if data["sifat"] not in VALID_SIFAT:
        raise ValueError("invalid_sifat")
    mood = normalize_mood(str(data["mood"]))
    if not mood:
        raise ValueError("invalid_mood")
    data["mood"] = mood
    if data["impulsif"] not in VALID_IMPULSIF:
        raise ValueError("invalid_impulsif")

    raw_clarification = data.get("needs_clarification", False)
    data["needs_clarification"] = (
        raw_clarification is True
        or str(raw_clarification).strip().lower() == "true"
    )
    question = str(data.get("clarification_question") or "").strip()
    data["clarification_question"] = (
        question if data["needs_clarification"] and question else None
    )

    apply_transaction_date(data, source_text)
    return data


def analyze_with_claude(user_text: str) -> Dict[str, Any]:
    candidate_models = os.getenv("CLAUDE_MODELS", "claude-haiku-4-5,claude-sonnet-4-6")
    last_error: Exception | None = None

    try:
        parsed = claude_parse_json(f"Input user: {user_text}", get_system_prompt())
        result = normalize_ai_result(parsed, user_text)
        report_ai_event("success")
        return result
    except Exception as exc:  # pragma: no cover - external provider fallback
        last_error = exc
        report_ai_failure(exc, candidate_models)
        logger.warning("Claude gagal dipakai (%s).", type(exc).__name__)

    report_ai_event("error", str(last_error)[:500] if last_error else "all_models_failed")
    raise RuntimeError(f"Semua model Claude gagal dipakai: {last_error}")


def extract_transaction_text_from_image(image_path: str, mime_type: str = "image/jpeg") -> str:
    prompt = (
        "Ekstrak isi transaksi dari gambar struk/nota/foto belanja jadi satu kalimat singkat bahasa Indonesia "
        "yang berisi keterangan dan nominal (contoh: 'Makan siang 45000'). "
        "Fokus pada total bayar/total harga jika ada. Jangan isi mood. "
        "Hanya balas INVALID_IMAGE jika benar-benar tidak ada angka/teks transaksi yang terbaca."
    )
    with open(image_path, "rb") as image_file:
        image_bytes = image_file.read()

    media = mime_type if mime_type.startswith("image/") else "image/jpeg"
    if media in {"image/heic", "image/heif"}:
        raise RuntimeError("FORMAT_HEIC")

    text = claude_extract_image_text(image_bytes, media, prompt).strip()
    if text and text.upper() != "INVALID_IMAGE":
        return text

    raise RuntimeError("INVALID_IMAGE")


def parse_nominal_fallback(text: str) -> int:
    return parse_nominal_from_text(text)


def analyze_without_gemini(user_text: str) -> Dict[str, Any]:
    text = user_text.strip()
    nominal = parse_nominal_fallback(text)

    lower_text = text.lower()
    jenis = "Pengeluaran"
    kategori = "Lain-lain"
    sifat = "Wants"
    mood = "Neutral"

    context_hit = classify_from_text(lower_text)
    if context_hit is not None:
        jenis = context_hit["jenis"]
        kategori = context_hit["kategori"]
        sifat = context_hit["sifat"]
    elif any(keyword in lower_text for keyword in ["kopi", "coffee", "starbucks", "espresso", "americano", "kafein"]):
        kategori = "Makanan & Minuman"
        sifat = (
            "Need"
            if any(keyword in lower_text for keyword in [
                "produktif", "kerja", "kantor", "fokus", "konsentrasi", "butuh supaya", "biar bisa",
                "ngantuk kerja", "melek", "meeting", "rapat",
            ])
            else "Wants"
        )
    elif any(keyword in lower_text for keyword in PAYDAY_SPLURGE_KEYWORDS + REWARD_SPENDING_KEYWORDS) and any(
        keyword in lower_text for keyword in ["makan", "nasi", "sarapan", "lunch", "dinner", "restaurant", "restoran"]
    ):
        kategori = "Makanan & Minuman"
        sifat = "Wants"

    detected_mood = detect_mood_in_text(text)
    if detected_mood:
        mood = detected_mood
    elif any(keyword in lower_text for keyword in ["sangat senang", "bahagia", "excited", "bersyukur"]):
        mood = "Happy"
    elif any(keyword in lower_text for keyword in ["senang", "happy"]):
        mood = "Happy"
    elif any(keyword in lower_text for keyword in ["sedih", "kecewa", "kesepian"]):
        mood = "Sad"
    elif any(keyword in lower_text for keyword in ["cemas", "overthinking", "overwhelmed", "stressed", "panik"]):
        mood = "Stressed"
    elif any(keyword in lower_text for keyword in ["marah", "frustrasi", "kesal", "angry"]):
        mood = "Angry"
    elif any(keyword in lower_text for keyword in ["ngantuk", "lelah", "burnout", "kecapean", "capek", "cape", "tired"]):
        mood = "Tired"

    impulsif = resolve_impulsif(
        {
            "keterangan": text,
            "nominal": nominal,
            "jenis": jenis,
            "kategori": kategori,
            "sifat": sifat,
            "mood": mood,
        },
        text,
    )

    result = {
        "keterangan": text,
        "nominal": nominal,
        "jenis": jenis,
        "kategori": kategori,
        "sifat": sifat,
        "mood": mood,
        "impulsif": impulsif,
    }
    normalize_category_fields(result, text)
    normalize_taxonomy(result)
    normalize_saving_fields(result, text)
    result["impulsif"] = resolve_impulsif(result, text)
    apply_transaction_date(result, text)
    return result


def format_transaction_preview(parsed: Dict[str, Any], greeting_name: str) -> str:
    date_line = ""
    label = format_recorded_at_label(parsed.get("recorded_at"))
    if label:
        date_line = f"Tanggal: {label}\n"

    warning = nominal_sanity_warning(
        int(parsed.get("nominal") or 0),
        source_text=str(parsed.get("keterangan") or ""),
        kategori=str(parsed.get("kategori") or ""),
    )
    warn_block = f"\n{warning}\n" if warning else ""
    flags = parsed.get("taxonomy_flags") or []
    flag_labels = {
        "risk_alert": "Risk Alert (Pinjol)",
        "late_pattern": "Pola Keterlambatan",
        "life_event": "Peristiwa Besar",
    }
    flag_line = ""
    if isinstance(flags, list) and flags:
        pretty = ", ".join(flag_labels.get(str(f), str(f)) for f in flags)
        flag_line = f"Flag taxonomy: {pretty}\n"
    return (
        f"Aku baca transaksi untuk {greeting_name} seperti ini:\n"
        f"{date_line}"
        f"Keterangan: {parsed['keterangan']}\n"
        f"Nominal: Rp{parsed['nominal']:,}\n"
        f"Jenis: {parsed['jenis']}\n"
        f"Kategori: {parsed['kategori']}\n"
        f"Prescription Bucket: {format_prescription_bucket(parsed)}\n"
        f"Sifat: {parsed['sifat']}\n"
        f"Mood: {parsed['mood']}\n"
        f"Impulsif: {parsed['impulsif']}\n"
        f"{flag_line}"
        f"{warn_block}\n"
        "Sudah benar?"
    )


def attach_prescription_bucket(parsed: Dict[str, Any]) -> bool:
    """Attach canonical Laravel category/bucket before Telegram confirmation."""
    ok, result, error = resolve_transaction_bucket(parsed)
    if not ok:
        logger.warning("Bucket preview belum tersedia: %s", error)
        parsed["bucket"] = None if parsed.get("jenis") in {
            "Pemasukan",
            "Kewajiban Pajak",
            "Piutang Keluar",
            "Piutang Masuk",
            "Utang Masuk",
            "Utang Keluar",
        } else "Belum dapat dicek"
        return False

    parsed["kategori"] = result.get("category") or parsed["kategori"]
    parsed["bucket"] = result.get("bucket")
    return True


def format_preview_with_mode(
    parsed: Dict[str, Any],
    greeting_name: str,
    *,
    basic_mode: bool = False,
) -> str:
    preview = format_transaction_preview(parsed, greeting_name)
    if basic_mode:
        preview += "\n\n_(mode biasa — kuota AI habis)_"
    return preview


async def notify_quota_exhausted_if_needed(message, user_id: int) -> None:
    if not user_id or not should_notify_quota_exhausted(user_id):
        return
    await message.reply_text(format_quota_exhausted_notice(), parse_mode="Markdown")
    mark_quota_exhausted_notified(user_id)


def parse_user_transaction(text: str, user_id: int) -> tuple[Dict[str, Any], bool]:
    if user_id and not has_ai_quota(user_id, "text"):
        parsed = analyze_without_gemini(text)
        report_ai_event("fallback", "quota_exhausted")
        return parsed, True

    try:
        parsed = analyze_with_claude(text)
        if user_id:
            record_ai_usage(user_id, "text")
        return parsed, False
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.warning("Gagal analisis input AI, fallback parser dipakai: %s", exc)
        report_ai_failure(exc, "analyze_with_claude")
        parsed = analyze_without_gemini(text)
        report_ai_event("fallback", str(exc)[:500])
        return parsed, False


async def save_transaction(
    message,
    parsed: Dict[str, Any],
    greeting_name: str,
    *,
    telegram_user_id: int | None = None,
    source: str = "manual",
) -> None:
    uid = telegram_user_id
    if uid is None and message.from_user:
        uid = message.from_user.id

    saved_db = False
    if uid:
        recorded_at = parsed.get("recorded_at")
        if recorded_at is None and message is not None:
            recorded_at = getattr(message, "date", None)
        ok, err, canonical = save_transaction_to_api(
            uid,
            parsed,
            source=source,
            recorded_at=recorded_at,
        )
        saved_db = ok
        if not ok:
            hint = (
                "Perbaiki di server — edit apps/bot-python/.env:\n"
                "• BOT_INTERNAL_API_TOKEN (sama dengan Laravel)\n"
                "• LARAVEL_APP_URL=https://domain-anda.com\n"
                "Lalu: sudo systemctl restart yfd-bot"
            )
            if "belum lengkap" in err.lower() or "kosong" in err.lower():
                body = f"Gagal simpan ke dashboard web.\n\n{err}\n\n{hint}"
            else:
                body = (
                    f"Gagal simpan ke dashboard web.\n\n{err}\n\n"
                    "Cek token sama di Laravel + bot, migrate, lalu restart bot."
                )
            await message.reply_text(body)
            return
        parsed["kategori"] = canonical.get("category") or parsed["kategori"]
        parsed["bucket"] = canonical.get("bucket")

    if not saved_db:
        await message.reply_text("Gagal menyimpan transaksi. Hubungi admin YFD.")
        return

    portal_hint = ""
    if saved_db and uid:
        ok, link = fetch_portal_login_url(uid)
        if ok:
            portal_hint = f"\n\nBuka dashboard (login otomatis, 30 menit):\n{link}"
        else:
            portal_base = (os.getenv("LARAVEL_APP_URL") or os.getenv("APP_URL") or "").strip().rstrip("/")
            if portal_base:
                portal_hint = f"\n\nKetik /web untuk link dashboard."

    await message.reply_text(
        f"Tercatat untuk {greeting_name}:\n"
        + (f"Tanggal: {format_recorded_at_label(parsed.get('recorded_at'))}\n" if parsed.get("recorded_at") else "")
        + f"Keterangan: {parsed['keterangan']}\n"
        f"Nominal: Rp{parsed['nominal']:,}\n"
        f"Jenis: {parsed['jenis']}\n"
        f"Kategori: {parsed['kategori']}\n"
        f"Prescription Bucket: {format_prescription_bucket(parsed)}\n"
        f"Sifat: {parsed['sifat']}\n"
        f"Mood: {parsed['mood']}\n"
        f"Impulsif: {parsed['impulsif']}"
        f"{portal_hint}"
    )


def is_authorized(update: Update) -> bool:
    if not update.effective_user:
        return False
    authorized_user_id = int(get_env("USER_ID"))
    return update.effective_user.id == authorized_user_id


async def process_note_input(
    message,
    text: str,
    user_id: int | None = None,
    *,
    mood_resolved: bool = False,
    clarification_resolved: bool = False,
) -> None:
    text = text.strip()
    if user_id is None:
        user = getattr(message, "from_user", None)
        user_id = user.id if user else 0

    if user_id and not is_license_active_for_user(user_id):
        await message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    if not text or not re.search(r"\d", text):
        await message.reply_text(HELP_TEXT)
        return

    # Prioritas mood:
    # 1) mood eksplisit dari user (`mood: ...`)
    # 2) deteksi keyword mood pada teks
    # 3) jika tetap tidak ketemu, baru tanya lewat tombol
    forced_mood = extract_forced_mood(text)
    detected_mood = detect_mood_in_text(text) if not forced_mood else None
    resolved_mood = forced_mood or detected_mood

    try:
        parsed, basic_mode = parse_user_transaction(text, user_id or 0)
    except Exception as fallback_exc:
        logger.warning("Parser gagal: %s", fallback_exc)
        await message.reply_text(HELP_TEXT)
        return

    if basic_mode and user_id:
        await notify_quota_exhausted_if_needed(message, user_id)

    if user_id:
        question = clarification_question(parsed, text)
        social_q = social_missing_details_question(parsed, text)
        should_ask = question and (
            not clarification_resolved
            or parsed.get("needs_clarification") is True
            or (social_q is not None and question == social_q)
        )
        if should_ask:
            PENDING_CONFIRMATIONS.pop(user_id, None)
            PENDING_MOOD_WAIT.pop(user_id, None)
            # Simpan input terbaru (termasuk klarifikasi sebelumnya) agar jawaban menumpuk.
            PENDING_CLARIFICATIONS[user_id] = {
                "source_text": text,
            }
            social_jenis = str(parsed.get("jenis") or "").strip() in {
                "Piutang Keluar",
                "Piutang Masuk",
                "Utang Masuk",
                "Utang Keluar",
            }
            header = (
                "Aku perlu memastikan data Likuiditas Sosial dulu:"
                if social_jenis and social_q and question == social_q
                else "Aku perlu memastikan dulu:"
            )
            await message.reply_text(
                f"{header}\n\n{question}\n\n"
                "Balas dengan keterangannya, atau ketik batal."
            )
            return

    if resolved_mood:
        parsed["mood"] = resolved_mood
    elif not mood_resolved and user_id:
        preferred_name = get_user_display_name(user_id) or "Kamu"
        PENDING_CONFIRMATIONS.pop(user_id, None)
        await queue_mood_from_parsed(
            message,
            user_id,
            parsed,
            preferred_name,
            intro="Transaksi sudah kebaca. Mood belum terdeteksi dari struk/catatan ini.",
            source_text=text,
            basic_mode=basic_mode,
        )
        return

    finalize_parsed_transaction(parsed, text, trust_ai_impulsif=not basic_mode)
    attach_prescription_bucket(parsed)

    if user_id:
        PENDING_CONFIRMATIONS.pop(user_id, None)

    preferred_name = get_user_display_name(user_id) if user_id else None
    greeting_name = preferred_name or "Kamu"

    if not user_id:
        await save_transaction(message, parsed, greeting_name)
        return

    PENDING_CONFIRMATIONS[user_id] = {
        "parsed": parsed,
        "greeting_name": greeting_name,
        "basic_mode": basic_mode,
    }
    await message.reply_text(
        format_preview_with_mode(parsed, greeting_name, basic_mode=basic_mode),
        reply_markup=build_confirmation_keyboard(),
    )


async def start_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        return
    if not is_license_active_for_user(user_id):
        await update.message.reply_text(
            "Selamat datang di *YFD First Aid*.\n"
            "Sebelum pakai bot, masukkan kode lisensi Anda (sama persis dengan di halaman pembayaran lunas atau email).\n"
            "Format: `/activate KODE-LISENSI-ANDA`",
            parse_mode="Markdown",
        )
        return

    preferred_name = get_user_display_name(user_id)
    if not preferred_name:
        PENDING_NAME_USERS.add(user_id)
        await update.message.reply_text("Lisensi aktif. Kamu mau dipanggil siapa?")
        return

    await update.message.reply_text(
        f"Halo {preferred_name}, perintah yang tersedia:\n"
        "/catat - catat transaksi baru\n"
        "/activate - aktivasi lisensi\n"
        "/hapuskilat - hapus data terakhir\n"
        "/hariini - rangkuman pengeluaran hari ini\n"
        "/piutang - daftar piutang aktif (tracker)\n"
        "/utang - daftar utang aktif (tracker)\n"
        "/kuota - sisa kuota AI parsing bulan ini\n"
        "/web - link masuk dashboard (otomatis)\n\n"
        "Login dashboard:\n"
        "• **/web** di bot → klik link (tanpa isi form)\n"
        "• Atau buka halaman portal + email & kode lisensi\n\n"
        "Bisa juga kirim **teks biasa** atau **foto struk**.\n"
        "Contoh:\n"
        "`/catat mkn malm 50rb karena lagi sedih banget jadi iseng beli`",
        parse_mode="Markdown",
    )


async def catat_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    text = " ".join(context.args).strip() if context.args else ""
    if not text:
        await update.message.reply_text(CATAT_HELP_TEXT, parse_mode="Markdown")
        return
    await process_note_input(update.message, text)


async def activate_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return

    if not is_license_required():
        await update.message.reply_text("Mode lisensi tidak aktif. Set `LICENSE_REQUIRED=true` jika ingin dipakai.")
        return

    key = " ".join(context.args).strip() if context.args else ""
    if not key:
        await update.message.reply_text("Gunakan format: `/activate KODE-LISENSI-ANDA`", parse_mode="Markdown")
        return

    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        await update.message.reply_text("Aktivasi gagal: akun Telegram tidak dikenali.")
        return

    ok, payload = activate_license_via_api(
        key, user_id, user.username if user else None
    )
    if not ok:
        mapping = {
            "bot_not_purchased": "Lisensi ini belum termasuk paket YFD First Aid. Beli YFD First Aid di Telegram dulu untuk aktivasi.",
            "license_not_found": "Kode lisensi tidak ditemukan.",
            "license_not_active": "Lisensi tidak aktif (mungkin suspended).",
            "license_expired": "Lisensi sudah expired.",
            "license_used_by_other_user": "Lisensi sudah terpakai oleh akun Telegram lain.",
            "config_missing": payload.get("message", "Server belum dikonfigurasi."),
            "network_error": "Aktivasi gagal (koneksi server). Coba lagi atau hubungi admin YFD.",
        }
        code = str(payload.get("error", ""))
        await update.message.reply_text(mapping.get(code, payload.get("message", "Aktivasi gagal.")))
        return

    activated_key = str(payload.get("license_key", key)).strip().upper()
    if user:
        PENDING_NAME_USERS.add(user.id)
    migrated = bool(payload.get("migrated_from_synthetic"))
    extra = (
        "\n\nData FTSA & diagnostik dari portal sudah dipindahkan ke akun Telegram Anda."
        if migrated
        else ""
    )
    await update.message.reply_text(
        f"Lisensi aktif. Kode: `{activated_key}`\nSekarang kamu mau dipanggil siapa?\n\n"
        "Setelah ini:\n"
        "1) Ketik `/web` untuk masuk dashboard\n"
        "2) **Wajib** isi *Baseline Data (Diagnostik)* di menu portal\n"
        "3) Baru `/catat` transaksi harian"
        f"{extra}",
        parse_mode="Markdown",
    )


async def hapuskilat_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return
    await update.message.reply_text(
        "Perintah `/hapuskilat` dimatikan karena fitur hapus cepat sudah tidak dipakai.\n"
        "Jika perlu koreksi data, hapus dari portal web.",
        parse_mode="Markdown",
    )


async def hariini_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT
                COUNT(*) AS total_tx,
                COALESCE(SUM(CASE WHEN type = 'Pemasukan' THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN type = 'Pengeluaran' THEN amount ELSE 0 END), 0) AS total_expense
            FROM bot_transactions
            WHERE telegram_user_id = %s
              AND DATE(recorded_at) = CURDATE()
            """,
            (user_id,),
        )
        row = cursor.fetchone() or {}
        cursor.close()
        conn.close()
    except Exception as exc:  # pragma: no cover - external db guard
        logger.exception("Gagal ambil rangkuman /hariini dari DB: %s", exc)
        await update.message.reply_text(
            "Gagal mengambil rangkuman hari ini dari dashboard web. Coba lagi sebentar."
        )
        return

    total_tx = int(row.get("total_tx") or 0)
    total_income = int(row.get("total_income") or 0)
    total_expense = int(row.get("total_expense") or 0)
    cashflow = total_income - total_expense

    if total_tx == 0:
        await update.message.reply_text(
            "Belum ada transaksi tercatat hari ini di dashboard web."
        )
        return

    await update.message.reply_text(
        "Rangkuman hari ini (dashboard web):\n"
        f"Jumlah transaksi: {total_tx}\n"
        f"Total pemasukan: Rp{total_income:,}\n"
        f"Total pengeluaran: Rp{total_expense:,}\n"
        f"Cashflow: Rp{cashflow:,}"
    )


async def _social_list_handler(update: Update, kind: str) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    ok, payload, err = fetch_social_liquidity(user_id, kind=kind)
    if not ok:
        await update.message.reply_text(
            f"Gagal ambil daftar {kind} dari dashboard.\n{err}"
        )
        return

    await update.message.reply_text(format_social_list(kind, payload))


async def piutang_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    await _social_list_handler(update, "piutang")


async def utang_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    await _social_list_handler(update, "utang")


async def message_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return

    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        return

    text = (update.message.text or "").strip()

    if user_id in PENDING_NAME_USERS:
        if len(text) < 2:
            await update.message.reply_text("Nama panggilan minimal 2 karakter. Coba lagi ya.")
            return
        set_user_display_name(user_id, text[:80])
        PENDING_NAME_USERS.discard(user_id)
        await update.message.reply_text(
            f"Siap, aku panggil kamu {text[:80]}.\n"
            "Sekarang kamu bisa kirim teks atau foto struk untuk catat transaksi."
        )
        return

    if not is_license_active_for_user(user_id):
        if text:
            await update.message.reply_text(
                "Lisensi kamu belum aktif.\n"
                "Masukkan kode yang sama dengan di halaman pembayaran lunas (copy-paste disarankan):\n"
                "`/activate KODE-LISENSI-ANDA`",
                parse_mode="Markdown",
            )
        return

    if user_id in PENDING_CLARIFICATIONS:
        pending = PENDING_CLARIFICATIONS.pop(user_id)
        if text.lower() in {"batal", "cancel", "batalkan"}:
            await update.message.reply_text("Klarifikasi dibatalkan. Transaksi tidak disimpan.")
            return
        combined_input = (
            f"{pending['source_text']}\n"
            f"Klarifikasi user: {text}"
        )
        await process_note_input(
            update.message,
            combined_input,
            user_id=user_id,
            clarification_resolved=True,
        )
        return

    if user_id in PENDING_MOOD_WAIT:
        mood_text = normalize_mood(text)
        if not mood_text:
            await prompt_mood_selection(
                update.message,
                user_id,
                intro="Pilih mood dengan menekan salah satu tombol di bawah ini:",
            )
            return

        pending = PENDING_MOOD_WAIT.pop(user_id)
        if pending["mode"] == "source_text":
            combined_input = f"{pending['source_text']}\nmood: {mood_text}"
            await process_note_input(update.message, combined_input, mood_resolved=True)
            return

        parsed = pending["parsed"]
        parsed["mood"] = mood_text
        finalize_parsed_transaction(
            parsed,
            pending.get("source_text", ""),
            trust_ai_impulsif=not pending.get("basic_mode", True),
        )
        attach_prescription_bucket(parsed)
        greeting_name = pending["greeting_name"]
        basic_mode = pending.get("basic_mode", False)
        PENDING_CONFIRMATIONS[user_id] = {
            "parsed": parsed,
            "greeting_name": greeting_name,
            "basic_mode": basic_mode,
        }
        await update.message.reply_text(
            format_preview_with_mode(parsed, greeting_name, basic_mode=basic_mode),
            reply_markup=build_confirmation_keyboard(),
        )
        return

    await process_note_input(update.message, text)


async def mood_callback_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    query = update.callback_query
    if not query or not query.data:
        return
    await query.answer()

    user = query.from_user
    user_id = user.id if user else 0
    if not user_id:
        return

    if not query.data.startswith("mood:"):
        return
    mood_value = query.data.split(":", 1)[1]
    mood_value = normalize_mood(mood_value)
    if not mood_value:
        return

    if user_id not in PENDING_MOOD_WAIT:
        try:
            await query.edit_message_text("Sudah tidak ada transaksi yang menunggu mood.")
        except Exception:
            pass
        return

    pending = PENDING_MOOD_WAIT.pop(user_id)
    try:
        await query.edit_message_text(f"Mood dipilih: {mood_value}. Memproses transaksi...")
    except Exception:
        pass

    if pending["mode"] == "source_text":
        combined_input = f"{pending['source_text']}\nmood: {mood_value}"
        await process_note_input(query.message, combined_input, user_id=user_id, mood_resolved=True)
        return

    parsed = pending["parsed"]
    parsed["mood"] = mood_value
    finalize_parsed_transaction(
        parsed,
        pending.get("source_text", ""),
        trust_ai_impulsif=not pending.get("basic_mode", True),
    )
    attach_prescription_bucket(parsed)
    greeting_name = pending["greeting_name"]
    basic_mode = pending.get("basic_mode", False)
    PENDING_CONFIRMATIONS[user_id] = {
        "parsed": parsed,
        "greeting_name": greeting_name,
        "basic_mode": basic_mode,
    }
    await query.message.reply_text(
        format_preview_with_mode(parsed, greeting_name, basic_mode=basic_mode),
        reply_markup=build_confirmation_keyboard(),
    )


async def confirm_callback_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    query = update.callback_query
    if not query or not query.data:
        return
    await query.answer()

    user = query.from_user
    user_id = user.id if user else 0
    if not user_id:
        return

    if not query.data.startswith("confirm:"):
        return

    action = query.data.split(":", 1)[1]
    pending = PENDING_CONFIRMATIONS.get(user_id)
    if not pending:
        try:
            await query.edit_message_text("Tidak ada transaksi yang menunggu konfirmasi.")
        except Exception:
            pass
        return

    if action == "yes":
        parsed = pending["parsed"]
        greeting_name = pending["greeting_name"]
        PENDING_CONFIRMATIONS.pop(user_id, None)
        try:
            await query.edit_message_text("Transaksi dikonfirmasi. Menyimpan...")
        except Exception:
            pass
        await save_transaction(
            query.message, parsed, greeting_name, telegram_user_id=user_id
        )
        return

    if action == "no":
        PENDING_CONFIRMATIONS.pop(user_id, None)
        try:
            await query.edit_message_text(
                "Oke, transaksi dibatalkan.\n"
                "Kirim ulang catatan dengan detail yang lebih jelas."
            )
        except Exception:
            await query.message.reply_text(
                "Oke, transaksi dibatalkan.\n"
                "Kirim ulang catatan dengan detail yang lebih jelas."
            )
        return


async def web_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    ok, link_or_err = fetch_portal_login_url(user_id)
    if not ok:
        await update.message.reply_text(
            f"Tidak bisa buat link dashboard.\n\n`{link_or_err}`\n\n"
            "Pastikan sudah /activate dan server Laravel + bot sudah dikonfigurasi.",
            parse_mode="Markdown",
        )
        return

    await update.message.reply_text(
        "Klik link ini untuk langsung masuk dashboard (tanpa ketik email/lisensi):\n"
        f"{link_or_err}\n\n"
        "**Langkah wajib setelah masuk:** menu *BASELINE DATA (WAJIB DI ISI)* → isi diagnostik keuangan.\n\n"
        "_Link berlaku 30 menit. Buka di browser yang sama perangkat Anda._",
        parse_mode="Markdown",
        disable_web_page_preview=False,
    )


async def kuota_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    await update.message.reply_text(
        format_quota_status(user_id),
        parse_mode="Markdown",
    )


async def process_struk_image_message(
    message,
    context: ContextTypes.DEFAULT_TYPE,
    file_id: str,
    *,
    intro: str,
    mime_type: str = "image/jpeg",
) -> None:
    user = message.from_user
    user_id = user.id if user else 0
    if not user_id:
        return

    if not has_ai_quota(user_id, "vision"):
        await notify_quota_exhausted_if_needed(message, user_id)
        await message.reply_text(format_vision_quota_blocked(), parse_mode="Markdown")
        return

    temp_path = ""
    try:
        telegram_file = await context.bot.get_file(file_id)
        with tempfile.NamedTemporaryFile(suffix=".jpg", delete=False) as tmp:
            temp_path = tmp.name
        await telegram_file.download_to_drive(temp_path)
        extracted_text = extract_transaction_text_from_image(temp_path, mime_type=mime_type)
        record_ai_usage(user_id, "vision")
    except Exception as exc:  # pragma: no cover - external provider guard
        logger.exception("Gagal proses gambar transaksi: %s", exc)
        err = str(exc)
        if "FORMAT_HEIC" in err:
            reply = "Format HEIC belum didukung. Kirim ulang sebagai JPG/PNG, atau kirim dalam bentuk teks."
        elif "INVALID_IMAGE" in err:
            reply = (
                "Maaf, teks nominal di foto belum terbaca. "
                "Coba foto lebih dekat/terang (hindari pantulan), atau ketik manual mis. `makan 45000`."
            )
        elif any(token in err.lower() for token in ("api key", "authentication", "401", "unauthorized")):
            reply = "Layanan baca gambar sedang bermasalah (konfigurasi AI). Coba lagi nanti atau kirim dalam bentuk teks."
        else:
            reply = "Maaf, gambar belum bisa dibaca. Coba foto lebih jelas atau kirim dalam bentuk teks."
        await message.reply_text(reply)
        return
    finally:
        try:
            if temp_path and os.path.exists(temp_path):
                os.remove(temp_path)
        except OSError:
            logger.warning("Gagal hapus file sementara gambar.")

    await queue_mood_from_source_text(message, user_id, extracted_text, intro=intro)


async def photo_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not update.message.photo:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        return
    if not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    largest_photo = update.message.photo[-1]
    await process_struk_image_message(
        update.message,
        context,
        largest_photo.file_id,
        intro="Struk sudah kebaca.",
    )


async def document_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not update.message.document:
        return

    document = update.message.document
    mime_type = (document.mime_type or "").lower()
    file_name = (document.file_name or "").lower()
    is_image = mime_type.startswith("image/") or file_name.endswith((".jpg", ".jpeg", ".png", ".webp", ".heic"))
    if not is_image:
        return

    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        return
    if not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    await process_struk_image_message(
        update.message,
        context,
        document.file_id,
        intro="File struk sudah kebaca.",
        mime_type=mime_type or "image/jpeg",
    )


async def voice_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id:
        return
    if not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    await update.message.reply_text(
        "Voice note belum didukung.\n"
        "Kirim teks biasa (contoh: `makan malam 50rb`) atau foto struk ya.",
        parse_mode="Markdown",
    )


def main() -> None:
    token = get_env("TELEGRAM_BOT_TOKEN")
    app = ApplicationBuilder().token(token).build()

    app.add_handler(CommandHandler("start", start_handler))
    app.add_handler(CommandHandler("catat", catat_handler))
    app.add_handler(CommandHandler("activate", activate_handler))
    app.add_handler(CommandHandler("hapuskilat", hapuskilat_handler))
    app.add_handler(CommandHandler("hariini", hariini_handler))
    app.add_handler(CommandHandler("piutang", piutang_handler))
    app.add_handler(CommandHandler("utang", utang_handler))
    app.add_handler(CommandHandler("kuota", kuota_handler))
    app.add_handler(CommandHandler("web", web_handler))
    app.add_handler(CallbackQueryHandler(mood_callback_handler, pattern=r"^mood:"))
    app.add_handler(CallbackQueryHandler(confirm_callback_handler, pattern=r"^confirm:"))
    app.add_handler(MessageHandler(filters.PHOTO, photo_handler))
    app.add_handler(MessageHandler(filters.Document.ALL, document_handler))
    app.add_handler(MessageHandler(filters.VOICE | filters.AUDIO, voice_handler))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, message_handler))

    logger.info("Bot berjalan...")
    log_laravel_api_config()
    log_ai_health_config()
    refresh_category_rules(force=True)
    rules_meta = get_rules()
    logger.info(
        "category_rules: siap — %s kategori (source=%s, v=%s)",
        len(rules_meta.get("categories", [])),
        rules_meta.get("source"),
        rules_meta.get("version"),
    )
    app.run_polling()


if __name__ == "__main__":
    main()
