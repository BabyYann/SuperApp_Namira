<?php

use Illuminate\Support\Facades\Route;

Route::prefix('yayasan')->name('yayasan.')->middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit|teacher|kepala_sekolah'])->group(function () {
    // Master Data
    Route::get('/dashboard', [\App\Modules\Yayasan\Controllers\UnitController::class, 'dashboard'])->name('dashboard');
    
    // Admin Only Routes
    Route::middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit|kepala_sekolah'])->group(function () {
        Route::get('/monitoring', [\App\Modules\Yayasan\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');
        Route::resource('units', \App\Modules\Yayasan\Controllers\UnitController::class);
        Route::resource('academic-years', \App\Modules\Yayasan\Controllers\AcademicYearController::class);
        Route::post('academic-years/{academic_year}/activate', [\App\Modules\Yayasan\Controllers\AcademicYearController::class, 'setAsActive'])->name('academic-years.activate');
        Route::resource('users', \App\Modules\Yayasan\Controllers\UserController::class);
        Route::post('users/{user}/reset-password', [\App\Modules\Yayasan\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('attendance-locations', \App\Modules\Yayasan\Controllers\AttendanceLocationController::class);
    });

    // Super Admin Global Settings (Control Center)
    Route::middleware(['role:super_admin_yayasan'])->group(function () {
        Route::get('/settings', [\App\Modules\Yayasan\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\App\Modules\Yayasan\Controllers\SettingController::class, 'updateGlobal'])->name('settings.update');
        Route::post('/settings/toggle-role', [\App\Modules\Yayasan\Controllers\SettingController::class, 'toggleUserRole'])->name('settings.toggle-role')->middleware('throttle:10,1');
        Route::post('/settings/toggle-feature', [\App\Modules\Yayasan\Controllers\SettingController::class, 'toggleFeature'])->name('settings.toggle-feature')->middleware('throttle:10,1');
        
        // WAHA WhatsApp Queue Management
        Route::get('/settings/waha-queue/stats', [\App\Modules\Yayasan\Controllers\SettingController::class, 'wahaQueueStats'])->name('settings.waha-queue.stats');
        Route::get('/settings/waha-queue/list', [\App\Modules\Yayasan\Controllers\SettingController::class, 'wahaQueueList'])->name('settings.waha-queue.list');
        Route::post('/settings/waha-queue/toggle', [\App\Modules\Yayasan\Controllers\SettingController::class, 'toggleWahaQueue'])->name('settings.waha-queue.toggle');
        Route::post('/settings/waha-queue/cancel', [\App\Modules\Yayasan\Controllers\SettingController::class, 'cancelWahaMessage'])->name('settings.waha-queue.cancel');
        Route::post('/settings/waha-queue/retry', [\App\Modules\Yayasan\Controllers\SettingController::class, 'retryWahaMessage'])->name('settings.waha-queue.retry');
    });

    Route::post('/switch-unit', [\App\Modules\Yayasan\Controllers\UnitController::class, 'switch'])->name('switch-unit');

    // Academic Module
    Route::resource('teachers', \App\Modules\Academic\Controllers\TeacherController::class);
    Route::post('classrooms/{classroom}/add-student', [\App\Modules\Academic\Controllers\ClassroomController::class, 'addStudent'])->name('classrooms.add-student');
    Route::delete('classrooms/{classroom}/remove-student/{student}', [\App\Modules\Academic\Controllers\ClassroomController::class, 'removeStudent'])->name('classrooms.remove-student');
    Route::resource('classrooms', \App\Modules\Academic\Controllers\ClassroomController::class);
    Route::post('students/import-va', [\App\Modules\Academic\Controllers\StudentController::class, 'importVa'])->name('students.import-va');
    Route::post('students/import-excel', [\App\Modules\Academic\Controllers\StudentController::class, 'importExcel'])->name('students.import-excel');
    Route::post('students/bulk-update-year', [\App\Modules\Academic\Controllers\StudentController::class, 'bulkUpdateYear'])->name('students.bulk-update-year');
    Route::resource('students', \App\Modules\Academic\Controllers\StudentController::class);
    Route::resource('subjects', \App\Modules\Academic\Controllers\SubjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('learning-objectives', \App\Modules\Academic\Controllers\LearningObjectiveController::class)->only(['index', 'store', 'update', 'destroy']);
    
    Route::post('schedules/clone', [\App\Modules\Academic\Controllers\ScheduleController::class, 'clone'])->name('schedules.clone');
    Route::post('schedules/reset', [\App\Modules\Academic\Controllers\ScheduleController::class, 'reset'])->name('schedules.reset');
    Route::get('schedules/export-pdf', [\App\Modules\Academic\Controllers\ScheduleController::class, 'exportPdf'])->name('schedules.export-pdf');
    Route::resource('schedules', \App\Modules\Academic\Controllers\ScheduleController::class)->only(['index', 'store', 'destroy', 'update']);
    
    // Teaching Journal
    Route::get('teaching-journal/export', [\App\Modules\Academic\Controllers\TeachingJournalController::class, 'exportMonthly'])->name('teaching-journal.export');
    Route::resource('teaching-journal', \App\Modules\Academic\Controllers\TeachingJournalController::class);

    // Student Attendance (Daily)
    Route::get('student-attendance', [\App\Modules\Academic\Controllers\StudentAttendanceController::class, 'index'])->name('student-attendance.index');
    Route::get('student-attendance/recap', [\App\Modules\Academic\Controllers\StudentAttendanceController::class, 'recap'])->name('student-attendance.recap');
    Route::get('student-attendance/export', [\App\Modules\Academic\Controllers\StudentAttendanceController::class, 'export'])->name('student-attendance.export');
    Route::get('student-attendance/{classroom}', [\App\Modules\Academic\Controllers\StudentAttendanceController::class, 'show'])->name('student-attendance.show');
    Route::post('student-attendance/{classroom}', [\App\Modules\Academic\Controllers\StudentAttendanceController::class, 'store'])->name('student-attendance.store');

    // Student Check-in Gerbang (QR / Barcode Scanner)
    Route::get('student-checkin', [\App\Modules\Academic\Controllers\StudentCheckinController::class, 'index'])->name('student-checkin.index');
    Route::post('student-checkin/scan', [\App\Modules\Academic\Controllers\StudentCheckinController::class, 'scan'])->name('student-checkin.scan');
    Route::get('student-checkin/recap', [\App\Modules\Academic\Controllers\StudentCheckinController::class, 'recap'])->name('student-checkin.recap');

    // Finance Module (nested inside Yayasan)
    Route::prefix('finance')->name('finance.')->middleware(['feature:feature_finance'])->group(function () {
        Route::get('/', [\App\Modules\Finance\Controllers\FinanceDashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('accounts', \App\Modules\Finance\Controllers\FinanceAccountController::class);
        Route::resource('types', \App\Modules\Finance\Controllers\FinanceTypeController::class);
        Route::resource('bills', \App\Modules\Finance\Controllers\StudentBillController::class);
        
        // Transactions
        Route::get('transactions/import', [\App\Modules\Finance\Controllers\TransactionController::class, 'import'])->name('transactions.import');
        Route::post('transactions/import', [\App\Modules\Finance\Controllers\TransactionController::class, 'processImport'])->name('transactions.import.process');
        Route::resource('transactions', \App\Modules\Finance\Controllers\TransactionController::class)->only(['index', 'create', 'store', 'show']);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('arrears', [\App\Modules\Finance\Controllers\FinanceReportController::class, 'arrears'])->name('arrears');
            Route::get('arrears/recap', [\App\Modules\Finance\Controllers\FinanceReportController::class, 'printRecap'])->name('arrears.recap');
            Route::get('arrears/{student}/print', [\App\Modules\Finance\Controllers\FinanceReportController::class, 'printArrearsLetter'])->name('arrears.print');
        });
    });

    // Employee Module Routes (Staff Management)
    Route::resource('staff', \App\Modules\Employee\Controllers\StaffController::class)->except(['create', 'edit']);

    // Attendance Approval
    Route::get('attendance-approvals', [\App\Modules\Yayasan\Controllers\AttendanceApprovalController::class, 'index'])->name('attendance-approvals.index');
    Route::put('attendance-approvals/{attendance}', [\App\Modules\Yayasan\Controllers\AttendanceApprovalController::class, 'update'])->name('attendance-approvals.update');

    // Attendance Data (Daily & Report)
    Route::get('attendance-data', [\App\Modules\Yayasan\Controllers\AttendanceDataController::class, 'index'])->name('attendance-data.index');
    Route::get('attendance-data/export', [\App\Modules\Yayasan\Controllers\AttendanceDataController::class, 'export'])->name('attendance-data.export');
    Route::get('attendance-data/export-pdf', [\App\Modules\Yayasan\Controllers\AttendanceDataController::class, 'exportPdf'])->name('attendance-data.export-pdf');
    Route::get('attendance-data/{user}/history', [\App\Modules\Yayasan\Controllers\AttendanceDataController::class, 'employeeHistory'])->name('attendance-data.employee-history');
    Route::get('attendance-data/{user}/export-individual-pdf', [\App\Modules\Yayasan\Controllers\AttendanceDataController::class, 'exportIndividualPdf'])->name('attendance-data.export-individual-pdf');

    // Holidays / Academic Calendar
    Route::get('holidays/export-ical', [\App\Modules\Yayasan\Controllers\HolidayController::class, 'exportIcal'])->name('holidays.export-ical');
    Route::resource('holidays', \App\Modules\Yayasan\Controllers\HolidayController::class)->only(['index', 'store', 'update', 'destroy']);

    // Class Promotion (Kenaikan Kelas)
    Route::get('promotion', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'index'])->name('promotion.index');
    Route::post('promotion/preview', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'preview'])->name('promotion.preview');
    Route::get('promotion/export-preview', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'exportPreview'])->name('promotion.export-preview');
    Route::post('promotion', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'store'])->name('promotion.store');
    Route::get('promotion/history', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'history'])->name('promotion.history');
    Route::get('promotion/export', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'export'])->name('promotion.export');
    Route::post('promotion/rollback/{promotion}', [\App\Modules\Academic\Controllers\ClassPromotionController::class, 'rollback'])->name('promotion.rollback');
});
