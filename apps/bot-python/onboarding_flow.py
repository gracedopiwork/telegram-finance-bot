"""Alur edukasi onboarding bot (revisi 15 Agustus 2026 Bagian 4.2–5, 6.0)."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

from onboarding_api import fetch_onboarding_status, set_onboarding_step
from bucket_explain import explain_bucket_choice

if TYPE_CHECKING:
    from telegram import InlineKeyboardMarkup

ONBOARDING_OK_CACHE: set[int] = set()

BUCKET_DETAILS: dict[str, str] = {
    "essential": (
        "*Essential Living*\n"
        "Pengeluaran untuk mempertahankan kehidupan dan kemampuan kerja hari ini.\n"
        "Contoh: kebutuhan pokok, tempat tinggal, utilitas, transportasi wajib, "
        "komunikasi dasar, kesehatan.\n"
        "Arah: ada batas atas yang sehat — jaga porsinya tidak terlalu besar."
    ),
    "future": (
        "*Future Building*\n"
        "Alokasi yang meningkatkan aset dan/atau kapasitas menghasilkan uang di masa depan.\n"
        "Contoh: investasi, pengembangan kapasitas, kebutuhan bisnis, tools kerja.\n"
        "Arah: umumnya masih kecil — sehat kalau terus dibangun naik."
    ),
    "protection": (
        "*Protection*\n"
        "Alokasi yang mengurangi risiko finansial yang bisa menghancurkan kondisi keuangan.\n"
        "Arah: ada batas atas yang sehat — porsi kecil sudah cukup selama perlindungan memadai."
    ),
    "flexible": (
        "*Flexible + Social*\n"
        "Kualitas hidup, hubungan sosial, spiritual, enjoyment, lifestyle — "
        "bukan kebutuhan dasar dan bukan investasi masa depan.\n"
        "Ini bagian hidup yang sehat, tapi proporsinya perlu dijaga seimbang."
    ),
}

FEATURE_BLURBS: dict[str, str] = {
    "catat": (
        "Kamu bisa mencatat transaksi langsung lewat chat atau foto struk.\n"
        "Setiap transaksi diproses berdasarkan kategori, sifat, dan mapping bucket YFD."
    ),
    "dashboard": (
        "Catatan transaksimu membentuk gambaran kondisi keuangan.\n"
        "Dashboard membantu melihat pola alokasi berdasarkan Financial Health Bucket."
    ),
    "pola": (
        "Keuangan bukan cuma soal angka.\n"
        "First Aid juga membantu melihat pola di balik keputusan: konteks, mood, dan perilaku."
    ),
    "fh": (
        "Tujuan akhirnya bukan hanya mencatat lebih banyak data.\n"
        "Data ini membantu memahami Financial Health dan jadi dasar Budget Prescription."
    ),
    "panduan": (
        "Kamu tidak perlu menghafal semuanya sekarang.\n"
        "Ketik /panduan kapan saja, atau buka versi lengkap di web."
    ),
}


def user_onboarding_done(user_id: int) -> bool:
    if user_id in ONBOARDING_OK_CACHE:
        return True
    ok, data = fetch_onboarding_status(user_id)
    if not ok:
        # Fail-open ke fitur jika API down setelah consent — jangan stuck total.
        return True
    if data.get("completed"):
        ONBOARDING_OK_CACHE.add(user_id)
        return True
    return False


def persist_step(user_id: int, step: str) -> None:
    ok, data = set_onboarding_step(user_id, step)
    if ok and data.get("completed"):
        ONBOARDING_OK_CACHE.add(user_id)


def welcome_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton("📖 Kenalan dulu", callback_data="onb:kenalan"),
                InlineKeyboardButton("🚀 Langsung mulai", callback_data="onb:langsung"),
            ]
        ]
    )


def fh_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [[InlineKeyboardButton("➡️ Kenalan dengan 4 Bucket", callback_data="onb:buckets")]]
    )


def buckets_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton("🏠 Essential Living", callback_data="onb:bucket:essential"),
                InlineKeyboardButton("📈 Future Building", callback_data="onb:bucket:future"),
            ],
            [
                InlineKeyboardButton("🛡️ Protection", callback_data="onb:bucket:protection"),
                InlineKeyboardButton("❤️ Flexible + Social", callback_data="onb:bucket:flexible"),
            ],
            [InlineKeyboardButton("➡️ Prinsip: tujuan menentukan bucket", callback_data="onb:purpose")],
        ]
    )


def purpose_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [[InlineKeyboardButton("➡️ Apa saja di First Aid?", callback_data="onb:features")]]
    )


def features_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton("💸 Catat Transaksi", callback_data="onb:feature:catat"),
                InlineKeyboardButton("📊 Dashboard", callback_data="onb:feature:dashboard"),
            ],
            [
                InlineKeyboardButton("🧠 Pola Keuangan", callback_data="onb:feature:pola"),
                InlineKeyboardButton("🩺 Financial Health", callback_data="onb:feature:fh"),
            ],
            [
                InlineKeyboardButton("📖 Panduan", callback_data="onb:feature:panduan"),
                InlineKeyboardButton("✅ Siap mulai", callback_data="onb:home"),
            ],
        ]
    )


def home_keyboard() -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton("💸 Catat Transaksi", callback_data="onb:go:catat"),
                InlineKeyboardButton("🩺 Mulai Check", callback_data="onb:go:check"),
            ],
            [
                InlineKeyboardButton("📊 Buka Dashboard", callback_data="onb:go:dashboard"),
                InlineKeyboardButton("📖 Panduan", callback_data="onb:go:panduan"),
            ],
        ]
    )


def panduan_toc_keyboard(guide_url: str, topics: list[dict[str, Any]]) -> InlineKeyboardMarkup:
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup

    rows: list[list[InlineKeyboardButton]] = []
    for topic in topics[:13]:
        tid = str(topic.get("id") or "")
        title = str(topic.get("title") or tid)
        short = title.split(". ", 1)[-1] if ". " in title else title
        if len(short) > 40:
            short = short[:37] + "…"
        rows.append(
            [InlineKeyboardButton(short, callback_data=f"panduan:topic:{tid}")]
        )
    if guide_url:
        rows.append([InlineKeyboardButton("🌐 Baca lengkap di Web", url=guide_url)])
    return InlineKeyboardMarkup(rows)


async def send_welcome(message, name: str, user_id: int) -> None:
    persist_step(user_id, "welcome")
    await message.reply_text(f"Senang kenal kamu, {name}. Selamat datang di YFD First Aid.")
    await message.reply_text(
        "Sebelum mulai mencatat keuangan, ada satu hal penting: "
        "YFD First Aid bukan hanya membantu kamu melihat ke mana uangmu pergi. "
        "Kami juga membantu kamu memahami bagaimana uang itu digunakan dalam Financial Health kamu.\n\n"
        "Kamu mau kenalan dulu dengan sistem YFD First Aid, atau langsung mulai?",
        reply_markup=welcome_keyboard(),
    )


async def resume_onboarding(message, name: str, user_id: int) -> None:
    ok, data = fetch_onboarding_status(user_id)
    step = str((data or {}).get("step") or "welcome") if ok else "welcome"
    if step == "done" or (ok and data.get("completed")):
        ONBOARDING_OK_CACHE.add(user_id)
        return
    if step in {"fh"}:
        await send_financial_health(message, user_id)
        return
    if step in {"buckets"}:
        await send_buckets_intro(message, user_id)
        return
    if step in {"purpose"}:
        await send_purpose(message, user_id)
        return
    if step in {"features"}:
        await send_features(message, user_id)
        return
    if step in {"home"}:
        await send_home(message, name, user_id)
        return
    await send_welcome(message, name, user_id)


async def send_financial_health(message, user_id: int) -> None:
    persist_step(user_id, "fh")
    await message.reply_text(
        "*Apa itu Financial Health?*\n\n"
        "Bukan hanya soal seberapa banyak uang yang kamu punya. "
        "Ini soal kondisi keuangan secara menyeluruh: mempertahankan kehidupan saat ini, "
        "membangun masa depan, melindungi diri dari risiko, dan menikmati kualitas hidup.\n\n"
        "YFD tidak hanya melihat total pengeluaran — kami melihat fungsi dari setiap alokasi uangmu.",
        parse_mode="Markdown",
        reply_markup=fh_keyboard(),
    )


async def send_buckets_intro(message, user_id: int) -> None:
    persist_step(user_id, "buckets")
    await message.reply_text(
        "Setiap uang yang keluar punya tujuan.\n\n"
        "Di YFD, pengeluaran dikelompokkan ke 4 Financial Health Bucket — "
        "bukan untuk menghakimi, tapi memahami fungsinya terhadap Financial Health kamu.\n\n"
        "Keempat bucket ini jadi dasar dashboard dan Budget Prescription."
    )
    await message.reply_text(
        "Pilih bucket untuk penjelasan singkat, atau lanjut ke prinsip tujuan:",
        reply_markup=buckets_keyboard(),
    )


async def send_purpose(message, user_id: int) -> None:
    persist_step(user_id, "purpose")
    await message.reply_text(
        "Satu jenis pengeluaran bisa punya tujuan berbeda.\n"
        "YFD tidak selalu menentukan bucket hanya dari nama barang — "
        "kami melihat fungsi dan tujuan penggunaan uang."
    )
    await message.reply_text(
        "*Contoh — Transportasi*\n"
        "• Kerja / sekolah / wajib → Essential Living\n"
        "• Gym / nongkrong / healing → Flexible + Social\n"
        "• Meeting bisnis → Future Building\n\n"
        "*Contoh — HP*\n"
        "• Ganti HP utama rusak → Essential Living\n"
        "• Upgrade lifestyle → Flexible + Social\n"
        "• HP operasional bisnis → Future Building\n\n"
        "*Gym*\n"
        "Default: Flexible + Social (kualitas hidup).\n"
        "Pengecualian: personal trainer/atlet (fisik = alat penghasilan) → Future Building.",
        parse_mode="Markdown",
        reply_markup=purpose_keyboard(),
    )


async def send_features(message, user_id: int) -> None:
    persist_step(user_id, "features")
    await message.reply_text(
        "Sekarang kamu sudah mengenal dasar sistemnya. "
        "Di YFD First Aid, kamu bisa mulai dari beberapa hal berikut:",
        reply_markup=features_keyboard(),
    )


async def send_home(message, name: str, user_id: int) -> None:
    persist_step(user_id, "home")
    await message.reply_text(
        f"Kamu sudah siap memulai perjalanan bersama YFD First Aid, {name}.\n\n"
        "Tidak perlu langsung sempurna. Kita mulai dari memahami kondisi keuanganmu hari ini, "
        "satu transaksi dan satu pola dalam satu waktu.\n\n"
        "Kamu mau mulai dari mana?",
        reply_markup=home_keyboard(),
    )
    persist_step(user_id, "done")
