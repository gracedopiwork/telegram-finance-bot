import json
import logging
import os
import re
import tempfile
from datetime import datetime
from typing import Any, Dict, List

import gspread
import google.generativeai as genai
import mysql.connector
from dotenv import load_dotenv
from oauth2client.service_account import ServiceAccountCredentials
from telegram import InlineKeyboardButton, InlineKeyboardMarkup, Update
from telegram.ext import (
    ApplicationBuilder,
    CallbackQueryHandler,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)

load_dotenv()

logging.basicConfig(
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)

SYSTEM_PROMPT = """
Anda adalah parser keuangan pribadi.
Ubah input user menjadi JSON VALID dengan schema berikut:
{
  "keterangan": string,
  "nominal": integer,
  "jenis": "Pemasukan" | "Pengeluaran",
  "kategori": "Makan" | "Transport" | "Listrik" | "Air" | "Jajan" | "Social" | "Gaji",
  "sub_kategori": "Pajak Kendaraan" | "Tabungan Rutin" | "TV Kabel / Streaming" | "Reksadana" | "Pembayaran SPP" | "Belanja Bulanan" | "Dana Darurat" | "Listrik" | "Pakaian" | "Servis Kendaraan" | "Nonton Konser" | "Pengeluaran lain-lain" | "Hadiah / Amplop sosial" | "Popok" | "Jajan / Makan diluar" | "Angkutan Umum" | "Skincare" | "Mainan Anak" | "Ulang Tahun keluarga" | "Vitamin" | "Alat Kesehatan",
  "sifat": "Need" | "Wants" | "Saving/Investement" | "Donation",
  "mood": "Sedih" | "Senang" | "Biasa Saja" | "Sangat Senang",
  "impulsif": "Yes" | "No"
}

Aturan:
1) keterangan: rapikan typo/singkatan agar mudah dibaca, gunakan kapitalisasi wajar.
2) nominal: ekstrak angka jadi integer bersih (contoh: 50rb => 50000, 1,2jt => 1200000).
3) jenis: pilih hanya Pemasukan atau Pengeluaran.
4) kategori, sub_kategori, sifat, mood: WAJIB pilih dari enum yang tersedia.
5) impulsif: "Yes" jika ada indikasi pembelian spontan seperti iseng, kepengen, diskon, tiba-tiba.
   Jika terlihat kebutuhan rutin/terencana, isi "No".
6) Balas HANYA JSON murni, tanpa markdown dan tanpa teks tambahan.
7) Jika input tidak mengandung nominal valid atau tidak bisa dipahami, balas:
   {"error":"invalid_input"}
"""

HELP_TEXT = (
    "Input belum terbaca.\n"
    "Contoh format yang benar:\n"
    "- makan malam 50rb\n"
    "- beli kopi 18000 karena ngantuk\n"
    "- nabung 200000"
)
CATAT_HELP_TEXT = (
    "Gunakan `/catat <catatan>` atau kirim teks biasa.\n"
    "Contoh: `/catat makan malam 50rb`"
)
ACTIVATE_HELP_TEXT = (
    "Lisensi belum aktif.\n"
    "Gunakan kode yang sama dengan di halaman setelah pembayaran (atau email).\n"
    "Aktifkan dengan:\n"
    "`/activate KODE-LISENSI-ANDA`"
)

VALID_JENIS = {"Pemasukan", "Pengeluaran"}
VALID_KATEGORI = {"Makan", "Transport", "Listrik", "Air", "Jajan", "Social", "Gaji"}
VALID_SUB_KATEGORI = {
    "Pajak Kendaraan",
    "Tabungan Rutin",
    "TV Kabel / Streaming",
    "Reksadana",
    "Pembayaran SPP",
    "Belanja Bulanan",
    "Dana Darurat",
    "Listrik",
    "Pakaian",
    "Servis Kendaraan",
    "Nonton Konser",
    "Pengeluaran lain-lain",
    "Hadiah / Amplop sosial",
    "Popok",
    "Jajan / Makan diluar",
    "Angkutan Umum",
    "Skincare",
    "Mainan Anak",
    "Ulang Tahun keluarga",
    "Vitamin",
    "Alat Kesehatan",
}
VALID_SIFAT = {"Need", "Wants", "Saving/Investement", "Donation"}
VALID_MOOD = {"Sedih", "Senang", "Biasa Saja", "Sangat Senang"}
VALID_IMPULSIF = {"Yes", "No"}
PENDING_NAME_USERS: set[int] = set()
PENDING_IMAGE_TRANSACTIONS: Dict[int, str] = {}
PENDING_CONFIRMATIONS: Dict[int, Dict[str, Any]] = {}

