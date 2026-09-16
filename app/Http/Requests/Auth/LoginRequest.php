<?php

namespace App\Http\Requests\Auth;

use App\Modules\Academic\Models\Student;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // Changed from 'email' to 'login' (accepts email OR NIS)
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the email from login input.
     * Supports Email, WhatsApp Phone number, or Student NIS.
     */
    protected function getEmailFromLogin(): ?string
    {
        $login = trim($this->input('login'));

        // If it starts with + or 08 or 62, or looks like phone number:
        $cleanPhone = preg_replace('/[^0-9]/', '', $login);
        if (strlen($cleanPhone) >= 9 && (str_starts_with($cleanPhone, '08') || str_starts_with($cleanPhone, '628'))) {
            $normalizedPhone = str_starts_with($cleanPhone, '62') ? ('0' . substr($cleanPhone, 2)) : $cleanPhone;
            
            $user = \App\Models\User::where('phone', $cleanPhone)
                ->orWhere('phone', $normalizedPhone)
                ->orWhere('email', "{$cleanPhone}@spmb.namiraschool.com")
                ->orWhere('email', "{$normalizedPhone}@spmb.namiraschool.com")
                ->first();

            if ($user) {
                return $user->email;
            }
        }

        // If it looks like a NIS (numeric), find the student
        if (is_numeric($login)) {
            $student = Student::where('nis', $login)->first();
            
            if ($student && $student->user) {
                return $student->user->email;
            }

            // Also check if any user has this exact phone
            $userByPhone = \App\Models\User::where('phone', $login)->first();
            if ($userByPhone) {
                return $userByPhone->email;
            }
            
            return null;
        }

        // Otherwise treat as email
        return $login;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->getEmailFromLogin();

        // Always set remember = true for persistent session
        if (!$email || !Auth::attempt(['email' => $email, 'password' => $this->input('password')], true)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
