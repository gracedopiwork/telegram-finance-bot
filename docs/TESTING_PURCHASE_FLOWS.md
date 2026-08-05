# Alur Testing — Semua Kemungkinan Pembelian YFD

Dokumen ini untuk QA manual end-to-end. Gunakan **email unik per skenario** agar data tidak bentrok.

## Prasyarat

### Environment

- [ ] `git pull` terbaru + `php artisan migrate --force`
- [ ] `php artisan config:clear && php artisan view:clear`
- [ ] Bot restart: `sudo systemctl restart yfd-bot`
- [ ] Midtrans **sandbox** aktif (atau mark-paid manual di admin)

### `.env` penting (Laravel)

```env
PORTAL_FTSA_REQUIRES_UPGRADE=true
PORTAL_FTSA_EVALUATION_MONTHS=12
PORTAL_FTSA_UNLOCK_PRODUCT_CODES=yfd-ftsa-premium
PORTAL_BOT_ONLY_PRODUCT_CODES=yfd-bot-telegram
BOT_INTERNAL_API_TOKEN=<sama dengan bot>
```

### `.env` penting (bot-python)

```env
LICENSE_REQUIRED=true
BOT_INTERNAL_API_TOKEN=<sama dengan Laravel>
LARAVEL_APP_URL=https://domain-anda.com
```

### Produk & harga (cek di admin)

| Kode | Produk |
|------|--------|
| `yfd-bot-telegram` | YFD Bot Telegram (lisensi selamanya) |
| `yfd-ftsa-premium` | FTSA Premium (evaluasi 12 bulan) |

### Data uji

Buat **6 email berbeda** (mis. `qa-a1@…`, `qa-b1@…`, dst.) — satu email per skenario di bawah.

---

## Matriks skenario

| # | Skenario | Urutan | Lisensi | Bot `/activate` | Portal home |
|---|----------|--------|---------|-----------------|-------------|
| **A** | Diagnostik gratis saja | Check-up landing | — | — | — |
| **B** | Diagnostik → beli Bot | Check-up → Bot | Baru (bot) | Wajib | Dashboard bot |
| **C** | Bot saja | Bot | Baru (bot) | Wajib | Dashboard bot |
| **D** | FTSA saja | FTSA | Baru (FTSA) | ❌ Ditolak | Behavioral (FTSA) |
| **E** | Bot → FTSA | Bot → FTSA | **Sama** (bot) | Sudah aktif | Dashboard lengkap + FTSA |
| **F** | FTSA → Bot | FTSA → Bot | **Sama** (FTSA) | Wajib setelah beli bot | Dashboard lengkap |
| **G** | Keamanan FTSA | FTSA tanpa beli bot | FTSA | ❌ Harus ditolak | — |

---

## A — Diagnostik gratis (landing only)

**Tujuan:** Check-up 16 langkah + hasil di landing, tanpa pembelian.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| A1 | Buka `/check-up` dari homepage | Wizard 16 langkah tampil |
| A2 | Isi semua soal + email `qa-a1@…` | Submit sukses |
| A3 | Halaman `/check-up/hasil` | Kartu skor + tahap keuangan tampil |
| A4 | Cek admin → Hasil Diagnostik | Record guest (`telegram_user_id` kosong) ada |

**Pass:** Hasil hanya di landing; belum ada akses portal.

---

## B — Diagnostik landing → beli Bot

**Tujuan:** Data check-up terhubung via email saat aktivasi bot.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| B1 | Ulangi A dengan email `qa-b1@…` | Hasil check-up tersimpan |
| B2 | Checkout `yfd-bot-telegram`, email **sama** | Order paid, 1 lisensi baru |
| B3 | Bot: `/activate KODE-LISENSI` | Aktivasi sukses |
| B4 | Portal login (email + lisensi) atau `/web` | Masuk dashboard bot |
| B5 | Menu Baseline / dashboard | Tahap keuangan dari check-up **sudah terhubung** (tidak perlu isi ulang 16 soal landing) |
| B6 | Banner FTSA di dashboard | Tampil “Beli FTSA Premium” (jika belum beli FTSA) |

**Pass:** Satu lisensi bot; diagnostik landing claim by email.

---

## C — Beli Bot saja (tanpa check-up landing)

