<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use effina\Larastitial\Http\Controllers\ApiController;

Route::get('/applicable', [ApiController::class, 'applicable'])
    ->name('larastitial.api.applicable');

Route::get('/{uuid}', [ApiController::class, 'show'])
    ->name('larastitial.api.show');

Route::post('/{uuid}/action', [ApiController::class, 'action'])
    ->name('larastitial.api.action');

Route::post('/{uuid}/respond', [ApiController::class, 'respond'])
    ->name('larastitial.api.respond');
