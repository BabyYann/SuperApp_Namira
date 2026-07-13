<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check for 'siswa' role ignoring Team Scope
        // Check for 'siswa' role ignoring Team Scope and get Team ID
        $studentRole = \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $request->user()->id)
            ->where('roles.name', 'siswa')
            ->select('model_has_roles.team_id')
            ->first();

        if ($studentRole) {
            // CRITICAL: Set the Team/Unit Context for Spatie
            session(['active_unit_id' => $studentRole->team_id]);
            return redirect()->route('student.dashboard');
        }

        // Check if user has roles that should go to Yayasan Dashboard
        $isYayasanUser = \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $request->user()->id)
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
            return redirect()->intended(route('yayasan.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
