"""PDF: estimasi biaya VPS Hostinger saat user bertambah hingga 1 juta."""

from pathlib import Path

from fpdf import FPDF
from fpdf.enums import XPos, YPos

OUT = Path(__file__).resolve().parents[1] / "docs" / "ESTIMASI_BIAYA_VPS_HOSTINGER.pdf"

FONT_REG = r"C:\Windows\Fonts\arial.ttf"
FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"


class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("A", "", FONT_REG)
        self.add_font("A", "B", FONT_BOLD)
        self.set_auto_page_break(auto=True, margin=16)

    def header(self):
        self.set_font("A", "B", 11)
        self.set_text_color(30, 30, 30)
        self.cell(
            0,
            6,
            "YFD Finance Bot - Estimasi Biaya VPS",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.set_font("A", "", 8)
        self.set_text_color(100, 100, 100)
        self.cell(
            0,
            5,
            "Hostinger VPS | Skala hingga 1.000.000 user | Juli 2026",
            new_x=XPos.LMARGIN,
            new_y=YPos.NEXT,
        )
        self.ln(2)
        self.set_draw_color(200, 200, 200)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-12)
        self.set_font("A", "", 8)
        self.set_text_color(120, 120, 120)
        self.cell(
            0,
            8,
            f"Halaman {self.page_no()}/{{nb}}",
            align="C",
        )

    def section(self, title: str):
        self.set_font("A", "B", 12)
        self.set_text_color(20, 90, 50)
        self.cell(0, 8, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(30, 30, 30)
        self.ln(1)

    def body(self, text: str):
        self.set_x(self.l_margin)
        self.set_font("A", "", 10)
        self.multi_cell(self.epw, 5.4, text)
        self.ln(2)

    def table(self, headers, rows, widths):
        self.set_font("A", "B", 8)
        self.set_fill_color(230, 245, 235)
        for h, w in zip(headers, widths):
            self.cell(w, 6.5, h, border=1, fill=True, align="C")
        self.ln()
        self.set_font("A", "", 7.5)
        for i, row in enumerate(rows):
            self.set_fill_color(248, 248, 248) if i % 2 else self.set_fill_color(255, 255, 255)
            for cell, w in zip(row, widths):
                align = "L" if w == widths[0] else "C"
                self.cell(w, 6.2, str(cell), border=1, fill=True, align=align)
            self.ln()
        self.ln(3)


def main():
    pdf = PDF()
    pdf.alias_nb_pages()
    pdf.add_page()

    pdf.section("1. Biaya tahun berjalan")
    pdf.table(
        ["Item", "Nilai"],
        [
            ["Provider / paket", "Hostinger VPS KVM 2"],
            ["Spesifikasi", "2 vCPU / 8 GB RAM / 100 GB NVMe"],
            ["Durasi VPS", "12 bulan"],
            ["Paket KVM 2", "Rp 2.170.800"],
            ["Pajak VPS", "Rp 238.788"],
            ["Domain", "Rp 250.000 / tahun"],
            ["TOTAL TAHUN INI (VPS + pajak + domain)", "Rp 2.659.588"],
            ["Setara VPS+pajak / bulan", "Rp 201.000"],
            ["Setara domain / bulan", "Rp 20.833"],
        ],
        [95, 95],
    )

    pdf.section("2. Estimasi VPS saat user bertambah (hingga 1 juta)")
    pdf.table(
        ["User (perkiraan)", "Infrastruktur", "Estimasi /bulan", "Estimasi /tahun"],
        [
            ["1 - 500", "Hostinger KVM 2", "Rp 201.000", "Rp 2.4 jt"],
            ["500 - 2.000", "Hostinger KVM 4", "Rp 250rb - 470rb", "Rp 3 - 5.6 jt"],
            ["2.000 - 10.000", "Hostinger KVM 8", "Rp 430rb - 850rb", "Rp 5 - 10 jt"],
            ["10.000 - 50.000", "VPS besar / 2 server", "Rp 1.5 - 4 jt", "Rp 18 - 48 jt"],
            ["50.000 - 200.000", "Multi-server + DB terpisah", "Rp 5 - 15 jt", "Rp 60 - 180 jt"],
            ["200.000 - 1.000.000", "Cluster / cloud scale-out", "Rp 20 - 80 jt+", "Rp 240 jt - 1 M+"],
        ],
        [40, 55, 45, 50],
    )
    pdf.body(
        "Di atas KVM 8, biasanya tidak cukup 1 VPS saja. "
        "Perlu app server + database server + load balancer + backup. "
        "Renewal Hostinger setelah tahun pertama bisa lebih tinggi dari harga promo."
    )

    pdf.section("3. Catatan kapasitas")
    pdf.body(
        "1) Beban server mengikuti user AKTIF yang sering catat, bukan hanya akun terdaftar.\n"
        "2) Bot + portal web + MySQL berjalan di server yang sama pada KVM 2/4/8.\n"
        "3) Saat user besar, database biasanya dipisah dari aplikasi.\n"
        "4) Domain tetap +/- Rp 250.000 / tahun (terpisah dari upgrade VPS).\n"
        "5) Biaya Claude AI API dihitung terpisah sesuai pemakaian."
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"PDF written: {OUT}")


if __name__ == "__main__":
    main()
