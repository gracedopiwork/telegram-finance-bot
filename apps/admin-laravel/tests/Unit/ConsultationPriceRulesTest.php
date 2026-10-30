<?php

namespace Tests\Unit;

use App\Services\ConsultationPricing\ConsultationPriceRules;
use App\Services\ConsultationPricing\FhcuRequiredException;
use App\Services\ConsultationPricing\FhcuStatusLookup;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class ConsultationPriceRulesTest extends TestCase
{
    /**
     * @return array{valid: bool, status: string, baseline: null, stage_key: ?string, message: string}
     */
    private function fhcuStatus(string $status, ?string $stageKey): array
    {
        return [
            'valid' => $status === 'valid',
            'status' => $status,
            'baseline' => null,
            'stage_key' => $stageKey,
            'message' => "FHCU {$status}",
        ];
    }

    private function lookupReturning(string $status, ?string $stageKey): void
    {
        $this->partialMock(FhcuStatusLookup::class, function (MockInterface $mock) use ($status, $stageKey) {
            $mock->shouldReceive('forEmail')->andReturn($this->fhcuStatus($status, $stageKey));
        });
    }

    private function rules(): ConsultationPriceRules
    {
        return $this->app->make(ConsultationPriceRules::class);
    }

    public function test_fixed_rule_ignores_fhcu_stage(): void
    {
        $this->lookupReturning('valid', 'comfortable');

        $quote = $this->rules()->for('recovery', null)->quote('klien@example.com');

        $this->assertSame(150_000, $quote->amount);
        $this->assertNull($quote->stageKey);
    }

    public function test_premarital_couple_never_looks_up_fhcu(): void
    {
        $this->mock(FhcuStatusLookup::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('forEmail', 'requireFor');
        });

        $rule = $this->rules()->for('premarital', 'couple');

        $this->assertFalse($rule->requiresFhcu());
        $this->assertSame(500_000, $rule->quote('klien@example.com')->amount);
    }

    public function test_fhcu_rule_prices_by_stage(): void
    {
        $this->lookupReturning('valid', 'growing');

        $quote = $this->rules()->for('standard', null)->quote('klien@example.com');

        $this->assertSame(250_000, $quote->amount);
        $this->assertSame('growing', $quote->stageKey);
    }

    public function test_missing_or_expired_fhcu_throws_fhcu_required(): void
    {
        foreach ([['premarital', 'female'], ['recovery', null]] as [$service, $role]) {
            foreach (['missing', 'expired'] as $status) {
                $this->lookupReturning($status, $status === 'expired' ? 'steady' : null);

                try {
                    $this->rules()->for($service, $role)->quote('klien@example.com');
                    $this->fail("{$service}: FHCU {$status} harus ditolak");
                } catch (FhcuRequiredException $e) {
                    $this->assertSame($status, $e->status);
                }
            }
        }
    }

    public function test_unknown_service_or_role_is_rejected(): void
    {
        foreach ([['premarital', null], ['gold', null]] as [$service, $role]) {
            try {
                $this->rules()->for($service, $role);
                $this->fail("{$service}/".var_export($role, true).' harus ditolak');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('service_type', $e->errors());
            }
        }
    }

    public function test_changing_config_changes_price(): void
    {
        $this->lookupReturning('valid', 'growing');
        config(['consultation_pricing.services.recovery.default.price' => 175_000]);

        $quote = $this->rules()->for('recovery', null)->quote('klien@example.com');

        $this->assertSame(175_000, $quote->amount);
        $this->assertSame(175_000, $this->rules()->for('recovery', null)->display()->amount);
    }
}