MOOD_LOOKUP = {m.lower(): m for m in VALID_MOOD}


def normalize_mood(text: str) -> str | None:
    if not text:
        return None
    return MOOD_LOOKUP.get(text.strip().lower())


def build_mood_keyboard() -> InlineKeyboardMarkup:
    buttons = [
        [
            InlineKeyboardButton("😢 Sedih", callback_data="mood:Sedih"),
            InlineKeyboardButton("🙂 Biasa Saja", callback_data="mood:Biasa Saja"),
        ],
        [
            InlineKeyboardButton("😊 Senang", callback_data="mood:Senang"),
            InlineKeyboardButton("🤩 Sangat Senang", callback_data="mood:Sangat Senang"),
        ],
    ]
    return InlineKeyboardMarkup(buttons)


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


def transaction_sheet_title() -> str:
    return get_env("GOOGLE_SHEET_TRANSACTION_TITLE", required=False) or "Transaksi"


def open_transaction_worksheet(spreadsheet: gspread.Spreadsheet) -> gspread.Worksheet:
    title = transaction_sheet_title()
    try:
        return spreadsheet.worksheet(title)
    except gspread.WorksheetNotFound:
        return spreadsheet.sheet1


def build_sheet_client(telegram_user_id: int | None = None) -> gspread.Worksheet:
    scope = [
        "https://spreadsheets.google.com/feeds",
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive.file",
        "https://www.googleapis.com/auth/drive",
    ]
    json_path = get_env("GOOGLE_SERVICE_ACCOUNT_JSON")
    credentials = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
    client = gspread.authorize(credentials)

    sid = lookup_user_spreadsheet_id(telegram_user_id) if telegram_user_id else None
    if sid:
        return open_transaction_worksheet(client.open_by_key(sid))

    sheet_name = get_env("GOOGLE_SHEET_NAME", required=False)
    if sheet_name:
        return open_transaction_worksheet(client.open(sheet_name))

    raise RuntimeError(
        "Spreadsheet belum tersedia. Pastikan pembayaran selesai, cek halaman sukses pembayaran atau email untuk sheet & lisensi, lalu `/activate`."
    )


def lookup_user_spreadsheet_id(telegram_user_id: int) -> str | None:
    if not telegram_user_id:
        return None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT spreadsheet_id FROM user_sheets
            WHERE telegram_user_id = %s AND status = 'active' LIMIT 1
            """,
            (telegram_user_id,),
        )
        row = cursor.fetchone()
        cursor.close()
        conn.close()
        if row and row.get("spreadsheet_id"):
            return str(row["spreadsheet_id"])
    except Exception as exc:  # pragma: no cover - external db guard
        logger.warning("Gagal lookup spreadsheet_id user %s: %s", telegram_user_id, exc)
    return None


def lookup_user_sheet_url(telegram_user_id: int) -> str | None:
    if not telegram_user_id:
        return None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT spreadsheet_id, spreadsheet_url FROM user_sheets
            WHERE telegram_user_id = %s AND status = 'active' LIMIT 1
            """,
            (telegram_user_id,),
        )
        row = cursor.fetchone()
        cursor.close()
        conn.close()
        if not row:
            return None
        url = (row.get("spreadsheet_url") or "").strip()
        if url:
            return url
        sid = (row.get("spreadsheet_id") or "").strip()
        if sid:
            return f"https://docs.google.com/spreadsheets/d/{sid}/edit"
    except Exception as exc:  # pragma: no cover - external db guard
        logger.warning("Gagal lookup spreadsheet_url user %s: %s", telegram_user_id, exc)
    return None


