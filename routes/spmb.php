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

// Registration Link for SD Namira Kota Probolinggo (Unit SD-02)
Route::get('/spmb/daftar/sd-kota', [PublicSpmbController::class, 'registerSDKota'])->name('spmb.register.sd-kota');
Route::post('/spmb/daftar/sd-kota', [PublicSpmbController::class, 'storeSDKota'])->name('spmb.store.sd-kota');

Route::get('/spmb/daftar/smp', [PublicSpmbController::class, 'registerSMP'])->name('spmb.register.smp');
Route::post('/spmb/daftar/smp', [PublicSpmbController::class, 'storeSMP'])->name('spmb.store.smp');

// TK & KB Namira Registration (System ready; locked for public until unlocked by Admin in Settings)
Route::get('/spmb/daftar/tk', [PublicSpmbController::class, 'registerTK'])->name('spmb.register.tk');
Route::post('/spmb/daftar/tk', [PublicSpmbController::class, 'storeTK'])->name('spmb.store.tk');

// Clean deep-link aliases for future-year Inden sharing (e.g. /spmb/daftar/sd/inden/2028-2029)
Route::get('/spmb/daftar/{unitSlug}/inden/{academicYearSlug?}', [PublicSpmbController::class, 'redirectIndenLink'])
    ->where('unitSlug', 'sd|sd-kota|smp|tk')
    ->where('academicYearSlug', '\d{4}-\d{4}')
    ->name('spmb.register.inden-redirect');

// Secure read-only live monitor endpoint (verified via SHA-256 CSPRNG key hash + rate-limited)
Route::get('/spmb/live-sync', [PublicSpmbController::class, 'liveMonitor'])
    ->middleware('throttle:15,1')
    ->name('spmb.live-monitor');

// Applicant Portal routes (for logged-in parents)
Route::middleware(['auth'])->prefix('spmb/portal')->name('spmb.applicant.')->group(function () {
    Route::get('/dashboard', [ApplicantPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/kartu-peserta/{applicant}', [ApplicantPortalController::class, 'printCard'])->name('print-card');
    Route::get('/surat-penerimaan/{applicant}', [ApplicantPortalController::class, 'printAcceptedLetter'])->name('print-skl');
    Route::get('/kwitansi/{applicant}', [ApplicantPortalController::class, 'printReceipt'])->name('print-receipt');
    Route::get('/rincian-biaya/{applicant}', [ApplicantPortalController::class, 'printFeeGuide'])->name('print-fee-guide');
    Route::post('/daftar-ulang/{applicant}/upload-bukti', [ApplicantPortalController::class, 'uploadReRegistrationProof'])->name('upload-proof');
    Route::post('/profil/{applicant}/update', [ApplicantPortalController::class, 'updateProfile'])->name('update-profile');
    Route::post('/pendaftaran/{applicant}/upload-bukti-awal', [ApplicantPortalController::class, 'updateRegistrationProof'])->name('upload-registration-proof');
    Route::post('/berkas/{applicant}/upload', [ApplicantPortalController::class, 'uploadSupportingDocument'])->name('upload-document');
});

// Admin & Panitia SPMB routes
Route::middleware(['auth'])->prefix('yayasan/spmb')->name('spmb.admin.')->group(function () {
    Route::get('/', [AdminSpmbController::class, 'index'])->name('index');
    Route::get('/export', [AdminSpmbController::class, 'export'])->name('export');
    Route::get('/va-template', [AdminSpmbController::class, 'downloadVaTemplate'])->name('va-template');
    Route::post('/va-import/preview', [AdminSpmbController::class, 'previewVaImport'])->name('va-import.preview');
    Route::post('/va-import/commit', [AdminSpmbController::class, 'commitVaImport'])->name('va-import.commit');
    Route::get('/pendaftar/{applicant}', [AdminSpmbController::class, 'show'])->name('show');
    Route::post('/pendaftar/{applicant}/update-biodata', [AdminSpmbController::class, 'updateApplicant'])->name('update-biodata');
    Route::post('/pendaftar/{applicant}/reset-password', [AdminSpmbController::class, 'resetParentPassword'])->name('reset-password');
    Route::post('/pendaftar/{applicant}/verifikasi-bayar', [AdminSpmbController::class, 'verifyPayment'])->name('verify-payment');
    Route::post('/pendaftar/{applicant}/atur-jadwal', [AdminSpmbController::class, 'setSchedule'])->name('set-schedule');
    Route::post('/pendaftar/{applicant}/simpan-evaluasi', [AdminSpmbController::class, 'saveEvaluation'])->name('save-evaluation');
    Route::post('/pendaftar/{applicant}/simpan-va', [AdminSpmbController::class, 'setVirtualAccount'])->name('set-va');
    Route::post('/pendaftar/{applicant}/verifikasi-daftar-ulang', [AdminSpmbController::class, 'verifyAdmissionPayment'])->name('verify-admission-payment');
    Route::post('/pendaftar/{applicant}/konversi-siswa', [AdminSpmbController::class, 'enrollStudent'])->name('enroll-student');
    Route::delete('/pendaftar/{applicant}', [AdminSpmbController::class, 'destroy'])->name('destroy');

    // Settings & Panitia Assignment
    Route::get('/pengaturan', [AdminSpmbController::class, 'settings'])->name('settings');
    Route::post('/pengaturan', [AdminSpmbController::class, 'updateSettings'])->name('update-settings');
    Route::post('/penugasan-panitia', [AdminSpmbController::class, 'assignPanitia'])->name('assign-panitia');
    Route::delete('/penugasan-panitia/{user}', [AdminSpmbController::class, 'removePanitia'])->name('remove-panitia');
});
