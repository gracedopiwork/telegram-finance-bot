# Proposal Pengembangan & Penyediaan  
**Telegram Finance Bot — Pencatatan Keuangan Pribadi + Lisensi & Admin**

| Dokumen | Versi |
|--------|-------|
| Tanggal | 6 Mei 2026 |
| Status | Draft proposal |

---

## Keputusan Paket

Dokumen ini mengikuti **Paket 3: Ecosystem (Full Branding & Remote Control)** dengan estimasi harga freelance **Rp 8.500.000** dan durasi implementasi sekitar **6 minggu**.

Ruang lingkup utama Paket 3:
- Bot Telegram + AI parsing + pencatatan transaksi
- Lisensi & aktivasi user
- Admin web untuk operasional
- Integrasi payment otomatis (Midtrans)
- Company profile website
- Sinkron dashboard massal (remote dashboard control) melalui master template/library

---

## 1. Ringkasan eksekutif

Diajukan solusi berupa **bot Telegram** yang membantu pengguna mencatat pemasukan dan pengeluaran dari **bahasa natural** (contoh: “makan malam 50rb karena lagi sedih”), dengan bantuan **analisis AI (Gemini)** dan penyimpanan ke **Google Sheets** sesuai format kolom yang disepakati. Untuk model **penjualan / SaaS**, sistem dilengkapi **aktivasi lisensi**, **basis data MySQL**, dan **API admin** untuk pengelolaan kunci lisensi.

Roadmap mencakup **panel admin berbasis web**, **input foto struk dengan OCR + konfirmasi pengguna**, serta opsi **pembayaran otomatis** (misalnya Midtrans) sesuai kebutuhan operasional.

**RAB (Rencana Anggaran Biaya)** untuk pengembangan dan biaya berkelanjutan disampaikan pada **Lampiran A**.

---

## 2. Latar belakang & masalah yang diselesaikan

- Pencatatan manual di aplikasi keuangan sering terasa lambat; banyak orang lebih nyaman **chat cepat di Telegram**.
- Input tidak selalu terstruktur; dibutuhkan **parser/intelijen** yang menormalisasi kategori, nominal, dan konteks emosi/perilaku (misalnya impulsif vs terencana).
- Untuk produk yang **dijual ke banyak pengguna**, diperlukan **lisensi**, **aktivasi per akun**, dan **administrasi** yang dapat diaudit.

---

## 3. Tujuan proyek

1. Memberikan cara mencatat transaksi yang **cepat dan natural** lewat Telegram.
2. Menyimpan data secara **terstruktur** ke Google Sheets (atau ke database saat migrasi).
3. Menyediakan fondasi **komersial**: lisensi, aktivasi, dan API admin.
4. Menyiapkan perluasan: **struk foto**, **dashboard web**, **integrasi pembayaran**.

---

## 4. Ruang lingkup solusi

### 4.1 Yang sudah tersedia (fondasi / MVP teknis)

- Bot Telegram: perintah `/catat`, input teks biasa, `/hapuskilat`, `/sheet`, `/hariini`.
- Integrasi Gemini untuk parsing ke struktur transaksi + fallback parser sederhana.
- Penyimpanan baris ke Google Sheets (service account).
- Mode lisensi opsional (`LICENSE_REQUIRED`): command `/activate <kode>`.
- Skema MySQL: `licenses`, `license_activations`, `transactions` (siap untuk laporan & struk).
- Admin API (Flask): pembuatan, pembacaan, dan pembaruan lisensi (Bearer token).
- Satu repositori dengan pemisahan layanan: `run.py bot` / `run.py web`.

### 4.2 Direncanakan — Fase berikutnya (Paket 3)

| Fase | Fokus | Output utama |
|------|--------|--------------|
| **Fase A** | Panel admin web | UI login, generate lisensi, daftar & filter, suspend/extend |
| **Fase B** | Pembayaran otomatis | Integrasi Midtrans: webhook → generate/aktivasi lisensi otomatis |
| **Fase C** | Company profile | Website profil perusahaan + halaman produk/paket |
| **Fase D** | Remote dashboard control | Update massal dashboard user dari master template/library |
| **Fase E** | Produksi VPS | Nginx, SSL, process manager, backup DB, monitoring dasar |
| **Fase F (opsional)** | Foto struk | Unggah foto → OCR → konfirmasi simpan → simpan bukti (URL storage) |

Ruang lingkup detail tiap fase dapat disesuaikan dalam dokumen **Statement of Work (SOW)** terpisah.

---

## 5. Fitur utama (produk)

| Fitur | Deskripsi singkat |
|-------|-------------------|
| Catat natural | User mengetik seperti percakapan; sistem mengekstrak nominal dan metadata |
| AI parsing | Normalisasi kategori/sub-kategori, sifat, mood, indikasi impulsif |
| Google Sheets | Sinkronisasi ke spreadsheet yang dishare ke service account |
| Rangkuman harian | Ringkasan transaksi hari yang sama |
| Lisensi | Satu kode mengikat ke satu Telegram user (sesuai aturan DB); masa aktif & status |
| Admin API | Operasi lisensi tanpa UI (cocok untuk otomasi nanti) |
| Midtrans otomatis | Checkout → notifikasi bayar → lisensi aktif |
| Company profile web | Branding perusahaan + halaman informasi produk |
| Remote dashboard control | Perubahan dashboard terpusat untuk seluruh user |
| *(opsional tambahan)* Struk foto | OCR + konfirmasi sebelum commit ke database/sheet |

