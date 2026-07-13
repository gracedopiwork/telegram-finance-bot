# Proposal Pengembangan & Penyediaan  
**YFD Finance Bot — Pencatatan Keuangan Pribadi via Telegram + Lisensi, Admin & Website**

| Dokumen | Nilai |
|--------|-------|
| Produk | Your Financial Doctor (YFD) Finance Bot |
| Tanggal | 12 Juli 2026 |
| Status | Proposal resmi |
| Paket | Ecosystem (Full Branding & Remote Control) |
| Nilai proyek | **Rp 10.000.000** (sepuluh juta rupiah) |
| Estimasi pengerjaan | ±6 minggu (sudah terimplementasi / siap serah terima sesuai deliverables) |

---

## 1. Ringkasan eksekutif

Diajukan solusi produk digital **YFD Finance Bot**: bot Telegram yang membantu pengguna mencatat pemasukan dan pengeluaran dari **bahasa natural** (contoh: *“makan malam 50rb karena lagi sedih”*), dengan bantuan **analisis AI (Google Gemini)** dan penyimpanan terstruktur ke **Google Sheets** milik masing-masing pelanggan.

Untuk model **penjualan / SaaS**, sistem dilengkapi:

- Aktivasi **lisensi** per akun Telegram  
- **Checkout & pembayaran otomatis** (Midtrans)  
- **Website company profile** + CMS konten  
- **Panel admin** operasional  
- **Provision Google Sheet** otomatis per order  
- **Remote dashboard control** (sync template Dashboard ke seluruh user)  
- Monitoring kesehatan AI (kuota / fallback) di admin  

**Nilai investasi pengembangan (fixed fee):** **Rp 10.000.000**  
Biaya operasional pihak ketiga (VPS, domain, Gemini, Midtrans fee) ditanggung pemilik produk — lihat Lampiran A.

---

## 2. Latar belakang & masalah yang diselesaikan

| Masalah | Solusi YFD |
|---------|------------|
| Aplikasi keuangan terasa lambat / ribet | Catat lewat chat Telegram dalam hitungan detik |
| Input tidak terstruktur | AI menormalisasi nominal, kategori, sifat, mood, impulsif |
| Sulit menjual ke banyak user | Lisensi + aktivasi + Midtrans + email delivery |
| Data tersebar / tidak punya template | Google Sheet per user + dashboard terpusat dari master |
| Perilaku belanja tidak terpantau | Mood + impulsif sebagai sinyal behavioral finance |

---

## 3. Tujuan proyek

1. Memberikan cara mencatat transaksi yang **cepat dan natural** lewat Telegram.  
2. Menyimpan data **terstruktur** ke Google Sheets (tab Transaksi) per pelanggan.  
3. Menyediakan fondasi **komersial**: checkout, lisensi, aktivasi, admin, email/WA.  
4. Menyediakan **website branding** + operasional sync dashboard massal.  
5. Menyiapkan produksi di **VPS** (HTTPS, process manager, dokumentasi).  

---

## 4. Ruang lingkup yang termasuk (Rp 10.000.000)

### 4.1 Bot Telegram (Python)

| Fitur | Keterangan |
|-------|------------|
| Input teks natural | Contoh: `/catat makan 50rb` atau teks biasa |
| Input foto struk | OCR via Gemini Vision → konfirmasi → simpan |
| AI parsing (Gemini) | Keterangan, nominal, jenis, kategori, sub-kategori, sifat, mood, impulsif |
| Fallback parser | Jika AI gagal / kuota habis, bot tetap bisa catat |
| Konfirmasi sebelum simpan | Tombol Benar / Ulangi |
| Prompt mood | Jika mood belum terdeteksi dari teks |
| Perintah | `/start`, `/activate`, `/catat`, `/sheet`, `/hariini`, `/hapuskilat` |
| Lisensi | Hanya user teraktivasi yang bisa catat (`LICENSE_REQUIRED`) |
| Nama panggilan | Onboarding singkat setelah aktivasi |
| Kategori selaras Sheet | Dropdown kategori & sub-kategori sesuai template YFD |
| Voice note | **Tidak termasuk** (dinonaktifkan; dapat ditawarkan sebagai add-on) |

