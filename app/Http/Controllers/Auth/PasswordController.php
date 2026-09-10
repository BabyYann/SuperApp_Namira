<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Invalidate sessions on other devices so old remember tokens/sessions are terminated
        try {
            Auth::logoutOtherDevices($validated['password']);
        } catch (\Throwable $e) {
            // Continue if session driver doesn't support it
        }

        return back();
    }
}
