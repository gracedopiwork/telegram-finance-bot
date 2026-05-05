import json
import logging
import os
import re
from datetime import datetime
from typing import Any, Dict, List

import gspread
import google.generativeai as genai
from dotenv import load_dotenv
from oauth2client.service_account import ServiceAccountCredentials
from telegram import Update
from telegram.ext import ApplicationBuilder, CommandHandler, ContextTypes, MessageHandler, filters

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


def get_env(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise RuntimeError(f"Environment variable '{name}' belum diisi.")
    return value


def build_sheet_client() -> gspread.Worksheet:
    scope = [
        "https://spreadsheets.google.com/feeds",
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive.file",
        "https://www.googleapis.com/auth/drive",
    ]
    json_path = get_env("GOOGLE_SERVICE_ACCOUNT_JSON")
    sheet_name = get_env("GOOGLE_SHEET_NAME")

    credentials = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
    client = gspread.authorize(credentials)
    return client.open(sheet_name).sheet1


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
        "gemini-2.0-flash",
        "gemini-2.0-flash-lite",
        "gemini-1.5-flash-latest",
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


def is_authorized(update: Update) -> bool:
    if not update.effective_user:
        return False
    authorized_user_id = int(get_env("USER_ID"))
    return update.effective_user.id == authorized_user_id


async def process_note_input(message, text: str) -> None:
    text = text.strip()
    if not text or not re.search(r"\d", text):
        await message.reply_text(HELP_TEXT)
        return

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

    try:
        worksheet = build_sheet_client()
        row = build_sheet_row(parsed)
        next_row = get_next_data_row(worksheet)
        worksheet.update(f"A{next_row}:J{next_row}", [row], value_input_option="USER_ENTERED")
        logger.info("Transaksi berhasil disimpan ke Google Sheets.")
    except Exception as exc:  # pragma: no cover - defensive guard for external services
        logger.exception("Gagal tulis ke Google Sheets: %s", exc)
        await message.reply_text("Gagal simpan ke Google Sheets. Coba lagi sebentar.")
        return

    await message.reply_text(
        "Tercatat:\n"
        f"Keterangan: {parsed['keterangan']}\n"
        f"Nominal: Rp{parsed['nominal']:,}\n"
        f"Jenis: {parsed['jenis']}\n"
        f"Kategori: {parsed['kategori']} / {parsed['sub_kategori']}\n"
        f"Sifat: {parsed['sifat']}\n"
        f"Mood: {parsed['mood']}\n"
        f"Impulsif: {parsed['impulsif']}"
    )


async def start_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not is_authorized(update):
        return
    await update.message.reply_text(
        "Perintah yang tersedia:\n"
        "/catat - catat transaksi baru\n"
        "/hapuskilat - hapus data terakhir\n"
        "/sheet - buka Google Sheet\n"
        "/hariini - rangkuman pengeluaran hari ini\n\n"
        "Contoh cepat:\n"
        "`/catat mkn malm 50rb karena lagi sedih banget jadi iseng beli`",
        parse_mode="Markdown",
    )


async def catat_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not is_authorized(update):
        return

    text = " ".join(context.args).strip() if context.args else ""
    if not text:
        await update.message.reply_text(CATAT_HELP_TEXT, parse_mode="Markdown")
        return
    await process_note_input(update.message, text)


async def hapuskilat_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not is_authorized(update):
        return
    try:
        worksheet = build_sheet_client()
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
    if not update.message or not is_authorized(update):
        return
    sheet_url = get_env("GOOGLE_SHEET_URL", required=False)
    if not sheet_url:
        await update.message.reply_text(
            "URL sheet belum diatur. Isi `GOOGLE_SHEET_URL` di file .env."
        )
        return
    await update.message.reply_text(f"Buka Google Sheet kamu di sini:\n{sheet_url}")


async def hariini_handler(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not update.message or not is_authorized(update):
        return

    try:
        worksheet = build_sheet_client()
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

    if not is_authorized(update):
        return

    await process_note_input(update.message, update.message.text or "")


def main() -> None:
    token = get_env("TELEGRAM_BOT_TOKEN")
    app = ApplicationBuilder().token(token).build()

    app.add_handler(CommandHandler("start", start_handler))
    app.add_handler(CommandHandler("catat", catat_handler))
    app.add_handler(CommandHandler("hapuskilat", hapuskilat_handler))
    app.add_handler(CommandHandler("sheet", sheet_handler))
    app.add_handler(CommandHandler("hariini", hariini_handler))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, message_handler))

    logger.info("Bot berjalan...")
    app.run_polling()


if __name__ == "__main__":
    main()
