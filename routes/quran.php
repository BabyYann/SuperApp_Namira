<?php

use App\Modules\Quran\Controllers\QuranAssessmentController;
use App\Modules\Quran\Controllers\QuranGroupController;
use App\Modules\Quran\Controllers\QuranReportController;
use App\Modules\Quran\Controllers\QuranRubricController;
use App\Modules\Quran\Controllers\QuranSettingController;
use App\Modules\Quran\Controllers\QuranStudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('quran')->name('quran.')->middleware([
    'role:super_admin_yayasan|admin_yayasan|pembina_yayasan|pengawas_yayasan|admin_unit|kepala_sekolah|koordinator_kurikulum|koordinator_alquran|guru_alquran|teacher|wali_kelas',
    'feature:feature_quran',
])->group(function () {
    // 1. Kelompok / Halaqah & Sesi Waktu
    Route::get('/', [QuranGroupController::class, 'index'])->name('index');
    Route::post('/sessions', [QuranGroupController::class, 'storeSession'])->name('sessions.store');
    Route::post('/sessions/seed-defaults', [QuranGroupController::class, 'seedDefaultSessions'])->name('sessions.seed');
    Route::put('/sessions/{session}', [QuranGroupController::class, 'updateSession'])->name('sessions.update');
    Route::delete('/sessions/{session}', [QuranGroupController::class, 'destroySession'])->name('sessions.destroy');

    Route::post('/groups', [QuranGroupController::class, 'storeGroup'])->name('groups.store');
    Route::put('/groups/{group}', [QuranGroupController::class, 'updateGroup'])->name('groups.update');
    Route::delete('/groups/{group}', [QuranGroupController::class, 'destroyGroup'])->name('groups.destroy');
    Route::post('/groups/{group}/enroll', [QuranGroupController::class, 'enrollStudents'])->name('groups.enroll');
    Route::delete('/enrollments/{enrollment}', [QuranGroupController::class, 'removeEnrollment'])->name('enrollments.destroy');

    // 2. Tracking Siswa & Naik Jilid
    Route::get('/students', [QuranStudentController::class, 'index'])->name('students.index');
    Route::post('/students/{student}/promote', [QuranStudentController::class, 'promote'])->name('students.promote');
    Route::get('/students/{student}/history', [QuranStudentController::class, 'history'])->name('students.history');

    // 3. Input Nilai Massal
    Route::get('/assessments', [QuranAssessmentController::class, 'index'])->name('assessments.index');
    Route::post('/assessments', [QuranAssessmentController::class, 'store'])->name('assessments.store');

    // 4. Cetak Rapor Ummi F4
    Route::get('/reports', [QuranReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/print', [QuranReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/pdf', [QuranReportController::class, 'pdf'])->name('reports.pdf');

    // 5. Master Jilid & Rubrik Materi / Hafalan
    Route::get('/rubrics', [QuranRubricController::class, 'index'])->name('rubrics.index');
    Route::post('/rubrics/levels', [QuranRubricController::class, 'storeLevel'])->name('rubrics.levels.store');
    Route::put('/rubrics/levels/{level}', [QuranRubricController::class, 'updateLevel'])->name('rubrics.levels.update');
    Route::post('/rubrics/items', [QuranRubricController::class, 'storeItem'])->name('rubrics.items.store');
    Route::put('/rubrics/items/{item}', [QuranRubricController::class, 'updateItem'])->name('rubrics.items.update');
    Route::delete('/rubrics/items/{item}', [QuranRubricController::class, 'destroyItem'])->name('rubrics.items.destroy');

    // 6. Pengaturan Rapor & Kunci Periode
    Route::get('/settings', [QuranSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [QuranSettingController::class, 'update'])->name('settings.update');
});
