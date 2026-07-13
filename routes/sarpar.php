<?php

use Illuminate\Support\Facades\Route;

Route::prefix('sarpar')->name('sarpar.')->middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|koordinator_sarpar|teacher', 'feature:feature_sarpar'])->group(function () {
    Route::get('/', [\App\Modules\Sarpar\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    Route::middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|koordinator_sarpar'])->group(function () {
        Route::resource('categories', \App\Modules\Sarpar\Controllers\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    });
    
    Route::resource('rooms', \App\Modules\Sarpar\Controllers\RoomController::class)->only(['index', 'store', 'update', 'destroy']);
    
    Route::get('inventories/export', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'export'])->name('inventories.export');
    Route::resource('inventories', \App\Modules\Sarpar\Controllers\InventoryController::class);
    
    Route::post('maintenance/{log}/handle', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'handle'])->name('maintenance.handle');
    Route::post('maintenance/{log}/cancel', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'cancel'])->name('maintenance.cancel');
    Route::resource('maintenance', \App\Modules\Sarpar\Controllers\MaintenanceController::class)->only(['index', 'store']);
    
    Route::post('loans/{loan}/return', [\App\Modules\Sarpar\Controllers\LoanController::class, 'return'])->name('loans.return');
    Route::post('loans/{loan}/lost', [\App\Modules\Sarpar\Controllers\LoanController::class, 'markLost'])->name('loans.lost');
    Route::resource('loans', \App\Modules\Sarpar\Controllers\LoanController::class)->only(['index', 'store']);

    Route::post('usage', [\App\Modules\Sarpar\Controllers\UsageLogController::class, 'store'])->name('usage.store');
});
