<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdvisorsController;
use App\Http\Controllers\Admin\ArticlesController;
use App\Http\Controllers\Admin\CategoryBucketMappingsController;
use App\Http\Controllers\Admin\DiagnosticQuestionsController;
use App\Http\Controllers\Admin\DiagnosticResultsController;
use App\Http\Controllers\Admin\DiagnosticStagesController;
use App\Http\Controllers\Admin\FtsaQuestionsController;
use App\Http\Controllers\Admin\FtsaResultsController;
use App\Http\Controllers\Admin\DigitalProductsController;
use App\Http\Controllers\Admin\FaqsController;
use App\Http\Controllers\Admin\AffiliatesController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\GoogleBusinessReviewsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublicCheckupController;
use App\Http\Controllers\Portal\AffiliateController as PortalAffiliateController;
use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use App\Http\Controllers\Portal\BaselineController as PortalBaselineController;
use App\Http\Controllers\Portal\CheckoutController as PortalCheckoutController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\DiagnosticController as PortalDiagnosticController;
use App\Http\Controllers\Portal\TimezoneController as PortalTimezoneController;
use App\Http\Controllers\Portal\TransactionsController as PortalTransactionsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Profile (YFD — Your Financial Doctor)
|--------------------------------------------------------------------------
| Setiap halaman company profile punya URL & view sendiri di
| resources/views/Companyprofile/*.blade.php
*/
Route::get('/',              [LandingController::class, 'home'])->name('company.home');
Route::get('/tentang',       [LandingController::class, 'tentang'])->name('company.tentang');
Route::get('/layanan',       [LandingController::class, 'layanan'])->name('company.layanan');
Route::get('/layanan/recovery', [LandingController::class, 'bundle'])->defaults('slug', 'recovery')->name('company.bundle.recovery');
Route::get('/layanan/edukasi',  [LandingController::class, 'bundle'])->defaults('slug', 'edukasi')->name('company.bundle.education');
Route::get('/paket',         [LandingController::class, 'paket'])->name('company.paket');
Route::get('/penasihat',     [LandingController::class, 'penasihat'])->name('company.penasihat');
Route::get('/produk',        [LandingController::class, 'produk'])->name('company.produk');
Route::get('/wealthpedia',   [LandingController::class, 'wealthpedia'])->name('company.wealthpedia');
Route::get('/wealthpedia/{slug}', [LandingController::class, 'wealthpediaShow'])->name('company.wealthpedia.show');
Route::get('/pertemuan',     [LandingController::class, 'pertemuan'])->name('company.pertemuan');
Route::get('/informasi',     [LandingController::class, 'informasi'])->name('company.informasi');

// Alias backward-compat untuk nama route lama 'landing'
Route::get('/landing',       [LandingController::class, 'home'])->name('landing');

