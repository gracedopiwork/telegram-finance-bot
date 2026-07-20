<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $tz = config('portal_ai.guidance_timezone', 'Asia/Jakarta');
        $weeklyTime = (string) config('portal_ai.guidance_weekly_time', '22:00');
        $monthlyTime = (string) config('portal_ai.guidance_monthly_time', '22:00');

        $schedule->command('portal:generate-guidance weekly --force')
            ->weeklyOn(0, $weeklyTime)
            ->timezone($tz)
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('portal:generate-guidance monthly --force')
            ->dailyAt($monthlyTime)
            ->timezone($tz)
            ->when(fn () => now($tz)->isLastOfMonth())
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('google-reviews:sync')
            ->dailyAt('06:30')
            ->timezone($tz)
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RedirectLegacyHosts::class,
        ]);
        // Midtrans POST tanpa CSRF token — exclude di level middleware + di route.
        $middleware->validateCsrfTokens(except: [
            'webhooks/midtrans',
        ]);
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => $request->is('admin') || $request->is('admin/*')
            ? route('admin.login')
            : route('portal.login'));
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->is('admin') || $request->is('admin/*')
            ? route('admin.index')
            : route('company.home'));
        $middleware->alias([
            'portal.auth' => \App\Http\Middleware\EnsurePortalAuth::class,
            'portal.bot' => \App\Http\Middleware\EnsureBotPortalAccess::class,
            'portal.baseline' => \App\Http\Middleware\EnsureBaselineExists::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Halaman ramah di resources/views/errors/* tampil otomatis saat APP_DEBUG=false.
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
