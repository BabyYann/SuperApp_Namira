<?php

use Illuminate\Support\Facades\Route;

Route::prefix('counseling')->name('counseling.')->middleware(['role:super_admin_yayasan|admin_unit|bk|teacher', 'feature:feature_counseling'])->group(function () {
    Route::resource('categories', \App\Modules\Counseling\Controllers\ViolationCategoryController::class);
    Route::resource('violations', \App\Modules\Counseling\Controllers\ViolationController::class);
    Route::resource('achievements', \App\Modules\Counseling\Controllers\AchievementController::class);
    Route::resource('sessions', \App\Modules\Counseling\Controllers\CounselingSessionController::class);
});
