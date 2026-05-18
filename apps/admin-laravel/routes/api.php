<?php

use App\Http\Controllers\Api\BotSheetController;
use Illuminate\Support\Facades\Route;

Route::post('/bot/orders/{orderCode}/ensure-sheet', [BotSheetController::class, 'ensureSheetAccess']);
