<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Daycare\Controllers\DaycareChildrenController;
use App\Modules\Daycare\Controllers\DaycareAttendanceController;
use App\Modules\Daycare\Controllers\DaycareDailyLogController;
use App\Modules\Daycare\Controllers\DaycareGrowthController;
use App\Modules\Daycare\Controllers\DaycareJournalController;
use App\Modules\Daycare\Controllers\DaycareReportController;

Route::middleware(['auth', 'verified'])->prefix('daycare')->name('daycare.')->group(function () {
    // Master Children & Profiles
    Route::get('/children', [DaycareChildrenController::class, 'index'])->name('children.index');
    Route::get('/children/create', [DaycareChildrenController::class, 'create'])->name('children.create');
    Route::post('/children', [DaycareChildrenController::class, 'store'])->name('children.store');
    Route::get('/children/{student}', [DaycareChildrenController::class, 'show'])->name('children.show');
    Route::put('/children/{student}', [DaycareChildrenController::class, 'update'])->name('children.update');
    Route::post('/children/{student}/pickups', [DaycareChildrenController::class, 'storePickup'])->name('children.pickups.store');
    Route::delete('/pickups/{pickup}', [DaycareChildrenController::class, 'destroyPickup'])->name('children.pickups.destroy');

    // Attendance & Handover (Check-in / Check-out)
    Route::get('/attendance', [DaycareAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [DaycareAttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [DaycareAttendanceController::class, 'checkOut'])->name('attendance.check-out');

    // Daily Care Logs (Timeline)
    Route::get('/children/{student}/logs', [DaycareDailyLogController::class, 'index'])->name('logs.index');
    Route::post('/children/{student}/logs', [DaycareDailyLogController::class, 'store'])->name('logs.store');
    Route::delete('/logs/{log}', [DaycareDailyLogController::class, 'destroy'])->name('logs.destroy');

    // Growth Monitoring
    Route::post('/children/{student}/growth', [DaycareGrowthController::class, 'store'])->name('growth.store');

    // Developmental Journal
    Route::post('/children/{student}/journals', [DaycareJournalController::class, 'store'])->name('journals.store');

    // Parent Output: Daily Report 1-Page Summary
    Route::get('/children/{student}/daily-report', [DaycareReportController::class, 'dailyReport'])->name('reports.daily');
});
