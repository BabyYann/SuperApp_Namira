<?php

use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([\App\Http\Middleware\EnsureStudentAccess::class, 'feature:feature_student_login'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/menu', [\App\Http\Controllers\StudentPortalController::class, 'menu'])->name('menu');
    Route::get('/academic', [\App\Http\Controllers\StudentPortalController::class, 'academic'])->name('academic');
    Route::get('/finance', [\App\Http\Controllers\StudentPortalController::class, 'finance'])->name('finance');
    Route::get('/counseling', [\App\Http\Controllers\StudentPortalController::class, 'counseling'])->name('counseling');
    
    Route::get('/productivity', [\App\Http\Controllers\StudentTaskController::class, 'index'])->name('productivity.index');
    Route::post('/productivity/tasks', [\App\Http\Controllers\StudentTaskController::class, 'store'])->name('tasks.store');
    Route::put('/productivity/tasks/{task}', [\App\Http\Controllers\StudentTaskController::class, 'update'])->name('tasks.update');
    Route::delete('/productivity/tasks/{task}', [\App\Http\Controllers\StudentTaskController::class, 'destroy'])->name('tasks.destroy');

    Route::post('/pickup-request', [\App\Http\Controllers\StudentPortalController::class, 'pickupRequest'])->name('pickup.request');
});
