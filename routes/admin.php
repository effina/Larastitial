<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use effina\Larastitial\Http\Controllers\AdminController;

Route::get('/', [AdminController::class, 'index'])->name('index');
Route::get('/create', [AdminController::class, 'create'])->name('create');
Route::post('/', [AdminController::class, 'store'])->name('store');
Route::get('/{interstitial}', [AdminController::class, 'show'])->name('show');
Route::get('/{interstitial}/edit', [AdminController::class, 'edit'])->name('edit');
Route::put('/{interstitial}', [AdminController::class, 'update'])->name('update');
Route::delete('/{interstitial}', [AdminController::class, 'destroy'])->name('destroy');
Route::post('/{id}/restore', [AdminController::class, 'restore'])->name('restore');
Route::get('/{interstitial}/stats', [AdminController::class, 'stats'])->name('stats');
Route::post('/{interstitial}/toggle', [AdminController::class, 'toggle'])->name('toggle');
Route::post('/{interstitial}/duplicate', [AdminController::class, 'duplicate'])->name('duplicate');
