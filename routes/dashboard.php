<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/dashboard', function () {
    $user = auth()->user();
    
    $isStudent = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->where('roles.name', 'siswa')
        ->exists();

    if ($isStudent) {
        return redirect()->route('student.dashboard');
    }

    $isYayasanUser = \DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->whereIn('roles.name', [
            'super_admin_yayasan', 
            'admin_yayasan', 
            'admin_unit', 
            'staff_yayasan', 
            'staff_unit', 
            'teacher'
        ])
        ->exists();

    if ($isYayasanUser) {
        return redirect()->route('yayasan.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
