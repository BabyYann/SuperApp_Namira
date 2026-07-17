<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function formatUnit($unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'code' => $unit->code,
            'category' => $unit->category,
            'level' => $unit->level,
        ];
    }

    private function getUserUnits(User $user): \Illuminate\Support\Collection
    {
        $hasGlobalRole = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan']);

        if ($hasGlobalRole) {
            return \App\Modules\Yayasan\Models\Unit::all();
        }

        return $user->getUnitsAttribute();
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        $units = $this->getUserUnits($user)->map(fn ($unit) => $this->formatUnit($unit));
        $roles = $user->getRoleNames();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $roles,
            ],
            'units' => $units,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $units = $this->getUserUnits($user)->map(fn ($unit) => $this->formatUnit($unit));
        $roles = $user->getRoleNames();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_url,
            'roles' => $roles,
            'units' => $units,
        ]);
    }

    public function units(Request $request): JsonResponse
    {
        $units = $this->getUserUnits($request->user())->map(fn ($unit) => $this->formatUnit($unit));

        return response()->json(['data' => $units]);
    }

    public function switchUnit(Request $request): JsonResponse
    {
        $request->validate([
            'unit_id' => 'required|integer|exists:units,id',
        ]);

        $user = $request->user();
        $unitId = $request->unit_id;

        $hasGlobalRole = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan']);

        if (! $hasGlobalRole) {
            $hasAccess = $user->getUnitsAttribute()->contains('id', $unitId);
            if (! $hasAccess) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke unit ini.',
                ], 403);
            }
        }

        session(['active_unit_id' => $unitId]);
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);

        return response()->json([
            'message' => 'Berhasil beralih ke ' . $unit->name,
            'active_unit_id' => $unitId,
            'active_unit_name' => $unit->name,
        ]);
    }
}
