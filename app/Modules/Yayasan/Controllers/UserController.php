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
    private function isGlobalAdminUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->whereIn('roles.name', ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'])
            ->exists();
    }

    private function syncSpmbParentRoles(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('spmb_applicants')) {
            return;
        }

        $parentRole = Role::firstOrCreate([
            'name' => 'orang_tua',
            'guard_name' => 'web',
        ]);

        $unassignedApplicants = \DB::table('spmb_applicants')
            ->whereNotNull('user_id')
            ->whereNotNull('unit_id')
            ->whereNotExists(function ($q) use ($parentRole) {
                $q->select(\DB::raw(1))
                  ->from('model_has_roles')
                  ->whereColumn('model_has_roles.model_id', 'spmb_applicants.user_id')
                  ->where('model_has_roles.role_id', $parentRole->id);
            })
            ->select('user_id', 'unit_id')
            ->get();

        foreach ($unassignedApplicants as $row) {
            \DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $parentRole->id,
                'model_type' => User::class,
                'model_id' => $row->user_id,
            ], [
                'team_id' => $row->unit_id,
            ]);
        }

        // Also fix any existing orang_tua role rows that have team_id = NULL but exist in spmb_applicants
        $nullUnitRows = \DB::table('model_has_roles')
            ->join('spmb_applicants', 'spmb_applicants.user_id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.role_id', $parentRole->id)
            ->whereNull('model_has_roles.team_id')
            ->whereNotNull('spmb_applicants.unit_id')
            ->select('model_has_roles.model_id', 'spmb_applicants.unit_id')
            ->get();

        foreach ($nullUnitRows as $row) {
            \DB::table('model_has_roles')
                ->where('role_id', $parentRole->id)
                ->where('model_id', $row->model_id)
                ->whereNull('team_id')
                ->update(['team_id' => $row->unit_id]);
        }
    }

    public function index(Request $request)
    {
        $this->syncSpmbParentRoles();

        $query = User::query();

        // Unit Isolation Check
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);
        if (!$isGlobalAdmin) {
            $unitId = session('active_unit_id');
            $query->where(function ($subQ) use ($unitId) {
                $subQ->whereExists(function ($q1) use ($unitId) {
                    $q1->select(\DB::raw(1))
                      ->from('model_has_roles')
                      ->whereColumn('model_has_roles.model_id', 'users.id')
                      ->where('model_has_roles.team_id', $unitId);
                })
                ->orWhereExists(function ($q2) use ($unitId) {
                    $q2->select(\DB::raw(1))
                       ->from('teachers')
                       ->whereColumn('teachers.user_id', 'users.id')
                       ->where('teachers.unit_id', $unitId);
                })
                ->orWhereExists(function ($q3) use ($unitId) {
                    $q3->select(\DB::raw(1))
                       ->from('staff')
                       ->whereColumn('staff.user_id', 'users.id')
                       ->where('staff.unit_id', $unitId);
                })
                ->orWhereExists(function ($q4) use ($unitId) {
                    $q4->select(\DB::raw(1))
                       ->from('students')
                       ->whereColumn('students.user_id', 'users.id')
                       ->where('students.unit_id', $unitId);
                })
                ->orWhereExists(function ($q5) use ($unitId) {
                    $q5->select(\DB::raw(1))
                       ->from('spmb_applicants')
                       ->whereColumn('spmb_applicants.user_id', 'users.id')
                       ->where('spmb_applicants.unit_id', $unitId);
                });
            });
        }

        // Search Filter
        $query->when($request->filled('search'), function ($q) use ($request) {
            $term = trim($request->search);
            $q->where(function ($sub) use ($term) {
                $sub->where('users.name', 'like', "%{$term}%")
                    ->orWhere('users.email', 'like', "%{$term}%")
                    ->orWhere('users.phone', 'like', "%{$term}%")
                    ->orWhereExists(function ($tQ) use ($term) {
                        $tQ->select(\DB::raw(1))
                           ->from('teachers')
                           ->whereColumn('teachers.user_id', 'users.id')
                           ->where(function ($tSub) use ($term) {
                               $tSub->where('nip', 'like', "%{$term}%")
                                    ->orWhere('phone', 'like', "%{$term}%")
                                    ->orWhere('full_name', 'like', "%{$term}%");
                           });
                    })
                    ->orWhereExists(function ($sQ) use ($term) {
                        $sQ->select(\DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.user_id', 'users.id')
                           ->where(function ($sSub) use ($term) {
                               $sSub->where('nip', 'like', "%{$term}%")
                                    ->orWhere('phone', 'like', "%{$term}%")
                                    ->orWhere('full_name', 'like', "%{$term}%");
                           });
                    })
                    ->orWhereExists(function ($stQ) use ($term) {
                        $stQ->select(\DB::raw(1))
                           ->from('students')
                           ->whereColumn('students.user_id', 'users.id')
                           ->where(function ($stSub) use ($term) {
                               $stSub->where('nis', 'like', "%{$term}%")
                                    ->orWhere('nisn', 'like', "%{$term}%")
                                    ->orWhere('full_name', 'like', "%{$term}%");
                           });
                    });
            });
        });

        // Role Filter (using whereExists to include team-scoped roles)
        $query->when($request->filled('role'), function ($q) use ($request) {
            $role = $request->role;
            $q->where(function ($roleSub) use ($role) {
                $roleSub->whereExists(function ($mq) use ($role) {
                    $mq->select(\DB::raw(1))
                       ->from('model_has_roles')
                       ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                       ->whereColumn('model_has_roles.model_id', 'users.id')
                       ->where('roles.name', $role);
                });

                if ($role === 'teacher') {
                    $roleSub->orWhereExists(function ($tq) {
                        $tq->select(\DB::raw(1))
                           ->from('teachers')
                           ->whereColumn('teachers.user_id', 'users.id');
                    });
                } elseif (in_array($role, ['staff_unit', 'staff_yayasan'])) {
                    $roleSub->orWhereExists(function ($sq) {
                        $sq->select(\DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.user_id', 'users.id');
                    });
                } elseif (in_array($role, ['siswa', 'student'])) {
                    $roleSub->orWhereExists(function ($stq) {
                        $stq->select(\DB::raw(1))
                           ->from('students')
                           ->whereColumn('students.user_id', 'users.id');
                    });
                }
            });
        });

        // Unit Filter
        $query->when($request->filled('unit_id'), function ($q) use ($request) {
            $unitId = $request->unit_id;
            $q->where(function ($subQ) use ($unitId) {
                $subQ->whereExists(function ($q1) use ($unitId) {
                    $q1->select(\DB::raw(1))
                       ->from('model_has_roles')
                       ->whereColumn('model_has_roles.model_id', 'users.id')
                       ->where('model_has_roles.team_id', $unitId);
                })
                ->orWhereExists(function ($q2) use ($unitId) {
                    $q2->select(\DB::raw(1))
                       ->from('teachers')
                       ->whereColumn('teachers.user_id', 'users.id')
                       ->where('teachers.unit_id', $unitId);
                })
                ->orWhereExists(function ($q3) use ($unitId) {
                    $q3->select(\DB::raw(1))
                       ->from('staff')
                       ->whereColumn('staff.user_id', 'users.id')
                       ->where('staff.unit_id', $unitId);
                })
                ->orWhereExists(function ($q4) use ($unitId) {
                    $q4->select(\DB::raw(1))
                       ->from('students')
                       ->whereColumn('students.user_id', 'users.id')
                       ->where('students.unit_id', $unitId);
                })
                ->orWhereExists(function ($q5) use ($unitId) {
                    $q5->select(\DB::raw(1))
                       ->from('spmb_applicants')
                       ->whereColumn('spmb_applicants.user_id', 'users.id')
                       ->where('spmb_applicants.unit_id', $unitId);
                });
            });
        });
        
        $users = $query->latest('users.id')->paginate(10)->withQueryString()->through(function ($user) {
            // Manual fetch of roles for display
            $roles = \DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->pluck('roles.name');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $roles,
                'units' => $user->getUnitsAttribute()->pluck('name'),
                'is_active' => !is_null($user->email_verified_at),
                'created_at' => $user->created_at ? $user->created_at->format('d M Y') : '-',
            ];
        });

        return Inertia::render('Yayasan/Users/Index', [
            'users' => $users,
            'units' => $isGlobalAdmin ? Unit::all() : Unit::where('id', session('active_unit_id'))->get(),
            'roles' => Role::where('name', '!=', 'super_admin_yayasan')->pluck('name'),
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'unit_id' => $request->input('unit_id', ''),
            ],
        ]);
    }

    public function create()
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);
        $units = $isGlobalAdmin 
            ? Unit::all() 
            : Unit::where('id', session('active_unit_id'))->get();

        $rolesQuery = Role::query();
        if (!$isGlobalAdmin) {
            $rolesQuery->whereNotIn('name', ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan']);
        } else {
            $rolesQuery->where('name', '!=', 'super_admin_yayasan');
        }

        return Inertia::render('Yayasan/Users/Create', [
            'units' => $units,
            'roles' => $rolesQuery->pluck('name'),
            'isGlobalAdmin' => $isGlobalAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required_without:role|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'role' => 'nullable|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        if (!$isGlobalAdmin) {
            if (empty($request->unit_id) || $request->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat membuat pengguna untuk unit lain.');
            }

            $disallowedRoles = ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan'];
            $requestedRoles = $request->roles ?? ($request->role ? [$request->role] : []);
            foreach ($requestedRoles as $r) {
                if (in_array($r, $disallowedRoles)) {
                    abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memberikan peran global yayasan.');
                }
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto-verify since Admin created it
        ]);

        $roles = $request->roles ?? ($request->role ? [$request->role] : []);
        $globalRoles = ['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'pengawas_yayasan', 'humas_yayasan'];

        // Assign Role based on whether it is a global role or unit role
        foreach ($roles as $r) {
            if (in_array($r, $globalRoles)) {
                setPermissionsTeamId(null);
            } else {
                setPermissionsTeamId($request->unit_id);
            }
            $user->assignRole($r);
        }

        // Sync Profile Data if unit_id is provided
        if ($request->unit_id) {
            foreach ($roles as $role) {
                $this->syncUserProfile($user, $role, $request->unit_id);
            }
        }
        
        // Reset Team ID to avoid polluting current session
        setPermissionsTeamId(session('active_unit_id'));

        return redirect()->route('yayasan.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

        // Fetch all current roles & team_id
        $roleInfos = \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', $user->id)
            ->select('model_has_roles.team_id', 'roles.name as role_name')
            ->get();
        
        $currentRoles = $roleInfos->pluck('role_name')->toArray();
        $targetUnitId = $roleInfos->first() ? $roleInfos->first()->team_id : null;

        if (!$isGlobalAdmin) {
            if ($targetUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk mengedit pengguna di unit lain.');
            }
        }

        $units = $isGlobalAdmin ? Unit::all() : Unit::where('id', session('active_unit_id'))->get();

        $rolesQuery = Role::query();
        if (!$isGlobalAdmin) {
            $rolesQuery->whereNotIn('name', ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan']);
        } else {
            $rolesQuery->where('name', '!=', 'super_admin_yayasan');
        }

        return Inertia::render('Yayasan/Users/Edit', [
            'user' => array_merge($user->toArray(), [
                'profile_photo_url' => $user->profile_photo_url,
            ]),
            'currentUnitId' => $targetUnitId,
            'currentRoles' => $currentRoles,
            'units' => $units,
            'roles' => $rolesQuery->pluck('name'),
            'isGlobalAdmin' => $isGlobalAdmin,
            'linkedProfiles' => $this->linkedProfiles($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

        $oldUnitId = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->value('team_id');

        if (!$isGlobalAdmin) {
            if ($oldUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk mengubah pengguna di unit lain.');
            }
            if ($request->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan pengguna ke unit lain.');
            }

            $disallowedRoles = ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan'];
            $requestedRoles = $request->roles ?? ($request->role ? [$request->role] : []);
            foreach ($requestedRoles as $r) {
                if (in_array($r, $disallowedRoles)) {
                    abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memberikan peran global yayasan.');
                }
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif,bmp|max:10240',
            'roles' => 'required_without:role|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'role' => 'nullable|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->hasFile('photo')) {
            if ($user->profile_photo) {
                \Storage::disk('public')->delete($user->profile_photo);
            }

            $file = $request->file('photo');
            $randomName = \Illuminate\Support\Str::random(40) . '.webp';
            $relativePath = 'profile-photos/' . $randomName;
            $destinationPath = storage_path('app/public/' . $relativePath);

            if (!file_exists(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
            }

            try {
                $imageContent = file_get_contents($file->getRealPath());
                $srcImage = @imagecreatefromstring($imageContent);

                if ($srcImage !== false) {
                    imagealphablending($srcImage, true);
                    imagesavealpha($srcImage, true);
                    imagewebp($srcImage, $destinationPath, 82);
                    imagedestroy($srcImage);
                } else {
                    $relativePath = $file->store('profile-photos', 'public');
                }
            } catch (\Throwable $e) {
                $relativePath = $file->store('profile-photos', 'public');
            }

            $updateData['profile_photo'] = $relativePath;
        }

        $user->update($updateData);

        $roles = $request->roles ?? ($request->role ? [$request->role] : []);
        $globalRoles = ['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'pengawas_yayasan', 'humas_yayasan'];

        // Revoke all previous roles to keep it clean (force delete from pivot)
        \DB::table('model_has_roles')->where('model_id', $user->id)->delete();

        foreach ($roles as $r) {
            if (in_array($r, $globalRoles)) {
                setPermissionsTeamId(null);
            } else {
                setPermissionsTeamId($request->unit_id);
            }
            $user->assignRole($r);
        }

        if ($request->unit_id) {
            // Sinkronkan seluruh profil pegawai (guru & staff) milik akun ini agar tidak terjadi double unit
            \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)
                ->update(['unit_id' => $request->unit_id, 'full_name' => $user->name]);
            \App\Modules\Employee\Models\Staff::where('user_id', $user->id)
                ->update(['unit_id' => $request->unit_id, 'full_name' => $user->name]);

            foreach ($roles as $role) {
                $this->syncUserProfile($user, $role, $request->unit_id);
            }
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

        if (auth()->id() === $user->id) {
             return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

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
     * Sinkronkan semua profil pegawai (guru/staff) milik user ke unit tertentu.
     */
    public function syncProfilesUnit(Request $request, User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

        $request->validate([
            'unit_id' => 'required|exists:units,id',
        ]);

        $unitId = (int) $request->unit_id;

        if (!$isGlobalAdmin) {
            $userUnitId = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->value('team_id');

            if ($userUnitId != session('active_unit_id') || $unitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan pegawai ke unit lain.');
            }
        }

        // 1. Update Teacher profiles
        \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)
            ->update(['unit_id' => $unitId, 'full_name' => $user->name]);

        // 2. Update Staff profiles
        \App\Modules\Employee\Models\Staff::where('user_id', $user->id)
            ->update(['unit_id' => $unitId, 'full_name' => $user->name]);

        // 3. Update team_id in model_has_roles for non-global roles
        $globalRoleNames = ['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'pengawas_yayasan', 'humas_yayasan'];
        $globalRoleIds = \DB::table('roles')->whereIn('name', $globalRoleNames)->pluck('id')->toArray();

        \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->whereNotIn('role_id', $globalRoleIds)
            ->update(['team_id' => $unitId]);

        $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
        $unitName = $unit ? $unit->name : "Unit #{$unitId}";

        return redirect()->back()->with('success', "Semua profil pegawai untuk {$user->name} berhasil disinkronkan ke {$unitName}.");
    }

    /**
     * Lepas atau hapus profil tertentu (guru/staff/siswa) yang terhubung ke akun user.
     */
    public function unlinkProfile(Request $request, User $user)
    {
        $currentUser = auth()->user();
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

        $request->validate([
            'type' => 'required|in:guru,staff,siswa',
            'profile_id' => 'required|integer',
            'action' => 'nullable|in:unlink,delete',
        ]);

        $type = $request->type;
        $profileId = (int) $request->profile_id;
        $action = $request->input('action', 'unlink');

        if ($type === 'guru') {
            $teacher = \App\Modules\Academic\Models\Teacher::where('id', $profileId)
                ->where('user_id', $user->id)
                ->first();

            if (!$teacher) {
                return redirect()->back()->with('error', 'Profil guru tidak ditemukan atau tidak terhubung dengan user ini.');
            }

            if (!$isGlobalAdmin && $teacher->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mengubah profil di unit lain.');
            }

            $hasSchedules = \App\Modules\Academic\Models\ClassSchedule::where('teacher_id', $teacher->id)->exists();
            $hasJournals = \App\Modules\Academic\Models\TeachingJournal::where('teacher_id', $teacher->id)->exists();
            $isHomeroom = \App\Modules\Academic\Models\Classroom::where('homeroom_teacher_id', $teacher->id)->exists();

            if ($action === 'delete') {
                if ($hasSchedules || $hasJournals || $isHomeroom) {
                    $teacher->update(['user_id' => null]);
                    return redirect()->back()->with('warning', 'Profil guru memiliki data jadwal/jurnal aktif sehingga tidak dihapus permanen, namun keterhubungan akun berhasil dilepas.');
                }
                $teacher->delete();
                return redirect()->back()->with('success', 'Profil guru berhasil dihapus.');
            } else {
                $teacher->update(['user_id' => null]);
                return redirect()->back()->with('success', 'Profil guru berhasil dilepas dari akun ini.');
            }
        } elseif ($type === 'staff') {
            $staff = \App\Modules\Employee\Models\Staff::where('id', $profileId)
                ->where('user_id', $user->id)
                ->first();

            if (!$staff) {
                return redirect()->back()->with('error', 'Profil staff tidak ditemukan atau tidak terhubung dengan user ini.');
            }

            if (!$isGlobalAdmin && $staff->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mengubah profil di unit lain.');
            }

            if ($action === 'delete') {
                $staff->delete();
                return redirect()->back()->with('success', 'Profil staff berhasil dihapus.');
            } else {
                $staff->update(['user_id' => null]);
                return redirect()->back()->with('success', 'Profil staff berhasil dilepas dari akun ini.');
            }
        } elseif ($type === 'siswa') {
            $student = \App\Modules\Academic\Models\Student::where('id', $profileId)
                ->where('user_id', $user->id)
                ->first();

            if (!$student) {
                return redirect()->back()->with('error', 'Profil siswa tidak ditemukan atau tidak terhubung dengan user ini.');
            }

            if (!$isGlobalAdmin && $student->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mengubah profil di unit lain.');
            }

            // Always only unlink user_id for student to preserve academic records
            $student->update(['user_id' => null]);
            return redirect()->back()->with('success', 'Profil siswa berhasil dilepas dari akun user ini.');
        }

        return redirect()->back()->with('error', 'Tipe profil tidak valid.');
    }

    /**
     * Semua profil (guru/staff/siswa) yang terhubung ke akun ini, beserta unitnya.
     * Dipakai halaman Edit User untuk memberi peringatan bila satu akun dipakai
     * oleh lebih dari satu orang / unit (lihat command users:split-shared-accounts).
     */
    private function linkedProfiles(User $user): array
    {
        $map = fn ($type) => fn ($row) => [
            'type' => $type,
            'id' => $row->id,
            'name' => $row->full_name,
            'unit_id' => $row->unit_id,
            'unit_name' => $row->unit_name,
        ];

        $query = fn ($table) => \DB::table($table)
            ->leftJoin('units', 'units.id', '=', "{$table}.unit_id")
            ->where("{$table}.user_id", $user->id)
            ->get(["{$table}.id", "{$table}.full_name", "{$table}.unit_id", 'units.name as unit_name']);

        return collect()
            ->merge($query('teachers')->map($map('guru')))
            ->merge($query('staff')->map($map('staff')))
            ->merge($query('students')->map($map('siswa')))
            ->values()
            ->all();
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
             $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
             $student = \App\Modules\Academic\Models\Student::where('user_id', $user->id)->first();
             if ($student) {
                 $updateData = [
                    'unit_id' => $unitId,
                    'full_name' => $user->name,
                 ];
                 if (!$student->academic_year_id && $activeYear) {
                     $updateData['academic_year_id'] = $activeYear->id;
                 }
                 $student->update($updateData);
             } else {
                 \App\Modules\Academic\Models\Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'academic_year_id' => $activeYear?->id,
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
        $isGlobalAdmin = $this->isGlobalAdminUser($currentUser);

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
