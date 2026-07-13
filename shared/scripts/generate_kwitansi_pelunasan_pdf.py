"""Generate kwitansi pelunasan proyek YFD Finance Bot."""

from datetime import date
from pathlib import Path

from fpdf import FPDF
from fpdf.enums import XPos, YPos

OUT = Path(__file__).resolve().parents[1] / "docs" / "KWITANSI_PELUNASAN_YFD_2026.pdf"

FONT_REG = r"C:\Windows\Fonts\arial.ttf"
FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"


class PDF(FPDF):
    def __init__(self):
        super().__init__()
        self.add_font("A", "", FONT_REG)
        self.add_font("A", "B", FONT_BOLD)


def line_row(pdf: PDF, label: str, value: str, label_w: float = 45):
    pdf.set_font("A", "B", 11)
    pdf.cell(label_w, 8, label)
    pdf.set_font("A", "", 11)
    pdf.multi_cell(0, 8, value)


def main():
    today = date(2026, 7, 13)
    pdf = PDF()
    pdf.set_auto_page_break(auto=True, margin=20)
    pdf.add_page()

    # Header
    pdf.set_font("A", "B", 18)
    pdf.set_text_color(20, 80, 45)
    pdf.cell(0, 10, "KWITANSI", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("A", "B", 12)
    pdf.set_text_color(30, 30, 30)
    pdf.cell(0, 7, "PELUNASAN PROYEK", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(2)
    pdf.set_draw_color(20, 80, 45)
    pdf.set_line_width(0.6)
    pdf.line(20, pdf.get_y(), 190, pdf.get_y())
    pdf.ln(8)

    pdf.set_font("A", "", 10)
    pdf.cell(0, 6, f"No. Kwitansi: YFD/PL/2026/001", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.cell(0, 6, f"Tanggal: {today.day:02d}/{today.month:02d}/{today.year}", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(6)

    pdf.set_font("A", "", 11)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(pdf.epw, 7, "Telah terima dari:")
    pdf.set_font("A", "B", 11)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(pdf.epw, 7, "Ayuti Bulaan / YFD (Your Financial Doctor)")
    pdf.set_font("A", "", 11)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(pdf.epw, 7, "Alamat: Bali")
    pdf.ln(3)

    pdf.set_x(pdf.l_margin)
    pdf.set_font("A", "", 11)
    pdf.cell(45, 8, "Uang sejumlah")
    pdf.set_font("A", "B", 12)
    pdf.set_fill_color(230, 245, 235)
    pdf.cell(60, 8, "  Rp 5.750.000  ", fill=True, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("A", "", 10)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(pdf.epw, 6, "(Terbilang: Lima Juta Tujuh Ratus Lima Puluh Ribu Rupiah)")
    pdf.ln(3)

    pdf.set_font("A", "", 11)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(pdf.epw, 7, "Untuk pembayaran:")
    pdf.set_font("A", "B", 11)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(
        pdf.epw,
        7,
        "Pelunasan Pengembangan Sistem YFD Finance Bot (Ecosystem Version)",
    )
    pdf.set_font("A", "", 10)
    pdf.ln(2)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(
        pdf.epw,
        6,
        "Rincian kontrak:\n"
        "- Total nilai proyek: Rp 10.000.000\n"
        "- DP (sudah dibayar): Rp 4.250.000\n"
        "- Pelunasan (kwitansi ini): Rp 5.750.000\n"
        "- Status: LUNAS",
    )
    pdf.ln(4)

    pdf.set_draw_color(200, 200, 200)
    pdf.set_line_width(0.3)
    pdf.line(20, pdf.get_y(), 190, pdf.get_y())
    pdf.ln(6)

    pdf.set_font("A", "", 10)
    pdf.multi_cell(
        0,
        6,
        "Dengan diterimanya pembayaran ini, kewajiban pelunasan proyek sebagaimana "
        "kontrak kerja freelance YFD Finance Bot dinyatakan LUNAS.",
    )
    pdf.ln(10)

    # Signatures
    col = 90
    pdf.set_font("A", "B", 10)
    pdf.cell(col, 6, "Yang Menyerahkan", align="C")
    pdf.cell(col, 6, "Yang Menerima", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("A", "", 9)
    pdf.cell(col, 5, "(Klien)", align="C")
    pdf.cell(col, 5, "(Freelancer / Developer)", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(22)
    pdf.set_font("A", "B", 10)
    pdf.cell(col, 6, "Ayuti Bulaan", align="C")
    pdf.cell(col, 6, "Grace Yoby Dopi", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("A", "", 9)
    pdf.cell(col, 5, "YFD", align="C")
    pdf.cell(col, 5, "gracedopi.work@gmail.com", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(4)
    pdf.cell(col, 5, "Tanda tangan: _______________", align="C")
    pdf.cell(col, 5, "Tanda tangan: _______________", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.ln(12)
    pdf.set_font("A", "", 8)
    pdf.set_text_color(120, 120, 120)
    pdf.set_x(pdf.l_margin)
    pdf.multi_cell(
        pdf.epw,
        5,
        "Kwitansi ini sah setelah ditandatangani penerima. Simpan sebagai bukti pelunasan.",
        align="C",
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"PDF written: {OUT}")


if __name__ == "__main__":
    main()
