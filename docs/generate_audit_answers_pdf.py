"""Generate PDF: jawaban audit website funnel & analytics untuk YFD."""

from pathlib import Path

from fpdf import FPDF

OUT = Path(__file__).resolve().parents[1] / "docs" / "Jawaban_Audit_Analytics_Website_Funnel.pdf"
FONT = Path(r"C:\Windows\Fonts\arial.ttf")
FONT_BOLD = Path(r"C:\Windows\Fonts\arialbd.ttf")


class Pdf(FPDF):
    def footer(self) -> None:
        self.set_y(-12)
        self.set_font("Body", size=8)
        self.set_text_color(120, 120, 120)
        self.cell(0, 8, f"Halaman {self.page_no()}/{{nb}}", align="C")


def h1(pdf: Pdf, text: str) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_font("BodyBold", size=14)
    pdf.set_text_color(15, 40, 80)
    pdf.multi_cell(0, 7, text)
    pdf.ln(2)


def h2(pdf: Pdf, text: str) -> None:
    pdf.ln(2)
    pdf.set_x(pdf.l_margin)
    pdf.set_font("BodyBold", size=11)
    pdf.set_text_color(20, 60, 110)
    pdf.multi_cell(0, 6, text)
    pdf.ln(1)


def body(pdf: Pdf, text: str) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Body", size=10)
    pdf.set_text_color(30, 30, 30)
    pdf.multi_cell(0, 5.2, text)
    pdf.ln(1)


def bullet(pdf: Pdf, text: str) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Body", size=10)
    pdf.set_text_color(30, 30, 30)
    pdf.multi_cell(0, 5.2, f"• {text}")


def qa(pdf: Pdf, num: int, question: str, answer: str) -> None:
    pdf.set_x(pdf.l_margin)
    pdf.set_font("BodyBold", size=10)
    pdf.set_text_color(15, 40, 80)
    pdf.multi_cell(0, 5.2, f"{num}. {question}")
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Body", size=10)
    pdf.set_text_color(30, 30, 30)
    pdf.multi_cell(0, 5.2, f"Jawaban: {answer}")
    pdf.ln(1.5)


