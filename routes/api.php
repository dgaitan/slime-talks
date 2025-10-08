<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::middleware('client.auth')->group(function () {
        Route::get('client/{client}', [ClientController::class, 'show']);
        Route::apiResource('customers', CustomerController::class);
    });
});