def upsert_user_sheet_row(telegram_user_id: int, spreadsheet_id: str, spreadsheet_url: str | None) -> None:
    conn = get_db_connection()
    cursor = conn.cursor()
    url = (spreadsheet_url or "").strip() or f"https://docs.google.com/spreadsheets/d/{spreadsheet_id}/edit"
    cursor.execute(
        """
        INSERT INTO user_sheets (telegram_user_id, spreadsheet_id, spreadsheet_url, status, created_at, updated_at)
        VALUES (%s, %s, %s, 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE spreadsheet_id = VALUES(spreadsheet_id), spreadsheet_url = VALUES(spreadsheet_url),
            status = 'active', updated_at = NOW()
        """,
        (telegram_user_id, spreadsheet_id, url),
    )
    conn.commit()
    cursor.close()
    conn.close()


def lookup_order_email_for_user(telegram_user_id: int) -> str | None:
    if not telegram_user_id:
        return None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT id FROM licenses
            WHERE assigned_user_id = %s AND status = 'active'
            ORDER BY id DESC LIMIT 1
            """,
            (telegram_user_id,),
        )
        lic = cursor.fetchone()
        if not lic:
            cursor.close()
            conn.close()
            return None
        cursor.execute(
            """
            SELECT email FROM orders
            WHERE license_id = %s AND status = 'paid'
            ORDER BY id DESC LIMIT 1
            """,
            (lic["id"],],
        )
        order_row = cursor.fetchone()
        cursor.close()
        conn.close()
        if not order_row:
            return None
        email = (order_row.get("email") or "").strip().lower()
        return email or None
    except Exception as exc:  # pragma: no cover - external db guard
        logger.warning("Gagal lookup order email user %s: %s", telegram_user_id, exc)
    return None


def ensure_sheet_drive_access(telegram_user_id: int) -> str | None:
    """Pastikan izin Drive untuk email checkout; kembalikan email tersebut."""
    from sheet_privacy import share_sheet_with_customer_email

    spreadsheet_id = lookup_user_spreadsheet_id(telegram_user_id)
    if not spreadsheet_id:
        return None
    json_path = get_env("GOOGLE_SERVICE_ACCOUNT_JSON", required=False)
    if not json_path or not os.path.isfile(json_path):
        return lookup_order_email_for_user(telegram_user_id)

    order_email = lookup_order_email_for_user(telegram_user_id)
    if not order_email:
        return None
    order_email = order_email.strip().lower()
    try:
        ok = share_sheet_with_customer_email(spreadsheet_id, json_path, order_email)
        if not ok:
            logger.warning(
                "ensure_sheet_drive_access: share gagal user=%s email=%s sheet=%s",
                telegram_user_id,
                order_email,
                spreadsheet_id,
            )
    except Exception as exc:  # pragma: no cover - external API guard
        logger.warning("ensure_sheet_drive_access gagal user=%s: %s", telegram_user_id, exc)
    return order_email


def ensure_user_sheet_from_order(telegram_user_id: int) -> None:
    """Sinkronkan user_sheets dari order lunas (sheet dikirim lewat email) jika belum ada."""
    if not telegram_user_id:
        return
    if lookup_user_spreadsheet_id(telegram_user_id):
        return
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT id FROM licenses
            WHERE assigned_user_id = %s AND status = 'active'
            ORDER BY id DESC LIMIT 1
            """,
            (telegram_user_id,),
        )
        lic = cursor.fetchone()
        if not lic:
            cursor.close()
            conn.close()
            return
        cursor.execute(
            """
            SELECT spreadsheet_id, spreadsheet_url FROM orders
            WHERE license_id = %s AND status = 'paid'
              AND spreadsheet_id IS NOT NULL AND spreadsheet_id != ''
            ORDER BY id DESC LIMIT 1
            """,
            (lic["id"],),
        )
        order_row = cursor.fetchone()
        cursor.close()
        conn.close()
        if not order_row:
            return
        upsert_user_sheet_row(
            telegram_user_id,
            str(order_row["spreadsheet_id"]),
            str(order_row["spreadsheet_url"]) if order_row.get("spreadsheet_url") else None,
        )
    except Exception as exc:  # pragma: no cover - external db guard
        logger.warning("ensure_user_sheet_from_order gagal user=%s: %s", telegram_user_id, exc)


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


def activate_license_for_user(license_key: str, user_id: int, username: str | None) -> str:
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
    cursor.close()
    conn.close()
    return key


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


