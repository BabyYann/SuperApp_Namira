<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Unit Isolation Check
        $currentUser = auth()->user();
        if (!$currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
            $query->whereExists(function ($q) use ($unitId) {
                $q->select(\DB::raw(1))
                  ->from('model_has_roles')
                  ->whereColumn('model_has_roles.model_id', 'users.id')
                  ->where('model_has_roles.team_id', $unitId);
            });
        }

        // Search Filter
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        // Role Filter
        $query->when($request->role, function ($q, $role) {
            $q->whereHas('roles', function ($sub) use ($role) {
                $sub->where('name', $role);
            });
        });

        // Unit Filter
        $query->when($request->unit_id, function ($q, $unitId) {
            // Check model_has_roles table for team_id match
            $q->whereHas('roles', function ($sub) use ($unitId) {
                // This is a bit tricky with Spatie's whereHas, usually it filters the Role model.
                // But we need to filter the PIVOT table (model_has_roles).
                // A raw whereExists is often safer for pivot columns in Spatie if relationships aren't standard.
                // However, let's try a direct join approach for the scope or just use whereHas if the relationship is set up with pivot access.
                // Simpler approach: Filter users who have ANY role with this team_id.
                // Spatie doesn't easily expose team_id in 'roles' relation query without custom setup.
                // Let's use a whereExists on model_has_roles.
                $sub->whereRaw("model_has_roles.model_id = users.id AND model_has_roles.team_id = ?", [$unitId]);
            });
        });
        
        // Optimization: Eager load roles if possible, but we are doing manual join below anyway.
        // Actually, let's stick to the manual mapping for now as it handles the specific display needs well.
        
        $users = $query->latest()->paginate(10)->through(function ($user) {
            
            // Manual fetch of roles for display
            $roles = \DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->pluck('roles.name');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'units' => $user->getUnitsAttribute()->pluck('name'),
                'is_active' => !is_null($user->email_verified_at), // Simple check for now
                'created_at' => $user->created_at->format('d M Y'),
            ];
        });

        return Inertia::render('Yayasan/Users/Index', [
            'users' => $users,
            'units' => auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) ? Unit::all() : Unit::where('id', session('active_unit_id'))->get(),
            'roles' => Role::where('name', '!=', 'super_admin_yayasan')->pluck('name'),
            'filters' => $request->only(['search', 'role', 'unit_id']),
        ]);
    }

    public function create()
    {
        $units = auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) 
            ? Unit::all() 
            : Unit::where('id', session('active_unit_id'))->get();

        return Inertia::render('Yayasan/Users/Create', [
            'units' => $units,
            'roles' => Role::where('name', '!=', 'super_admin_yayasan')->pluck('name'), // Exclude Super Admin for safety for now
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id', // Optional if global role
        ]);

        if (!$isGlobalAdmin) {
            if (empty($request->unit_id) || $request->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat membuat pengguna untuk unit lain.');
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto-verify since Admin created it
        ]);

        // Assist Assign Role based on Unit
        if ($request->unit_id) {
            // Assign Role SCOPED to Unit (Team)
            setPermissionsTeamId($request->unit_id);
            $user->assignRole($request->role);

            // Auto-Create Academic/Staff Profile (Reverse Sync)
            $this->syncUserProfile($user, $request->role, $request->unit_id);

        } else {
            // Assign Global Role (No Team)
            setPermissionsTeamId(null);
            $user->assignRole($request->role);
        }
        
        // Reset Team ID to avoid polluting current session
        setPermissionsTeamId(session('active_unit_id'));

        return redirect()->route('yayasan.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        // Logic to Determine their main unit (simplified for now)
        // Check roles with team_id
        $roleInfo = \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', $user->id)
            ->select('model_has_roles.team_id', 'roles.name as role_name')
            ->first();
        
        $targetUnitId = $roleInfo ? $roleInfo->team_id : null;

        if (!$isGlobalAdmin) {
            if ($targetUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk mengedit pengguna di unit lain.');
            }
        }

        $units = $isGlobalAdmin ? Unit::all() : Unit::where('id', session('active_unit_id'))->get();

        return Inertia::render('Yayasan/Users/Edit', [
              'user' => $user,
              'currentUnitId' => $targetUnitId,
              'currentRole' => $roleInfo ? $roleInfo->role_name : null,
              'units' => $units,
              'roles' => Role::where('name', '!=', 'super_admin_yayasan')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        $oldUnitId = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->value('team_id');

        if (!$isGlobalAdmin) {
            if ($oldUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mengubah pengguna di unit lain.');
            }
            if ($request->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan pengguna ke unit lain.');
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Revoke all previous roles to keep it clean (force delete from pivot)
        \DB::table('model_has_roles')->where('model_id', $user->id)->delete();

        if ($request->unit_id) {
            setPermissionsTeamId($request->unit_id);
            $user->assignRole($request->role);
            
            // Sync Profile Data (Create or Update)
            $this->syncUserProfile($user, $request->role, $request->unit_id);

        } else {
             // Important: If unit_id is null, we must explicitly set team_id to null
            setPermissionsTeamId(null);
            $user->assignRole($request->role);
        }
        
        // Reset Team ID to prevent session pollution
        setPermissionsTeamId(session('active_unit_id'));

        return redirect()->route('yayasan.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('super_admin_yayasan')) {
             return redirect()->back()->with('error', 'Tidak bisa menghapus Super Admin.');
        }

        $currentUser = auth()->user();
        $isGlobalAdmin = $currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        if (!$isGlobalAdmin) {
            $userUnitId = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->value('team_id');

            if ($userUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat menghapus pengguna dari unit lain.');
            }
        }

        $user->delete();
        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Helper to Auto-Sync User Data with Specific Profiles (Teacher, Staff, Student)
     */
    private function syncUserProfile(User $user, string $role, $unitId)
    {
        if (!$unitId) return;

        // 1. Teacher Sync
        if ($role === 'teacher') {
            $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $teacher->update([
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                ]);
            } else {
                 \App\Modules\Academic\Models\Teacher::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                    'gender' => 'L', // Default
                ]);
            }
        }

        // 2. Staff Sync
        if (in_array($role, ['staff_unit', 'staff_yayasan'])) {
             $staff = \App\Modules\Employee\Models\Staff::where('user_id', $user->id)->first();
             if ($staff) {
                 $staff->update([
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                 ]);
             } else {
                 \App\Modules\Employee\Models\Staff::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                    'gender' => 'L', // Default
                    'is_active' => true,
                ]);
             }
        }

        // 3. Student Sync
        if ($role === 'siswa') {
             $student = \App\Modules\Academic\Models\Student::where('user_id', $user->id)->first();
             if ($student) {
                 $student->update([
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                 ]);
             } else {
                 \App\Modules\Academic\Models\Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                    'gender' => 'L',
                ]);
             }
        }
    }

    /**
     * Reset user password (Admin action)
     * Supports two modes:
     * 1. Generate random password and send via email
     * 2. Set manual password (for "gaptek" users)
     */
    public function resetPassword(Request $request, User $user)
    {
        // Prevent resetting super admin password
        if ($user->hasRole('super_admin_yayasan')) {
            return redirect()->back()->with('error', 'Tidak bisa reset password Super Admin.');
        }

        $currentUser = auth()->user();
        $isGlobalAdmin = $currentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        if (!$isGlobalAdmin) {
            $userUnitId = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->value('team_id');

            if ($userUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mereset password pengguna dari unit lain.');
            }
        }

        $request->validate([
            'reset_mode' => 'required|in:email,manual',
            'password' => 'required_if:reset_mode,manual|nullable|min:8|confirmed',
        ]);

        if ($request->reset_mode === 'email') {
            // Generate random password
            $randomPassword = \Illuminate\Support\Str::random(10);
            $user->update(['password' => Hash::make($randomPassword)]);

            // Send email with new password
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Halo {$user->name},\n\n" .
                    "Password akun Namira School Foundation Anda telah di-reset oleh Administrator.\n\n" .
                    "Password baru Anda: {$randomPassword}\n\n" .
                    "Silakan login dan segera ubah password Anda.\n\n" .
                    "Salam,\nNamira School Foundation",
                    function ($message) use ($user) {
                        $message->to($user->email)
                                ->subject('Password Baru - Namira School Foundation');
                    }
                );
                return redirect()->back()->with('success', "Password berhasil di-reset dan dikirim ke email {$user->email}.");
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Password berhasil di-reset tapi gagal mengirim email: ' . $e->getMessage());
            }
        } else {
            // Manual password set
            $user->update(['password' => Hash::make($request->password)]);
            return redirect()->back()->with('success', "Password {$user->name} berhasil diubah. Silakan beritahu user secara langsung.");
        }
    }
}
