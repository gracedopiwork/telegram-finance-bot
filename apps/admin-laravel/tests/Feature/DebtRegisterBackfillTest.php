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

    public function test_it_ignores_baselines_without_debt(): void
    {
        FinancialBaseline::factory()->create(['total_debt' => null]);
        FinancialBaseline::factory()->create(['total_debt' => 0]);

        $this->assertSame(0, (new DebtRegisterBackfill)());
    }
}
