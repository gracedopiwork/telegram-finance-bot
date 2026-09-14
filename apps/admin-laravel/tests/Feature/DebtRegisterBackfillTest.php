<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\DebtRegister;
use App\Support\DebtRegisterBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtRegisterBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_legacy_total_debt_into_one_unspecified_row(): void
    {
        $baseline = FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create();

        $filled = (new DebtRegisterBackfill)();

        $this->assertSame(1, $filled);

        $row = $baseline->fresh()->debt_register[0];
        $this->assertSame(DebtRegisterBackfill::PLACEHOLDER_NAME, $row['debt_name']);
        $this->assertSame('lainnya', $row['debt_type']);
        $this->assertSame(12_000_000, $row['principal_outstanding']);
        $this->assertNull($row['monthly_installment_reference']);
        $this->assertSame(DebtRegister::LINKED_EXPENSE_CATEGORY, $row['linked_expense_category']);
        $this->assertNotEmpty($row['debt_id']);
    }

    public function test_last_updated_is_the_migration_date_not_the_original_fill_date(): void
    {
        FinancialBaseline::factory()->withLegacyTotalDebt(5_000_000)->create([
            'assessed_at' => now()->subYear(),
        ]);

        (new DebtRegisterBackfill)();

        $row = FinancialBaseline::query()->first()->debt_register[0];

        // Staleness harus dihitung dari titik data ini terakhir diverifikasi,
        // bukan dari kapan user dulu mengetik angkanya.
        $this->assertSame(now()->toDateString(), $row['last_updated']);
        $this->assertFalse(DebtRegister::isStale($row['last_updated']));
    }

    public function test_it_skips_rows_that_already_have_a_register(): void
    {
        FinancialBaseline::factory()
            ->withLegacyTotalDebt(9_000_000)
            ->withDebtRegister([['debt_id' => 'KEEP', 'debt_name' => 'KPR BCA']])
            ->create();

        $this->assertSame(0, (new DebtRegisterBackfill)());
        $this->assertSame('KEEP', FinancialBaseline::query()->first()->debt_register[0]['debt_id']);
    }

    public function test_rollback_clears_only_the_rows_it_created(): void
    {
        $migrated = FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create();
        (new DebtRegisterBackfill)();

        $this->assertSame(1, (new DebtRegisterBackfill)->rollback());
        $this->assertNull($migrated->fresh()->debt_register);

        // total_debt tidak ikut dihapus — kolomnya memang bukan milik migrasi ini.
        $this->assertSame(12_000_000, $migrated->fresh()->total_debt);
    }

    public function test_rollback_never_touches_user_entered_debts(): void
    {
        $userRows = FinancialBaseline::factory()->withDebtRegister([
            ['debt_id' => 'USER-1', 'debt_name' => 'KPR BCA', 'debt_type' => 'kpr',
                'principal_outstanding' => 450_000_000, 'last_updated' => now()->toDateString()],
        ])->create();

        // Satu baris, tapi bukan placeholder — ini data asli user.
        $this->assertSame(0, (new DebtRegisterBackfill)->rollback());
        $this->assertCount(1, $userRows->fresh()->debt_register);
        $this->assertSame('KPR BCA', $userRows->fresh()->debt_register[0]['debt_name']);
    }

    public function test_rollback_leaves_a_detailed_register_alone(): void
    {
        // Placeholder yang sudah dirinci user jadi beberapa baris: bukan lagi
        // hasil migrasi, jadi rollback harus melewatinya.
        $detailed = FinancialBaseline::factory()->withDebtRegister([
            ['debt_id' => 'A', 'debt_name' => DebtRegisterBackfill::PLACEHOLDER_NAME, 'debt_type' => 'lainnya'],
            ['debt_id' => 'B', 'debt_name' => 'KPR BCA', 'debt_type' => 'kpr'],
        ])->create();

        $this->assertSame(0, (new DebtRegisterBackfill)->rollback());
        $this->assertCount(2, $detailed->fresh()->debt_register);
    }

    public function test_up_then_down_then_up_is_stable(): void
    {
        $b = FinancialBaseline::factory()->withLegacyTotalDebt(7_500_000)->create();

        $this->assertSame(1, (new DebtRegisterBackfill)());
        $this->assertSame(1, (new DebtRegisterBackfill)->rollback());
        $this->assertSame(1, (new DebtRegisterBackfill)());

        $register = $b->fresh()->debt_register;
        $this->assertCount(1, $register);
        $this->assertSame(7_500_000, $register[0]['principal_outstanding']);
    }

    public function test_it_ignores_baselines_without_debt(): void
    {
        FinancialBaseline::factory()->create(['total_debt' => null]);
        FinancialBaseline::factory()->create(['total_debt' => 0]);

        $this->assertSame(0, (new DebtRegisterBackfill)());
    }
}