def normalize_ai_result(data: Dict[str, Any]) -> Dict[str, Any]:
    if data.get("error") == "invalid_input":
        raise ValueError("invalid_input")

    required_keys = ["keterangan", "nominal", "jenis", "kategori", "sub_kategori", "sifat", "mood", "impulsif"]
    if any(key not in data for key in required_keys):
        raise ValueError("missing_keys")

    nominal = data["nominal"]
    if not isinstance(nominal, int) or nominal <= 0:
        raise ValueError("invalid_nominal")

    if data["jenis"] not in VALID_JENIS:
        raise ValueError("invalid_jenis")
    if data["kategori"] not in VALID_KATEGORI:
        raise ValueError("invalid_kategori")
    if data["sub_kategori"] not in VALID_SUB_KATEGORI:
        raise ValueError("invalid_sub_kategori")
    if data["sifat"] not in VALID_SIFAT:
        raise ValueError("invalid_sifat")
    if data["mood"] not in VALID_MOOD:
        raise ValueError("invalid_mood")
    if data["impulsif"] not in VALID_IMPULSIF:
        raise ValueError("invalid_impulsif")

    data["keterangan"] = str(data["keterangan"]).strip()
    return data


def analyze_with_gemini(user_text: str) -> Dict[str, Any]:
    api_key = get_env("GEMINI_API_KEY")
    genai.configure(api_key=api_key)
    candidate_models = [
        "gemini-2.5-flash",
        "gemini-2.5-flash-lite",
        "gemini-2.0-flash",
        "gemini-2.0-flash-lite",
    ]
    last_error: Exception | None = None

    for model_name in candidate_models:
        try:
            model = genai.GenerativeModel(model_name)
            response = model.generate_content(
                [SYSTEM_PROMPT, f"Input user: {user_text}"],
                generation_config={"temperature": 0},
            )
            raw_text = response.text if hasattr(response, "text") else ""
            parsed = extract_json(raw_text)
            return normalize_ai_result(parsed)
        except Exception as exc:  # pragma: no cover - external provider fallback
            last_error = exc
            logger.warning("Model %s gagal dipakai (%s).", model_name, type(exc).__name__)

    raise RuntimeError(f"Semua model Gemini gagal dipakai: {last_error}")


def extract_transaction_text_from_image(image_path: str) -> str:
    api_key = get_env("GEMINI_API_KEY")
    genai.configure(api_key=api_key)
    candidate_models = [
        "gemini-2.5-flash",
        "gemini-2.5-flash-lite",
        "gemini-2.0-flash",
        "gemini-2.0-flash-lite",
    ]
    prompt = (
        "Ekstrak isi transaksi dari gambar struk/foto jadi satu kalimat singkat bahasa Indonesia "
        "yang berisi keterangan dan nominal. Jangan isi mood. Jika tidak terbaca, balas INVALID_IMAGE."
    )
    last_error: Exception | None = None

    for model_name in candidate_models:
        try:
            model = genai.GenerativeModel(model_name)
            with open(image_path, "rb") as image_file:
                image_bytes = image_file.read()
            response = model.generate_content(
                [
                    prompt,
                    {"mime_type": "image/jpeg", "data": image_bytes},
                ],
                generation_config={"temperature": 0},
            )
            text = (response.text if hasattr(response, "text") else "").strip()
            if text and text.upper() != "INVALID_IMAGE":
                return text
        except Exception as exc:  # pragma: no cover - external provider fallback
            last_error = exc
            logger.warning("Model vision %s gagal dipakai (%s).", model_name, type(exc).__name__)

    raise RuntimeError(f"Gagal ekstrak transaksi dari gambar: {last_error}")


def parse_nominal_fallback(text: str) -> int:
    cleaned = text.lower().replace(" ", "")
    multiplier = 1
    if "jt" in cleaned:
        multiplier = 1_000_000
    elif "rb" in cleaned or "k" in cleaned:
        multiplier = 1_000

    matches = re.findall(r"\d+[.,]?\d*", cleaned)
    if not matches:
        raise ValueError("invalid_nominal")

    value = matches[0].replace(",", ".")
    number = float(value)
    nominal = int(number * multiplier)
    if nominal <= 0:
        raise ValueError("invalid_nominal")
    return nominal


