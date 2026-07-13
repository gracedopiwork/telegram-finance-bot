"""Generate updated freelance contract PDF for YFD Finance Bot."""

from datetime import date
from pathlib import Path

from fpdf import FPDF
from fpdf.enums import XPos, YPos

OUT = Path(__file__).resolve().parents[1] / "docs" / "KONTRAK_KERJA_FREELANCE_YFD_2026.pdf"

# Windows fonts with Latin support
FONT_REG = r"C:\Windows\Fonts\arial.ttf"
FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"


class ContractPDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("ArialUni", "", FONT_REG)
        self.add_font("ArialUni", "B", FONT_BOLD)
        self.set_auto_page_break(auto=True, margin=18)

    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("ArialUni", "", 8)
        self.set_text_color(120, 120, 120)
        self.cell(
            0,
            5,
            "Kontrak Kerja Freelance - YFD Finance Bot (Ecosystem)",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-14)
        self.set_font("ArialUni", "", 8)
        self.set_text_color(130, 130, 130)
        self.cell(0, 8, f"Halaman {self.page_no()}/{{nb}}", align="C")

    def h1(self, text: str):
        self.set_font("ArialUni", "B", 14)
        self.set_text_color(20, 80, 45)
        self.multi_cell(0, 7, text, align="C")
        self.ln(2)

    def h2(self, text: str):
        self.set_font("ArialUni", "B", 11)
        self.set_text_color(20, 80, 45)
        self.cell(0, 8, text, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(20, 20, 20)

    def p(self, text: str, bold: bool = False):
        self.set_x(self.l_margin)
        self.set_font("ArialUni", "B" if bold else "", 10)
        self.set_text_color(20, 20, 20)
        self.multi_cell(self.epw, 5.5, text)
        self.ln(1.5)

    def bullet(self, text: str):
        self.set_font("ArialUni", "", 10)
        self.set_text_color(20, 20, 20)
        left = self.l_margin
        self.set_x(left + 4)
        self.multi_cell(self.epw - 4, 5.5, f"- {text}")
        self.set_x(left)

    def pasal(self, nomor: str, judul: str):
        self.ln(2)
        self.set_font("ArialUni", "B", 11)
        self.set_text_color(20, 80, 45)
        self.cell(0, 7, f"PASAL {nomor}", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_font("ArialUni", "B", 10)
        self.cell(0, 6, judul, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(20, 20, 20)
        self.ln(1)


def main():
    today = date(2026, 7, 13)
    hari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"][today.weekday()]
    bulan = [
        "",
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ][today.month]

    pdf = ContractPDF()
    pdf.alias_nb_pages()
    pdf.add_page()

    pdf.h1("KONTRAK KERJA FREELANCE")
    pdf.h1("PENGEMBANGAN SISTEM YFD FINANCE BOT")
    pdf.set_font("ArialUni", "B", 11)
    pdf.multi_cell(0, 6, "(ECOSYSTEM VERSION)", align="C")
    pdf.ln(2)
    pdf.p(
        "Perjanjian untuk membangun sistem bot keuangan pribadi berbasis Telegram, "
        "website company profile, lisensi SaaS, pembayaran Midtrans, "
        "dan dashboard web pengguna (portal transaksi & analitik)."
    )
    pdf.p(
        f"Pada hari ini, {hari} tanggal {today.day:02d} bulan {bulan} tahun {today.year}, "
        "telah disepakati perjanjian kerja freelance antara:"
    )

    pdf.h2("PIHAK PERTAMA (Klien)")
    pdf.p("Nama: Ayuti Bulaan")
    pdf.p("Brand/Perusahaan: YFD (Your Financial Doctor)")
    pdf.p("Alamat: Bali")
    pdf.p("No. HP/Email: _______________________________")
    pdf.p("Selanjutnya disebut sebagai PIHAK PERTAMA.")

    pdf.h2("PIHAK KEDUA (Freelancer / Developer)")
    pdf.p("Nama: Grace Yoby Dopi")
    pdf.p("Alamat: Wajo")
    pdf.p("No. HP/Email: gracedopi.work@gmail.com")
    pdf.p("Selanjutnya disebut sebagai PIHAK KEDUA.")

    # PASAL 1
    pdf.pasal("1", "RUANG LINGKUP PEKERJAAN")
    pdf.p(
        'PIHAK KEDUA sepakat untuk mengembangkan dan menyerahkan sistem '
        '"YFD Finance Bot - Ecosystem Version" dengan cakupan sebagai berikut:'
    )
    pdf.p("A. Bot Telegram Finance", bold=True)
    for item in [
        "Bot pencatatan keuangan berbasis Telegram",
        "Input teks natural language (contoh: makan malam 50rb)",
        "AI parsing menggunakan Claude AI (Anthropic)",
        "Fallback parser lokal jika AI gagal / kuota habis",
        "Kategori & sub-kategori otomatis sesuai aturan YFD",
        "Tagging sifat (Need/Wants/Saving/Donation), mood, dan impulsif",
        "OCR / pembacaan foto struk via Claude AI Vision + konfirmasi user",
        "Perintah: /start, /activate, /catat, /hariini, /hapuskilat, /kuota, /web",
        "Onboarding nama panggilan setelah aktivasi lisensi",
        "Penyimpanan transaksi ke database MySQL via API Laravel (bukan Google Sheets)",
        "Link masuk dashboard web otomatis via /web",
    ]:
        pdf.bullet(item)

    pdf.p("B. Sistem SaaS, Lisensi & Pembayaran", bold=True)
    for item in [
        "Sistem aktivasi lisensi (/activate) mengikat 1 kode ke 1 akun Telegram",
        "Checkout website + integrasi pembayaran Midtrans (webhook settlement)",
        "Pembuatan lisensi otomatis setelah pembayaran lunas",
        "Email/WA delivery: kode lisensi, link bot Telegram, akses dashboard web",
        "Integrasi WA opsional (Fonnte) jika dikonfigurasi oleh PIHAK PERTAMA",
    ]:
        pdf.bullet(item)

    pdf.p("C. Website, Portal User, Admin & Ecosystem", bold=True)
    for item in [
        "Website company profile (home, tentang, layanan, paket, penasihat, produk, Wealthpedia, informasi)",
        "Portal pengguna (dashboard web): transaksi, dashboard keuangan, perilaku/emosi, baseline/diagnostik",
        "Import CSV transaksi (opsional) dari file eksternal ke portal",
        "CMS admin: settings, paket, layanan, tim dokter, FAQ, artikel, produk digital",
        "Manajemen orders (status, resend email/delivery)",
        "Monitoring status AI Claude di admin (sukses / rate limit / fallback / kuota)",
        "Struktur monorepo modular (bot Python + Laravel + MySQL bersama)",
    ]:
        pdf.bullet(item)

    pdf.p("D. Keamanan & Infrastruktur", bold=True)
    for item in [
        "Setup / hardening dasar di VPS (Nginx, SSL, systemd untuk bot & queue)",
        "Setup database MySQL bersama (bot + website + portal)",
        "Dokumentasi setup & operasi (.env.example, deployment guide)",
        "Pengamanan secret via environment variables",
    ]:
        pdf.bullet(item)

    pdf.p("E. Yang tidak termasuk dalam kontrak ini", bold=True)
    for item in [
        "Voice note / speech-to-text produksi (dapat add-on)",
        "Aplikasi mobile native (iOS/Android)",
        "Multi-seat lisensi (1 kode untuk banyak user Telegram)",
        "Integrasi Google Sheets sebagai penyimpanan utama (sistem sudah full web)",
        "Insight/coaching AI mingguan otomatis di luar fitur portal yang sudah ada",
        "Biaya VPS, domain, Claude AI API, Midtrans fee, SMTP, WA, dan layanan pihak ketiga",
        "Maintenance bulanan setelah masa support berakhir",
    ]:
        pdf.bullet(item)

    # PASAL 2
    pdf.pasal("2", "NILAI PROYEK")
    pdf.p("Total nilai proyek pengembangan:", bold=True)
    pdf.p("Rp 10.000.000", bold=True)
    pdf.p("(Terbilang: Sepuluh Juta Rupiah)")
    pdf.p("Nilai tersebut mencakup: development, integrasi, testing, deployment dasar, dan support awal sesuai Pasal 6.")
    pdf.p(
        "Tidak termasuk: biaya VPS Hostinger, domain, API Claude AI (Anthropic), biaya Midtrans, "
        "biaya email/WA, dan layanan pihak ketiga lainnya."
    )

    # PASAL 3
    pdf.pasal("3", "SISTEM PEMBAYARAN")
    pdf.p("Pembayaran dilakukan dengan skema:")
    pdf.p("Tahap 1 - DP (42,5%)", bold=True)
    pdf.p("Sebesar Rp 4.250.000 sebelum pengerjaan dimulai / sebagai pembayaran awal.")
    pdf.p("Tahap 2 - Pelunasan (57,5%)", bold=True)
    pdf.p("Sebesar Rp 5.750.000 setelah sistem selesai dan sebelum serah terima final.")
    pdf.p("Pengerjaan / serah terima final mengikuti kesepakatan setelah pembayaran DP diterima oleh PIHAK KEDUA.")
    pdf.p("Pembayaran ditransfer ke rekening yang ditunjuk PIHAK KEDUA (akan diinformasikan terpisah).")

    # PASAL 4
    pdf.pasal("4", "TIMELINE PENGERJAAN")
    pdf.p("Estimasi pengerjaan total: +/- 6 minggu (dapat menyesuaikan jika scope sudah sebagian selesai / UAT).")
    pdf.p("Milestone indikatif:")
    for item in [
        "Minggu 1: Setup VPS/database MySQL, fondasi bot Telegram, arsitektur dasar",
        "Minggu 2: AI Claude parsing, kategori, simpan transaksi ke API/web DB",
        "Minggu 3: Midtrans, lisensi otomatis, email/WA delivery",
        "Minggu 4: Website company profile + CMS admin + orders",
        "Minggu 5: Portal user (dashboard web) + monitoring AI Claude",
        "Minggu 6: QA, bug fixing, final deployment, launching, handover",
    ]:
        pdf.bullet(item)
    pdf.p("PIHAK KEDUA wajib memberikan update progress kepada PIHAK PERTAMA selama masa pengerjaan.")

    # PASAL 5
    pdf.pasal("5", "KEPEMILIKAN SOURCE CODE & SISTEM")
    pdf.p("Setelah pelunasan dilakukan:")
    for item in [
        "Seluruh source code menjadi milik penuh PIHAK PERTAMA",
        "Database user menjadi milik penuh PIHAK PERTAMA",
        "Domain, VPS, API, dan seluruh akses utama berada di bawah kepemilikan PIHAK PERTAMA",
        "PIHAK KEDUA wajib membantu proses handover sistem",
    ]:
        pdf.bullet(item)
    pdf.p("PIHAK KEDUA tidak diperkenankan:")
    for item in [
        "menjual ulang source code tanpa izin",
        "membagikan data user",
        "atau menggunakan sistem untuk kepentingan lain tanpa persetujuan PIHAK PERTAMA",
    ]:
        pdf.bullet(item)

    # PASAL 6
    pdf.pasal("6", "SUPPORT & MAINTENANCE")
    pdf.p("PIHAK KEDUA memberikan support, monitoring, dan bug fixing selama:")
    pdf.p("2 Bulan Setelah Launch", bold=True)
    pdf.p("Support mencakup: bug/error sistem, troubleshooting, minor adjustment.")
    pdf.p("Support tidak mencakup: penambahan fitur baru, redesign besar, perubahan konsep bisnis, perubahan arsitektur sistem.")
    pdf.p("Maintenance lanjutan dapat dibuat melalui perjanjian terpisah.")

    # PASAL 7
    pdf.pasal("7", "REVISI")
    pdf.p("PIHAK PERTAMA berhak mendapatkan revisi minor selama masa pengerjaan.")
    pdf.p("Yang termasuk revisi: penyesuaian tampilan minor, perbaikan bug, penyesuaian flow kecil.")
    pdf.p("Yang tidak termasuk revisi: perubahan konsep utama, penambahan fitur besar, perubahan sistem di luar ruang lingkup Pasal 1.")

    # PASAL 8
    pdf.pasal("8", "BIAYA OPERASIONAL SISTEM")
    pdf.p("Biaya operasional setelah launch menjadi tanggung jawab PIHAK PERTAMA.")
    pdf.p("Estimasi (Hostinger VPS KVM 2, berdasarkan checkout aktual Juli 2026):")
    for item in [
        "Paket KVM 2 durasi 12 bulan: Rp 2.170.800",
        "Pajak VPS: Rp 238.788",
        "Domain: Rp 250.000 / tahun",
        "Total tahun ini (VPS + pajak + domain): Rp 2.659.588",
        "Setara VPS+pajak: +/- Rp 201.000/bulan",
        "SSL Let's Encrypt: gratis",
        "API Claude AI (Anthropic): mengikuti penggunaan / paket yang dipilih",
        "Midtrans fee: mengikuti ketentuan Midtrans per transaksi",
    ]:
        pdf.bullet(item)
    pdf.p("Detail perhitungan VPS dilampirkan pada dokumen terpisah ESTIMASI_BIAYA_VPS_HOSTINGER.pdf. Harga perpanjangan tahun berikutnya dapat lebih tinggi.")

    # PASAL 9
    pdf.pasal("9", "PEMBATALAN PROYEK")
    pdf.p("Apabila proyek dibatalkan oleh PIHAK PERTAMA setelah pengerjaan dimulai: pembayaran DP dianggap hangus.")
    pdf.p(
        "Apabila proyek gagal diselesaikan karena kelalaian PIHAK KEDUA: "
        "PIHAK KEDUA wajib mengembalikan pembayaran sesuai proporsi pekerjaan yang belum selesai."
    )

    # PASAL 10
    pdf.pasal("10", "PENUTUP")
    pdf.p(
        "Kontrak ini dibuat secara sadar dan disetujui kedua belah pihak tanpa paksaan. "
        "Dengan menandatangani kontrak ini, kedua pihak menyatakan setuju terhadap seluruh isi perjanjian."
    )
    pdf.ln(6)

    # Signatures
    col_w = 90
    y0 = pdf.get_y()
    pdf.set_font("ArialUni", "B", 10)
    pdf.cell(col_w, 6, "PIHAK PERTAMA", align="C")
    pdf.cell(col_w, 6, "PIHAK KEDUA", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("ArialUni", "", 10)
    pdf.cell(col_w, 6, "(YFD)", align="C")
    pdf.cell(col_w, 6, "(Freelancer / Developer)", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(22)
    pdf.cell(col_w, 6, "Nama: Ayuti Bulaan", align="C")
    pdf.cell(col_w, 6, "Nama: Grace Yoby Dopi", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.cell(col_w, 6, "Tanda Tangan: _______________", align="C")
    pdf.cell(col_w, 6, "Tanda Tangan: _______________", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.cell(col_w, 6, f"Tanggal: {today.day:02d}/{today.month:02d}/{today.year}", align="C")
    pdf.cell(col_w, 6, f"Tanggal: {today.day:02d}/{today.month:02d}/{today.year}", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"PDF written: {OUT}")


if __name__ == "__main__":
    main()