/*
|--------------------------------------------------------------------------
| Financial Health Check-Up (gratis di landing — tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/check-up', [PublicCheckupController::class, 'show'])->name('checkup.show');
Route::post('/check-up', [PublicCheckupController::class, 'store'])->name('checkup.store');
Route::get('/check-up/hasil', [PublicCheckupController::class, 'result'])->name('checkup.result');

/*
|--------------------------------------------------------------------------
| Checkout & Webhook
|--------------------------------------------------------------------------
*/
Route::get('/checkout/{code}',  [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout',        [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/finish/done', [CheckoutController::class, 'finish'])->name('checkout.finish');
Route::match(['get', 'post'], '/webhooks/midtrans', [WebhookController::class, 'midtrans'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.midtrans');

/*
|--------------------------------------------------------------------------
| Authentication (AdminLTE) — /admin/login (bukan /login publik)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Login publik → portal pelanggan (bukan admin console)
|--------------------------------------------------------------------------
*/
Route::redirect('/login', '/portal/login', 302);

// Form lama yang masih POST ke /login (sebelum admin dipindah ke /admin/login)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.legacy.attempt');

/*
|--------------------------------------------------------------------------
| Admin (Company Profile CRUD) — protected
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/server-health', [\App\Http\Controllers\Admin\ServerHealthController::class, 'index'])->name('server-health.index');

    // Settings (key/value)
    Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Google Business Profile reviews (owner OAuth sync)
    Route::get('/google-reviews/connect', [GoogleBusinessReviewsController::class, 'connect'])->name('google-reviews.connect');
    Route::get('/google-reviews/callback', [GoogleBusinessReviewsController::class, 'callback'])->name('google-reviews.callback');
    Route::get('/google-reviews/pick-location', [GoogleBusinessReviewsController::class, 'pickLocationForm'])->name('google-reviews.pick-location');
    Route::post('/google-reviews/pick-location', [GoogleBusinessReviewsController::class, 'pickLocation'])->name('google-reviews.pick-location.store');
    Route::post('/google-reviews/sync', [GoogleBusinessReviewsController::class, 'sync'])->name('google-reviews.sync');
    Route::post('/google-reviews/disconnect', [GoogleBusinessReviewsController::class, 'disconnect'])->name('google-reviews.disconnect');
    Route::post('/google-reviews/{review}/toggle', [GoogleBusinessReviewsController::class, 'toggle'])->name('google-reviews.toggle');

    // CRUD resources
    Route::resource('packages',         PackagesController::class)->except(['show']);
    Route::resource('advisors',         AdvisorsController::class)->except(['show']);
    Route::resource('services',         ServicesController::class)->except(['show']);
    Route::resource('faqs',             FaqsController::class)->except(['show']);
    Route::resource('articles',         ArticlesController::class)->except(['show']);
    Route::resource('digital-products', DigitalProductsController::class)->except(['show']);
    Route::patch('digital-products/{digital_product}/billing-mode', [DigitalProductsController::class, 'updateBillingMode'])
        ->name('digital-products.billing-mode');
    Route::resource('category-bucket-mappings', CategoryBucketMappingsController::class)->except(['show']);
    Route::post('category-bucket-mappings/sync-defaults', [CategoryBucketMappingsController::class, 'syncDefaults'])
        ->name('category-bucket-mappings.sync');
    Route::resource('diagnostic-questions', DiagnosticQuestionsController::class)->except(['show']);
    Route::get('diagnostic-results', [DiagnosticResultsController::class, 'index'])->name('diagnostic-results.index');
    Route::get('diagnostic-results/{financial_baseline}', [DiagnosticResultsController::class, 'show'])->name('diagnostic-results.show');
    Route::delete('diagnostic-results/{financial_baseline}', [DiagnosticResultsController::class, 'destroy'])->name('diagnostic-results.destroy');
    Route::get('ftsa-results', [FtsaResultsController::class, 'index'])->name('ftsa-results.index');
    Route::get('ftsa-results/{financial_baseline}', [FtsaResultsController::class, 'show'])->name('ftsa-results.show');
    Route::delete('ftsa-results/{financial_baseline}', [FtsaResultsController::class, 'destroy'])->name('ftsa-results.destroy');
    Route::get('ftsa-questions', [FtsaQuestionsController::class, 'index'])->name('ftsa-questions.index');
    Route::post('ftsa-questions/sync', [FtsaQuestionsController::class, 'sync'])->name('ftsa-questions.sync');
    Route::get('ftsa-questions/{ftsa_question}/edit', [FtsaQuestionsController::class, 'edit'])->name('ftsa-questions.edit');
    Route::put('ftsa-questions/{ftsa_question}', [FtsaQuestionsController::class, 'update'])->name('ftsa-questions.update');
    Route::get('diagnostic-stages', [DiagnosticStagesController::class, 'index'])->name('diagnostic-stages.index');
    Route::get('diagnostic-stages/{diagnostic_stage}/edit', [DiagnosticStagesController::class, 'edit'])->name('diagnostic-stages.edit');
    Route::put('diagnostic-stages/{diagnostic_stage}', [DiagnosticStagesController::class, 'update'])->name('diagnostic-stages.update');

    // Transaksi (read-mostly)
    Route::get('orders',                [OrdersController::class, 'index'])->name('orders.index');
    Route::get('orders/create',         [OrdersController::class, 'create'])->name('orders.create');
    Route::post('orders',               [OrdersController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}',        [OrdersController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrdersController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('orders/{order}/sync-payment', [OrdersController::class, 'syncPayment'])->name('orders.syncPayment');
    Route::post('orders/{order}/resend-delivery', [OrdersController::class, 'resendDelivery'])->name('orders.resendDelivery');
    Route::post('orders/{order}/resend-delivery-email', [OrdersController::class, 'resendDeliveryEmail'])->name('orders.resendDeliveryEmail');
    Route::post('orders/{order}/purge-customer-data', [OrdersController::class, 'purgeCustomerData'])->name('orders.purgeCustomerData');
    Route::post('orders/{order}/affiliate', [OrdersController::class, 'upsertAffiliate'])->name('orders.affiliate');
    Route::delete('orders/{order}',     [OrdersController::class, 'destroy'])->name('orders.destroy');

    Route::get('affiliates', [AffiliatesController::class, 'index'])->name('affiliates.index');
    Route::get('affiliates/create', [AffiliatesController::class, 'create'])->name('affiliates.create');
    Route::get('affiliates/search', [AffiliatesController::class, 'search'])->name('affiliates.search');
    Route::get('affiliates/suggest-code', [AffiliatesController::class, 'suggestCode'])->name('affiliates.suggest-code');
    Route::post('affiliates', [AffiliatesController::class, 'store'])->name('affiliates.store');
    Route::get('affiliates/claims', [AffiliatesController::class, 'claims'])->name('affiliates.claims');
    Route::post('affiliates/claims/{claim}', [AffiliatesController::class, 'processClaim'])->name('affiliates.claims.process');
    Route::get('affiliates/commissions', [AffiliatesController::class, 'commissions'])->name('affiliates.commissions');
    Route::get('affiliates/{affiliate}', [AffiliatesController::class, 'show'])->name('affiliates.show');
    Route::patch('affiliates/{affiliate}', [AffiliatesController::class, 'update'])->name('affiliates.update');
    Route::post('affiliates/{affiliate}/toggle', [AffiliatesController::class, 'toggle'])->name('affiliates.toggle');
});

/*
|--------------------------------------------------------------------------
| Portal pengguna (dashboard keuangan — login terpisah dari admin)
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('login.attempt');
    Route::get('/masuk', [PortalAuthController::class, 'autoLogin'])
        ->name('auto-login')
        ->middleware('signed');

    Route::middleware('portal.auth')->group(function () {
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::post('/pengaturan/zona-waktu', [PortalTimezoneController::class, 'updateManual'])->name('timezone.manual');
        Route::post('/pengaturan/zona-waktu/auto', [PortalTimezoneController::class, 'updateAuto'])->name('timezone.auto');
        Route::get('/baseline', [PortalBaselineController::class, 'index'])->name('baseline');
        Route::get('/baseline/baru', [PortalBaselineController::class, 'create'])->name('baseline.create');
        Route::post('/baseline', [PortalBaselineController::class, 'store'])->name('baseline.store');
        Route::get('/ftsa/baru', [PortalBaselineController::class, 'createFtsa'])->name('ftsa.create');
        Route::post('/ftsa', [PortalBaselineController::class, 'storeFtsa'])->name('ftsa.store');
        Route::get('/diagnostik', [PortalDiagnosticController::class, 'show'])->name('diagnostic');
        Route::post('/diagnostik', [PortalDiagnosticController::class, 'store'])->name('diagnostic.store');
        Route::get('/emotional', [PortalDashboardController::class, 'emotional'])->name('emotional');
        Route::post('/checkout/ftsa', [PortalCheckoutController::class, 'ftsaSnap'])->name('checkout.ftsa');
        Route::get('/checkout/ftsa/{order}/status', [PortalCheckoutController::class, 'ftsaStatus'])->name('checkout.ftsa.status');
        Route::post('/checkout/bot', [PortalCheckoutController::class, 'botSnap'])->name('checkout.bot');
        Route::get('/checkout/bot/{order}/status', [PortalCheckoutController::class, 'botStatus'])->name('checkout.bot.status');

        Route::get('/', function () {
            $request = request();
            $email = (string) (\App\Support\PortalSession::email($request) ?? '');
            $telegramUserId = (int) \App\Support\PortalSession::telegramUserId($request);

            if (app(\App\Services\PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId)) {
                $onboarding = app(\App\Services\PortalOnboardingService::class);

                return redirect()->route($onboarding->portalHomeRouteName($email, $telegramUserId));
            }

            return redirect()->route('portal.dashboard');
        })->name('home');

        Route::middleware('portal.bot')->group(function () {
            Route::get('/premium', [PortalDashboardController::class, 'premium'])->name('premium');
            Route::get('/referral', [PortalAffiliateController::class, 'index'])->name('affiliate');
            Route::post('/referral/klaim', [PortalAffiliateController::class, 'claim'])->name('affiliate.claim');
            Route::get('/transaksi', [PortalDashboardController::class, 'transactions'])->name('transactions');
            Route::get('/transaksi/template', [PortalTransactionsController::class, 'importTemplate'])->name('transactions.template');
            Route::post('/transaksi/import', [PortalTransactionsController::class, 'import'])->name('transactions.import');
            Route::delete('/transaksi', [PortalTransactionsController::class, 'destroySelected'])->name('transactions.destroy-selected');
            Route::delete('/transaksi/bulan', [PortalTransactionsController::class, 'destroyMonth'])->name('transactions.destroy-month');
            Route::delete('/transaksi/{transaction}', [PortalTransactionsController::class, 'destroy'])->name('transactions.destroy');
            Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');
            Route::post('/dashboard/generate-manual', [PortalDashboardController::class, 'generateManualFinancialGuidance'])->name('dashboard.generate-manual');
            Route::post('/emotional/generate-manual', [PortalDashboardController::class, 'generateManualBehavioralGuidance'])->name('emotional.generate-manual');
        });
    });
});
