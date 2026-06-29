<?php

use App\Http\Controllers\Api\BotSheetController;
use App\Http\Controllers\Api\DashboardSyncWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/bot/orders/{orderCode}/ensure-sheet', [BotSheetController::class, 'ensureSheetAccess']);
Route::post('/dashboard/sync-webhook', [DashboardSyncWebhookController::class, 'trigger']);