def analyze_without_gemini(user_text: str) -> Dict[str, Any]:
    text = user_text.strip()
    nominal = parse_nominal_fallback(text)

    lower_text = text.lower()
    jenis = "Pengeluaran"
    kategori = "Jajan"
    sub_kategori = "Jajan / Makan diluar"
    sifat = "Wants"
    mood = "Biasa Saja"

    if any(keyword in lower_text for keyword in ["gaji", "bonus", "income", "fee", "honor"]):
        jenis = "Pemasukan"
        kategori = "Gaji"
        sub_kategori = "Tabungan Rutin"
        sifat = "Need"
    elif any(keyword in lower_text for keyword in ["listrik", "pln"]):
        kategori = "Listrik"
        sub_kategori = "Listrik"
        sifat = "Need"
    elif any(keyword in lower_text for keyword in ["bensin", "transport", "angkot", "ojek", "tol", "parkir"]):
        kategori = "Transport"
        sub_kategori = "Angkutan Umum"
        sifat = "Need"
    elif any(keyword in lower_text for keyword in ["air", "pdam"]):
        kategori = "Air"
        sub_kategori = "Belanja Bulanan"
        sifat = "Need"
    elif any(keyword in lower_text for keyword in ["makan", "nasi", "sarapan", "lunch", "dinner"]):
        kategori = "Makan"
        sub_kategori = "Jajan / Makan diluar"
        sifat = "Need"
    elif any(keyword in lower_text for keyword in ["hadiah", "amplop", "ultah", "ulang tahun"]):
        kategori = "Social"
        sub_kategori = "Hadiah / Amplop sosial"
        sifat = "Donation"

    if any(keyword in lower_text for keyword in ["sangat senang", "bahagia", "excited"]):
        mood = "Sangat Senang"
    elif any(keyword in lower_text for keyword in ["senang", "happy"]):
        mood = "Senang"
    elif any(keyword in lower_text for keyword in ["sedih", "kecewa", "capek"]):
        mood = "Sedih"

    impulsive_keywords = [
        "iseng",
        "diskon",
        "tiba-tiba",
        "lapar mata",
        "lucu",
        "gemes",
        "pengen",
        "kepengen",
        "fomo",
    ]
    impulsif = "Yes" if any(keyword in lower_text for keyword in impulsive_keywords) else "No"

    return {
        "keterangan": text,
        "nominal": nominal,
        "jenis": jenis,
        "kategori": kategori,
        "sub_kategori": sub_kategori,
        "sifat": sifat,
        "mood": mood,
        "impulsif": impulsif,
    }


def build_sheet_row(parsed: Dict[str, Any]) -> List[Any]:
    now = datetime.now()

    return [
        now.strftime("%Y-%m-%d %H:%M:%S"),  # Tanggal
        now.strftime("%B"),  # Bulan
        parsed["jenis"],  # Jenis (Pemasukan/Pengeluaran)
        parsed["kategori"],  # Kategori
        parsed["sub_kategori"],  # Sub Kategori
        parsed["nominal"],  # Nominal
        parsed["sifat"],  # Sifat
        parsed["mood"],
        parsed["impulsif"],  # Impulsivitas (Yes/No)
        parsed["keterangan"],  # Notes
    ]


def get_next_data_row(worksheet: gspread.Worksheet) -> int:
    rows = worksheet.get_all_values()
    return len(rows) + 1


def format_transaction_preview(parsed: Dict[str, Any], greeting_name: str) -> str:
    return (
        f"Aku baca transaksi untuk {greeting_name} seperti ini:\n"
        f"Keterangan: {parsed['keterangan']}\n"
        f"Nominal: Rp{parsed['nominal']:,}\n"
        f"Jenis: {parsed['jenis']}\n"
        f"Kategori: {parsed['kategori']} / {parsed['sub_kategori']}\n"
        f"Sifat: {parsed['sifat']}\n"
        f"Mood: {parsed['mood']}\n"
        f"Impulsif: {parsed['impulsif']}\n\n"
        "Sudah benar?"
    )


