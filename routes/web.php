<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    /*$message = json_encode([
        'name' => 'Geek',
        'alias' => 'Human',
        'age' => 26
    ]);
    \Illuminate\Support\Facades\Log::channel('mpesa')
        ->info($message);*/
    return response()->json('This link has no power here...', 200);
});
