<?php

use Illuminate\Support\Facades\Route;
use Dashed\DashedCookies\Controllers\ConsentController;

Route::post('/dashed-cookies/consent', [ConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('dashed.cookies.consent');
