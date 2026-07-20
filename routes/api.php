<?php

use App\Http\Controllers\Api\AcademicApiController;
use App\Http\Controllers\Api\AdminManagementApiController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CounselingApiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\LmsApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PublicRelationsApiController;
use App\Http\Controllers\Api\SarparApiController;
use App\Http\Controllers\Api\StudentPortalApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

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

    Route::prefix('lms')->group(function () {
        Route::get('/classrooms', [LmsApiController::class, 'classrooms']);
        Route::get('/classrooms/{id}', [LmsApiController::class, 'classroomShow']);
        Route::get('/materials/{id}', [LmsApiController::class, 'materialShow']);
        Route::post('/materials', [LmsApiController::class, 'materialStore']);
        Route::get('/assignments/{id}', [LmsApiController::class, 'assignmentShow']);
        Route::post('/assignments', [LmsApiController::class, 'assignmentStore']);
        Route::post('/assignments/{id}/submit', [LmsApiController::class, 'assignmentSubmit']);
        Route::get('/submissions/{id}', [LmsApiController::class, 'submissionShow']);
        Route::post('/submissions/{id}/grade', [LmsApiController::class, 'submissionGrade']);
        Route::get('/gradebook/{classroom}', [LmsApiController::class, 'gradebook']);
        Route::get('/announcements', [LmsApiController::class, 'announcements']);
        Route::post('/announcements', [LmsApiController::class, 'announcementStore']);
        Route::get('/my-tasks', [LmsApiController::class, 'myTasks']);
    });

    Route::prefix('pr')->group(function () {
        Route::get('/news', [PublicRelationsApiController::class, 'news']);
        Route::get('/news/{id}', [PublicRelationsApiController::class, 'newsShow']);
        Route::get('/events', [PublicRelationsApiController::class, 'events']);
        Route::get('/events/{id}', [PublicRelationsApiController::class, 'eventShow']);
        Route::get('/destinations', [PublicRelationsApiController::class, 'destinations']);
        Route::get('/destinations/{id}', [PublicRelationsApiController::class, 'destinationShow']);
    });

    Route::prefix('student')->group(function () {
        Route::get('/dashboard', [StudentPortalApiController::class, 'dashboard']);
        Route::get('/tasks', [StudentPortalApiController::class, 'tasks']);
        Route::post('/tasks/{id}/complete', [StudentPortalApiController::class, 'completeTask']);
        Route::get('/pickup', [StudentPortalApiController::class, 'pickupIndex']);
        Route::post('/pickup', [StudentPortalApiController::class, 'pickupStore']);
        Route::get('/grades', [StudentPortalApiController::class, 'grades']);
        Route::get('/schedule', [StudentPortalApiController::class, 'schedule']);
        Route::get('/profile', [StudentPortalApiController::class, 'profile']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminManagementApiController::class, 'users']);
        Route::post('/users', [AdminManagementApiController::class, 'storeUser']);
        Route::put('/users/{id}', [AdminManagementApiController::class, 'updateUser']);
        Route::get('/roles', [AdminManagementApiController::class, 'roles']);
        Route::post('/units/{id}/switch', [AdminManagementApiController::class, 'switchUnit']);
    });

    Route::prefix('employee')->group(function () {
        Route::get('/staff', [EmployeeApiController::class, 'staff']);
        Route::get('/attendance', [EmployeeApiController::class, 'attendance']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationApiController::class, 'index']);
        Route::post('/{id}/read', [NotificationApiController::class, 'markRead']);
        Route::post('/read-all', [NotificationApiController::class, 'markAllRead']);
    });
});
