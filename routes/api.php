<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/extension/login', [\App\Http\Controllers\Api\ExtensionController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/extension/jobs', [\App\Http\Controllers\Api\ExtensionController::class, 'storeJob']);
});