def main() -> None:
    if not FONT.exists() or not FONT_BOLD.exists():
        raise SystemExit(f"Font Arial tidak ditemukan: {FONT}")

    pdf = Pdf(format="A4")
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=16)
    pdf.add_font("Body", fname=str(FONT))
    pdf.add_font("BodyBold", fname=str(FONT_BOLD))
    pdf.add_page()
    pdf.set_margins(16, 16, 16)

    h1(pdf, "Jawaban Developer — Audit Website Funnel & Setup Analytics")
    body(
        pdf,
        "Dokumen ini menjawab checklist pertanyaan di Audit_Analytics_Website_Funnel.pdf "
        "berdasarkan audit kode website YFD (yourfinancialdoctor.id) saat ini. "
        "Tanggal penyusunan: 1 September 2026.",
    )
    body(
        pdf,
        "Ringkas status: GA4/GTM/pixel belum terpasang di codebase; kolom UTM belum ada di tabel order; "
        "webhook Pivot sudah meng-update row order yang sama saat lunas (jadi siap untuk atribusi begitu UTM disimpan).",
    )

    h2(pdf, "1. Analytics Dasar — apakah sudah terekam sama sekali?")
    qa(
        pdf,
        1,
        "Apakah GA4 sudah terpasang di semua halaman (home, /produk, /check-up, /checkout/*, Wealthpedia)?",
        "Belum. GA4 belum terpasang di codebase website tersebut (baik sebagian maupun seluruh halaman).",
    )
    qa(
        pdf,
        2,
        "Pemasangan lewat GTM, atau kode GA4 ditanam langsung?",
        "Belum memakai GTM maupun GA4 inline. Rekomendasi: pasang via Google Tag Manager agar penambahan tracking ke depan lebih fleksibel.",
    )
    qa(
        pdf,
        3,
        "Bisa dibuatkan akses viewer (read-only) GA4 untuk Catherina & tim?",
        "Bisa, setelah property GA4 dibuat. Butuh Measurement ID / undangan dari akun Google Analytics YFD. Akses viewer tidak perlu hak edit.",
    )

    h2(pdf, "2. Funnel Tracking — event apa yang direkam?")
    qa(
        pdf,
        4,
        "Event apa saja yang sudah dikonfigurasi di GA4? (page_view, view_item, begin_checkout, purchase)",
        "Belum ada. Karena GA4 belum terpasang, event minimal tersebut juga belum dikonfigurasi.",
    )
    qa(
        pdf,
        5,
        "Kalau belum lengkap, mana yang paling cepat ditambahkan minggu ini?",
        "Setelah GA4 + GTM siap: prioritaskan begin_checkout dan purchase. Estimasi ~1–2 hari kerja setelah Measurement ID & GTM container tersedia.",
    )
    qa(
        pdf,
        6,
        "Apakah tombol CTA punya event click tracking terpisah dari page_view?",
        "Belum. CTA belum punya click event sendiri. Bisa ditambahkan setelah GTM aktif supaya bedakan “klik tombol” vs “buka halaman dari link lain”.",
    )

    h2(pdf, "3. Atribusi Campaign (UTM) — apakah nempel sampai transaksi?")
    qa(
        pdf,
        7,
        "Apakah nilai UTM disimpan ke database saat submit checkout (kolom terpisah di order)?",
        "Belum. UTM (utm_source, utm_medium, utm_campaign, utm_content, dll.) tidak disimpan di tabel orders. Yang ada sekarang hanya referral_code (kode affiliate) — itu berbeda dari UTM campaign.",
    )
    qa(
        pdf,
        8,
        "Setelah Pivot konfirmasi lunas (webhook), apakah status di-update di row yang sama yang menyimpan UTM?",
        "Secara arsitektur: YA, webhook Pivot meng-update row order yang sama saat lunas. Jadi begitu UTM sudah disimpan saat checkout (#7), atribusi “order lunas dari campaign jarkom kategori Surviving” bisa terlihat. Saat ini kolom UTM belum ada, jadi belum bisa dibuktikan di data.",
    )
    qa(
        pdf,
        9,
        "Kalau belum ada, berapa lama menambah kolom utm_source / utm_content di order?",
        "Estimasi ~0,5–1 hari kerja (migration + isi otomatis dari query string saat checkout disubmit). Ini prioritas tertinggi untuk follow-up jarkom.",
    )

    pdf.add_page()
    h2(pdf, "4. Cross-Domain Tracking ke Pivot")
    qa(
        pdf,
        10,
        "Apakah GA4 sudah dikonfigurasi cross-domain antara yourfinancialdoctor.id dan domain Pivot?",
        "Belum (GA4 belum ada). Cross-domain ke Pivot juga rawan karena domain pembayaran di luar kontrol YFD.",
    )
    qa(
        pdf,
        11,
        "Kalau tidak memungkinkan, apakah purchase bisa dicatat server-side via webhook Pivot → GA4?",
        "Belum diimplementasi, tetapi ini jalur yang kami rekomendasikan (Measurement Protocol). Lebih reliable daripada andalkan browser setelah redirect ke Pivot. Bisa dikerjakan bersamaan dengan setup event purchase.",
    )

    h2(pdf, "5. Pixel / Tracking Ads")
    qa(
        pdf,
        12,
        "Apakah Meta Pixel dan/atau TikTok Pixel sudah terpasang?",
        "Belum. Belum ada Meta Pixel maupun TikTok Pixel di website.",
    )
    qa(
        pdf,
        13,
        "Kalau belum, bisa dipasang bareng setup GA4?",
        "Bisa. Idealnya dipasang bersamaan lewat GTM (satu container, banyak tag) agar tidak dua kali kerja.",
    )

    h2(pdf, "6. Dashboard Ringkas")
    qa(
        pdf,
        14,
        "Bisa dibuatkan dashboard sederhana (Looker Studio / Sheet) untuk leads, sales per produk, sumber campaign? Estimasi waktu?",
        "Belum ada. Setelah GA4 + UTM di order hidup: (a) Looker Studio dari GA4, atau (b) ringkasan dari database order (lebih akurat untuk sales lunas). Estimasi dashboard sederhana: 2–4 hari setelah fondasi #1, #7, dan #11 siap.",
    )

    h2(pdf, "Prioritas untuk follow-up jarkom minggu ini")
    body(pdf, "Sesuai dokumen audit (#4, #7, #8, #10/#11):")
    bullet(pdf, "#4 Event funnel GA4 — BELUM")
    bullet(pdf, "#7 UTM di database order — BELUM (kerjakan dulu)")
    bullet(pdf, "#8 UTM nempel ke order lunas — alur webhook SIAP, menunggu #7")
    bullet(pdf, "#10 Cross-domain GA4 — BELUM / kurang andal ke Pivot")
    bullet(pdf, "#11 Purchase server-side dari webhook — BELUM, direkomendasikan")
    pdf.ln(2)
    body(
        pdf,
        "Workaround jarkom sekarang: pengiriman tetap jalan; matching conversion manual dari daftar order lunas "
        "+ email/timestamp 7 hari. Infra UTM + GA4 dikerjakan untuk batch follow-up berikutnya.",
    )

    h2(pdf, "Urutan pengerjaan yang disarankan")
    bullet(pdf, "A. Tambah kolom UTM di orders + isi otomatis saat checkout (cepat, langsung berguna untuk jarkom).")
    bullet(pdf, "B. Pasang GTM + GA4 di semua halaman publik.")
    bullet(pdf, "C. Event begin_checkout + purchase server-side dari webhook Pivot.")
    bullet(pdf, "D. Pixel Meta/TikTok via GTM + dashboard ringkas KPI mingguan.")
    pdf.ln(2)
    body(
        pdf,
        "Yang dibutuhkan dari tim non-teknis untuk mulai: Measurement ID GA4 dan/atau GTM Container ID "
        "(plus Pixel ID Meta/TikTok jika iklan segera jalan).",
    )

    pdf.ln(4)
    pdf.set_font("Body", size=9)
    pdf.set_text_color(100, 100, 100)
    pdf.multi_cell(
        0,
        4.5,
        "Sumber: audit codebase telegram-finance-bot / apps/admin-laravel. "
        "Dokumen ini menjawab Audit_Analytics_Website_Funnel.pdf untuk penilaian bersama dr. Catherina & tim.",
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"Wrote {OUT}")


if __name__ == "__main__":
    main()
