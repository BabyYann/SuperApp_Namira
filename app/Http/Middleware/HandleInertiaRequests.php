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
        $user = $request->user();
        $authUserPayload = [];

        // Load units once per request (7 rows) so User::getUnitsAttribute(), session, and services share it
        $allUnits = \App\Modules\Yayasan\Models\Unit::orderBy('id')->get();
        $request->attributes->set('resolved_all_units', $allUnits);

        if ($user) {
            // Fetch ALL roles once (ignoring Team Scope) and memoize on request for services
            $allRoles = \DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', get_class($user))
                ->pluck('roles.name')
                ->unique()
                ->values()
                ->toArray();

            $request->attributes->set('resolved_user_roles_' . $user->id, $allRoles);

            // Eager-load teacher_profile once so is_teacher, profile_photo_url, and controllers reuse it
            if (!$user->relationLoaded('teacher_profile')) {
                $user->load(['teacher_profile:id,user_id,unit_id,photo']);
            }

            $authUserPayload = array_merge($user->toArray(), [
                'role' => $allRoles[0] ?? null, // Deprecated
                'roles' => $allRoles,
                'is_teacher' => $user->teacher_profile !== null,
                'units' => $user->getUnitsAttribute()->toArray(),
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $authUserPayload,
            ],
            'session' => call_user_func(function () use ($user, $allUnits) {
                $activeUnitId = session('active_unit_id') ?? $user?->unit_id;
                $activeUnit = $activeUnitId ? $allUnits->firstWhere('id', (int) $activeUnitId) : null;
                if (!$activeUnit) {
                    $activeUnit = $allUnits->first();
                    $activeUnitId = $activeUnit?->id;
                }

                $isDaycare = $activeUnit ? $activeUnit->isDaycare() : false;
                $isFormal = $activeUnit ? $activeUnit->isFormalSchool() : true;

                return [
                    'active_unit_id' => $activeUnitId,
                    'active_unit_name' => $activeUnit?->name ?? 'Pilih Unit',
                    'active_unit_logo' => $activeUnit?->logo_url,
                    'active_unit_type' => $activeUnit?->unit_type ?? ($isDaycare ? 'daycare' : 'formal_school'),
                    'is_daycare' => $isDaycare,
                    'is_formal_school' => $isFormal,
                    'features' => $activeUnit ? [
                        'academic' => $activeUnit->hasFeature('academic'),
                        'quran' => $activeUnit->hasFeature('quran'),
                        'daycare' => $activeUnit->hasFeature('daycare'),
                        'finance' => $activeUnit->hasFeature('finance'),
                        'sarpar' => $activeUnit->hasFeature('sarpar'),
                        'counseling' => $activeUnit->hasFeature('counseling'),
                        'public_relations' => $activeUnit->hasFeature('public_relations'),
                    ] : [
                        'academic' => !$isDaycare,
                        'quran' => !$isDaycare,
                        'daycare' => $isDaycare,
                        'finance' => true,
                        'sarpar' => true,
                        'counseling' => true,
                        'public_relations' => true,
                    ],
                    'available_units' => $allUnits->map(function($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'logo_url' => $u->logo_url,
                            'category' => $u->category,
                            'unit_type' => $u->unit_type ?? ($u->isDaycare() ? 'daycare' : 'formal_school'),
                            'is_daycare' => $u->isDaycare(),
                            'is_formal_school' => $u->isFormalSchool(),
                        ];
                    })->values(),
                ];
            }),
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
