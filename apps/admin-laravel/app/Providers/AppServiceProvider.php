<?php

namespace App\Providers;

use App\Models\Setting;
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
        View::composer('*', function ($view) {
            try {
                $yfd = [
                    'brand'     => Setting::val('brand.name', 'Your Financial Doctor'),
                    'short'     => Setting::val('brand.short', 'YFD'),
                    'tagline'   => Setting::val('brand.tagline', "Indonesia's First Financial Health Center"),
                    'motto'     => Setting::val('brand.motto', 'Building Financially Healthy Generations.'),
                    'logo'      => Setting::val('brand.logo', 'images/yfd-logo.png'),
                    'phone'     => Setting::val('contact.phone', '+6285111228911'),
                    'wa_number' => Setting::val('contact.wa_number', '6285111228911'),
                    'email'     => Setting::val('contact.email', 'yfinancialdoctor@gmail.com'),
                    'instagram' => Setting::val('contact.instagram', 'your_financial_doctor'),
                    'tiktok'    => Setting::val('contact.tiktok', 'your_financial_doctor'),
                    'address'   => Setting::val('contact.address', 'Indonesia'),
                ];

                $waMsg = Setting::val('contact.wa_message',
                    'Halo YFD, saya tertarik untuk konsultasi finansial. Mohon info jadwal dan paket yang tersedia. Terima kasih.'
                );

                $waBookingUrl = "https://wa.me/{$yfd['wa_number']}?text=" . rawurlencode($waMsg ?? '');

                $view->with('yfd', $yfd);
                $view->with('waBookingUrl', $waBookingUrl);
                $view->with('waDefaultMsg', $waMsg);
            } catch (\Throwable $e) {
                // Fallback for fresh installs / migration not yet run.
                $view->with('yfd', [
                    'brand' => 'Your Financial Doctor',
                    'short' => 'YFD',
                    'tagline' => "Indonesia's First Financial Health Center",
                    'motto' => 'Building Financially Healthy Generations.',
                    'logo' => 'images/yfd-logo.png',
                    'phone' => '+6285111228911',
                    'wa_number' => '6285111228911',
                    'email' => 'yfinancialdoctor@gmail.com',
                    'instagram' => 'your_financial_doctor',
                    'tiktok' => 'your_financial_doctor',
                    'address' => 'Indonesia',
                ]);
                $view->with('waBookingUrl', 'https://wa.me/6285111228911');
                $view->with('waDefaultMsg', '');
            }
        });
    }
}
