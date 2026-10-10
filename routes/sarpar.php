<?php

use Illuminate\Support\Facades\Route;

Route::prefix('sarpar')->name('sarpar.')->middleware([
    'role:super_admin_yayasan|admin_yayasan|pembina_yayasan|pengawas_yayasan|staff_yayasan|admin_unit|staff_unit|koordinator_sarpar|koordinator_kurikulum|koordinator_kesiswaan|kepala_sekolah|teacher|wali_kelas|bk|finance|staff_admin_keuangan|humas_yayasan|humas_unit|satpam|panitia_spmb', 
    'feature:feature_sarpar'
])->group(function () {
    // Read-only accessible to all school & foundation staff
    Route::get('/', [\App\Modules\Sarpar\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('rooms', [\App\Modules\Sarpar\Controllers\RoomController::class, 'index'])->name('rooms.index');
    Route::get('inventories', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'index'])->name('inventories.index');
    Route::get('inventories/export', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'export'])->name('inventories.export');
    Route::get('inventories/print-stickers', [\App\Modules\Sarpar\Controllers\StickerController::class, 'printStickers'])->name('inventories.print-stickers');
    Route::get('inventories/{inventory}', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'show'])->name('inventories.show');
    Route::get('transfers/{transfer}/pdf', [\App\Modules\Sarpar\Controllers\AssetActionController::class, 'transferPdf'])->name('transfers.pdf');
    Route::get('disposals/{disposal}/pdf', [\App\Modules\Sarpar\Controllers\AssetActionController::class, 'disposalPdf'])->name('disposals.pdf');
    
    // Maintenance report and list
    Route::get('maintenance', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('maintenance', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'store'])->name('maintenance.store');
    
    // Loans list & consumable usage log
    Route::get('loans', [\App\Modules\Sarpar\Controllers\LoanController::class, 'index'])->name('loans.index');
    Route::post('usage', [\App\Modules\Sarpar\Controllers\UsageLogController::class, 'store'])->name('usage.store');

    // Management Routes (Strictly for Sarpras Coordinator, Unit Admin, Principal, Foundation Admin)
    Route::middleware(['role:super_admin_yayasan|admin_yayasan|staff_yayasan|admin_unit|koordinator_sarpar|kepala_sekolah'])->group(function () {
        Route::resource('categories', \App\Modules\Sarpar\Controllers\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('rooms', [\App\Modules\Sarpar\Controllers\RoomController::class, 'store'])->name('rooms.store');
        Route::put('rooms/{room}', [\App\Modules\Sarpar\Controllers\RoomController::class, 'update'])->name('rooms.update');
        Route::delete('rooms/{room}', [\App\Modules\Sarpar\Controllers\RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::post('inventories', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'store'])->name('inventories.store');
        Route::put('inventories/{inventory}', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'update'])->name('inventories.update');
        Route::delete('inventories/{inventory}', [\App\Modules\Sarpar\Controllers\InventoryController::class, 'destroy'])->name('inventories.destroy');

        // Mutasi & Penghapusan Barang
        Route::post('inventories/{inventory}/transfer', [\App\Modules\Sarpar\Controllers\AssetActionController::class, 'transfer'])->name('inventories.transfer');
        Route::post('inventories/{inventory}/disposal', [\App\Modules\Sarpar\Controllers\AssetActionController::class, 'disposal'])->name('inventories.disposal');

        // Maintenance handling
        Route::post('maintenance/{log}/handle', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'handle'])->name('maintenance.handle');
        Route::post('maintenance/{log}/cancel', [\App\Modules\Sarpar\Controllers\MaintenanceController::class, 'cancel'])->name('maintenance.cancel');

        // Loan processing
        Route::post('loans', [\App\Modules\Sarpar\Controllers\LoanController::class, 'store'])->name('loans.store');
        Route::post('loans/{loan}/return', [\App\Modules\Sarpar\Controllers\LoanController::class, 'return'])->name('loans.return');
        Route::post('loans/{loan}/lost', [\App\Modules\Sarpar\Controllers\LoanController::class, 'markLost'])->name('loans.lost');
        Route::post('loans/{loan}/reminder', [\App\Modules\Sarpar\Controllers\LoanController::class, 'sendReminder'])->name('loans.reminder');
    });
});