async def save_transaction(message, parsed: Dict[str, Any], greeting_name: str) -> None:
    uid = message.from_user.id if message.from_user else None
    try:
        if uid:
            ensure_user_sheet_from_order(uid)
        worksheet = build_sheet_client(uid)
        row = build_sheet_row(parsed)
        next_row = get_next_data_row(worksheet)
        worksheet.update(f"A{next_row}:J{next_row}", [row], value_input_option="USER_ENTERED")
        logger.info("Transaksi berhasil disimpan ke Google Sheets.")
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.exception("Gagal tulis ke Google Sheets: %s", exc)
        await message.reply_text("Gagal simpan ke Google Sheets. Coba lagi sebentar.")
        return

    await message.reply_text(
        f"Tercatat untuk {greeting_name}:\n"
        f"Keterangan: {parsed['keterangan']}\n"
        f"Nominal: Rp{parsed['nominal']:,}\n"
        f"Jenis: {parsed['jenis']}\n"
        f"Kategori: {parsed['kategori']} / {parsed['sub_kategori']}\n"
        f"Sifat: {parsed['sifat']}\n"
        f"Mood: {parsed['mood']}\n"
        f"Impulsif: {parsed['impulsif']}"
    )


def is_authorized(update: Update) -> bool:
    if not update.effective_user:
        return False
    authorized_user_id = int(get_env("USER_ID"))
    return update.effective_user.id == authorized_user_id