### 4.2 Google Sheets & Drive

| Fitur | Keterangan |
|-------|------------|
| Sheet per order/user | Copy dari template master saat order lunas |
| Tab Transaksi | Append baris (tidak menimpa data lama) |
| Tab Dashboard | Template rumus/chart; sync massal dari master |
| Share otomatis | Email checkout + service account (viewer/editor sesuai kebijakan) |
| Tanggal readable | Format teks `dd-mm-yyyy HH:MM:SS` (bukan serial number) |

### 4.3 Website & pembayaran (Laravel)

| Fitur | Keterangan |
|-------|------------|
| Company profile | Home, tentang, layanan, paket, penasihat, produk, Wealthpedia, informasi |
| Checkout | Form order → Midtrans payment link |
| Webhook Midtrans | Settlement → order paid → buat lisensi |
| Halaman sukses | Instruksi aktivasi + cek email |
| Email delivery | Kode lisensi, link bot Telegram, link Google Sheet |
| WA (opsional) | Integrasi Fonnte jika dikonfigurasi |

### 4.4 Admin panel

| Fitur | Keterangan |
|-------|------------|
| Login admin | Autentikasi web |
| CMS konten | Settings, paket, layanan, tim dokter, FAQ, artikel, produk digital |
| Orders | Lihat order, status, provision/reshare sheet, resend email |
| Sync dashboard | Trigger sync master → semua sheet aktif |
| Status AI Gemini | Monitoring sukses / 429 / fallback + saran upgrade billing |

### 4.5 Infrastruktur & dokumentasi

| Item | Keterangan |
|------|------------|
| Monorepo | `apps/bot-python`, `apps/admin-laravel`, `shared/` |
| Deploy VPS | Nginx, SSL, systemd (`yfd-bot`, `yfd-queue`) |
| Dokumentasi | README, `.env.example`, DEPLOYMENT, API contract |
| Skema DB | MySQL bersama bot + Laravel |

---

## 5. Yang tidak termasuk (di luar Rp 10 jt)

Kecuali disepakati add-on terpisah:

- Voice note / speech-to-text produksi  
- Aplikasi mobile native (iOS/Android)  
- Multi-seat lisensi (1 kode untuk banyak Telegram user)  
- Custom kategori per user di luar dropdown Sheet  
- Coaching AI / insight mingguan otomatis (fitur lanjutan)  
- Biaya akun pihak ketiga (VPS, domain, Gemini paid, Midtrans fee, email SMTP)  
- Support & maintenance bulanan setelah masa garansi (lihat pasal 11)  

---

## 6. Arsitektur teknis

```
[Pelanggan Telegram]
        │
        ▼
[Bot Python — yfd-bot]
   ├── Google Gemini (parse teks / foto struk)
   ├── MySQL (lisensi & mapping sheet)
   └── Google Sheets API (tab Transaksi)

[Website yourfinancialdoctor.id — Laravel]
   ├── Company profile + CMS
   ├── Checkout → Midtrans
   ├── Webhook → license + provision Sheet
   ├── Email / WA delivery
   └── Admin (orders, sync dashboard, AI health)

[Google Drive]
   ├── Template master workbook
   └── Copy sheet per order (privacy per user)
```

### Stack

| Lapisan | Teknologi |
|---------|-----------|
| Bot | Python, python-telegram-bot |
| AI | Google Gemini (Flash family) |
| Web / Admin | Laravel, AdminLTE, Blade |
| Database | MySQL |
| Spreadsheet | Google Sheets + Service Account + OAuth |
| Payment | Midtrans |
| Deploy | VPS Linux, Nginx, SSL, systemd |

---

## 7. Model lisensi & bisnis (kerangka produk)

