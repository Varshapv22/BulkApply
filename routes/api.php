<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/extension/login', [\App\Http\Controllers\Api\ExtensionController::class, 'login'])
    ->middleware('feature:feature.chrome_extension');

Route::middleware(['auth:sanctum', 'feature:feature.chrome_extension'])->group(function () {
    Route::post('/extension/jobs', [\App\Http\Controllers\Api\ExtensionController::class, 'storeJob']);
    Route::get('/extension/profile', [\App\Http\Controllers\Api\ExtensionController::class, 'profile']);
    Route::post('/extension/jobs/{job}/auto-apply-status', [\App\Http\Controllers\Api\ExtensionController::class, 'autoApplyStatus']);
});
