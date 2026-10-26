<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/dashboard', function () {
    $user = auth()->user();
    
    $isStudent = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->whereIn('roles.name', ['siswa', 'student'])
        ->exists();

    if ($isStudent) {
        return redirect()->route('student.dashboard');
    }

    $isPembina = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->where('roles.name', 'pembina_yayasan')
        ->exists();

    if ($isPembina) {
        return redirect()->route('yayasan.pembina.dashboard');
    }

    $isYayasanUser = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->whereIn('roles.name', [
            'super_admin_yayasan', 
            'admin_yayasan', 
            'pembina_yayasan',
            'pengawas_yayasan',
            'admin_unit', 
            'staff_yayasan', 
            'staff_unit', 
            'kepala_sekolah',
            'teacher',
            'guru',
            'wali_kelas',
            'koordinator_kurikulum',
            'koordinator_kesiswaan',
            'koordinator_sarpar',
            'bk',
            'counseling',
            'finance',
            'staff_admin_keuangan',
            'humas_yayasan',
            'humas_unit',
            'panitia_spmb',
            'satpam',
        ])
        ->exists();

    if ($isYayasanUser) {
        return redirect()->route('yayasan.dashboard');
    }

    $isCoach = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->where('roles.name', 'pelatih_ekskul')
        ->exists();

    if ($isCoach) {
        return redirect()->route('extracurricular.index');
    }

    $isSpmbApplicant = \DB::table('spmb_applicants')
        ->where('user_id', $user->id)
        ->exists();

    if ($isSpmbApplicant) {
        return redirect()->route('spmb.applicant.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
