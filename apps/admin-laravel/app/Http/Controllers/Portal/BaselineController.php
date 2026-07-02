<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\BaselineClaimService;
use App\Services\BucketPrescriptionService;
use App\Services\FtsaEvaluationService;
use App\Services\PortalAccessService;
use App\Services\PortalFeatureService;
use App\Services\PortalOnboardingService;
use App\Support\FinancialBaselineSchema;
use App\Support\PortalSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BaselineController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);

        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route($this->portalHomeRoute($request))
                ->with('error', 'Database baseline belum siap. Admin: php artisan migrate --force');
        }

        $baseline = FinancialBaseline::latestForUser($telegramUserId);

        if ($baseline === null) {
            return $this->redirectWhenNoBaseline($request);
        }

        try {
            $prescription = app(BucketPrescriptionService::class)->idealsForStage($baseline->financial_stage);
            $stageMeta = app(BucketPrescriptionService::class)->stageMeta($baseline->financial_stage);
            $domains = config('baseline_assessment.ftsa_domains', []);
            if (! is_array($domains)) {
                $domains = [];
            }

            return view('portal.baseline.result', [
                'active' => 'baseline',
                'baseline' => $baseline,
                'stageMeta' => $stageMeta,
                'prescription' => $prescription,
                'domains' => $domains,
                'reviewDue' => $baseline->isReviewDue(),
                'months' => $this->monthOptions(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->portalHomeRoute($request))
                ->with(
                    'error',
                    config('app.debug')
                        ? 'Gagal memuat baseline: '.$e->getMessage()
                        : 'Gagal memuat hasil baseline. Coba refresh atau hubungi admin.'
                );
        }
    }

    public function create(Request $request): View|RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        if ($telegramUserId <= 0) {
            return redirect()->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route($this->portalHomeRoute($request))
                ->with('error', 'Database baseline belum siap. Admin: php artisan migrate --force && php artisan config:clear');
        }

        $email = (string) (PortalSession::email($request) ?? '');
        $onboarding = app(PortalOnboardingService::class);
        $access = app(PortalAccessService::class);
        $isFtsaOnly = $access->isFtsaOnlyPortalUser($email);

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email);
        $ftsaStatus = app(PortalFeatureService::class)->ftsaEntitlementStatus($telegramUserId, $email);
        $ftsaEval = app(FtsaEvaluationService::class);
        $ftsaRetakeLocked = $ftsaEval->isRetakeLocked($telegramUserId);

        if ($isFtsaOnly && $ftsaRetakeLocked) {
            return redirect()->route('portal.emotional')
                ->with(
                    'info',
                    'FTSA tidak dapat diisi ulang sebelum masa evaluasi berakhir pada '
                    .$ftsaStatus['ends_at']?->format('d M Y').'.'
                );
        }

        $baselineConfig = app(\App\Services\DiagnosticConfigService::class)->fullBaselineConfig();
        if (! isset($baselineConfig['financial_stage'])) {
            return redirect()->route($this->portalHomeRoute($request))
                ->with('error', 'Konfigurasi baseline tidak terbaca. Admin: php artisan config:clear');
        }

        return view('portal.baseline.form', [
            'active' => 'baseline',
            'config' => $baselineConfig,
            'hasBaseline' => ! $onboarding->userNeedsBaseline($email, $telegramUserId),
            'ftsaUnlocked' => $ftsaUnlocked,
            'ftsaEndsAt' => $ftsaStatus['ends_at'],
            'ftsaRetakeLocked' => $ftsaRetakeLocked,
            'isFtsaOnlyPortalUser' => $isFtsaOnly,
            'needsFinancialDiagnostic' => $onboarding->userNeedsFinancialDiagnostic($email, $telegramUserId),
            'months' => $this->monthOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        if ($telegramUserId <= 0) {
            return redirect()->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        if (! FinancialBaselineSchema::isReady()) {
            return back()->withInput()->with(
                'error',
                'Database baseline belum siap. Admin: php artisan migrate --force && php artisan config:clear'
            );
        }

        $baselineConfig = app(\App\Services\DiagnosticConfigService::class)->fullBaselineConfig();
        if (! isset($baselineConfig['financial_stage'])) {
            return back()->withInput()->with('error', 'Konfigurasi baseline tidak terbaca. Admin: php artisan config:clear');
        }

        $this->normalizeSnapshotInput($request);

        $email = (string) (PortalSession::email($request) ?? '');
        $isFtsaOnly = app(PortalAccessService::class)->isFtsaOnlyPortalUser($email);
        $ftsaEval = app(FtsaEvaluationService::class);

        if ($ftsaEval->isRetakeLocked($telegramUserId)) {
            if ($isFtsaOnly) {
                $endsAt = app(PortalFeatureService::class)->ftsaEntitlementStatus($telegramUserId, $email)['ends_at'];

                return back()->withInput()->with(
                    'error',
                    'FTSA tidak dapat diisi ulang sebelum masa evaluasi berakhir pada '.$endsAt?->format('d M Y').'.'
                );
            }
        }

        try {
            $service = app(BaselineAssessmentService::class);
            $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email);

            if ($isFtsaOnly) {
                $rules = $service->validationRulesFtsaOnly($ftsaUnlocked);
            } else {
                $rules = $service->validationRules();
                if (! $ftsaUnlocked) {
                    foreach (range(1, 32) as $i) {
                        unset($rules["ftsa.{$i}"]);
                    }
                }
            }

            if ($ftsaEval->isRetakeLocked($telegramUserId)) {
                foreach (range(1, 32) as $i) {
                    unset($rules["ftsa.{$i}"]);
                }
            }

            $validated = $request->validate($rules);
            $existing = FinancialBaseline::latestForUser($telegramUserId);

            if ($ftsaEval->isRetakeLocked($telegramUserId) && $existing !== null) {
                $priorFtsa = $existing->answers_json['ftsa'] ?? [];
                if (is_array($priorFtsa) && $priorFtsa !== []) {
                    $validated['ftsa'] = $priorFtsa;
                }
            }

            if ($isFtsaOnly) {
                $priorFs = $existing?->answers_json['fs'] ?? [];
                $validated['fs'] = (is_array($priorFs) && $priorFs !== []) ? $priorFs : [];
            }

            $result = $service->assess($validated, $ftsaUnlocked);
            $snapshot = $validated['snapshot'] ?? [];

            $payload = $this->buildBaselinePayload($request, $telegramUserId, $result, $snapshot, $ftsaUnlocked);

            if ($isFtsaOnly && $existing !== null) {
                $payload = $this->mergeExistingSnapshotFields($existing, $payload);
                $existing->update($payload);
            } else {
                FinancialBaseline::query()->create($payload);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                'Gagal menyimpan baseline (database). Admin: php artisan migrate --force'
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                config('app.debug')
                    ? 'Error: '.$e->getMessage()
                    : 'Gagal menyimpan baseline. Coba login ulang dari bot (/web) lalu isi lagi.'
            );
        }

        $successMsg = ($isFtsaOnly ?? false)
            ? 'FTSA berhasil disimpan. Behavioral dashboard Anda sudah aktif.'
            : 'Baseline berhasil disimpan. Prescription bucket dashboard disesuaikan dengan tahap keuangan Anda.';

        return redirect()
            ->route(($isFtsaOnly ?? false) ? 'portal.emotional' : 'portal.baseline')
            ->with('success', $successMsg);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function buildBaselinePayload(Request $request, int $telegramUserId, array $result, array $snapshot, bool $ftsaUnlocked): array
    {
        $payload = [
            'telegram_user_id' => $telegramUserId,
            'email' => $this->sessionEmail($request),
            'assessed_at' => $result['assessed_at'],
            'next_review_at' => $result['next_review_at'],
            'financial_stage_score' => $result['financial_stage_score'],
            'financial_stage' => $result['financial_stage'],
            'stage_label' => $result['stage_label'],
            'current_goal' => $snapshot['current_goal'] ?? null,
            'avg_monthly_income' => $this->nullableInt($snapshot['avg_monthly_income'] ?? null),
            'emergency_fund' => $this->nullableInt($snapshot['emergency_fund'] ?? null),
            'cash_savings' => $this->nullableInt($snapshot['cash_savings'] ?? null),
            'total_investment' => $this->nullableInt($snapshot['total_investment'] ?? null),
            'total_asset' => $this->nullableInt($snapshot['total_asset'] ?? null),
            'total_debt' => $this->nullableInt($snapshot['total_debt'] ?? null),
            'has_bpjs' => (bool) ($snapshot['has_bpjs'] ?? false),
            'has_health_insurance' => (bool) ($snapshot['has_health_insurance'] ?? false),
            'has_income_protection' => (bool) ($snapshot['has_income_protection'] ?? false),
            'has_life_insurance' => (bool) ($snapshot['has_life_insurance'] ?? false),
            'ftsa_chd' => $result['ftsa_chd'],
            'ftsa_rvd' => $result['ftsa_rvd'],
            'ftsa_ssd' => $result['ftsa_ssd'],
            'ftsa_esd' => $result['ftsa_esd'],
            'dominant_archetype' => $result['dominant_archetype'],
            'dominant_archetype_label' => $result['dominant_archetype_label'],
            'chd_level' => $result['chd_level'],
            'rvd_level' => $result['rvd_level'],
            'ssd_level' => $result['ssd_level'],
            'esd_level' => $result['esd_level'],
            'answers_json' => $result['answers'],
        ];

        if (! $ftsaUnlocked) {
            $payload['ftsa_chd'] = 0;
            $payload['ftsa_rvd'] = 0;
            $payload['ftsa_ssd'] = 0;
            $payload['ftsa_esd'] = 0;
            $payload['dominant_archetype'] = 'locked';
            $payload['dominant_archetype_label'] = 'FTSA Premium Locked';
            $payload['chd_level'] = null;
            $payload['rvd_level'] = null;
            $payload['ssd_level'] = null;
            $payload['esd_level'] = null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergeExistingSnapshotFields(FinancialBaseline $existing, array $payload): array
    {
        foreach ([
            'current_goal',
            'avg_monthly_income',
            'emergency_fund',
            'cash_savings',
            'total_investment',
            'total_asset',
            'total_debt',
            'has_bpjs',
            'has_health_insurance',
            'has_income_protection',
            'has_life_insurance',
        ] as $field) {
            if (($payload[$field] === null || $payload[$field] === false) && $existing->{$field} !== null) {
                $payload[$field] = $existing->{$field};
            }
        }

        return $payload;
    }

    private function normalizeSnapshotInput(Request $request): void
    {
        $snapshot = $request->input('snapshot', []);
        if (! is_array($snapshot)) {
            return;
        }

        foreach ([
            'avg_monthly_income',
            'emergency_fund',
            'cash_savings',
            'total_investment',
            'total_asset',
            'total_debt',
        ] as $field) {
            if (array_key_exists($field, $snapshot) && $snapshot[$field] === '') {
                $snapshot[$field] = null;
            }
        }

        $request->merge(['snapshot' => $snapshot]);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function monthOptions(): array
    {
        $options = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            try {
                $label = $cursor->copy()->locale(app()->getLocale())->translatedFormat('F Y');
            } catch (\Throwable) {
                $label = $cursor->format('M Y');
            }

            $options[] = [
                'value' => $cursor->format('Y-m'),
                'label' => $label,
            ];
            $cursor = $cursor->copy()->subMonth();
        }

        return $options;
    }

    private function portalHomeRoute(Request $request): string
    {
        $email = (string) (PortalSession::email($request) ?? '');

        return app(PortalAccessService::class)->defaultPortalHomeRoute($email);
    }

    private function sessionEmail(Request $request): ?string
    {
        $email = PortalSession::email($request);

        return $email !== null && $email !== '' ? $email : null;
    }

    private function redirectWhenNoBaseline(Request $request): RedirectResponse
    {
        return redirect()->route($this->portalHomeRoute($request))
            ->with('info', 'Belum ada data baseline. Gunakan banner di dashboard untuk mengisi diagnostik atau FTSA.');
    }
}