**Tujuan:** Jalur bot-first klasik.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| C1 | Checkout `yfd-bot-telegram`, email `qa-c1@…` | Paid + lisensi |
| C2 | Coba buka `/portal` tanpa `/activate` | Login gagal / minta aktivasi bot |
| C3 | `/activate KODE` di bot | Sukses |
| C4 | Buka `/portal/` (dashboard) | Redirect ke **Baseline Data** (belum ada baseline) |
| C5 | Isi Baseline (diagnostik + snapshot) | Simpan sukses → dashboard aktif |
| C6 | Sidebar | INPUT DATA, FINANCIAL HEALTH, BEHAVIORAL tampil |
| C7 | Cek `expires_at` lisensi di admin | **NULL** (selamanya) |

**Pass:** Middleware `portal.baseline` memblokir dashboard kosong; FTSA terkunci sampai upgrade.

---

## D — Beli FTSA saja

**Tujuan:** Portal terbatas FTSA, tanpa bot.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| D1 | Checkout `yfd-ftsa-premium`, email `qa-d1@…` | Paid + lisensi FTSA |
| D2 | Portal login (email + lisensi), **tanpa** `/activate` | Sukses → redirect **FTSA 1–32** atau Behavioral |
| D3 | Form baseline | **Tanpa** blok “Baseline Data Keuangan”; hanya FTSA 1–32 |
| D4 | Isi FTSA 1–32 → simpan | Redirect ke Behavioral dashboard |
| D5 | Sidebar | **Tidak ada** INPUT DATA & FINANCIAL HEALTH |
| D6 | Coba buka `/portal/` langsung | Redirect ke Behavioral (bukan dashboard bot) |
| D7 | Banner opsional check-up | Link ke `/check-up` (diagnostik opsional) |
| D8 | Bot: `/activate KODE-FTSA` | **Ditolak** — belum termasuk paket bot |

**Pass:** FTSA-only scope; bot activation blocked.

---

## E — Bot dulu → upgrade FTSA

**Tujuan:** Satu lisensi bot; FTSA menempel tanpa kode baru.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| E1 | Selesaikan skenario **C** dengan `qa-e1@…` | Bot aktif + baseline |
| E2 | Checkout `yfd-ftsa-premium`, email **sama** | Paid |
| E3 | Cek admin → 2 order, **1 lisensi** (`license_id` sama) | Kode lisensi **tidak berubah** |
| E4 | Email / halaman sukses | Copy “FTSA aktif pada lisensi bot yang sama” |
| E5 | Portal → Baseline / FTSA | FTSA 1–32 unlocked; bisa isi |
| E6 | Behavioral dashboard | Profil FTSA muncul setelah isi |
| E7 | `/activate` ulang | Tidak perlu (bot sudah aktif) |

**Pass:** `is_ftsa_upgrade` = true; satu kode lisensi.

---

## F — FTSA dulu → beli Bot (unified license)

**Tujuan:** Lisensi FTSA dipakai ulang; migrasi data saat `/activate`.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| F1 | Selesaikan skenario **D** dengan `qa-f1@…` | FTSA terisi, portal behavioral |
| F2 | (Opsional) Check-up landing dengan email sama | Data diagnostik tersimpan |
| F3 | Checkout `yfd-bot-telegram`, email **sama** | Paid |
| F4 | Admin: 2 order, **1 lisensi**, kode = kode FTSA | `expires_at` lisensi → **NULL** |
| F5 | Halaman sukses / email | “Kode sama dengan FTSA” |
| F6 | Portal login (belum `/activate`) | Error: minta `/activate` di bot dulu |
| F7 | Bot: `/activate KODE-FTSA-YANG-SAMA` | Sukses + pesan migrasi data (jika ada data sintetis) |
| F8 | Portal login atau `/web` | Dashboard **lengkap** (bot + FTSA) |
| F9 | Baseline / Hasil FTSA | Data FTSA & diagnostik **masih ada** (tidak hilang) |
| F10 | Sidebar | Semua menu bot + FTSA |

**Pass:** `is_bot_after_ftsa` = true; migrasi synthetic → Telegram ID.

---

## G — Keamanan: FTSA tidak boleh aktifkan bot

**Tujuan:** Pastikan lisensi FTSA-only tidak membuka bot tanpa pembayaran.

