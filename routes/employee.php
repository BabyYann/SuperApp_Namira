<?php

use Illuminate\Support\Facades\Route;

Route::prefix('employee')->name('employee.')->middleware(['role:teacher|staff_unit|staff_yayasan|super_admin_yayasan|admin_unit', 'feature:feature_employee'])->group(function () {
    Route::get('/attendance', [\App\Modules\Employee\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [\App\Modules\Employee\Controllers\AttendanceController::class, 'store'])->name('attendance.check-in');
    Route::put('/attendance/check-out/{attendance}', [\App\Modules\Employee\Controllers\AttendanceController::class, 'update'])->name('attendance.check-out');
});
