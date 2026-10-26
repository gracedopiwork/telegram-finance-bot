<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\BaselineClaimService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Batch 1 W2: halaman pengantar FHCU (/check-up), pilihan persetujuan penelitian,
 * dan blok FHCU di /kebijakan-privasi.
 */
class FhcuIntroConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Migrasi 2026_07_03 hanya melonggarkan telegram_user_id di MySQL/pgsql. Di sqlite
        // test kolom itu tetap NOT NULL, sehingga baris tamu (check-up tanpa portal) gagal
        // disimpan. Samakan dengan produksi agar alur tamu bisa diuji.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_user_id')->nullable()->change();
            });
        }
    }

    public function test_check_up_shows_intro_with_unselected_research_choice(): void
    {
        $response = $this->get('/check-up')->assertOk();

        $response->assertSee('Kenali Kesehatan Finansialmu')
            ->assertSee('Saya bersedia')
            ->assertSee('Saya tidak bersedia')
            ->assertSee('Mulai Financial Health Check-Up')
            ->assertSee('/kebijakan-privasi')
            ->assertDontSee('name="fs[', false);

        preg_match_all('/<input[^>]*name="research_consent"[^>]*>/', $response->getContent(), $inputs);
        $this->assertCount(2, $inputs[0], 'Harus ada tepat dua pilihan persetujuan.');
        foreach ($inputs[0] as $input) {
            $this->assertStringNotContainsString('checked', $input, 'Tidak boleh ada pilihan yang terpilih otomatis.');
        }
    }

    public function test_start_requires_a_research_choice(): void
    {
        $this->post('/check-up/mulai')->assertSessionHasErrors('research_consent');

        $this->post('/check-up/mulai', ['research_consent' => '0'])
            ->assertRedirect(route('checkup.form'));
        $this->assertSame(false, session('checkup.research_consent'));

        $this->post('/check-up/mulai', ['research_consent' => '1'])
            ->assertRedirect(route('checkup.form'));
        $this->assertSame(true, session('checkup.research_consent'));
    }

    public function test_form_requires_choice_first(): void
    {
        $this->get('/check-up/isi')->assertRedirect(route('checkup.show'));

        $this->withSession(['checkup.research_consent' => false])
            ->get('/check-up/isi')
            ->assertOk()
            ->assertSee('name="fs[', false);
    }

    public function test_new_fhcu_stores_research_choice(): void
    {
        $this->submit('w2-yes@example.com')->assertRedirect(route('checkup.result'));

        $yes = FinancialBaseline::query()->where('email', 'w2-yes@example.com')->sole();
        $this->assertSame(true, $yes->research_consent);
        $this->assertNotNull($yes->research_consent_at);
        $this->assertSame(config('baseline_assessment.research_consent_version'), $yes->research_consent_version);

        $this->submit('w2-no@example.com', ['checkup.research_consent' => false])
            ->assertRedirect(route('checkup.result'));

        $no = FinancialBaseline::query()->where('email', 'w2-no@example.com')->sole();
        $this->assertSame(false, $no->research_consent);
        $this->assertNotNull($no->research_consent_at);
    }

    public function test_retake_of_review_due_fhcu_updates_research_choice(): void
    {
        $oldConsentAt = now()->subMonths(4)->startOfSecond();
        $row = FinancialBaseline::factory()->create([
            'telegram_user_id' => null,
            'email' => 'w2-retake@example.com',
            // isReviewDue() membaca assessed_at, jadi keduanya harus lama.
            'assessed_at' => now()->subMonths(4),
            'next_review_at' => now()->subMonth(),
            'answers_json' => ['fs' => ['q' => 'a'], 'ftsa' => []],
            'research_consent' => true,
            'research_consent_at' => $oldConsentAt,
            'research_consent_version' => 'old',
        ]);

        $this->submit('w2-retake@example.com', ['checkup.research_consent' => false])
            ->assertRedirect(route('checkup.result'));

        $this->assertSame(1, FinancialBaseline::query()->where('email', 'w2-retake@example.com')->count());
        $row->refresh();
        $this->assertSame(false, $row->research_consent);
        $this->assertNotNull($row->research_consent_at);
        $this->assertTrue($row->research_consent_at->greaterThan($oldConsentAt));
        $this->assertSame(config('baseline_assessment.research_consent_version'), $row->research_consent_version);
    }

    public function test_portal_user_is_sent_to_portal_diagnostic_from_form(): void
    {
        $this->portalLogin();

        $this->get('/check-up/isi')->assertRedirect(route('portal.diagnostic'));

        $this->withSession(['checkup.research_consent' => true])
            ->get('/check-up/isi')
            ->assertRedirect(route('portal.diagnostic'));
    }

    public function test_privacy_page_explains_fhcu_and_research(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertSee('Financial Health Check-Up (FHCU) &amp; Konsultasi', false)
            ->assertSee('Saya bersedia');

        $html = $this->get('/check-up')->assertOk()->getContent();
        $this->assertSame(
            2,
            substr_count($html, 'href="'.route('company.privacy').'"'),
            'Link Kebijakan Privasi harus ada di teks pengantar dan di footer.'
        );
    }

    public function test_research_choice_does_not_move_checkup_dates(): void
    {
        $frozen = Carbon::parse('2026-10-26 10:00:00');
        $this->travelTo($frozen);
        $this->portalLogin(555201, 'w2-dates@example.com');

        $this->submit('w2-dates@example.com')->assertRedirect(route('checkup.result'));

        $baseline = FinancialBaseline::latestForUser(555201);
        $this->assertNotNull($baseline);
        $this->assertTrue($frozen->equalTo($baseline->assessed_at), 'assessed_at = '.$baseline->assessed_at);
        $this->assertTrue(
            $frozen->copy()->addMonths((int) config('baseline_assessment.review_months'))->equalTo($baseline->next_review_at),
            'next_review_at = '.$baseline->next_review_at
        );
        $this->assertNotNull($baseline->research_consent_at);
        $this->assertTrue($frozen->equalTo($baseline->research_consent_at));

        $before = $baseline->only([
            'assessed_at', 'next_review_at', 'research_consent', 'research_consent_at', 'research_consent_version',
        ]);

        $this->travelTo($frozen->copy()->addDay());
        $this->post('/portal/baseline', [
            'snapshot' => ['job_type' => 'employee', 'avg_monthly_income' => 12_000_000],
        ])->assertRedirect();

        $after = FinancialBaseline::latestForUser(555201);
        $this->assertSame(12_000_000, $after->avg_monthly_income, 'Snapshot harus tetap tersimpan.');
        foreach ($before as $key => $value) {
            $this->assertEquals($value, $after->{$key}, "{$key} bergeser setelah simpan snapshot.");
        }
    }

    public function test_claim_merge_keeps_research_choice(): void
    {
        // A: hanya baris tamu yang punya pilihan — pilihan itu pindah ke baris utama.
        [$primary, $guest] = $this->siblingRows(555301, 'w2-merge@example.com', null, [
            'research_consent' => true,
            'research_consent_at' => now()->subMinutes(5)->startOfSecond(),
            'research_consent_version' => '2026-10-25',
        ]);
        $assessedAt = $primary->assessed_at;
        $nextReviewAt = $primary->next_review_at;

        app(BaselineClaimService::class)->claimForUser('w2-merge@example.com', 555301);

        $survivor = FinancialBaseline::query()->where('email', 'w2-merge@example.com')->sole();
        $this->assertSame($primary->id, $survivor->id, 'Fixture: baris portal harus jadi baris utama.');
        $this->assertSame(true, $survivor->research_consent);
        $this->assertTrue($guest->research_consent_at->equalTo($survivor->research_consent_at));
        $this->assertSame('2026-10-25', $survivor->research_consent_version);
        $this->assertTrue($assessedAt->equalTo($survivor->assessed_at));
        $this->assertTrue($nextReviewAt->equalTo($survivor->next_review_at));

        // B: keduanya punya pilihan — yang terbaru menang.
        [$primaryB, $guestB] = $this->siblingRows(555302, 'w2-merge-b@example.com', [
            'research_consent' => false,
            'research_consent_at' => now()->subDay()->startOfSecond(),
            'research_consent_version' => 'old',
        ], [
            'research_consent' => true,
            'research_consent_at' => now()->subMinutes(5)->startOfSecond(),
            'research_consent_version' => '2026-10-25',
        ]);

        app(BaselineClaimService::class)->claimForUser('w2-merge-b@example.com', 555302);

        $survivorB = FinancialBaseline::query()->where('email', 'w2-merge-b@example.com')->sole();
        $this->assertSame($primaryB->id, $survivorB->id, 'Fixture: baris portal harus jadi baris utama.');
        $this->assertSame(true, $survivorB->research_consent);
        $this->assertTrue($guestB->research_consent_at->equalTo($survivorB->research_consent_at));
        $this->assertSame('2026-10-25', $survivorB->research_consent_version);
    }

    /**
     * Baris portal (FTSA lengkap → skor kelengkapan tertinggi) + baris tamu dengan email sama.
     *
     * @return array{0: FinancialBaseline, 1: FinancialBaseline}
     */
    private function siblingRows(int $telegramUserId, string $email, ?array $primaryConsent, ?array $guestConsent): array
    {
        $fs = $this->fsPayload()['fs'];

        $primary = FinancialBaseline::factory()->create(array_merge([
            'telegram_user_id' => $telegramUserId,
            'email' => $email,
            'assessed_at' => now()->subHour()->startOfSecond(),
            'next_review_at' => now()->subHour()->addMonths(3)->startOfSecond(),
            'answers_json' => ['fs' => $fs, 'ftsa' => array_fill_keys(range(1, 32), 3)],
        ], $primaryConsent ?? []));

        $guest = FinancialBaseline::factory()->create(array_merge([
            'telegram_user_id' => null,
            'email' => $email,
            'assessed_at' => now()->startOfSecond(),
            'next_review_at' => now()->addMonths(3)->startOfSecond(),
            'answers_json' => ['fs' => $fs, 'ftsa' => []],
        ], $guestConsent ?? []));

        return [$primary->fresh(), $guest->fresh()];
    }

    /**
     * Jawaban FHCU valid: opsi pertama untuk setiap pertanyaan tahap finansial.
     *
     * @return array{fs: array<string, string>}
     */
    private function fsPayload(): array
    {
        $answers = [];
        foreach (app(BaselineAssessmentService::class)->validationRulesFinancialStageOnly() as $key => $rule) {
            if (! Str::startsWith($key, 'fs.')) {
                continue;
            }
            $options = Str::after(collect(explode('|', $rule))->first(fn ($part) => Str::startsWith($part, 'in:')), 'in:');
            $answers[$key] = explode(',', $options)[0];
        }

        return Arr::undot($answers);
    }

    private function submit(string $email, array $session = ['checkup.research_consent' => true]): TestResponse
    {
        return $this->withSession($session)->post('/check-up', $this->fsPayload() + ['email' => $email]);
    }
}
