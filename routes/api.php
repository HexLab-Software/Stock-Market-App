<?php

use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/telegram-link', [PortfolioController::class, 'telegramLink']);
    Route::apiResource('portfolio', PortfolioController::class);
});

Route::post('/telegram/webhook/' . config('services.telegram.token'), [TelegramController::class, 'handle']);

// Cron endpoints for Cloud Scheduler (protected by middleware)
Route::prefix('cron')->middleware('cron.auth')->group(function () {
    Route::get('/fetch-stocks', function () {
        Artisan::call('stocks:fetch');
        return response()->json(['status' => 'success', 'message' => 'Stock data fetch initiated']);
    });

    Route::get('/daily-report', function () {
        Artisan::call('report:daily');
        return response()->json(['status' => 'success', 'message' => 'Daily reports sent']);
    });
});