- Satu **lisensi** mengikat ke **satu akun Telegram**.  
- Masa aktif diatur di field `expires_at` (sesuai paket penjualan).  
- Setelah bayar: customer dapat kode `/activate`, link bot, dan Google Sheet.  
- Paket produk (contoh: Lite / Pro / Ecosystem) dikelola dari admin CMS.  

---

## 8. Keamanan & privasi (garis besar)

- Secret (`TELEGRAM_BOT_TOKEN`, `GEMINI_API_KEY`, OAuth, Midtrans) hanya di `.env` server.  
- API internal bot ↔ Laravel dilindungi `BOT_INTERNAL_API_TOKEN`.  
- Data keuangan per user di spreadsheet terpisah.  
- Rekomendasi produksi: `APP_DEBUG=false`, HTTPS, backup DB berkala, rotasi API key jika bocor.  

---

## 9. Tanggung jawab pemilik produk (klien)

Pemilik produk menyediakan / menanggung:

1. Akun **Telegram Bot** (BotFather)  
2. Akun **Google Cloud** (Service Account + OAuth Sheets/Drive)  
3. **Gemini API key** + billing jika volume melebihi free tier  
4. **Domain + VPS** + akses SSH  
5. Akun **Midtrans** (production keys)  
6. SMTP email / Fonnte (jika WA dipakai)  
7. Konten branding (logo, copy, paket harga jual ke end-user)  

---

## 10. Jadwal indikatif (referensi pengerjaan)

| Fase | Fokus | Durasi* |
|------|--------|---------|
| 1 | Fondasi bot + lisensi + Sheets | 1 minggu |
| 2 | Admin web + CMS | 1 minggu |
| 3 | Midtrans + email delivery + provision sheet | 1 minggu |
| 4 | Company profile website | 1 minggu |
| 5 | Remote dashboard sync + AI health | 1 minggu |
| 6 | Hardening produksi VPS + UAT + dokumentasi | 1 minggu |

\*Total ±6 minggu. Jadwal aktual mengikuti akses akun pihak ketiga dan feedback UAT.

---

## 11. Deliverables & garansi

### Deliverables

- Kode sumber di repository yang disepakati  
- Sistem berjalan di VPS klien (domain + SSL)  
- Dokumentasi setup & operasi singkat  
- Admin operasional + bot siap dipakai end-user  
- Template Google Sheet master + flow provision  

### Garansi (disarankan dicantumkan di kontrak)

- **14–30 hari** perbaikan bug kritis pada fitur yang termasuk scope (bukan fitur baru)  
- Perubahan requirement di luar pasal 4 = add-on / Change Request  

---

## 12. Nilai kontrak & cara bayar (usulan)

| Komponen | Jumlah |
|----------|--------|
| **Pengembangan & penyediaan Paket Ecosystem** | **Rp 10.000.000** |
| PPN (jika berlaku) | Sesuai ketentuan |
| Biaya operasional bulanan pihak ketiga | Di luar kontrak pengembangan (Lampiran A.2) |

### Usulan termin pembayaran

| Termin | Persentase | Jumlah | Milestone |
|--------|------------|--------|-----------|
| DP | 42,5% | Rp 4.250.000 | Kickoff + akses akun |
| Pelunasan | 57,5% | Rp 5.750.000 | Go-live produksi + serah terima |

*(Termin dapat dinegosiasikan.)*

---

## Lampiran A — RAB

### A.1 Biaya pengembangan (fixed fee)

| No | Uraian | Jumlah (Rp) |
|----|--------|-------------|
| 1 | Bot Telegram + AI parsing + fallback + kategori Sheet + mood/impulsif | 2.500.000 |
| 2 | Integrasi Google Sheets/Drive (provision, append, privacy, tanggal) | 1.500.000 |
| 3 | Lisensi, aktivasi, mapping user ↔ sheet (MySQL) | 1.000.000 |
| 4 | Website company profile + CMS admin | 1.500.000 |
| 5 | Checkout Midtrans + webhook + email delivery | 1.500.000 |
| 6 | Remote dashboard sync + monitoring AI health | 1.000.000 |
| 7 | Deploy VPS, hardening, dokumentasi, UAT & bugfix minor | 1.000.000 |
| | **Total pengembangan** | **10.000.000** |

