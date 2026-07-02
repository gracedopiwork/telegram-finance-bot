<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RedirectLegacyHosts::class,
        ]);
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => $request->is('admin') || $request->is('admin/*')
            ? route('admin.login')
            : route('portal.login'));
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