| Langkah | Aksi | Hasil yang diharapkan |
|---------|------|------------------------|
| G1 | Setelah **D** (belum beli bot) | — |
| G2 | `/activate` di bot dengan kode FTSA | Pesan: belum termasuk paket YFD Bot |
| G3 | `/catat` transaksi | Minta aktivasi / ditolak |
| G4 | Portal → `/portal/transaksi` | Redirect (tanpa akses bot) |

**Pass:** Entitlement bot hanya dari order `yfd-bot-telegram` paid.

---

## Checklist per area fitur

### Landing check-up (`/check-up`)

- [ ] Wizard 16 langkah dari DB/config
- [ ] Hasil `/check-up/hasil` tampil skor + tahap
- [ ] Email tersimpan di `financial_baselines`

### Portal login (`/portal/login`)

- [ ] FTSA-only: login tanpa `/activate`
- [ ] Bot: wajib `/activate` dulu (kecuali auto-login `/web`)
- [ ] FTSA+bot belum activate: pesan jelas arahkan ke `/activate`

### Redirect setelah login

| Profil | Belum baseline/FTSA | Sudah lengkap |
|--------|----------------------|---------------|
| Bot only | → Baseline create | → Dashboard |
| FTSA only | → FTSA form / Behavioral | → Behavioral |
| Bot + FTSA | → sesuai kebutuhan FTSA/baseline | → Dashboard |

### Admin panel

- [ ] `/admin/orders` — status paid, `license_id` benar
- [ ] `/admin/diagnostic-results` — jawaban check-up
- [ ] `/admin/ftsa-results` — jawaban 1–32
- [ ] Satu email + skenario E/F: **satu** `license_id` di semua order terkait

---

## Verifikasi database (opsional)

Ganti email dan jalankan di MySQL:

```sql
-- Order & lisensi per email
SELECT o.order_code, o.status, o.email, dp.code AS product,
       l.license_key, l.assigned_user_id, l.expires_at
FROM orders o
LEFT JOIN cp_digital_products dp ON dp.id = o.digital_product_id
LEFT JOIN licenses l ON l.id = o.license_id
WHERE LOWER(o.email) = 'qa-f1@contoh.com'
ORDER BY o.id;

-- Baseline terhubung
SELECT id, email, telegram_user_id, financial_stage,
       dominant_archetype, assessed_at
FROM financial_baselines
WHERE LOWER(email) = 'qa-f1@contoh.com'
   OR telegram_user_id = (
       SELECT assigned_user_id FROM licenses
       WHERE license_key = 'TFB-XXXX-XXXX-XXXX' LIMIT 1
   )
ORDER BY assessed_at DESC;
```

**Synthetic ID FTSA-only:** `assigned_user_id` ≥ `9_000_000_000_000`  
**Setelah `/activate` bot:** `assigned_user_id` = ID Telegram (angka kecil)

---

## Urutan testing yang disarankan

1. **G** (keamanan) — cepat, pastikan tidak ada lobang  
2. **A → B** (diagnostik + bot)  
3. **C** (bot only)  
4. **D** (FTSA only)  
5. **E** (bot → FTSA)  
6. **F** (FTSA → bot) — skenario paling kompleks  

Estimasi: **2–3 jam** manual penuh (termasuk menunggu webhook Midtrans atau mark-paid admin).

---

## Troubleshooting cepat

| Gejala | Cek |
|--------|-----|
| `/activate` gagal “server” | `BOT_INTERNAL_API_TOKEN`, `LARAVEL_APP_URL`, restart bot |
| FTSA-only masuk ke check-up landing | `git pull`, clear config, login ulang |
| Dua lisensi untuk email sama | Order lama sebelum deploy unified license |
| Data FTSA hilang setelah `/activate` | Pastikan commit `743e4c0+`; cek migrasi di log Laravel |
| Dashboard bot tanpa baseline | Normal — isi Baseline Data dulu |

---

## Catatan regresi setelah deploy

Setiap release yang menyentuh `Portal*`, `License*`, `Baseline*`, atau `bot.py`:

- [ ] Ulangi minimal **D**, **F**, **G** (15 menit smoke test)
- [ ] Satu transaksi `/catat` + muncul di portal
- [ ] Satu check-up landing + satu login portal dengan email sama
