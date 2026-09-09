<?php

use App\Http\Controllers\FcmDeviceTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.any.portal'])->group(function () {
    Route::post('fcm/register', [FcmDeviceTokenController::class, 'register'])
        ->middleware('throttle:30,1')
        ->name('fcm.register');
    Route::delete('fcm/unregister', [FcmDeviceTokenController::class, 'unregister'])
        ->middleware('throttle:30,1')
        ->name('fcm.unregister');
});
