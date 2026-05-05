# Telegram Finance Bot (Gemini + Google Sheets)

Bot Telegram untuk mencatat pengeluaran dari bahasa natural, dianalisis oleh Gemini AI lalu disimpan ke Google Sheets.

## Fitur

- Input natural, contoh: `mkn malm 50rb karena lagi sedih banget jadi iseng beli`
- Perintah bot:
  - `/catat <teks>` untuk mencatat transaksi
  - `/hapuskilat` untuk menghapus data terakhir di sheet
  - `/sheet` untuk membuka Google Sheet
  - `/hariini` untuk melihat rangkuman pengeluaran hari ini
- Gemini mengubah input ke JSON:
  - `keterangan` (normalisasi typo/singkatan)
  - `nominal` (integer bersih)
  - `jenis` (`Needs`, `Wants`, `Saving/Investment`)
  - `mood` (`Senang`, `Sedih`, `Stres`, `Netral`)
  - `impulsif` (`Ya` / `Tidak`)
- Simpan ke Google Sheets mengikuti urutan kolom:
  - `[Tanggal, Bulan, Jenis, Kategori, Sub Kategori, Nominal, Sifat, Mood, Impulsivitas, Notes]`
- Hanya merespons `USER_ID` yang diizinkan.

## 1) Persiapan Google Sheets

1. Buat project di Google Cloud dan aktifkan:
   - Google Sheets API
2. Buat Service Account lalu unduh kredensial JSON.
3. Simpan file JSON tersebut sebagai `service_account.json` di folder project.
4. Buka Google Spreadsheet Anda, lalu share ke email service account (role Editor).

## 2) Konfigurasi Environment

1. Copy `.env.example` menjadi `.env`
2. Isi semua nilai:
   - `TELEGRAM_BOT_TOKEN`
   - `GEMINI_API_KEY`
   - `GOOGLE_SHEET_NAME`
   - `GOOGLE_SHEET_URL` (link spreadsheet untuk command `/sheet`)
   - `GOOGLE_SERVICE_ACCOUNT_JSON` (biasanya `service_account.json`)
   - `USER_ID` (Telegram user id Anda)

## 3) Install dan Jalankan

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
python bot.py
```

## 4) Contoh Input

- `mkn malm 50rb karena lagi sedih banget jadi iseng beli`
- `beli bensin 30000`
- `nabung 200rb gajian`

Jika input tidak ada angka atau tidak dapat dipahami, bot akan balas format bantuan.
