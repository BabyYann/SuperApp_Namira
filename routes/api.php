<?php

use App\Http\Controllers\Api\AcademicApiController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CounselingApiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\SarparApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/units', [AuthController::class, 'units']);
    Route::post('/switch-unit', [AuthController::class, 'switchUnit']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::get('/locations', [AttendanceController::class, 'locations']);
    });

    Route::prefix('academic')->group(function () {
        Route::get('/schedules', [AcademicApiController::class, 'schedules']);
        Route::get('/students', [AcademicApiController::class, 'students']);
        Route::get('/journals', [AcademicApiController::class, 'journals']);
        Route::get('/student-attendance', [AcademicApiController::class, 'studentAttendance']);
        Route::get('/student-attendance/recap', [AcademicApiController::class, 'studentAttendanceRecap']);
    });

    Route::prefix('finance')->group(function () {
        Route::get('/dashboard', [FinanceApiController::class, 'dashboard']);
        Route::get('/bills', [FinanceApiController::class, 'bills']);
        Route::get('/bills/{id}', [FinanceApiController::class, 'billDetail']);
        Route::get('/transactions', [FinanceApiController::class, 'transactions']);
    });

    Route::prefix('counseling')->group(function () {
        Route::get('/violations', [CounselingApiController::class, 'violations']);
        Route::get('/categories', [CounselingApiController::class, 'categories']);
        Route::get('/sessions', [CounselingApiController::class, 'sessions']);
        Route::get('/sessions/{id}', [CounselingApiController::class, 'sessionDetail']);
        Route::get('/achievements', [CounselingApiController::class, 'achievements']);
    });

    Route::prefix('sarpar')->group(function () {
        Route::get('/dashboard', [SarparApiController::class, 'dashboard']);
        Route::get('/inventories', [SarparApiController::class, 'inventories']);
        Route::get('/categories', [SarparApiController::class, 'categories']);
        Route::get('/rooms', [SarparApiController::class, 'rooms']);
        Route::get('/loans', [SarparApiController::class, 'loans']);
        Route::get('/maintenance', [SarparApiController::class, 'maintenance']);
    });
});
