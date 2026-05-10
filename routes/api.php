<?php

use App\Http\Controllers\Analysis\AnalysisController;
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Resume\ResumeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);

    Route::middleware('auth:sanctum')->post('/logout', LogoutController::class);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);

    Route::apiResource('resumes', ResumeController::class)
        ->only(['index', 'store', 'show', 'destroy']);

    Route::get('/analyses/{analysis}/status', [AnalysisController::class, 'status']);

    Route::apiResource('analyses', AnalysisController::class)
        ->only(['index', 'store', 'show']);

    Route::get('/applications/stats', [ApplicationController::class, 'stats']);
    Route::patch('/applications/{application}/move', [ApplicationController::class, 'move']);

    Route::apiResource('applications', ApplicationController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});
