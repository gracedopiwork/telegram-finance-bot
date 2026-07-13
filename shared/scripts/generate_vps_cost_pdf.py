"""Generate PDF estimasi biaya VPS Hostinger - angka sesuai checkout aktual klien."""

from pathlib import Path

from fpdf import FPDF
from fpdf.enums import XPos, YPos

OUT = Path(__file__).resolve().parents[1] / "docs" / "ESTIMASI_BIAYA_VPS_HOSTINGER.pdf"


class PDF(FPDF):
    def header(self):
        self.set_font("Helvetica", "B", 11)
        self.set_text_color(30, 30, 30)
        self.cell(
            0,
            6,
            "YFD Finance Bot - Estimasi Biaya Operasional VPS",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.set_font("Helvetica", "", 8)
        self.set_text_color(100, 100, 100)
        self.cell(
            0,
            5,
            "Provider: Hostinger VPS KVM 2 | Berdasarkan checkout aktual | Juli 2026",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.ln(2)
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-12)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(120, 120, 120)
        self.cell(
            0,
            8,
            f"Halaman {self.page_no()}/{{nb}} - Harga Hostinger dapat berubah saat perpanjangan",
            align="C",
        )

    def section(self, title: str):
        self.set_font("Helvetica", "B", 12)
        self.set_text_color(20, 90, 50)
        self.cell(0, 8, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(30, 30, 30)
        self.ln(1)

    def body(self, text: str):
        self.set_font("Helvetica", "", 10)
        self.multi_cell(0, 5.5, text)
        self.ln(2)

    def table(self, headers, rows, widths):
        self.set_font("Helvetica", "B", 9)
        self.set_fill_color(230, 245, 235)
        for h, w in zip(headers, widths):
            self.cell(w, 7, h, border=1, fill=True, align="C")
        self.ln()
        self.set_font("Helvetica", "", 8)
        for i, row in enumerate(rows):
            if i % 2:
                self.set_fill_color(248, 248, 248)
            else:
                self.set_fill_color(255, 255, 255)
            for cell, w in zip(row, widths):
                align = "L" if w == widths[0] else "C"
                self.cell(w, 6.5, str(cell), border=1, fill=True, align=align)
            self.ln()
        self.ln(3)


def main():
    pdf = PDF()
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_page()

    pdf.section("1. Ringkasan")
    pdf.body(
        "Dokumen ini merinci biaya VPS Hostinger untuk menjalankan YFD Finance Bot "
        "(website Laravel + bot Telegram Python + MySQL + Nginx). "
        "Angka di bawah mengikuti checkout Hostinger aktual untuk tahun berjalan. "
        "Biaya ini DILUAR kontrak pengembangan Rp 10.000.000 dan ditanggung pemilik produk."
    )

    pdf.section("2. Paket yang dipilih")
    pdf.table(
        ["Item", "Detail"],
        [
            ["Provider", "Hostinger"],
            ["Paket", "VPS KVM 2 (2 vCPU / 8 GB RAM / 100 GB NVMe)"],
            ["Durasi", "12 bulan"],
            ["Cocok untuk", "Website + bot Telegram + MySQL (produksi)"],
        ],
        [45, 145],
    )

    pdf.section("3. Rincian pembayaran tahun ini (checkout Hostinger)")
    pdf.table(
        ["Komponen", "Harga coret", "Yang dibayar"],
        [
            ["Paket KVM 2 (12 bulan)", "Rp 4.094.000", "Rp 2.170.800"],
            ["Nama Domain", "Rp 1.059.900", "Rp 0 (gratis)"],
            ["Proteksi Privasi Domain WHOIS", "-", "Rp 0"],
            ["Pajak", "-", "Rp 238.788"],
            ["TOTAL DIBAYAR TAHUN INI", "Rp 5.153.400*", "Rp 2.409.588"],
        ],
        [75, 50, 65],
    )
    pdf.body(
        "*Harga coret total sebelum diskon (referensi tampilan checkout). "
        "Yang dibayar aktual tahun ini: Rp 2.409.588 untuk 12 bulan."
    )

    pdf.section("4. Setara per bulan (tahun pertama)")
    pdf.table(
        ["Perhitungan", "Jumlah"],
        [
            ["VPS saja (Rp 2.170.800 / 12)", "Rp 180.900 / bulan"],
            ["Total termasuk pajak (Rp 2.409.588 / 12)", "Rp 200.799 / bulan"],
            ["Dibulatkan untuk komunikasi ke klien", "+/- Rp 201.000 / bulan"],
        ],
        [115, 75],
    )

    pdf.section("5. Estimasi operasional bulanan lengkap")
    pdf.table(
        ["Komponen", "Estimasi", "Keterangan"],
        [
            ["VPS Hostinger KVM 2 + pajak*", "Rp 201.000", "Rata-rata tahun 1 (12 bln)"],
            ["Domain", "Rp 0", "Gratis di checkout tahun ini"],
            ["SSL Let's Encrypt", "Rp 0", "Gratis"],
            ["Claude AI API (Anthropic)", "Sesuai pemakaian", "Ditanggung pemilik produk"],
            ["Email SMTP", "Rp 0-50.000", "Opsional"],
            ["Fonnte WA (opsional)", "Sesuai paket", "Jika notifikasi WA dipakai"],
            ["Midtrans fee", "Per transaksi", "Dari penjualan end-user"],
        ],
        [60, 40, 90],
    )
    pdf.body(
        "*Setelah tahun pertama, harga perpanjangan Hostinger biasanya lebih tinggi "
        "dari harga promo. Siapkan cadangan budget saat renew."
    )

    pdf.section("6. Ringkasan untuk keputusan klien")
    pdf.body(
        "1) Kontrak pengembangan sistem: Rp 10.000.000 (sekali / fixed fee).\n"
        "2) Biaya Hostinger tahun ini (KVM 2, 12 bulan + pajak): Rp 2.409.588.\n"
        "3) Setara sekitar Rp 201.000/bulan di tahun pertama.\n"
        "4) Domain termasuk gratis di checkout ini.\n"
        "5) Biaya Claude AI API, Midtrans fee, dan WA (jika dipakai) dihitung terpisah.\n"
        "6) Setup & deploy awal sudah termasuk dalam nilai pengembangan Rp 10.000.000."
    )

    pdf.section("7. Catatan")
    pdf.body(
        "- Dokumen ini mengikuti rincian checkout Hostinger yang ditunjukkan klien/developer.\n"
        "- Bukan invoice resmi Hostinger; simpan bukti pembayaran Hostinger sebagai dokumen formal.\n"
        "- Maintenance bulanan (jika diminta) adalah add-on terpisah dari kontrak pengembangan."
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"PDF written: {OUT}")


if __name__ == "__main__":
    main()
