<?php

use Illuminate\Support\Facades\Route;

Route::prefix('lms/teacher')->name('lms.teacher.')->middleware(['role:super_admin_yayasan|admin_yayasan|admin_unit|teacher'])->group(function () {
    Route::get('/classrooms', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'index'])->name('classrooms.index');
    Route::get('/classrooms/{classroom}', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'show'])->name('classrooms.show');
    Route::post('/classrooms/{classroom}/announcements', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'storeAnnouncement'])->name('classrooms.announcements.store');
    Route::post('/classrooms/{classroom}/materials', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'storeMaterial'])->name('classrooms.materials.store');
    Route::post('/classrooms/{classroom}/assignments', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'storeAssignment'])->name('classrooms.assignments.store');
    Route::get('/classrooms/{classroom}/assignments/{assignment}/submissions', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'submissions'])->name('classrooms.assignments.submissions');
    Route::post('/classrooms/{classroom}/assignments/{assignment}/submissions/{submission}/grade', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'gradeSubmission'])->name('classrooms.assignments.submissions.grade');
    Route::get('/classrooms/{classroom}/gradebook', [\App\Modules\LMS\Controllers\Guru\LmsClassroomController::class, 'gradebook'])->name('classrooms.gradebook');
});

Route::prefix('lms/student')->name('lms.student.')->middleware(['role:siswa'])->group(function () {
    Route::get('/classrooms', [\App\Modules\LMS\Controllers\Siswa\LmsStudentController::class, 'index'])->name('classrooms.index');
    Route::get('/classrooms/{classroom}', [\App\Modules\LMS\Controllers\Siswa\LmsStudentController::class, 'show'])->name('classrooms.show');
    Route::get('/classrooms/{classroom}/assignments/{assignment}', [\App\Modules\LMS\Controllers\Siswa\LmsStudentController::class, 'showAssignment'])->name('classrooms.assignments.show');
    Route::post('/classrooms/{classroom}/assignments/{assignment}/submit', [\App\Modules\LMS\Controllers\Siswa\LmsStudentController::class, 'submitAssignment'])->name('classrooms.assignments.submit');
    Route::get('/grades', [\App\Modules\LMS\Controllers\Siswa\LmsStudentController::class, 'grades'])->name('grades.index');
});
