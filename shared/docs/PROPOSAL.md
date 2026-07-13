# Proposal Pengembangan & Penyediaan  
**YFD Finance Bot — Pencatatan Keuangan Pribadi via Telegram + Portal Web + Lisensi**

| Dokumen | Nilai |
|--------|-------|
| Produk | Your Financial Doctor (YFD) Finance Bot |
| Tanggal | 13 Juli 2026 |
| Status | Proposal resmi |
| Paket | Ecosystem (Full Branding & Web Dashboard) |
| Nilai proyek | **Rp 10.000.000** (sepuluh juta rupiah) |
| Estimasi pengerjaan | ±6 minggu (sudah terimplementasi / siap serah terima sesuai deliverables) |

---

## 1. Ringkasan eksekutif

Diajukan solusi produk digital **YFD Finance Bot**: bot Telegram yang membantu pengguna mencatat pemasukan dan pengeluaran dari **bahasa natural** (contoh: *“makan malam 50rb karena lagi sedih”*), dengan bantuan **analisis AI (Claude AI / Anthropic)** dan penyimpanan ke **database MySQL + dashboard web** (portal pengguna).

Untuk model **penjualan / SaaS**, sistem dilengkapi:

- Aktivasi **lisensi** per akun Telegram  
- **Checkout & pembayaran otomatis** (Midtrans)  
- **Website company profile** + CMS konten  
- **Portal web pengguna** (transaksi, dashboard keuangan, perilaku/emosi)  
- **Panel admin** operasional  
- Monitoring kesehatan AI (kuota / fallback) di admin  

**Nilai investasi pengembangan (fixed fee):** **Rp 10.000.000**  
Biaya operasional pihak ketiga (VPS, domain, Claude AI, Midtrans fee) ditanggung pemilik produk — lihat Lampiran A.

---

## 2. Latar belakang & masalah yang diselesaikan

| Masalah | Solusi YFD |
|---------|------------|
| Aplikasi keuangan terasa lambat / ribet | Catat lewat chat Telegram dalam hitungan detik |
| Input tidak terstruktur | AI menormalisasi nominal, kategori, sifat, mood, impulsif |
| Sulit menjual ke banyak user | Lisensi + aktivasi + Midtrans + email/WA delivery |
| Data tersebar / sulit dipantau | Semua transaksi di **web dashboard** (MySQL) |
| Perilaku belanja tidak terpantau | Mood + impulsif + portal emotional/financial |

---

## 3. Tujuan proyek

1. Memberikan cara mencatat transaksi yang **cepat dan natural** lewat Telegram.  
2. Menyimpan data **terstruktur** di database web (MySQL) + tampil di portal.  
3. Menyediakan fondasi **komersial**: checkout, lisensi, aktivasi, admin, email/WA.  
4. Menyediakan **website branding** + portal analitik pengguna.  
5. Menyiapkan produksi di **VPS** (HTTPS, process manager, dokumentasi).  

---

## 4. Ruang lingkup yang termasuk (Rp 10.000.000)

### 4.1 Bot Telegram (Python)

| Fitur | Keterangan |
|-------|------------|
| Input teks natural | Contoh: `/catat makan 50rb` atau teks biasa |
| Input foto struk | OCR via Claude AI Vision → konfirmasi → simpan |
| AI parsing (Claude) | Keterangan, nominal, jenis, kategori, sub-kategori, sifat, mood, impulsif |
| Fallback parser | Jika AI gagal / kuota habis, bot tetap bisa catat |
| Konfirmasi sebelum simpan | Tombol Benar / Ulangi |
| Prompt mood | Jika mood belum terdeteksi dari teks |
| Perintah | `/start`, `/activate`, `/catat`, `/hariini`, `/hapuskilat`, `/kuota`, `/web` |
| Lisensi | Hanya user teraktivasi yang bisa catat (`LICENSE_REQUIRED`) |
| Nama panggilan | Onboarding singkat setelah aktivasi |
| Simpan data | API Laravel → tabel `bot_transactions` (MySQL) |
| Voice note | **Tidak termasuk** (dapat ditawarkan sebagai add-on) |

### 4.2 Portal web pengguna (bukan Google Sheets)

| Fitur | Keterangan |
|-------|------------|
| Login portal | `/portal/login` atau magic link dari bot `/web` |
| Transaksi | List, hapus, import CSV (opsional) |
| Dashboard keuangan | KPI, tren, ringkasan |
| Dashboard perilaku | Mood / emotional insights |
| Baseline / diagnostik / FTSA | Fitur portal terkait assessment |
| Premium upsell | Halaman upgrade jika ada |

