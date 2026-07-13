<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => array_merge($request->user()?->toArray() ?? [], [
                    'role' => $request->user()?->roles->first()?->name, // Deprecated
                    // Fetch ALL roles ignoring Team Scope
                    'roles' => $request->user() ? \DB::table('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('model_has_roles.model_id', $request->user()->id)
                        ->where('model_has_roles.model_type', get_class($request->user()))
                        ->pluck('roles.name')
                        ->toArray() : [],
                    'is_teacher' => $request->user()?->teacher_profile()->exists(),
                    'units' => $request->user() ? $request->user()->getUnitsAttribute()->toArray() : [],
                ]),
            ],
            'session' => [
                'active_unit_id' => session('active_unit_id'),
                'active_unit_name' => session('active_unit_id') 
                    ? \App\Modules\Yayasan\Models\Unit::find(session('active_unit_id'))?->name 
                    : 'Pilih Unit',
                'available_units' => \App\Modules\Yayasan\Models\Unit::select('id', 'name')->get(), // Optimized for Super Admin
            ],
            'app_settings' => \Illuminate\Support\Facades\Cache::rememberForever('system_settings', function () {
                // If table doesn't exist yet, return empty array to prevent breaking
                if (!\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                    return [];
                }
                return \App\Modules\Yayasan\Models\SystemSetting::all()->keyBy('key')->map(function($s) {
                    if ($s->type === 'image' && $s->value) {
                        return asset('storage/' . $s->value);
                    }
                    return $s->value;
                })->toArray();
            }),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'firebase' => [
                'apiKey' => env('FIREBASE_API_KEY'),
                'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
                'projectId' => env('FIREBASE_PROJECT_ID', 'notif-namira'),
                'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
                'messagingSenderId' => env('FIREBASE_SENDER_ID'),
                'appId' => env('FIREBASE_APP_ID'),
                'vapidKey' => env('FIREBASE_VAPID_KEY'),
            ],
        ];
    }
}
