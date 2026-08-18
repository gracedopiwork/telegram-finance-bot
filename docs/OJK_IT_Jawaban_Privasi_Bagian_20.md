# Jawaban IT — Bagian 20 Data Privacy (untuk OJK)

Sumber pertanyaan: *YFD First Aid Data Privacy, User Access & Informed Consent* revisi 15 Agustus 2026, Bagian 20.  
Dijawab dari implementasi kode saat ini (Agustus 2026). Bukan jaminan legal; untuk dilengkapi lawyer jika perlu.

## 1. Bagaimana mekanisme authentication user saat ini?

Ada dua pintu, terpisah:

- **Telegram bot:** user memasukkan kode lisensi (`/activate`). Server memverifikasi lisensi lalu mengikat Telegram user ID ke lisensi. Pesan bot hanya diproses jika lisensi aktif.
- **Portal web:** sesi server-side (cookie Laravel). Login memakai **email + kode lisensi** atau **email + password** yang user buat sendiri. Tanpa sesi valid, middleware `portal.auth` mengarahkan ke halaman login.

API internal bot → Laravel memakai token rahasia (`BOT_INTERNAL_API_TOKEN` / header `X-Bot-Token` atau Bearer). Token ini **bukan** login user; hanya untuk server bot.

## 2. Apa unique identifier utama setiap user?

- **Internal user_id operasional:** `telegram_user_id` (angka Telegram). Semua transaksi, baseline, dan dashboard keuangan difilter dengan ID ini.
- **Pengenal portal:** email pembelian/lisensi (huruf kecil), terhubung ke `telegram_user_id` setelah aktivasi.
- Telegram ID **tidak** dipakai sebagai satu-satunya kunci di URL publik; portal memakai sesi, bukan `?user_id=` di query string.

## 3. Bagaimana Telegram terhubung dengan dashboard web?

1. User beli produk → dapat kode lisensi (email).
2. User `/activate` di bot → API lisensi menyimpan `telegram_user_id` pada lisensi.
3. User login portal dengan email + lisensi/password → sesi diisi `telegram_user_id` + email.
4. Dashboard hanya memuat transaksi/baseline milik `telegram_user_id` di sesi itu.

## 4. Apakah backend sudah melakukan ownership / authorization check?

Ya, di jalur portal:

- Middleware `portal.auth` wajib sesi.
- Transaksi, likuiditas sosial, dan data dashboard di-query dengan `telegram_user_id` sesi.
- Beberapa aksi (hapus transaksi, update piutang) menolak jika ID record ≠ ID sesi.

API bot tidak menerima “saya user X” dari browser; hanya server bot ber-token yang boleh menulis transaksi.

## 5. Apakah URL atau parameter dapat dimanipulasi untuk mengakses data user lain?

Portal tidak menampilkan data lewat `/portal/transaksi/{id}` tanpa cek pemilik. Mengganti ID di URL/request ditolak jika bukan milik sesi.  
Dashboard tidak menerima `telegram_user_id` dari query string user.

**Catatan jujur untuk OJK:** panel **admin internal** (bukan portal user) dapat melihat hasil diagnostik/FTSA untuk keperluan layanan. Ini bukan akses antar-user di dashboard. Mekanisme “minta izin per kasus lalu buka data minimum” masih prosedural/manual, belum tombol consent per tiket di produk.

## 6. Bagaimana mekanisme permintaan akses internal setelah user memberikan persetujuan?

Saat ini **manual**: user/tim menghubungi WhatsApp Admin YFD `+62 851-1122-8911`. Belum ada alur self-service “izinkan admin melihat akun saya selama 24 jam”. Sesuai dokumen privasi Bagian 10/19, ini disengaja di tahap awal (SOP + SLA, bukan fitur otomatis).

## 7. Bagaimana sistem membatasi akses internal hanya pada data minimum yang diperlukan?

Belum ada pemisahan teknis “hanya transaksi bulan ini” vs “seluruh riwayat” untuk akses admin. Pembatasan minimum-necessary masih **kebijakan operasional** (siapa boleh buka panel admin), bukan ACL per-field. Direncanakan menyusul flag data spesifik di database (Catatan IT Bagian 2 & 19).

## 8. Bagaimana consent disimpan dan diberi versioning (Lapis 1 & Lapis 2)?

- **Lapis 1 (pra-pembelian):** teks ringkasan kebijakan di halaman beli / tautan kebijakan lengkap. Tidak terikat `user_id` (belum ada akun).
- **Lapis 2 (pasca-aktivasi):** syarat produk — checkbox consent di bot sebelum onboarding. **Record consent terikat user_id** (`consent_version`, waktu, metode Bot/Web) masih dalam implementasi; kebijakan user-facing sudah ada di portal *Akun, privacy dan panduan*.

Kalau OJK bertanya “apakah sudah ada log consent per user hari ini”: sebutkan jujur status rollout bot Lapis 2, dan bahwa teks kebijakan versi `1.1` (14 Agustus 2026) sudah dipublikasikan di portal.

## 9. Di mana data First Aid di-hosting — Indonesia atau luar negeri?

Aplikasi dan database produksi dijalankan di **VPS (Hostinger), wilayah yang dipakai YFD untuk server Indonesia**.  
Layanan pihak ketiga di luar negeri yang ikut memproses data:

- **Anthropic (Claude)** — parsing transaksi / sebagian teks guidance (data transaksi & narasi dikirim ke API).
- **Telegram** — pesan chat.
- **Midtrans** — data pembayaran checkout.

Transfer ke pemroses luar negeri perlu dicek legal (UU PDP transfer lintas negara). Ini poin untuk lawyer, bukan hanya IT.

## 10. Siapa yang menerima alert pertama kali jika ada indikasi insiden, dan bagaimana sampai ke penanggung jawab data dalam hitungan jam?

- **Penanggung jawab data (dokumen):** dr. Ayuti Bulaan, QWP & dr. Catherina, QWP — kontak operasional WhatsApp Admin `+62 851-1122-8911`.
- **Deteksi teknis saat ini:** log server / health check; **belum ada** pipeline otomatis “jam pertama insiden diketahui” dengan countdown 72 jam.
- **Kewajiban 3×24 jam (Pasal 46 UU PDP)** tetap berlaku. Yang wajib disiapkan segera (bukan fitur user): SOP internal, template notifikasi user, draf lapor otoritas, dan satu orang on-call yang mencatat timestamp “pertama kali diketahui”.

---

### Ringkas untuk slide OJK

| Topik | Status |
|---|---|
| Isolasi data antar user di portal | Ada (sesi + filter `telegram_user_id`) |
| Auth bot vs web | Ada, terpisah |
| Consent user-facing | Teks kebijakan di portal; record Lapis 2 menyusul |
| Akses internal berizin per kasus | Manual via WA, belum ACL otomatis |
| Hosting app/DB | VPS Indonesia |
| Subprocessor luar negeri | Claude, Telegram, Midtrans |
| Notifikasi breach 72 jam | Kewajiban hukum ada; tooling deteksi masih SOP/manual |
