<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdvisorsController;
use App\Http\Controllers\Admin\ArticlesController;
use App\Http\Controllers\Admin\DigitalProductsController;
use App\Http\Controllers\Admin\FaqsController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LandingController;
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
Route::get('/paket',         [LandingController::class, 'paket'])->name('company.paket');
Route::get('/penasihat',     [LandingController::class, 'penasihat'])->name('company.penasihat');
Route::get('/produk',        [LandingController::class, 'produk'])->name('company.produk');
Route::get('/wealthpedia',   [LandingController::class, 'wealthpedia'])->name('company.wealthpedia');
Route::get('/pertemuan',     [LandingController::class, 'pertemuan'])->name('company.pertemuan');
Route::get('/informasi',     [LandingController::class, 'informasi'])->name('company.informasi');

// Alias backward-compat untuk nama route lama 'landing'
Route::get('/landing',       [LandingController::class, 'home'])->name('landing');

/*
|--------------------------------------------------------------------------
| Checkout & Webhook
|--------------------------------------------------------------------------
*/
Route::get('/checkout/{code}',  [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout',        [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/finish/done', [CheckoutController::class, 'finish'])->name('checkout.finish');
Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.midtrans');

/*
|--------------------------------------------------------------------------
| Authentication (AdminLTE)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin (Company Profile CRUD) — protected
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // Dashboard & legacy actions
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/user-sheets', [AdminController::class, 'storeUserSheet'])->name('user-sheets.store');
    Route::post('/dashboard-sync', [AdminController::class, 'runDashboardSync'])->name('dashboard-sync');

    // Settings (key/value)
    Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // CRUD resources
    Route::resource('packages',         PackagesController::class)->except(['show']);
    Route::resource('advisors',         AdvisorsController::class)->except(['show']);
    Route::resource('services',         ServicesController::class)->except(['show']);
    Route::resource('faqs',             FaqsController::class)->except(['show']);
    Route::resource('articles',         ArticlesController::class)->except(['show']);
    Route::resource('digital-products', DigitalProductsController::class)->except(['show']);

    // Transaksi (read-mostly)
    Route::get('orders',                [OrdersController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}',        [OrdersController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrdersController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('orders/{order}/provision-sheet', [OrdersController::class, 'provisionSheet'])->name('orders.provisionSheet');
    Route::delete('orders/{order}',     [OrdersController::class, 'destroy'])->name('orders.destroy');
});
