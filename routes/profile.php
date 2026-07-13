<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

Route::post('/push-tokens', [\App\Http\Controllers\PushTokenController::class, 'store'])->name('push-tokens.store');
Route::delete('/push-tokens', [\App\Http\Controllers\PushTokenController::class, 'destroy'])->name('push-tokens.destroy');