---

## 6. Arsitektur teknis (gambaran)

```
[Pengguna Telegram] ←→ [Bot Python — long-running]
                              ↓
                    [Gemini API] (parsing)
                              ↓
                    [Google Sheets API]

[Lisensi / aktivasi] ←→ [MySQL]

[Admin / nanti UI web] ←→ [Flask API — HTTP]
                              ↓
                         [MySQL]

*(planned)* [Midtrans Webhook] → [Backend] → [generate/update lisensi]
*(planned)* [Object Storage] → simpan file gambar struk
```

**Catatan deployment:** bot dan web admin dalam **satu proyek**, biasanya dijalankan sebagai **dua proses** pada satu VPS (normal untuk stabilitas).

---

## 7. Stack teknologi

| Lapisan | Pilihan saat ini |
|---------|------------------|
| Bot | Python, `python-telegram-bot` |
| AI | Google Gemini |
| Spreadsheet | Google Sheets + service account |
| Database | MySQL |
| Admin backend | Python Flask |
| Infrastruktur (disarankan) | VPS Linux, Nginx, SSL (Let’s Encrypt), process manager |

---

## 8. Model lisensi & bisnis (kerangka)

- **Paket** dapat berbasis durasi (misalnya 30 hari, 1 tahun) atau lifetime — diatur di field `expires_at` dan kebijakan produk.
- **Satu lisensi ↔ satu akun Telegram** (sesuai implementasi saat ini); paket multi-seat dapat ditinjau dengan penyesuaian skema.
- **Manual vs otomatis:** awal operasi bisa manual (transfer → admin generate key); otomasi via gateway pembayaran pada fase lanjutan.

---

## 9. Keamanan & privasi (garis besar)

- Rahasia lingkungan (token bot, kunci API, DB) hanya di server / `.env`, tidak di-commit.
- Admin API dilindungi **Bearer token**; untuk produksi disarankan upgrade ke sesi login + HTTPS saja.
- Data keuangan bersifat sensitif — akses server, backup, dan log harus dibatasi (detail dapat dimasukkan di dokumen keamanan terpisah).

---

## 10. Dependensi & tanggung jawab pihak klien / pemilik produk

- Akun **Telegram Bot** (token dari BotFather).
- Akun **Google Cloud** + Service Account + spreadsheet yang dishare ke email service account.
- **Gemini API key** (kuota & billing sesuai kebijakan Google).
- **MySQL** (hosted atau di VPS yang sama).
- **Domain + VPS** (jika admin web & webhook pembayaran dipublikasikan).
- Kebijakan **Midtrans** (jika fase pembayaran otomatis dijalankan).

---

## 11. Jadwal indikatif Paket 3 (perlu konfirmasi scope)

| Fase | Perkiraan durasi* |
|------|-------------------|
| Finalisasi fondasi + hardening dasar | 1 minggu |
| Admin web + lisensi | 1 minggu |
| Midtrans + webhook + notifikasi lisensi | 1 minggu |
| Company profile web | 1 minggu |
| Remote dashboard control + uji massal | 1 minggu |
| Go-live VPS + dokumentasi operasi | 1 minggu |

\*Total estimasi ±6 minggu; dapat berubah bergantung revisi fitur dan ketersediaan akses API/testing.

---

## 12. Deliverables akhir (target)

- Kode sumber ter-versioning dengan dokumentasi setup (`README`, `.env.example`).
- Skema database (`database/schema.sql`).
- Panduan deploy singkat untuk VPS.
- Admin web operasional untuk lisensi & user sheet.
- Integrasi pembayaran otomatis (Midtrans webhook flow).
- Website company profile.
- Mekanisme remote dashboard control (master -> user sheets).
- (Opsional) Dokumen SOW dengan acceptance criteria per fase.

---

## Lampiran A — RAB (Rencana Anggaran Biaya)

Dokumen ini berisi **kerangka RAB** untuk proposal. Angka **Rp …** atau kolom kosong sengaja disiapkan agar diisi sesuai **rate internal**, **negosiasi dengan klien**, atau **penawaran resmi** Anda. Tidak termasuk PPN kecuali dicantumkan terpisah.

### A.1 Biaya pengembangan (sekali bayar / fixed fee per paket)

Asumsi kolom **volume** dapat berupa perkiraan **hari kerja** atau **paket tetap** — sesuaikan dengan cara Anda menawarkan.

