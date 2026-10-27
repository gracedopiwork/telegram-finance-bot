<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 1 W2 (revisi founder 2026-10-27): intro FHCU tanpa pilihan penelitian; pemberitahuan
 * data muncul di langkah email, link Kebijakan Privasi di tab baru; blok FHCU di
 * /kebijakan-privasi tanpa paragraf Penelitian.
 */
class FhcuIntroConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_intro_has_no_research_choice_and_starts_the_wizard(): void
    {
        $html = $this->get('/check-up')
            ->assertOk()
            ->assertSee('Kenali Kesehatan Finansialmu')
            ->assertSee('href="'.route('checkup.form').'"', false)
            ->assertDontSee('name="research_consent"', false)
            ->assertDontSee('name="fs[', false)
            ->getContent();

        $this->assertStringNotContainsStringIgnoringCase('penelitian', $html);
    }

    public function test_wizard_opens_directly_for_guests_and_sends_portal_users_to_portal(): void
    {
        $this->get('/check-up/isi')->assertOk()->assertSee('name="fs[', false);

        $this->portalLogin();
        $this->get('/check-up/isi')->assertRedirect(route('portal.diagnostic'));
    }

    public function test_email_step_shows_data_notice_with_privacy_link_in_new_tab(): void
    {
        $html = $this->get('/check-up/isi')
            ->assertOk()
            ->assertSee('Privasi Anda Penting')
            ->assertSee('YFD Data Insight')
            ->assertSee('Lanjutkan &amp; Lihat Hasil', false)
            ->getContent();

        $this->assertStringNotContainsStringIgnoringCase('penelitian', $html);

        // Link di pemberitahuan harus membuka tab baru agar jawaban wizard tidak hilang.
        $privacy = preg_quote(route('company.privacy'), '/');
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="'.$privacy.'"[^>]*target="_blank"[^>]*rel="noopener"[^>]*>/',
            $html
        );
        // Link pemberitahuan + link footer.
        $this->assertSame(2, substr_count($html, 'href="'.route('company.privacy').'"'));
    }

    public function test_privacy_page_fhcu_block_has_no_research_section(): void
    {
        $html = $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertSee('Financial Health Check-Up (FHCU) &amp; Konsultasi', false)
            ->assertSee('Data FHCU digunakan untuk menampilkan dan mengirim hasil check-up ke email kamu dan menghubungimu terkait layanan yang kamu minta.')
            ->assertDontSee('menentukan tarif konsultasi')
            ->getContent();

        $this->assertStringNotContainsStringIgnoringCase('penelitian', $html);
    }
}
