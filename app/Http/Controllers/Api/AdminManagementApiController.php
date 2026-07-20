<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AdminManagementApiController extends Controller
{
    use HasUnitScope;

    // GET /admin/users
    public function users(Request $request): JsonResponse
    {
        $query = User::query()->with('roles');

        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }
        if ($unitId = $request->get('unit_id')) {
            $query->whereHas('roles', fn ($q) => $q->where('model_has_roles.team_id', $unitId));
        }

        $items = $query->paginate($request->get('per_page', 15));

        $items->getCollection()->transform(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->getRoleNames(),
            'units' => $u->getUnitsAttribute()->map(fn ($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
            ]),
        ]);

        return response()->json($items);
    }

    // POST /admin/users
    public function storeUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $teamId = $request->unit_id;
        $role = Role::findByName($request->role, 'web');
        $user->syncRoles([$role]);
        if ($teamId) {
            \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->where('role_id', $role->id)
                ->update(['team_id' => $teamId]);
        }

        return response()->json(['data' => $user, 'message' => 'User berhasil dibuat'], 201);
    }

    // PUT /admin/users/:id
    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        if ($request->filled('role')) {
            $role = Role::findByName($request->role, 'web');
            $user->syncRoles([$role]);
            if ($request->filled('unit_id')) {
                \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->where('role_id', $role->id)
                    ->update(['team_id' => $request->unit_id]);
            }
        }

        return response()->json(['data' => $user, 'message' => 'User berhasil diperbarui']);
    }

    // GET /admin/roles
    public function roles(): JsonResponse
    {
        $roles = Role::with('permissions')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'permissions' => $r->permissions->pluck('name'),
        ]);

        return response()->json(['data' => $roles]);
    }

    // POST /admin/units/:id/switch
    public function switchUnit(Request $request, $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);
        $request->user()->currentAccessToken()?->delete();

        session(['active_unit_id' => $unit->id]);
        $token = $request->user()->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Berhasil beralih ke ' . $unit->name,
            'token' => $token,
            'active_unit_id' => $unit->id,
            'active_unit_name' => $unit->name,
        ]);
    }
}