### A.2 Biaya operasional pihak ketiga (estimasi, ditanggung klien)

| No | Uraian | Estimasi | Keterangan |
|----|--------|----------|------------|
| 1 | VPS (2 vCPU / 2–4 GB) | Rp 100.000 – 250.000 / bulan | Tergantung provider |
| 2 | Domain `.id` / `.com` | Rp 100.000 – 200.000 / tahun | |
| 3 | Google Gemini API (paid) | Rp 0 – 100.000 / bulan* | *Volume kecil biasanya sangat murah; free tier terbatas |
| 4 | Midtrans fee | Mengikuti Midtrans | Per transaksi end-user |
| 5 | SMTP / email | Rp 0 – 50.000 / bulan | Atau SMTP provider |
| 6 | Fonnte WA (opsional) | Sesuai paket Fonnte | |

\*Contoh kasar: ± Rp 10–30 per transaksi teks di paid tier Gemini Flash — detail mengikuti harga Google.

### A.3 Ringkasan

| Komponen | Jumlah |
|----------|--------|
| Investasi awal pengembangan | **Rp 10.000.000** |
| Operasional bulanan (A.2) | **Ditanggung pemilik produk** |

---

## Lampiran B — Add-on (opsional, di luar kontrak utama)

| Add-on | Estimasi (mulai dari) |
|--------|------------------------|
| Voice note produksi (speech → transaksi) | Rp 1.500.000 |
| Insight / laporan mingguan otomatis ke Telegram | Rp 2.000.000 |
| Multi-seat / akun keluarga | Rp 1.500.000 |
| Maintenance & support bulanan | Rp 500.000 – 1.000.000 / bulan |
| Custom fitur baru | Quotation terpisah |

---

## Lampiran C — SOP singkat sync Dashboard

1. Edit rumus/tampilan hanya di **Spreadsheet Master** (tab Dashboard).  
2. Uji di 1–3 akun dummy.  
3. Jalankan sync dari admin (atau Apps Script webhook) dengan versi baru.  
4. Sistem menyalin tab Dashboard ke semua `user_sheets` aktif.  
5. Tab **Transaksi** tidak diubah.  

---

## Lampiran D — Acceptance criteria (ringkas)

Sistem dianggap selesai jika:

1. User bisa checkout → bayar Midtrans → menerima email lisensi + link sheet + link bot.  
2. User bisa `/activate` lalu catat transaksi teks & foto struk ke Sheet.  
3. Admin bisa kelola konten website, lihat order, sync dashboard, lihat status AI.  
4. Bot menolak user tanpa lisensi aktif.  
5. Dokumentasi deploy tersedia dan layanan berjalan di VPS klien.  

---

## 13. Langkah selanjutnya

1. Menyetujui proposal & nilai **Rp 10.000.000**.  
2. Menandatangani kontrak / SOW singkat + jadwal termin.  
3. Menyerahkan akses akun (Telegram, Google, Midtrans, VPS, domain).  
4. Kickoff & UAT.  
5. Go-live + serah terima.  

---

## Lampiran E — Kontak & persetujuan

**Penyedia / pengembang:** _____________________  
**Klien / pemilik produk:** _____________________  
**Nilai disepakati:** Rp 10.000.000  
**Penyetuju klien:** _____________________  Tanggal: ___________  
**Penyetuju penyedia:** _____________________  Tanggal: ___________  

---

*Dokumen ini merupakan proposal ruang lingkup dan nilai proyek. Detail teknis operasional tersedia di `README.md`, `shared/docs/DEPLOYMENT.md`, dan `shared/docs/API_CONTRACT.md`.*
