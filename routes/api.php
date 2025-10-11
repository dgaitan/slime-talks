<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MessageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::middleware('client.auth')->group(function () {
        Route::get('client/{client}', [ClientController::class, 'show']);
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/active', [CustomerController::class, 'getActiveCustomers']);
        Route::get('customers/active-for-sender', [CustomerController::class, 'getActiveCustomersForSender']);
        Route::get('channels', [ChannelController::class, 'index']);
        Route::post('channels', [ChannelController::class, 'store']);
        Route::get('channels/{channel}', [ChannelController::class, 'show']);
        Route::get('channels/customer/{customerUuid}', [ChannelController::class, 'getCustomerChannels']);
        Route::get('channels/by-email', [ChannelController::class, 'getChannelsByEmail']);
        Route::post('messages', [MessageController::class, 'store']);
        Route::post('messages/send-to-customer', [MessageController::class, 'sendToCustomer']);
        Route::get('messages/channel/{channelUuid}', [MessageController::class, 'getChannelMessages']);
        Route::get('messages/customer/{customerUuid}', [MessageController::class, 'getCustomerMessages']);
        Route::get('messages/between', [MessageController::class, 'getMessagesBetweenCustomers']);
    });
});