### 4.3 Website & pembayaran (Laravel)

| Fitur | Keterangan |
|-------|------------|
| Company profile | Home, tentang, layanan, paket, penasihat, produk, Wealthpedia, informasi |
| Checkout | Form order → Midtrans payment link |
| Webhook Midtrans | Settlement → order paid → buat lisensi |
| Halaman sukses | Instruksi aktivasi + cek email |
| Email/WA delivery | Kode lisensi, link bot Telegram, akses dashboard web |

### 4.4 Admin panel

| Fitur | Keterangan |
|-------|------------|
| Login admin | Autentikasi web |
| CMS konten | Settings, paket, layanan, tim dokter, FAQ, artikel, produk digital |
| Orders | Lihat order, status, resend email |
| Status AI Claude | Monitoring sukses / rate limit / fallback + kuota |

### 4.5 Infrastruktur & dokumentasi

| Item | Keterangan |
|------|------------|
| Monorepo | `apps/bot-python`, `apps/admin-laravel`, `shared/` |
| Deploy VPS | Nginx, SSL, systemd (`yfd-bot`, `yfd-queue`) |
| Dokumentasi | README, `.env.example`, DEPLOYMENT |
| Skema DB | MySQL bersama bot + Laravel + portal |

---

## 5. Yang tidak termasuk (di luar Rp 10 jt)

Kecuali disepakati add-on terpisah:

- Voice note / speech-to-text produksi  
- Aplikasi mobile native (iOS/Android)  
- Multi-seat lisensi (1 kode untuk banyak Telegram user)  
- Integrasi Google Sheets sebagai penyimpanan utama (sistem sudah **full web**)  
- Coaching AI mingguan di luar fitur portal yang sudah ada  
- Biaya akun pihak ketiga (VPS, domain, Claude AI, Midtrans fee, email SMTP)  
- Support & maintenance bulanan setelah masa garansi  

---

## 6. Arsitektur teknis

```
[Pelanggan Telegram]
        │
        ▼
[Bot Python — yfd-bot]
   ├── Claude AI (parse teks / foto struk)
   ├── MySQL (lisensi)
   └── API Laravel → simpan transaksi (bot_transactions)

[Website + Portal — Laravel]
   ├── Company profile + CMS
   ├── Checkout → Midtrans
   ├── Webhook → license
   ├── Email / WA delivery
   ├── Portal user (/portal/*)
   └── Admin (orders, AI health)

[MySQL]
   └── Source of truth untuk transaksi & lisensi
```

### Stack

| Lapisan | Teknologi |
|---------|-----------|
| Bot | Python, python-telegram-bot |
| AI | Claude AI (Anthropic) |
| Web / Portal / Admin | Laravel, AdminLTE, Blade |
| Database | MySQL |
| Payment | Midtrans |
| Deploy | VPS Linux (Hostinger), Nginx, SSL, systemd |

---

## 7. Model lisensi & bisnis (kerangka produk)

- Satu **lisensi** mengikat ke **satu akun Telegram**.  
- Masa aktif diatur di field `expires_at` (sesuai paket penjualan).  
- Setelah bayar: customer dapat kode `/activate`, link bot, dan akses **dashboard web** (`/web`).  

---

## 8. Keamanan & privasi (garis besar)

- Secret (`TELEGRAM_BOT_TOKEN`, Claude API key, Midtrans) hanya di `.env` server.  
- API internal bot ↔ Laravel dilindungi `BOT_INTERNAL_API_TOKEN`.  
- Data keuangan tersimpan di MySQL; akses portal per user terautentikasi.  
- Rekomendasi produksi: `APP_DEBUG=false`, HTTPS, backup DB berkala.  

---

## 9. Tanggung jawab pemilik produk (klien)

1. Akun **Telegram Bot** (BotFather)  
2. **Claude AI API key** (Anthropic) + billing sesuai pemakaian  
3. **Domain + VPS Hostinger** + akses SSH  
4. Akun **Midtrans** (production keys)  
5. SMTP email / Fonnte (jika WA dipakai)  
6. Konten branding (logo, copy, paket harga jual ke end-user)  

---

## 10. Jadwal indikatif

| Fase | Fokus | Durasi* |
|------|--------|---------|
| 1 | Setup VPS/DB, fondasi bot | 1 minggu |
| 2 | AI Claude + simpan transaksi ke web API | 1 minggu |
| 3 | Midtrans + lisensi + email/WA | 1 minggu |
| 4 | Website + CMS admin + orders | 1 minggu |
| 5 | Portal user (dashboard web) + AI health | 1 minggu |
| 6 | Hardening, UAT, handover | 1 minggu |

