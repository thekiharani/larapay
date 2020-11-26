<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('access_token', [\App\Http\Controllers\Api\MpesaController::class, 'generateAccessToken'])
    ->name('payments.access_token');
Route::post('stk_init', [\App\Http\Controllers\Api\MpesaController::class, 'stkInit'])
    ->name('payments.stk_init');
Route::post('stk_save', [\App\Http\Controllers\Api\MpesaController::class, 'stkSave'])
    ->name('payments.stk_save');
Route::post('c2b_validation', [\App\Http\Controllers\Api\MpesaController::class, 'c2bValidation'])
    ->name('payments.c2b_validation');
Route::post('c2b_confirmation', [\App\Http\Controllers\Api\MpesaController::class, 'c2bConfirmation'])
    ->name('payments.c2b_confirmation');
Route::post('c2b_register_urls', [\App\Http\Controllers\Api\MpesaController::class, 'c2bRegisterUrls'])
    ->name('payments.c2b_register_urls');
Route::post('b2c_init', [\App\Http\Controllers\Api\MpesaController::class, 'b2cInit'])
    ->name('payments.b2c_init');
Route::post('b2c_save', [\App\Http\Controllers\Api\MpesaController::class, 'b2cSave'])
    ->name('payments.b2c_save');

