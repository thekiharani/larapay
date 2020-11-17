<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('access_token', [App\Http\Controllers\Api\MpesaController::class, 'generateAccessToken'])
    ->name('payments.access_token');
Route::post('stk_push', [App\Http\Controllers\Api\MpesaController::class, 'customerSTKPush'])
    ->name('payments.stk_push');
Route::post('c2b_validation', [App\Http\Controllers\Api\MpesaController::class, 'c2bValidation'])
    ->name('payments.c2b_validation');
Route::post('c2b_confirmation', [App\Http\Controllers\Api\MpesaController::class, 'c2bConfirmation'])
    ->name('payments.c2b_confirmation');

