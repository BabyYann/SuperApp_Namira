<?php

use Illuminate\Support\Facades\Route;

Route::prefix('counseling')->name('counseling.')->middleware(['role:super_admin_yayasan|admin_yayasan|pembina_yayasan|pengawas_yayasan|admin_unit|kepala_sekolah|bk|counseling|teacher|guru|wali_kelas|koordinator_kesiswaan', 'feature:feature_counseling'])->group(function () {
    Route::resource('categories', \App\Modules\Counseling\Controllers\ViolationCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('violations', \App\Modules\Counseling\Controllers\ViolationController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::resource('achievements', \App\Modules\Counseling\Controllers\AchievementController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::resource('sessions', \App\Modules\Counseling\Controllers\CounselingSessionController::class)->except(['show']);
});
