<?php

namespace App\Providers;

use App\Models\FinancialBaseline;
use App\Models\Setting;
use App\Services\PortalAccessService;
use App\Services\PortalOnboardingService;
use App\Support\FinancialBaselineSchema;
use App\Support\PortalSession;
use App\Support\PrimaryCheckupUrl;
use App\Support\TelegramBotUrl;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Share $yfd contact data and $waBookingUrl with all views.
     * Reads from Setting (cached). Falls back to defaults if DB not ready.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        View::composer('*', function ($view) {
            try {
                $logo = Setting::val('brand.logo', 'images/yfd-logo.png');
                $logoFooter = trim((string) (Setting::val('brand.logo_footer') ?? ''));
                if ($logoFooter === '') {
                    $logoFooter = $logo;
                }

                $yfd = [
                    'brand'     => Setting::val('brand.name', 'Your Financial Doctor'),
                    'short'     => Setting::val('brand.short', 'YFD'),
                    'tagline'   => Setting::val('brand.tagline', "Indonesia's First Financial Health Center"),
                    'motto'     => Setting::val('brand.motto', 'Building Financially Healthy Generations.'),
                    'logo'      => $logo,
                    'logo_footer' => $logoFooter,
                    'phone'     => Setting::val('contact.phone', '+6285111228911'),
                    'wa_number' => Setting::val('contact.wa_number', '6285111228911'),
                    'email'     => Setting::val('contact.email', 'yfinancialdoctor@gmail.com'),
                    'instagram' => Setting::val('contact.instagram', 'your_financial_doctor'),
                    'tiktok'    => Setting::val('contact.tiktok', 'your_financial_doctor'),
                    'threads'   => Setting::val('contact.threads', 'your_financial_doctor'),
                    'address'   => Setting::val('contact.address', 'Indonesia'),
                ];

                $waMsg = Setting::val('contact.wa_message',
                    'Halo YFD, saya tertarik untuk konsultasi finansial. Mohon info jadwal dan paket yang tersedia. Terima kasih.'
                );

                $waBookingUrl = "https://wa.me/{$yfd['wa_number']}?text=" . rawurlencode($waMsg ?? '');

                $view->with('yfd', $yfd);
                $view->with('waBookingUrl', $waBookingUrl);
                $view->with('waDefaultMsg', $waMsg);
                self::shareTelegramBotUrls($view);
                $pc = PrimaryCheckupUrl::resolve();
                $view->with('primaryCheckupUrl', $pc['url']);
                $view->with('primaryCheckupNewTab', $pc['new_tab']);
            } catch (\Throwable $e) {
                // Fallback for fresh installs / migration not yet run.
                $view->with('yfd', [
                    'brand' => 'Your Financial Doctor',
                    'short' => 'YFD',
                    'tagline' => "Indonesia's First Financial Health Center",
                    'motto' => 'Building Financially Healthy Generations.',
                    'logo' => 'images/yfd-logo.png',
                    'logo_footer' => 'images/yfd-logo.png',
                    'phone' => '+6285111228911',
                    'wa_number' => '6285111228911',
                    'email' => 'yfinancialdoctor@gmail.com',
                    'instagram' => 'your_financial_doctor',
                    'tiktok' => 'your_financial_doctor',
                    'threads' => 'your_financial_doctor',
                    'address' => 'Indonesia',
                ]);
                $view->with('waBookingUrl', 'https://wa.me/6285111228911');
                $view->with('waDefaultMsg', '');
                self::shareTelegramBotUrls($view);
                try {
                    $pu = route('company.paket');
                } catch (\Throwable) {
                    $pu = '#';
                }
                $view->with('primaryCheckupUrl', $pu);
                $view->with('primaryCheckupNewTab', false);
            }
        });

        View::composer('portal.*', function ($view): void {
            try {
                $request = request();
                if ($request === null || ! PortalSession::isAuthenticated($request)) {
                    return;
                }
                if (! FinancialBaselineSchema::isReady()) {
                    $view->with('needsBaseline', false);

                    return;
                }
                $telegramUserId = (int) PortalSession::telegramUserId($request);
                $email = (string) (PortalSession::email($request) ?? '');
                app(\App\Services\BaselineClaimService::class)->claimForUser($email, $telegramUserId);
                $onboarding = app(PortalOnboardingService::class);
                $access = app(PortalAccessService::class);
                $featureService = app(\App\Services\PortalFeatureService::class);
                $portalOnboardingComplete = $access->hasBotPortalAccess($email, $telegramUserId)
                    ? $onboarding->hasBotPortalOnboardingComplete($email, $telegramUserId)
                    : $onboarding->hasFtsaPortalOnboardingComplete($email, $telegramUserId);
                $needsBaseline = $onboarding->userNeedsBotOnboardingBaseline($email, $telegramUserId);
                $needsFtsa = $onboarding->userNeedsFtsa($email, $telegramUserId);
                $needsFinancialDiagnostic = $onboarding->userNeedsFinancialDiagnostic($email, $telegramUserId);
                $ftsaUnlocked = $featureService->canAccessFtsa($telegramUserId, $email);
                $hasBotPortalAccess = $access->hasBotPortalAccess($email, $telegramUserId);

                $view->with('portalOnboardingComplete', $portalOnboardingComplete);
                $view->with('needsBaseline', $needsBaseline);
                $view->with('needsFtsa', $needsFtsa);
                $view->with('needsFinancialDiagnostic', $needsFinancialDiagnostic);
                $view->with('ftsaUnlocked', $ftsaUnlocked);
                $view->with('hasBotPortalAccess', $hasBotPortalAccess);
                $view->with('isFtsaOnlyPortalUser', $access->isFtsaOnlyPortalUser($email, $telegramUserId));
                $ftsaEval = app(\App\Services\FtsaEvaluationService::class);
                $view->with('ftsaRetakeLocked', $ftsaEval->isRetakeLocked($telegramUserId));
                $view->with('ftsaRetakeAvailableAt', $ftsaEval->retakeAvailableAt($telegramUserId));
                $view->with('portalDiagnosticUrl', $onboarding->portalDiagnosticUrl());
                $view->with('portalFtsaUrl', $onboarding->portalFtsaUrl());
                $view->with('portalBaselineUrl', $onboarding->portalBaselineUrl($email, $telegramUserId));
                $view->with('portalTransactionsUrl', $onboarding->portalTransactionsUrl());
                $needsSnapshotOnly = $needsBaseline && ! $needsFinancialDiagnostic;
                $view->with('baselineUrl', $portalOnboardingComplete
                    ? route('portal.baseline')
                    : ($needsSnapshotOnly
                        ? $onboarding->portalSnapshotEntryUrl($email, $telegramUserId)
                        : $onboarding->firstBaselineUrl($email, $telegramUserId)));
                $view->with('diagnosticCheckupUrl', $onboarding->portalDiagnosticUrl());
            } catch (\Throwable) {
                // Portal routes may not be registered during early boot.
            }
        });
    }

    private static function shareTelegramBotUrls($view): void
    {
        try {
            $view->with('telegramBotUrl', TelegramBotUrl::resolve());
            $view->with('telegramBotAppUrl', TelegramBotUrl::appDeepLink());
        } catch (\Throwable) {
            $view->with('telegramBotUrl', null);
            $view->with('telegramBotAppUrl', null);
        }
    }
}
