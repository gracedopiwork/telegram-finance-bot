<?php

namespace Tests\Feature;

use App\Models\BotTransaction;
use App\Models\CategoryBucketMapping;
use App\Services\CategoryBucketMappingService;
use App\Services\CategoryBucketService;
use App\Support\TransactionTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryBucketServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_freelancer_expense_is_future_building(): void
    {
        $this->assertBucket(
            'Future Building',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Bisnis & Karir',
            'Need',
            'Pelunasan jasa freelancer IT dan web developer untuk proyek YFD',
        );
    }

    public function test_self_development_is_future_building(): void
    {
        foreach ([
            ['Pendidikan', 'Bayar seminar financial planning'],
            ['Pendidikan', 'Biaya sertifikasi bisnis'],
            ['Pendidikan', 'Beli buku The Psychology of Money'],
            ['Pendidikan', 'Bayar les piano bulanan'],
            ['Pendidikan', 'Kelas public speaking'],
            ['Pendidikan', 'Coaching karier bersama mentor'],
            ['Pendidikan', 'Beli piano untuk belajar'],
        ] as [$category, $notes]) {
            $this->assertBucket(
                'Future Building',
                TransactionTaxonomy::TYPE_EXPENSE,
                $category,
                'Need',
                $notes,
            );
        }
    }

    public function test_sport_and_gym_are_flexible_social(): void
    {
        foreach ([
            ['Lifestyle & Hiburan', 'Bayar coaching tenis'],
            ['Lifestyle & Hiburan', 'Bayar les renang'],
            ['Lifestyle & Hiburan', 'Bayar personal trainer di gym'],
            ['Lifestyle & Hiburan', 'Bayar kelas pilates'],
        ] as [$category, $notes]) {
            $this->assertBucket(
                'Flexible + Social',
                TransactionTaxonomy::TYPE_EXPENSE,
                $category,
                'Wants',
                $notes,
            );
        }
    }

    public function test_makeup_is_flexible_not_protection_or_essential(): void
    {
        $this->assertBucket(
            'Flexible + Social',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Kesehatan & Kebersihan Diri',
            'Wants',
            'Beli makeup cushion Maybelline 185rb',
        );
        $this->assertBucket(
            'Flexible + Social',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Proteksi',
            'Need',
            'Beli cushion premium Maybelline',
        );
        $this->assertBucket(
            'Flexible + Social',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Kesehatan & Kebersihan Diri',
            'Need',
            'Beli sunscreen 89rb',
        );
    }

    public function test_asuransi_jiwa_is_protection_not_stolen_by_household(): void
    {
        $this->assertBucket(
            'Protection',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Proteksi',
            'Need',
            'Bayar premi asuransi jiwa 500rb',
        );
    }

    public function test_grab_to_gym_is_flexible_not_gym_membership(): void
    {
        $this->assertBucket(
            'Flexible + Social',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Transportasi',
            'Wants',
            'Grab ke gym Imam Bonjol 21000',
        );
    }

    public function test_tumbler_replacement_is_essential_not_protection(): void
    {
        $this->assertBucket(
            'Essential Living',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Lifestyle & Hiburan',
            'Need',
            'Beli tumbler karena tumbler lama rusak 200 rb',
        );
        $this->assertBucket(
            'Essential Living',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Proteksi',
            'Need',
            'Beli tumbler ganti yang rusak 200000',
        );
    }

    public function test_emergency_fund_saving_is_protection(): void
    {
        $this->assertBucket(
            'Protection',
            TransactionTaxonomy::TYPE_SAVING,
            'Investasi & Tabungan',
            'Need',
            'Top up emergency fund bulanan',
        );
    }

    public function test_document_essential_categories_are_essential_living(): void
    {
        foreach ([
            ['Kesehatan & Kebersihan Diri', 'Bayar dokter dan obat'],
            ['Tempat Tinggal', 'Bayar token listrik'],
            ['Tempat Tinggal', 'Bayar tagihan air PDAM'],
            ['Pendidikan', 'Bayar uang sekolah anak'],
            ['Cicilan & Hutang', 'Bayar cicilan motor'],
        ] as [$category, $notes]) {
            $this->assertBucket(
                'Essential Living',
                TransactionTaxonomy::TYPE_EXPENSE,
                $category,
                'Need',
                $notes,
            );
        }
    }

    public function test_context_changes_bucket_without_changing_transaction_type(): void
    {
        $cases = [
            ['Future Building', 'Makanan & Minuman', 'Need', 'Ngopi meeting kerja dengan klien'],
            ['Future Building', 'Bisnis & Karir', 'Need', 'Starbucks meeting klien 85rb'],
            ['Flexible + Social', 'Makanan & Minuman', 'Wants', 'Ngopi untuk healing'],
            ['Future Building', 'Lifestyle & Hiburan', 'Need', 'Beli laptop kerja'],
            ['Essential Living', 'Lifestyle & Hiburan', 'Need', 'Ganti HP utama rusak'],
            ['Flexible + Social', 'Lifestyle & Hiburan', 'Wants', 'Upgrade iPhone karena FOMO'],
            ['Flexible + Social', 'Lifestyle & Hiburan', 'Wants', 'Beli piano untuk hobi pribadi'],
        ];

        foreach ($cases as [$bucket, $category, $nature, $notes]) {
            $row = $this->transaction(
                TransactionTaxonomy::TYPE_EXPENSE,
                $category,
                $nature,
                $notes,
            );

            $this->assertSame(TransactionTaxonomy::TYPE_EXPENSE, $row->type);
            $this->assertSame($bucket, app(CategoryBucketService::class)->resolve($row));
        }
    }

    public function test_business_transport_is_future_building(): void
    {
        foreach ([
            'Grab dari kos ke aktivitas networking bisnis di Nusa Dua',
            'Grab dari kos ke cafe komeda ketemu client bisnis',
            'Tiket pesawat untuk meeting klien di Jakarta',
            'Grab ke meeting kerja 45rb',
            'Gojek ke klien untuk bisnis 30rb',
            'Ojek ke rapat client 25rb',
            'Grab untuk bisnis pitching investor 50rb',
        ] as $notes) {
            $this->assertBucket(
                'Future Building',
                TransactionTaxonomy::TYPE_EXPENSE,
                'Transportasi',
                'Need',
                $notes,
            );
        }
    }

    public function test_lifestyle_transport_is_flexible_social(): void
    {
        $this->assertBucket(
            'Flexible + Social',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Transportasi',
            'Wants',
            'Grab ke mall nongkrong sama teman',
        );
    }

    public function test_piutang_keluar_excluded_from_prescription_buckets(): void
    {
        $this->assertBucket(
            null,
            TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'Lain-lain',
            'Need',
            'Di pinjam Catherine 1 jt buat bayar RS',
        );
    }

    public function test_hutang_masuk_excluded_from_prescription_buckets(): void
    {
        $this->assertBucket(
            null,
            TransactionTaxonomy::TYPE_PAYABLE_IN,
            'Lain-lain',
            'Need',
            'Pinjam dari Ayuti 1jt',
        );
    }

    public function test_hutang_keluar_excluded_from_prescription_buckets(): void
    {
        $this->assertBucket(
            null,
            TransactionTaxonomy::TYPE_PAYABLE_OUT,
            'Lain-lain',
            'Need',
            'Bayar utang ke Ayuti 1jt',
        );
    }

    public function test_income_is_excluded_from_prescription_buckets(): void
    {
        $this->assertBucket(
            null,
            TransactionTaxonomy::TYPE_INCOME,
            'Freelance',
            'Need',
            'Terima honor freelance',
        );
    }

    public function test_contextual_mapping_does_not_become_category_default(): void
    {
        CategoryBucketMapping::query()->delete();
        CategoryBucketMapping::query()->create([
            'category' => 'Lifestyle & Hiburan',
            'sub_category' => '-',
            'bucket' => 'Future Building',
            'transaction_type' => 'expense',
            'nature' => 'Need',
            'match_keywords' => 'laptop kerja',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        CategoryBucketMapping::query()->create([
            'category' => 'Lifestyle & Hiburan',
            'sub_category' => '-',
            'bucket' => 'Essential Living',
            'transaction_type' => 'expense',
            'nature' => 'Need',
            'match_keywords' => null,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        app(CategoryBucketMappingService::class)->forgetCache();

        $this->assertBucket(
            'Essential Living',
            TransactionTaxonomy::TYPE_EXPENSE,
            'Lifestyle & Hiburan',
            'Need',
            'Ganti HP utama rusak',
        );
    }

    public function test_api_preview_returns_bucket_without_saving(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);
        $mappingCount = CategoryBucketMapping::query()->count();

        $response = $this->withToken('test-token')->postJson(
            '/api/bot/transactions/preview',
            $this->classificationPayload()
        );

        $response->assertOk()->assertJson([
            'ok' => true,
            'category' => 'Bisnis & Karir',
            'bucket' => 'Future Building',
        ]);
        $this->assertDatabaseCount('bot_transactions', 0);
        $this->assertSame($mappingCount, CategoryBucketMapping::query()->count());
    }

    public function test_api_save_returns_same_canonical_bucket_as_preview(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);
        $payload = array_merge($this->classificationPayload(), [
            'telegram_user_id' => 12345,
            'amount' => 5750000,
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'source' => 'manual',
        ]);

        $preview = $this->withToken('test-token')
            ->postJson('/api/bot/transactions/preview', $payload)
            ->assertOk()
            ->json();
        $saved = $this->withToken('test-token')
            ->postJson('/api/bot/transactions', $payload)
            ->assertOk()
            ->json();

        $this->assertSame($preview['category'], $saved['category']);
        $this->assertSame($preview['bucket'], $saved['bucket']);
        $this->assertDatabaseCount('bot_transactions', 1);
    }

    public function test_api_preview_requires_internal_token(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $this->postJson('/api/bot/transactions/preview', $this->classificationPayload())
            ->assertUnauthorized();
    }

    private function assertBucket(
        ?string $expected,
        string $type,
        string $category,
        string $nature,
        string $notes,
    ): void {
        $this->assertSame(
            $expected,
            app(CategoryBucketService::class)->resolve(
                $this->transaction($type, $category, $nature, $notes)
            )
        );
    }

    private function transaction(
        string $type,
        string $category,
        string $nature,
        string $notes,
    ): BotTransaction {
        return new BotTransaction([
            'type' => $type,
            'category' => $category,
            'sub_category' => '-',
            'nature' => $nature,
            'notes' => $notes,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function classificationPayload(): array
    {
        return [
            'type' => TransactionTaxonomy::TYPE_EXPENSE,
            'category' => 'Bisnis & Karir',
            'sub_category' => '-',
            'nature' => 'Need',
            'notes' => 'Pelunasan jasa freelancer IT dan web developer untuk proyek YFD',
        ];
    }
}
