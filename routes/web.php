<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use effina\Larastitial\Http\Controllers\InterstitialController;

Route::get('/{uuid}', [InterstitialController::class, 'show'])
    ->name('larastitial.show');

Route::post('/{uuid}/action', [InterstitialController::class, 'action'])
    ->name('larastitial.action');

Route::post('/{uuid}/respond', [InterstitialController::class, 'respond'])
    ->name('larastitial.respond');
