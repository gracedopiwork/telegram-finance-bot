<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\Setting;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateClaimTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_tax_is_two_point_five_percent(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'affiliate.tax_individual_percent'],
            ['value' => '2.5', 'type' => 'text', 'group' => 'affiliate']
        );
        Setting::query()->updateOrCreate(
            ['key' => 'affiliate.tax_corporate_percent'],
            ['value' => '2', 'type' => 'text', 'group' => 'affiliate']
        );
        Setting::query()->updateOrCreate(
            ['key' => 'affiliate.min_claim_amount'],
            ['value' => '25000', 'type' => 'text', 'group' => 'affiliate']
        );

        $service = app(AffiliateService::class);
        $this->assertSame(2.5, $service->taxPercentForPayeeType('individual'));
        $this->assertSame(2.0, $service->taxPercentForPayeeType('corporate'));
        // NPWP tidak lagi mengubah tarif
        $this->assertSame(2.5, $service->taxPercent('123456789'));
        $this->assertSame(2.5, $service->taxPercent(null));
    }
}
