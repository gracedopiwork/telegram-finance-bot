<?php

namespace Tests\Unit;

use App\Support\TransactionTaxonomy;
use PHPUnit\Framework\TestCase;

class TransactionTaxonomyV18Test extends TestCase
{
    public function test_need_want_only_applies_to_pengeluaran(): void
    {
        $this->assertTrue(TransactionTaxonomy::appliesNature(TransactionTaxonomy::TYPE_EXPENSE));
        $this->assertFalse(TransactionTaxonomy::appliesNature(TransactionTaxonomy::TYPE_INCOME));
        $this->assertFalse(TransactionTaxonomy::appliesNature(TransactionTaxonomy::TYPE_RECEIVABLE_OUT));
        $this->assertFalse(TransactionTaxonomy::appliesNature(TransactionTaxonomy::TYPE_PAYABLE_IN));
        $this->assertFalse(TransactionTaxonomy::appliesNature(TransactionTaxonomy::TYPE_SAVING));
    }

    public function test_impulsif_scope_matches_v18(): void
    {
        $this->assertTrue(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_EXPENSE));
        $this->assertTrue(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_RECEIVABLE_OUT));
        $this->assertTrue(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_PAYABLE_IN));
        $this->assertFalse(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_RECEIVABLE_IN));
        $this->assertFalse(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_PAYABLE_OUT));
        $this->assertFalse(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_INCOME));
        $this->assertFalse(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_TAX));
        $this->assertFalse(TransactionTaxonomy::appliesImpulsive(TransactionTaxonomy::TYPE_SAVING));
    }

    public function test_normalize_clears_nature_for_non_expense(): void
    {
        $out = TransactionTaxonomy::normalize('Piutang Keluar', 'Need', 'Lain-lain');
        $this->assertSame('Piutang Keluar', $out['type']);
        $this->assertNull($out['nature']);

        $expense = TransactionTaxonomy::normalize('Pengeluaran', 'Wants', 'Hadiah');
        $this->assertSame('Pengeluaran', $expense['type']);
        $this->assertSame('Wants', $expense['nature']);
    }
}
