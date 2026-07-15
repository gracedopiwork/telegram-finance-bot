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
            'Jasa',
            'Need',
            'Pelunasan jasa freelancer IT dan web developer untuk proyek YFD',
        );
    }

    public function test_self_development_is_future_building(): void
    {
        foreach ([
            ['Pendidikan', 'Bayar seminar financial planning'],
            ['Pendidikan', 'Biaya sertifikasi bisnis'],
            ['Buku', 'Beli buku The Psychology of Money'],
            ['Self Development', 'Bayar les piano bulanan'],
            ['Self Development', 'Kelas public speaking'],
            ['Pengembangan Diri', 'Coaching karier bersama mentor'],
            ['Alat Musik', 'Beli piano untuk belajar'],
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

    public function test_sport_lessons_are_essential_living(): void
    {
        foreach ([
            ['Olahraga', 'Bayar coaching tenis'],
            ['Olahraga', 'Bayar les renang'],
            ['Kesehatan', 'Bayar personal trainer di gym'],
            ['Olahraga', 'Bayar kelas pilates'],
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

    public function test_emergency_fund_saving_is_protection(): void
    {
        $this->assertBucket(
            'Protection',
            TransactionTaxonomy::TYPE_SAVING,
            'Dana Darurat',
            'Need',
            'Top up emergency fund bulanan',
        );
    }

    public function test_document_essential_categories_are_essential_living(): void
    {
        foreach ([
            ['Kesehatan', 'Bayar dokter dan obat'],
            ['Listrik', 'Bayar token listrik'],
            ['Air', 'Bayar tagihan air PDAM'],
            ['Pajak', 'Bayar pajak kendaraan'],
            ['Pendidikan', 'Bayar uang sekolah anak'],
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
            ['Essential Living', 'Jajan', 'Need', 'Ngopi meeting kerja dengan klien'],
            ['Flexible + Social', 'Jajan', 'Wants', 'Ngopi untuk healing'],
            ['Future Building', 'Elektronik', 'Need', 'Beli laptop kerja'],
            ['Essential Living', 'Elektronik', 'Need', 'Ganti HP utama rusak'],
            ['Flexible + Social', 'Elektronik', 'Wants', 'Upgrade iPhone karena FOMO'],
            ['Flexible + Social', 'Alat Musik', 'Wants', 'Beli piano untuk hobi pribadi'],
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
            'category' => 'Elektronik',
            'sub_category' => '-',
            'bucket' => 'Future Building',
            'transaction_type' => 'expense',
            'nature' => 'Need',
            'match_keywords' => 'laptop kerja',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        CategoryBucketMapping::query()->create([
            'category' => 'Elektronik',
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
            'Elektronik',
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
            'category' => 'Jasa',
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
            'category' => 'Jasa',
            'sub_category' => '-',
            'nature' => 'Need',
            'notes' => 'Pelunasan jasa freelancer IT dan web developer untuk proyek YFD',
        ];
    }
}
