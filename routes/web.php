<?php

use Illuminate\Support\Facades\Route;

// Public routes (no auth)
require __DIR__.'/public.php';

// Dashboard redirect (auth + verified)
require __DIR__.'/dashboard.php';

// Auth-protected routes
Route::middleware('auth')->group(function () {
    require __DIR__.'/profile.php';
    require __DIR__.'/yayasan.php';
    require __DIR__.'/public-relations.php';
    require __DIR__.'/employee.php';
    require __DIR__.'/counseling.php';
    require __DIR__.'/sarpar.php';
    require __DIR__.'/student-portal.php';
    require __DIR__.'/lms.php';
});

require __DIR__.'/auth.php';
