"""Proposal add-on: Referral/Affiliate + Marketplace digital YFD."""

from datetime import date
from pathlib import Path

from fpdf import FPDF
from fpdf.enums import XPos, YPos

OUT = Path(__file__).resolve().parents[1] / "docs" / "PROPOSAL_ADDON_REFERRAL_MARKETPLACE_YFD.pdf"

FONT_REG = r"C:\Windows\Fonts\arial.ttf"
FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"


class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("A", "", FONT_REG)
        self.add_font("A", "B", FONT_BOLD)
        self.set_auto_page_break(auto=True, margin=16)

    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("A", "", 8)
        self.set_text_color(120, 120, 120)
        self.cell(
            0,
            5,
            "Proposal Add-on YFD - Referral & Marketplace",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-12)
        self.set_font("A", "", 8)
        self.set_text_color(120, 120, 120)
        self.cell(0, 8, f"Halaman {self.page_no()}/{{nb}}", align="C")

    def h1(self, text: str):
        self.set_font("A", "B", 14)
        self.set_text_color(20, 80, 45)
        self.multi_cell(self.epw, 7, text, align="C")
        self.ln(2)

    def section(self, title: str):
        self.ln(2)
        self.set_font("A", "B", 11)
        self.set_text_color(20, 80, 45)
        self.set_x(self.l_margin)
        self.cell(0, 7, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(30, 30, 30)

    def p(self, text: str, bold: bool = False):
        self.set_x(self.l_margin)
        self.set_font("A", "B" if bold else "", 10)
        self.multi_cell(self.epw, 5.4, text)
        self.ln(1.2)

    def bullet(self, text: str):
        self.set_x(self.l_margin + 3)
        self.set_font("A", "", 10)
        self.multi_cell(self.epw - 3, 5.3, f"- {text}")

    def table(self, headers, rows, widths):
        self.set_font("A", "B", 8)
        self.set_fill_color(230, 245, 235)
        for h, w in zip(headers, widths):
            self.cell(w, 6.5, h, border=1, fill=True, align="C")
        self.ln()
        self.set_font("A", "", 8)
        for i, row in enumerate(rows):
            self.set_fill_color(248, 248, 248) if i % 2 else self.set_fill_color(255, 255, 255)
            for cell, w in zip(row, widths):
                align = "L" if w == widths[0] else "C"
                self.cell(w, 6.2, str(cell), border=1, fill=True, align=align)
            self.ln()
        self.ln(2)


def main():
    today = date(2026, 7, 15)
    pdf = PDF()
    pdf.alias_nb_pages()
    pdf.add_page()

    pdf.h1("PROPOSAL ADD-ON PENGEMBANGAN")
    pdf.set_font("A", "B", 12)
    pdf.set_text_color(20, 80, 45)
    pdf.multi_cell(pdf.epw, 6, "Sistem Referral / Affiliate + Marketplace Produk Digital", align="C")
    pdf.set_text_color(30, 30, 30)
    pdf.ln(2)
    pdf.p(f"Tanggal: {today.day:02d}/{today.month:02d}/{today.year}")
    pdf.p("Untuk: YFD (Your Financial Doctor)")
    pdf.p("Dari: Grace Yoby Dopi")
    pdf.p(
        "Dokumen ini merupakan penawaran tambahan (add-on) di luar kontrak Ecosystem "
        "Rp 10.000.000 yang sudah berjalan."
    )

    pdf.section("1. Latar belakang")
    pdf.p(
        "Ada 2 kebutuhan baru yang diajukan:"
    )
    pdf.bullet(
        "Referral/affiliate untuk FTSA: diskon pembeli + komisi referrer + klaim saldo + NPWP/pajak."
    )
    pdf.bullet(
        "Ke depan: jual produk digital milik pihak lain (buku/jurnal/belajar finance), "
        "checkout potong saldo atau bayar biasa (arah marketplace)."
    )
    pdf.p(
        "Keduanya memungkinkan secara teknis di sistem saat ini (Laravel + Midtrans + portal), "
        "tetapi merupakan modul baru dan memperbesar scope."
    )

    pdf.section("2. Paket A - Referral / Affiliate FTSA (disarankan dikerjakan dulu)")
    pdf.p("Ruang lingkup:", bold=True)
    for item in [
        "Kode referral per user/affiliate",
        "Input kode referral saat checkout FTSA",
        "Diskon pembeli Rp 25.000 (contoh launch: 199k menjadi 174k)",
        "Komisi referrer Rp 25.000 masuk saldo dashboard",
        "Halaman saldo + riwayat komisi di portal",
        "Klaim saldo bulanan",
        "Input NPWP saat klaim",
        "Potongan pajak otomatis saat klaim (persentase disepakati)",
        "Admin: pantau affiliate, approve/pencairan klaim, laporan sederhana",
        "Proteksi dasar: cegah self-referral / kode invalid",
    ]:
        pdf.bullet(item)

    pdf.p("Estimasi bisnis (sesuai brief):", bold=True)
    pdf.bullet("Harga normal FTSA: Rp 299.000")
    pdf.bullet("Harga launch: Rp 199.000")
    pdf.bullet("Bayar dengan referral: Rp 174.000")
    pdf.bullet("Komisi affiliate: Rp 25.000")
    pdf.bullet("Net ke YFD sebelum fee Midtrans: Rp 149.000")

    pdf.p("Perkiraan biaya & waktu:", bold=True)
    pdf.table(
        ["Item", "Nilai"],
        [
            ["Biaya pengembangan Paket A", "Rp 3.000.000"],
            ["Durasi", "+/- 2-3 minggu"],
            ["Termin usulan", "DP 50% / Pelunasan 50%"],
        ],
        [90, 100],
    )

    pdf.section("3. Paket B - Marketplace Produk Digital (fase berikutnya)")
    pdf.p("Ruang lingkup:", bold=True)
    for item in [
        "Katalog produk digital pihak ketiga (buku/jurnal/kursus finance, dll.)",
        "Profil penjual / seller sederhana",
        "Checkout produk (Midtrans)",
        "Opsi bayar pakai saldo wallet (jika Paket A sudah ada)",
        "Order & delivery digital (link/file setelah bayar)",
        "Bagi hasil platform vs penjual (persentase disepakati)",
        "Admin: kelola produk seller, order, settlement/payout",
    ]:
        pdf.bullet(item)

    pdf.p("Perkiraan biaya & waktu:", bold=True)
    pdf.table(
        ["Opsi", "Biaya", "Durasi"],
        [
            ["B1 - Toko digital + Midtrans (belum multi-seller penuh)", "Rp 3.000.000", "2 minggu"],
            ["B2 - Marketplace + wallet + bagi hasil seller", "Rp 6.000.000", "3-4 minggu"],
            ["B3 - Full marketplace (lebih lengkap)", "Rp 8.000.000 - 12.000.000", "5-7 minggu"],
        ],
        [95, 50, 45],
    )
    pdf.p(
        "Rekomendasi: kerjakan Paket A dulu. Paket B menyusul setelah flow referral/saldo stabil."
    )

    pdf.section("4. Ringkasan penawaran")
    pdf.table(
        ["Paket", "Isi utama", "Biaya"],
        [
            ["A", "Referral + diskon + saldo + klaim + NPWP/pajak", "Rp 3.000.000"],
            ["B2", "Marketplace digital + wallet + bagi hasil", "Rp 6.000.000"],
            ["A + B2", "Dikerjakan berurutan (bundling)", "Rp 8.500.000*"],
        ],
        [25, 105, 60],
    )
    pdf.p(
        "*Jika A + B2 diambil bersamaan: Rp 8.500.000 (hemat Rp 500.000 dari 3jt + 6jt)."
    )

    pdf.section("5. Yang belum termasuk")
    for item in [
        "Aplikasi mobile native",
        "Pengiriman produk fisik / logistik kurir",
        "Integrasi marketplace eksternal (Shopee/Tokopedia/TikTok Shop resmi)",
        "Biaya Midtrans, Claude AI, VPS, domain, dan layanan pihak ketiga",
        "Konsultasi pajak formal dari konsultan pajak (aturan % pajak ditentukan klien)",
        "Maintenance bulanan setelah masa support add-on",
    ]:
        pdf.bullet(item)

    pdf.section("6. Dukungan setelah selesai")
    pdf.p("Bug fixing terkait add-on: 1 bulan setelah serah terima paket yang dikerjakan.")

    pdf.section("7. Hal yang perlu dikonfirmasi klien")
    for item in [
        "Persentase pajak saat klaim komisi (contoh: berapa % jika ada/ tidak ada NPWP)",
        "Pencairan klaim: transfer manual admin atau otomatis",
        "Referral hanya untuk FTSA atau semua produk",
        "Untuk marketplace: % bagi hasil YFD vs seller",
        "Mulai dari Paket A saja, atau A+B sekaligus",
    ]:
        pdf.bullet(item)

    pdf.section("8. Usulan langkah selanjutnya")
    pdf.p("1) Pilih paket (A / B / A+B).")
    pdf.p("2) Konfirmasi aturan pajak & bagi hasil.")
    pdf.p("3) DP add-on sesuai paket.")
    pdf.p("4) Kickoff pengerjaan.")

    pdf.ln(6)
    pdf.p("Hormat saya,")
    pdf.ln(10)
    pdf.p("Grace Yoby Dopi", bold=True)
    pdf.p("gracedopi.work@gmail.com")

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"PDF written: {OUT}")


if __name__ == "__main__":
    main()
