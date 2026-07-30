<?php

use Illuminate\Support\Facades\Route;

// Public routes (no auth)
require __DIR__.'/public.php';

// Dashboard redirect (auth + verified)
require __DIR__.'/dashboard.php';

// Auth-protected routes
Route::middleware('auth')->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationApiController::class, 'index']);
        Route::post('/read-all', [\App\Http\Controllers\Api\NotificationApiController::class, 'markAllRead']);
        Route::post('/test-trigger', [\App\Http\Controllers\Api\NotificationApiController::class, 'testTrigger']);
        Route::post('/test-trigger-admin', [\App\Http\Controllers\Api\NotificationApiController::class, 'testTriggerAdmin']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationApiController::class, 'markRead']);
        // Debug routes - REMOVE after notification confirmed working
        Route::get('/debug/diagnose', [\App\Http\Controllers\Api\NotificationDebugController::class, 'diagnose']);
        Route::post('/debug/force-test-admin', [\App\Http\Controllers\Api\NotificationDebugController::class, 'forceTestToAdmin']);
        Route::post('/debug/test-fcm-direct', [\App\Http\Controllers\Api\NotificationDebugController::class, 'testFcmDirect']);
    });
    require __DIR__.'/profile.php';
    require __DIR__.'/yayasan.php';
    require __DIR__.'/public-relations.php';
    require __DIR__.'/employee.php';
    require __DIR__.'/counseling.php';
    require __DIR__.'/sarpar.php';
    require __DIR__.'/student-portal.php';
    require __DIR__.'/lms.php';
});

require __DIR__.'/auth.php';
