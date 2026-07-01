<?php

namespace App\Providers;

use App\Models\FinancialBaseline;
use App\Models\Setting;
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
                $view->with('telegramBotUrl', TelegramBotUrl::resolve());
                $view->with('telegramBotAppUrl', TelegramBotUrl::appDeepLink());
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
                $view->with('telegramBotUrl', TelegramBotUrl::resolve());
                $view->with('telegramBotAppUrl', TelegramBotUrl::appDeepLink());
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
                $view->with('needsBaseline', FinancialBaseline::userNeedsBaseline($telegramUserId));
                $view->with('baselineUrl', route('portal.baseline.create'));
            } catch (\Throwable) {
                // Portal routes may not be registered during early boot.
            }
        });
    }
}
