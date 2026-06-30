<?php

use App\Http\Controllers\Api\BotAiHealthController;
use App\Http\Controllers\Api\BotPortalLinkController;
use App\Http\Controllers\Api\BotTransactionController;
use App\Http\Controllers\Api\DashboardSyncWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/bot/ai-health', [BotAiHealthController::class, 'record']);
Route::post('/bot/transactions', [BotTransactionController::class, 'store']);
Route::post('/bot/portal-link', [BotPortalLinkController::class, 'create']);
Route::post('/dashboard/sync-webhook', [DashboardSyncWebhookController::class, 'trigger']);