async def process_note_input(message, text: str, user_id: int | None = None) -> None:
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

    forced_mood = None
    mood_match = re.search(r"\bmood\s*:\s*(.+)$", text, flags=re.IGNORECASE)
    if mood_match:
        raw_mood = mood_match.group(1).strip()
        if raw_mood in VALID_MOOD:
            forced_mood = raw_mood

    try:
        parsed = analyze_with_gemini(text)
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.warning("Gagal analisis input AI, fallback parser dipakai: %s", exc)
        try:
            parsed = analyze_without_gemini(text)
        except Exception as fallback_exc:
            logger.warning("Fallback parser juga gagal: %s", fallback_exc)
            await message.reply_text(HELP_TEXT)
            return

    if forced_mood:
        parsed["mood"] = forced_mood

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
    }
    await message.reply_text(
        format_transaction_preview(parsed, greeting_name),
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
            "Selamat datang. Sebelum pakai bot, masukkan kode lisensi Anda (sama persis dengan di halaman pembayaran lunas atau email).\n"
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
        "/sheet - buka Google Sheet\n"
        "/hariini - rangkuman pengeluaran hari ini\n\n"
        "Contoh cepat:\n"
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
    try:
        activated_key = activate_license_for_user(key, user.id if user else 0, user.username if user else None)
    except ValueError as exc:
        mapping = {
            "empty_key": "Kode lisensi tidak boleh kosong.",
            "license_not_found": "Kode lisensi tidak ditemukan.",
            "license_not_active": "Lisensi tidak aktif (mungkin suspended).",
            "license_expired": "Lisensi sudah expired.",
            "license_used_by_other_user": "Lisensi sudah terpakai oleh akun Telegram lain.",
        }
        await update.message.reply_text(mapping.get(str(exc), "Aktivasi gagal."))
        return
    except Exception as exc:  # pragma: no cover - external db guard
        logger.exception("Aktivasi lisensi gagal: %s", exc)
        await update.message.reply_text("Aktivasi gagal karena masalah server.")
        return

    if user and user.id:
        ensure_user_sheet_from_order(user.id)
        ensure_sheet_drive_access(user.id)

    if user:
        PENDING_NAME_USERS.add(user.id)
    await update.message.reply_text(
        f"Lisensi aktif. Kode: `{activated_key}`\nSekarang kamu mau dipanggil siapa?",
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
    try:
        if user_id:
            ensure_user_sheet_from_order(user_id)
        worksheet = build_sheet_client(user_id)
        used_rows = len(worksheet.col_values(1))
        if used_rows <= 1:
            await update.message.reply_text("Belum ada data transaksi untuk dihapus.")
            return
        deleted_row = worksheet.row_values(used_rows)
        worksheet.delete_rows(used_rows)
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.exception("Gagal menghapus baris terakhir: %s", exc)
        await update.message.reply_text("Gagal menghapus data terakhir. Coba lagi sebentar.")
        return

    keterangan = deleted_row[9] if len(deleted_row) > 9 else "-"
    nominal = deleted_row[5] if len(deleted_row) > 5 else "-"
    await update.message.reply_text(
        "Data terakhir dihapus.\n"
        f"Keterangan: {keterangan}\n"
        f"Nominal: Rp{nominal}"
    )


async def sheet_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return
    share_ok = True
    order_email = None
    if user_id:
        from sheet_privacy import share_sheet_with_customer_email

        ensure_user_sheet_from_order(user_id)
        spreadsheet_id = lookup_user_spreadsheet_id(user_id)
        order_email = lookup_order_email_for_user(user_id)
        json_path = get_env("GOOGLE_SERVICE_ACCOUNT_JSON", required=False)
        if spreadsheet_id and order_email and json_path and os.path.isfile(json_path):
            try:
                share_ok = share_sheet_with_customer_email(
                    spreadsheet_id, json_path, order_email
                )
            except Exception as exc:
                share_ok = False
                logger.warning("sheet_handler share gagal user=%s: %s", user_id, exc)
        elif user_id:
            order_email = ensure_sheet_drive_access(user_id)
    sheet_url = lookup_user_sheet_url(user_id)
    if not sheet_url:
        sheet_url = get_env("GOOGLE_SHEET_URL", required=False)
    if not sheet_url:
        await update.message.reply_text(
            "Belum ada link Google Sheet untuk akun ini.\n\n"
            "• Buka lagi halaman sukses pembayaran (ada link sheet setelah lunas) atau cek email.\n"
            "• Pastikan sudah /activate dengan kode yang benar.\n"
            "• Di server Laravel admin: GOOGLE_SERVICE_ACCOUNT_JSON + GOOGLE_USER_SHEET_TEMPLATE_ID "
            "harus benar, dan queue/worker jalan agar sheet per order terbuat.\n"
            "• Atau isi GOOGLE_SHEET_URL di .env bot sebagai link fallback (sheet tunggal).\n\n"
            "Tunggu 1–2 menit setelah lunas lalu coba /sheet lagi."
        )
        return

    lines = [f"Buka Google Sheet kamu di sini:\n{sheet_url}"]
    if order_email:
        lines.append(
            f"\nBuka dengan Gmail yang *sama dengan yang Anda input saat checkout*:\n`{order_email}`\n\n"
            "Di browser Google, klik foto profil → *Ganti akun* jika perlu."
        )
        if not share_ok:
            lines.append(
                "\n⚠️ Share ke email checkout gagal. Pastikan `GOOGLE_OAUTH_*` di .env bot = Laravel. "
                "Admin: `php artisan google:sheet-setup --reshare=KODE_ORDER`."
            )
        elif get_env("GOOGLE_SHEET_FALLBACK_LINK_READER", "true").lower() in ("1", "true", "yes"):
            lines.append(
                "\nJika tetap diminta akses, muat ulang link — mode fallback link aktif; "
                f"login sebagai `{order_email}` atau akun Google mana pun yang punya link."
            )
    else:
        lines.append(
            "\nBuka dengan Gmail yang sama dengan email yang Anda input saat checkout."
        )
    await update.message.reply_text("\n".join(lines), parse_mode="Markdown")


async def hariini_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message:
        return
    user = update.effective_user
    user_id = user.id if user else 0
    if not user_id or not is_license_active_for_user(user_id):
        await update.message.reply_text(ACTIVATE_HELP_TEXT, parse_mode="Markdown")
        return

    try:
        if user_id:
            ensure_user_sheet_from_order(user_id)
        worksheet = build_sheet_client(user_id)
        rows = worksheet.get_all_values()
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.exception("Gagal ambil data untuk rangkuman harian: %s", exc)
        await update.message.reply_text("Gagal mengambil rangkuman hari ini. Coba lagi sebentar.")
        return

    today_prefix = datetime.now().strftime("%Y-%m-%d")
    today_rows: List[List[str]] = []
    for row in rows[1:]:
        if len(row) < 6:
            continue
        if row[0].startswith(today_prefix):
            today_rows.append(row)

    if not today_rows:
        await update.message.reply_text("Belum ada transaksi tercatat hari ini.")
        return

    total_pengeluaran = 0
    for row in today_rows:
        jenis_transaksi = row[2].strip()
        nominal_text = re.sub(r"[^\d]", "", row[5])
        if jenis_transaksi != "Pengeluaran":
            continue
        if nominal_text.isdigit():
            total_pengeluaran += int(nominal_text)

    await update.message.reply_text(
        "Rangkuman hari ini:\n"
        f"Jumlah transaksi: {len(today_rows)}\n"
        f"Total pengeluaran: Rp{total_pengeluaran:,}"
    )


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
            f"Siap, aku panggil kamu {text[:80]}.\nSekarang kamu bisa kirim teks transaksi atau foto struk."
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

    if user_id in PENDING_IMAGE_TRANSACTIONS:
        mood_text = normalize_mood(text)
        if not mood_text:
            await update.message.reply_text(
                "Pilih mood kamu dengan menekan salah satu tombol di bawah ini:",
                reply_markup=build_mood_keyboard(),
            )
            return
        extracted = PENDING_IMAGE_TRANSACTIONS.pop(user_id)
        combined_input = f"{extracted}\nmood: {mood_text}"
        await process_note_input(update.message, combined_input)
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

    if user_id not in PENDING_IMAGE_TRANSACTIONS:
        try:
            await query.edit_message_text("Sudah tidak ada transaksi gambar yang menunggu mood.")
        except Exception:
            pass
        return

    extracted = PENDING_IMAGE_TRANSACTIONS.pop(user_id)
    try:
        await query.edit_message_text(f"Mood dipilih: {mood_value}. Memproses transaksi...")
    except Exception:
        pass

    combined_input = f"{extracted}\nmood: {mood_value}"
    await process_note_input(query.message, combined_input, user_id=user_id)


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
            await query.edit_message_text("Transaksi dikonfirmasi. Menyimpan ke Google Sheet...")
        except Exception:
            pass
        await save_transaction(query.message, parsed, greeting_name)
        return

    if action == "no":
        PENDING_CONFIRMATIONS.pop(user_id, None)
        try:
            await query.edit_message_text("Oke, transaksi dibatalkan.")
        except Exception:
            pass
        await query.message.reply_text("Silakan kirim ulang catatan transaksi yang benar.")


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

    try:
        largest_photo = update.message.photo[-1]
        telegram_file = await context.bot.get_file(largest_photo.file_id)
        with tempfile.NamedTemporaryFile(suffix=".jpg", delete=False) as tmp:
            temp_path = tmp.name
        await telegram_file.download_to_drive(temp_path)
        extracted_text = extract_transaction_text_from_image(temp_path)
    except Exception as exc:  # pragma: no cover - external provider guard
        logger.exception("Gagal proses gambar transaksi: %s", exc)
        await update.message.reply_text(
            "Maaf, gambar belum bisa dibaca. Coba foto lebih jelas atau kirim dalam bentuk teks."
        )
        return
    finally:
        try:
            if "temp_path" in locals() and os.path.exists(temp_path):
                os.remove(temp_path)
        except OSError:
            logger.warning("Gagal hapus file sementara gambar.")

    PENDING_IMAGE_TRANSACTIONS[user_id] = extracted_text
    await update.message.reply_text(
        "Gambar sudah kebaca. Pilih mood kamu saat transaksi ini:",
        reply_markup=build_mood_keyboard(),
    )


def main() -> None:
    token = get_env("TELEGRAM_BOT_TOKEN")
    app = ApplicationBuilder().token(token).build()

    app.add_handler(CommandHandler("start", start_handler))
    app.add_handler(CommandHandler("catat", catat_handler))
    app.add_handler(CommandHandler("activate", activate_handler))
    app.add_handler(CommandHandler("hapuskilat", hapuskilat_handler))
    app.add_handler(CommandHandler("sheet", sheet_handler))
    app.add_handler(CommandHandler("hariini", hariini_handler))
    app.add_handler(CallbackQueryHandler(mood_callback_handler, pattern=r"^mood:"))
    app.add_handler(CallbackQueryHandler(confirm_callback_handler, pattern=r"^confirm:"))
    app.add_handler(MessageHandler(filters.PHOTO, photo_handler))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, message_handler))

    logger.info("Bot berjalan...")
    app.run_polling()


if __name__ == "__main__":
    main()
