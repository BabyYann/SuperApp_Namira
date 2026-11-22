<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeacherController extends Controller
{
    private function syncTeacherRoles(User $user, int $unitId, array $flags): void
    {
        $managedRoles = [
            'teacher' => (bool) ($flags['is_regular_teacher'] ?? false),
            'guru_alquran' => (bool) ($flags['is_quran_teacher'] ?? false),
            'koordinator_alquran' => (bool) ($flags['is_quran_coordinator'] ?? false),
        ];

        // Ensure at least one role is enabled if none provided
        if (!in_array(true, $managedRoles, true)) {
            $managedRoles['teacher'] = true;
        }

        foreach ($managedRoles as $roleName => $shouldHave) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if ($shouldHave) {
                \DB::table('model_has_roles')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'team_id' => $unitId,
                    ],
                    []
                );
            } else {
                \DB::table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->where(function ($q) use ($unitId) {
                        $q->where('team_id', $unitId)->orWhereNull('team_id');
                    })
                    ->delete();
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function index()
    {
        // Scope by active unit
        $unitId = session('active_unit_id');
        $teacherType = request('teacher_type');

        $teachers = Teacher::with('user')
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->when(request('gender'), function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when($teacherType, function ($query, $type) use ($unitId) {
                $hasRoleSql = function ($q, array $roleNames) use ($unitId) {
                    $q->whereExists(function ($sub) use ($roleNames, $unitId) {
                        $sub->select(\DB::raw(1))
                            ->from('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereColumn('model_has_roles.model_id', 'teachers.user_id')
                            ->where('model_has_roles.model_type', User::class)
                            ->whereIn('roles.name', $roleNames)
                            ->where(function ($t) use ($unitId) {
                                $t->where('model_has_roles.team_id', $unitId)->orWhereNull('model_has_roles.team_id');
                            });
                    });
                };

                if ($type === 'quran') {
                    $hasRoleSql($query, ['guru_alquran', 'koordinator_alquran']);
                } elseif ($type === 'quran_only') {
                    $hasRoleSql($query, ['guru_alquran', 'koordinator_alquran']);
                    $query->whereNotExists(function ($sub) use ($unitId) {
                        $sub->select(\DB::raw(1))
                            ->from('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereColumn('model_has_roles.model_id', 'teachers.user_id')
                            ->where('model_has_roles.model_type', User::class)
                            ->where('roles.name', 'teacher')
                            ->where(function ($t) use ($unitId) {
                                $t->where('model_has_roles.team_id', $unitId)->orWhereNull('model_has_roles.team_id');
                            });
                    });
                } elseif ($type === 'dual') {
                    $hasRoleSql($query, ['teacher']);
                    $hasRoleSql($query, ['guru_alquran', 'koordinator_alquran']);
                } elseif ($type === 'regular') {
                    $hasRoleSql($query, ['teacher']);
                    $query->whereNotExists(function ($sub) use ($unitId) {
                        $sub->select(\DB::raw(1))
                            ->from('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereColumn('model_has_roles.model_id', 'teachers.user_id')
                            ->where('model_has_roles.model_type', User::class)
                            ->whereIn('roles.name', ['guru_alquran', 'koordinator_alquran'])
                            ->where(function ($t) use ($unitId) {
                                $t->where('model_has_roles.team_id', $unitId)->orWhereNull('model_has_roles.team_id');
                            });
                    });
                }
            })
            ->latest()
            ->paginate(request('per_page', 10))
            ->withQueryString();

        // Hydrate unit role labels for each teacher
        $pageUserIds = $teachers->getCollection()->pluck('user_id')->filter()->unique()->values();
        $rolesByUser = [];
        if ($pageUserIds->isNotEmpty()) {
            $roleRows = \DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', User::class)
                ->whereIn('model_has_roles.model_id', $pageUserIds)
                ->where(function ($q) use ($unitId) {
                    $q->where('model_has_roles.team_id', $unitId)->orWhereNull('model_has_roles.team_id');
                })
                ->select('model_has_roles.model_id', 'roles.name')
                ->get();

            foreach ($roleRows as $row) {
                $rolesByUser[$row->model_id][] = $row->name;
            }
        }

        $teachers->getCollection()->transform(function ($t) use ($rolesByUser) {
            $roles = array_values(array_unique($rolesByUser[$t->user_id] ?? []));
            $t->role_names = $roles;
            $t->is_regular_teacher = in_array('teacher', $roles, true);
            $t->is_quran_teacher = in_array('guru_alquran', $roles, true);
            $t->is_quran_coordinator = in_array('koordinator_alquran', $roles, true);

            return $t;
        });

        // Fetch registered users who don't have a teacher profile in this unit yet
        $existingTeacherUserIds = Teacher::where('unit_id', $unitId)->pluck('user_id')->filter()->toArray();
        $availableUsersQuery = User::whereNotIn('id', $existingTeacherUserIds);
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $availableUsersQuery->where(function ($q) use ($unitId) {
                $q->whereHas('roles', function ($sub) use ($unitId) {
                    $sub->whereRaw("model_has_roles.model_id = users.id AND (model_has_roles.team_id = ? OR model_has_roles.team_id IS NULL)", [$unitId]);
                })->orDoesntHave('roles');
            });
        }

        // Return lightweight plain arrays to avoid N+1 on User::$appends['profile_photo_url']
        $availableUsers = $availableUsersQuery
            ->latest()
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])
            ->values();

        // Statistics
        $totalTeachers = Teacher::where('unit_id', $unitId)->count();
        $countL = Teacher::where('unit_id', $unitId)->where('gender', 'L')->count();
        $countP = Teacher::where('unit_id', $unitId)->where('gender', 'P')->count();
        $countQuran = Teacher::where('unit_id', $unitId)
            ->whereExists(function ($sub) use ($unitId) {
                $sub->select(\DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'teachers.user_id')
                    ->where('model_has_roles.model_type', User::class)
                    ->whereIn('roles.name', ['guru_alquran', 'koordinator_alquran'])
                    ->where(function ($t) use ($unitId) {
                        $t->where('model_has_roles.team_id', $unitId)->orWhereNull('model_has_roles.team_id');
                    });
            })
            ->count();

        return Inertia::render('Academic/Teachers/Index', [
            'teachers' => $teachers,
            'availableUsers' => $availableUsers,
            'filters' => request()->only(['search', 'gender', 'teacher_type']),
            'stats' => [
                'total' => $totalTeachers,
                'male' => $countL,
                'female' => $countP,
                'quran' => $countQuran,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menambah data guru.');
        }

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;
        
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required_without:user_id|nullable|email',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:10240', // Max 10MB
            'is_regular_teacher' => 'nullable|boolean',
            'is_quran_teacher' => 'nullable|boolean',
            'is_quran_coordinator' => 'nullable|boolean',
        ]);

        try {
            \DB::transaction(function () use ($validated, $unitId, $request) {
                // 1. Find or Create User
                if (!empty($validated['user_id'])) {
                    $user = User::findOrFail($validated['user_id']);
                } else {
                    $user = User::where('email', $validated['email'])->first();

                    if (!$user) {
                        // Generate password from NIP or default
                        $password = !empty($validated['nip']) ? $validated['nip'] : 'guru123';
                        $user = User::create([
                            'name' => $validated['full_name'],
                            'email' => $validated['email'],
                            'password' => Hash::make($password), 
                        ]);
                    }
                }
                
                // Sync teacher/quran roles
                $hasExplicitFlags = $request->has('is_regular_teacher') || $request->has('is_quran_teacher') || $request->has('is_quran_coordinator');
                $this->syncTeacherRoles($user, (int) $unitId, $hasExplicitFlags ? [
                    'is_regular_teacher' => $request->boolean('is_regular_teacher'),
                    'is_quran_teacher' => $request->boolean('is_quran_teacher'),
                    'is_quran_coordinator' => $request->boolean('is_quran_coordinator'),
                ] : [
                    'is_regular_teacher' => true,
                    'is_quran_teacher' => false,
                    'is_quran_coordinator' => false,
                ]);

                // 2. Handle File Upload
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('teachers', 'public');
                }

                // 3. Create Teacher Profile
                Teacher::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $validated['full_name'],
                    'nip' => $validated['nip'] ?? null,
                    'gender' => $validated['gender'],
                    'phone' => $validated['phone'] ?? null,
                    'photo' => $photoPath,
                ]);
            });

            return redirect()->back()->with('success', 'Guru berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan guru: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $teacher = Teacher::with(['user', 'classroom'])->find($id);
        if (!$teacher) {
            return redirect()->route('yayasan.teachers.index')
                ->with('error', 'Data guru tidak ditemukan atau telah dihapus.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $teacher->unit_id != session('active_unit_id')) {
            abort(403);
        }
        
        // Fetch Teaching Schedule
        $schedules = \App\Modules\Academic\Models\ClassSchedule::with(['classroom', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');
            
        // Fetch Attendance Summary (Mock or Actual if implemented)
        $attendanceHistory = \App\Models\EmployeeAttendance::where('user_id', $teacher->user_id)
            ->latest('date')
            ->limit(30)
            ->get();
        
        return Inertia::render('Academic/Teachers/Show', [
            'teacher' => $teacher,
            'schedules' => $schedules,
            'attendanceHistory' => $attendanceHistory,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat mengubah data guru.');
        }

        $teacher = Teacher::find($id);
        if (!$teacher) {
            return redirect()->route('yayasan.teachers.index')
                ->with('error', 'Data guru tidak ditemukan atau telah dihapus.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $teacher->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah data guru dari unit lain.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:10240',
            'is_regular_teacher' => 'nullable|boolean',
            'is_quran_teacher' => 'nullable|boolean',
            'is_quran_coordinator' => 'nullable|boolean',
        ]);

        $teacher->full_name = $validated['full_name'];
        $teacher->nip = $validated['nip'] ?? null;
        $teacher->gender = $validated['gender'];
        $teacher->phone = $validated['phone'] ?? null;

        // Handle File Upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($teacher->photo) {
                \Storage::disk('public')->delete($teacher->photo);
            }
            $teacher->photo = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->save();
        
        // Update User name and roles if user exists
        if ($teacher->user) {
            $teacher->user->update(['name' => $validated['full_name']]);

            if ($request->has('is_regular_teacher') || $request->has('is_quran_teacher') || $request->has('is_quran_coordinator')) {
                $this->syncTeacherRoles($teacher->user, (int) $teacher->unit_id, [
                    'is_regular_teacher' => $request->boolean('is_regular_teacher'),
                    'is_quran_teacher' => $request->boolean('is_quran_teacher'),
                    'is_quran_coordinator' => $request->boolean('is_quran_coordinator'),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Data guru diperbarui.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menghapus data guru.');
        }

        $teacher = Teacher::find($id);
        if (!$teacher) {
            // Sudah terhapus sebelumnya (misal akibat double click / refresh)
            return redirect()->route('yayasan.teachers.index')
                ->with('success', 'Data guru sudah berhasil dihapus sebelumnya.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $teacher->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus data guru dari unit lain.');
        }

        \DB::transaction(function () use ($teacher) {
            if ($teacher->photo) {
                \Storage::disk('public')->delete($teacher->photo);
            }

            $user = $teacher->user;

            // Hapus profil guru
            $teacher->delete();

            // Tangani akun user terkait jika ada
            if ($user) {
                // Periksa apakah user memiliki peran atau profil lain (misal: staff, siswa, admin)
                $hasOtherProfiles = \DB::table('staff')->where('user_id', $user->id)->exists()
                    || \DB::table('students')->where('user_id', $user->id)->exists();

                $nonTeacherRoles = $user->roles()->where('name', '!=', 'teacher')->count();

                if (!$hasOtherProfiles && $nonTeacherRoles === 0) {
                    // Akun murni guru: aman dihapus total
                    try {
                        $user->delete();
                    } catch (\Throwable $e) {
                        // Jika ada relasi foreign key lain di audit/log, cabut peran teacher
                        $user->removeRole('teacher');
                    }
                } else {
                    // Akun memiliki profil/peran lain: cabut peran teacher saja agar akun staf/yayasan tetap utuh
                    $user->removeRole('teacher');
                }
            }
        });

        return redirect()->route('yayasan.teachers.index')->with('success', 'Guru berhasil dihapus.');
    }
}