| No | Uraian pekerjaan | Volume | Satuan | Harga satuan (Rp) | Jumlah (Rp) |
|----|------------------|--------|--------|-------------------|-------------|
| 1 | Finalisasi fondasi: lisensi, DB MySQL, admin API, dokumentasi dasar | ___ | hari / paket | ___ | ___ |
| 2 | Fase A — Panel admin web (login, generate lisensi, daftar, suspend/extend) | ___ | hari / paket | ___ | ___ |
| 3 | Fase B — Foto struk: upload → OCR → konfirmasi → simpan bukti & catatan | ___ | hari / paket | ___ | ___ |
| 4 | Fase C — Integrasi pembayaran (Midtrans): checkout & webhook → lisensi otomatis | ___ | hari / paket | ___ | ___ |
| 5 | Fase D — Go-live VPS: Nginx, SSL, process manager, backup & dokumentasi operasi | ___ | hari / paket | ___ | ___ |
| 6 | QA, perbaikan bug minor pasca UAT (mis. putaran 1–2) | ___ | hari / paket | ___ | ___ |
| | **Subtotal pengembangan (A.1)** | | | | **Rp ___** |

**Catatan:** Item 1–6 dapat dibuat **paket bundling** (mis. “Paket Go-live” = item 1+2+D) agar RAB lebih singkat untuk klien.

### A.2 Biaya infrastruktur & layanan pihak ketiga (berkelanjutan / tahunan)

Biasanya ditanggung **pemilik produk** atau **klien** sesuai kontrak — dicantumkan agar transparan.

| No | Uraian | Periode | Estimasi (Rp/periode)* | Keterangan |
|----|--------|---------|-------------------------|------------|
| 1 | VPS (Linux, contoh 2 vCPU / 2 GB RAM) | /bulan | ___ | Skala naik jika traffic & DB besar |
| 2 | Domain `.com` / `.id` | /tahun | ___ | Opsional jika pakai IP saja |
| 3 | Google Gemini API | /bulan | ___ | Tergantung volume pemanggilan |
| 4 | Google Workspace / Cloud (Sheets API & kuota) | /bulan | ___ | Banyak kasus masih dalam free tier terbatas |
| 5 | MySQL terkelola (jika tidak di VPS) | /bulan | ___ | Opsional |
| 6 | Penyimpanan objek struk (S3-compatible / Cloudinary, dll.) | /bulan | ___ | Jika Fase B aktif |
| 7 | Midtrans fee transaksi | per trx | ___ % / tetap | Mengikuti ketentuan Midtrans |
| | **Subtotal recurring (indikatif)** | | **Rp ___ / bulan** | *Estimasi, bukan komitmen penyedia |

\*Estimasi mengikuti harga pasar dan dapat berubah; verifikasi ke penyedia masing-masing.

### A.3 Cadangan risiko & perubahan ruang lingkup (opsional)

| No | Uraian | % dari subtotal A.1 atau nominal tetap | Jumlah (Rp) |
|----|--------|------------------------------------------|-------------|
| 1 | Kontingensi perubahan requirement | ___ % | ___ |
| 2 | Pelatihan singkat admin / video Loom | ___ paket | ___ |

### A.4 Ringkasan total RAB

| Komponen | Jumlah (Rp) |
|----------|-------------|
| Subtotal pengembangan (A.1) | 8.500.000 |
| Subtotal cadangan (A.3), jika dipakai | ___ |
| **Total investasi awal (estimasi)** | **Rp 8.500.000** *(belum termasuk cadangan opsional/PPN)* |
| Estimasi biaya operasional bulanan (A.2) | Rp ___ / bulan |

**PPN:** ___ % → **Total termasuk PPN:** Rp ___ *(isi jika berlaku)*

---

## 13. Langkah selanjutnya

1. Menyetujui **scope** fase (minimal: admin UI vs struk vs Midtrans vs semua).
2. Menetapkan **paket lisensi** dan SLA support.
3. Menyiapkan akun & akses yang tertera di bagian 10.
4. Kickoff pengembangan fase yang disepakati.

---

## Lampiran C — SOP Sinkron Dashboard (Opsional)

Tujuan SOP ini adalah agar admin dapat memperbaiki rumus/tampilan dashboard untuk seluruh user tanpa mengubah data transaksi masing-masing.

1. Admin mengubah dashboard hanya di **Spreadsheet Master**.
2. Lakukan verifikasi cepat di file uji (minimal 1–3 akun dummy).
3. Jalankan `sync_dashboard.py --version vX.Y --dry-run` untuk validasi target.
4. Jalankan `sync_dashboard.py --version vX.Y` untuk publish.
5. Sistem update `dashboard_version` dan `last_synced_at` di tabel `user_sheets`.
6. Jika terjadi error pada sebagian user, lakukan retry hanya untuk target gagal.

Catatan:
- Setiap user tetap memakai spreadsheet terpisah (privasi antar user aman).
- Sync hanya menyentuh tab dashboard template, bukan tab data transaksi.

---

## Lampiran B — Kontak & penyesuaian

Bagian ini dapat diisi nama penyedia, kontak, dan nomor revisi dokumen.

**Penyedia / pengembang:** _____________________  
**Klien / pemilik produk:** _____________________  
**Penyetuju:** _____________________   Tanggal: ___________
