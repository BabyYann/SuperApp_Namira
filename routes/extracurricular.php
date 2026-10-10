<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Extracurricular\Controllers\ExtracurricularController;
use App\Modules\Extracurricular\Controllers\ExtracurricularMemberController;
use App\Modules\Extracurricular\Controllers\ExtracurricularSessionController;
use App\Modules\Extracurricular\Controllers\ExtracurricularInstructorController;
use App\Modules\Extracurricular\Controllers\ExtracurricularGradeController;

Route::prefix('extracurricular')->name('extracurricular.')->middleware([
    'role:super_admin_yayasan|admin_yayasan|pembina_yayasan|pengawas_yayasan|staff_yayasan|admin_unit|staff_unit|kepala_sekolah|koordinator_kesiswaan|koordinator_kurikulum|wali_kelas|teacher|pelatih_ekskul|bk',
    'feature:feature_extracurricular'
])->group(function () {
    Route::get('/', [ExtracurricularController::class, 'index'])->name('index');
    Route::post('/', [ExtracurricularController::class, 'store'])->name('store');
    Route::get('/{extracurricular}', [ExtracurricularController::class, 'show'])->name('show');
    Route::put('/{extracurricular}', [ExtracurricularController::class, 'update'])->name('update');
    Route::delete('/{extracurricular}', [ExtracurricularController::class, 'destroy'])->name('destroy');

    // Members management
    Route::post('/{extracurricular}/members', [ExtracurricularMemberController::class, 'store'])->name('members.store');
    Route::put('/{extracurricular}/members/{member}', [ExtracurricularMemberController::class, 'updateStatus'])->name('members.update');
    Route::delete('/{extracurricular}/members/{member}', [ExtracurricularMemberController::class, 'destroy'])->name('members.destroy');

    // Sessions & Attendances
    Route::post('/{extracurricular}/sessions', [ExtracurricularSessionController::class, 'store'])->name('sessions.store');
    Route::put('/{extracurricular}/sessions/{session}', [ExtracurricularSessionController::class, 'update'])->name('sessions.update');
    Route::delete('/{extracurricular}/sessions/{session}', [ExtracurricularSessionController::class, 'destroy'])->name('sessions.destroy');

    // Instructors / Coaches
    Route::post('/{extracurricular}/instructors', [ExtracurricularInstructorController::class, 'store'])->name('instructors.store');
    Route::delete('/{extracurricular}/instructors/{instructor}', [ExtracurricularInstructorController::class, 'destroy'])->name('instructors.destroy');

    // Grades / Semester Assessments
    Route::post('/{extracurricular}/grades', [ExtracurricularGradeController::class, 'store'])->name('grades.store');
});
