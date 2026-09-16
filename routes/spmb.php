<?php

use App\Modules\SPMB\Controllers\AdminSpmbController;
use App\Modules\SPMB\Controllers\ApplicantPortalController;
use App\Modules\SPMB\Controllers\PublicSpmbController;
use Illuminate\Support\Facades\Route;

// Public SPMB routes (no auth required)
Route::get('/spmb', [PublicSpmbController::class, 'index'])->name('spmb.index');
Route::get('/ppdb', function () {
    return redirect()->route('spmb.index');
})->name('ppdb.index');

Route::get('/spmb/daftar/sd', [PublicSpmbController::class, 'registerSD'])->name('spmb.register.sd');
Route::post('/spmb/daftar/sd', [PublicSpmbController::class, 'storeSD'])->name('spmb.store.sd');

// Applicant Portal routes (for logged-in parents)
Route::middleware(['auth'])->prefix('spmb/portal')->name('spmb.applicant.')->group(function () {
    Route::get('/dashboard', [ApplicantPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/kartu-peserta/{applicant}', [ApplicantPortalController::class, 'printCard'])->name('print-card');
    Route::get('/surat-penerimaan/{applicant}', [ApplicantPortalController::class, 'printAcceptedLetter'])->name('print-skl');
    Route::post('/daftar-ulang/{applicant}/upload-bukti', [ApplicantPortalController::class, 'uploadReRegistrationProof'])->name('upload-proof');
});

// Admin & Panitia SPMB routes
Route::middleware(['auth'])->prefix('yayasan/spmb')->name('spmb.admin.')->group(function () {
    Route::get('/', [AdminSpmbController::class, 'index'])->name('index');
    Route::get('/pendaftar/{applicant}', [AdminSpmbController::class, 'show'])->name('show');
    Route::post('/pendaftar/{applicant}/verifikasi-bayar', [AdminSpmbController::class, 'verifyPayment'])->name('verify-payment');
    Route::post('/pendaftar/{applicant}/atur-jadwal', [AdminSpmbController::class, 'setSchedule'])->name('set-schedule');
    Route::post('/pendaftar/{applicant}/simpan-evaluasi', [AdminSpmbController::class, 'saveEvaluation'])->name('save-evaluation');
    Route::post('/pendaftar/{applicant}/simpan-va', [AdminSpmbController::class, 'setVirtualAccount'])->name('set-va');
    Route::post('/pendaftar/{applicant}/verifikasi-daftar-ulang', [AdminSpmbController::class, 'verifyAdmissionPayment'])->name('verify-admission-payment');
    Route::post('/pendaftar/{applicant}/konversi-siswa', [AdminSpmbController::class, 'enrollStudent'])->name('enroll-student');

    // Settings & Panitia Assignment
    Route::get('/pengaturan', [AdminSpmbController::class, 'settings'])->name('settings');
    Route::post('/pengaturan', [AdminSpmbController::class, 'updateSettings'])->name('update-settings');
    Route::post('/penugasan-panitia', [AdminSpmbController::class, 'assignPanitia'])->name('assign-panitia');
    Route::delete('/penugasan-panitia/{user}', [AdminSpmbController::class, 'removePanitia'])->name('remove-panitia');
});
