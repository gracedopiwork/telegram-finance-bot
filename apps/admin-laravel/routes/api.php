<?php

use App\Http\Controllers\Api\BotAiHealthController;
use App\Http\Controllers\Api\BotCategoryRulesController;
use App\Http\Controllers\Api\BotConsentController;
use App\Http\Controllers\Api\BotLicenseActivateController;
use App\Http\Controllers\Api\BotOnboardingController;
use App\Http\Controllers\Api\BotPortalLinkController;
use App\Http\Controllers\Api\BotSocialLiquidityController;
use App\Http\Controllers\Api\BotTransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/bot/ai-health', [BotAiHealthController::class, 'record']);
Route::post('/bot/activate', [BotLicenseActivateController::class, 'store']);
Route::get('/bot/consent', [BotConsentController::class, 'show']);
Route::post('/bot/consent', [BotConsentController::class, 'store']);
Route::get('/bot/onboarding', [BotOnboardingController::class, 'show']);
Route::post('/bot/onboarding', [BotOnboardingController::class, 'store']);
Route::get('/bot/category-rules', [BotCategoryRulesController::class, 'show']);
Route::post('/bot/transactions/preview', [BotTransactionController::class, 'preview']);
Route::post('/bot/transactions', [BotTransactionController::class, 'store']);
Route::get('/bot/social-liquidity', [BotSocialLiquidityController::class, 'index']);
Route::post('/bot/portal-link', [BotPortalLinkController::class, 'create']);