\*Total ±6 minggu.

---

## 11. Deliverables & garansi

- Kode sumber + sistem live di VPS  
- Bot + portal web + admin operasional  
- Dokumentasi setup  
- Garansi bug kritis **14–30 hari** pada scope Pasal/ruang lingkup ini  

---

## 12. Nilai kontrak & cara bayar

| Komponen | Jumlah |
|----------|--------|
| **Pengembangan Paket Ecosystem** | **Rp 10.000.000** |

| Termin | Persentase | Jumlah | Milestone |
|--------|------------|--------|-----------|
| DP | 42,5% | **Rp 4.250.000** | Kickoff / pembayaran awal |
| Pelunasan | 57,5% | **Rp 5.750.000** | Go-live + serah terima |

---

## Lampiran A — RAB

### A.1 Biaya pengembangan (fixed fee)

| No | Uraian | Jumlah (Rp) |
|----|--------|-------------|
| 1 | Bot Telegram + AI Claude + fallback + mood/impulsif | 2.500.000 |
| 2 | API simpan transaksi + portal web (dashboard/transaksi) | 2.000.000 |
| 3 | Lisensi, aktivasi, mapping user (MySQL) | 1.000.000 |
| 4 | Website company profile + CMS admin | 1.500.000 |
| 5 | Checkout Midtrans + email/WA delivery | 1.500.000 |
| 6 | Monitoring AI + kuota + hardening portal | 500.000 |
| 7 | Deploy VPS, dokumentasi, UAT & bugfix minor | 1.000.000 |
| | **Total pengembangan** | **10.000.000** |

### A.2 Biaya operasional (ditanggung klien)

| No | Uraian | Estimasi |
|----|--------|----------|
| 1 | VPS Hostinger KVM 2 (tahun 1) | **Rp 2.409.588 / tahun** (~Rp 201rb/bln) |
| 2 | Domain | **Rp 250.000 / tahun** |
| 3 | Claude AI API | Sesuai pemakaian (naik seiring user) |
| 4 | Midtrans fee | Mengikuti Midtrans |
| 5 | SMTP / Fonnte | Opsional |

Detail scaling VPS hingga 1.000.000 user: lihat `ESTIMASI_BIAYA_VPS_HOSTINGER.pdf`.

### A.3 Ringkasan

| Komponen | Jumlah |
|----------|--------|
| Investasi awal pengembangan | **Rp 10.000.000** |
| Operasional Hostinger tahun 1 (VPS+pajak) | **Rp 2.409.588** |
| Domain tahun 1 | **Rp 250.000** |
| Claude AI + Midtrans | **Variabel** (lihat PDF estimasi) |

---

## Lampiran B — Add-on (opsional)

| Add-on | Estimasi (mulai dari) |
|--------|------------------------|
| Voice note produksi | Rp 1.500.000 |
| Maintenance bulanan | Rp 500.000 – 1.000.000 / bulan |
| Custom fitur baru | Quotation terpisah |

---

## Lampiran C — Acceptance criteria (ringkas)

1. User bisa checkout → bayar Midtrans → menerima lisensi + akses bot + dashboard web.  
2. User bisa `/activate` lalu catat transaksi teks & foto struk; data muncul di portal.  
3. User bisa `/web` untuk masuk dashboard tanpa isi form manual.  
4. Admin bisa kelola konten, order, dan pantau status AI.  
5. Bot menolak user tanpa lisensi aktif.  
6. Dokumentasi deploy tersedia dan layanan berjalan di VPS.  

---

## 13. Langkah selanjutnya

1. Menyetujui proposal & nilai **Rp 10.000.000**.  
2. Menandatangani kontrak (DP Rp 4.250.000 / pelunasan Rp 5.750.000).  
3. Menyerahkan akses akun (Telegram, Claude AI, Midtrans, VPS, domain).  
4. Kickoff / UAT / go-live + serah terima.  

---

## Lampiran D — Kontak & persetujuan

**Penyedia / pengembang:** Grace Yoby Dopi  
**Klien / pemilik produk:** Ayuti Bulaan / YFD  
**Nilai disepakati:** Rp 10.000.000  
**Penyetuju klien:** _____________________  Tanggal: ___________  
**Penyetuju penyedia:** _____________________  Tanggal: ___________  

---

*Sistem penyimpanan utama: MySQL + portal web. Google Sheets tidak lagi menjadi jalur kritis pencatatan.*
