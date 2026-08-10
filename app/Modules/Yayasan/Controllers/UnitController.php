<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index()
    {
        $query = Unit::query();

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'pembina_yayasan'])) {
            $unitId = session('active_unit_id');
            if ($unitId) {
                $query->where('id', $unitId);
            }
        }

        $units = $query->get();

        return Inertia::render('Yayasan/Units/Index', [
            'units' => $units,
        ]);
    }

    public function dashboard()
    {
        $unitId = session('active_unit_id');
        
        // Get upcoming events for next 30 days (global + unit specific)
        $upcomingEvents = \App\Modules\Yayasan\Models\Holiday::with('unit')
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays(30)->toDateString())
            ->where(function ($q) use ($unitId) {
                $q->whereNull('unit_id')
                  ->orWhere('unit_id', $unitId);
            })
            ->orderBy('date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($h) {
                $h->display_color = $h->displayColor;
                return $h;
            });
        
        $user = auth()->user();
        $isGlobal = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan']);
        $studentsCount = $isGlobal 
            ? \App\Modules\Academic\Models\Student::count()
            : \App\Modules\Academic\Models\Student::where('unit_id', $unitId)->count();
        $unitsCount = $isGlobal ? Unit::count() : 1;

        // Teacher Specific Payload
        $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        $teacherData = null;

        if ($teacher) {
            $dayMap = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ];
            $todayIndo = $dayMap[date('l')] ?? 'Senin';

            $schedules = \App\Modules\Academic\Models\ClassSchedule::with(['classroom', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->where('day', $todayIndo)
                ->orderBy('start_time', 'asc')
                ->get();

            $nowTime = date('H:i:s');
            $currentSchedule = $schedules->first(function ($s) use ($nowTime) {
                return $nowTime >= $s->start_time && $nowTime <= $s->end_time;
            }) ?? $schedules->first();

            $homeroomClass = \App\Modules\Academic\Models\Classroom::where('homeroom_teacher_id', $teacher->id)->first();
            $homeroomStats = null;

            if ($homeroomClass) {
                $totalClassStudents = \App\Modules\Academic\Models\Student::where('classroom_id', $homeroomClass->id)->count();
                $todayAtt = \App\Models\StudentAttendance::where('classroom_id', $homeroomClass->id)
                    ->whereDate('date', today())
                    ->get();

                $stats = [
                    'H' => $todayAtt->where('status', 'H')->count(),
                    'S' => $todayAtt->where('status', 'S')->count(),
                    'I' => $todayAtt->where('status', 'I')->count(),
                    'A' => $todayAtt->where('status', 'A')->count(),
                ];

                $hasAttendance = $todayAtt->count() > 0;
                $rate = ($hasAttendance && $totalClassStudents > 0) 
                    ? round(($stats['H'] / $totalClassStudents) * 100) 
                    : 0;

                $homeroomStats = [
                    'classroom_id' => $homeroomClass->id,
                    'class_name' => $homeroomClass->name,
                    'total_students' => $totalClassStudents,
                    'has_attendance' => $hasAttendance,
                    'present' => $stats['H'],
                    'sick' => $stats['S'],
                    'permission' => $stats['I'],
                    'alpha' => $stats['A'],
                    'rate' => $rate,
                ];
            }

            // Employee Checkin Status today
            $employeeAttendance = \App\Models\EmployeeAttendance::where('user_id', $user->id)
                ->whereDate('date', today())
                ->first();

            $teacherData = [
                'teacher_id' => $teacher->id,
                'title' => $teacher->title ?? 'Guru Pengajar',
                'homeroom_class' => $homeroomClass ? $homeroomClass->name : null,
                'today_day' => $todayIndo,
                'today_date' => now()->locale('id')->translatedFormat('l, j F Y'),
                'current_schedule' => $currentSchedule ? [
                    'id' => $currentSchedule->id,
                    'subject_name' => $currentSchedule->subject->name ?? 'Mata Pelajaran',
                    'classroom_name' => $currentSchedule->classroom->name ?? 'Kelas',
                    'start_time' => substr($currentSchedule->start_time, 0, 5),
                    'end_time' => substr($currentSchedule->end_time, 0, 5),
                ] : null,
                'schedules' => $schedules->map(fn($s) => [
                    'id' => $s->id,
                    'subject_name' => $s->subject->name ?? 'Mata Pelajaran',
                    'classroom_name' => $s->classroom->name ?? 'Kelas',
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time' => substr($s->end_time, 0, 5),
                ]),
                'homeroom_stats' => $homeroomStats,
            ];
        }

        // Employee Checkin Status today for ALL users (teachers, satpam, staff, admin, etc.)
        $employeeAttendance = \App\Models\EmployeeAttendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $attendanceStatus = $employeeAttendance ? [
            'checked_in' => true,
            'time' => substr($employeeAttendance->check_in_time ?? $employeeAttendance->clock_in ?? '07:05:00', 0, 5),
            'status' => $employeeAttendance->status ?? 'Tepat Waktu',
        ] : [
            'checked_in' => false,
            'time' => null,
            'status' => 'Belum Absen',
        ];

        // Determine human-readable Role Title
        $roleTitle = 'Pegawai Yayasan';
        if ($user->hasRole('super_admin_yayasan')) {
            $roleTitle = 'Super Admin Yayasan';
        } elseif ($user->hasRole('admin_yayasan')) {
            $roleTitle = 'Admin Yayasan';
        } elseif ($user->hasRole('admin_unit')) {
            $roleTitle = 'Admin Unit';
        } elseif ($user->hasRole('kepala_sekolah')) {
            $roleTitle = 'Kepala Sekolah';
        } elseif ($user->hasRole('satpam')) {
            $roleTitle = 'Petugas Keamanan / Satpam';
        } elseif ($user->hasRole('staff_yayasan')) {
            $roleTitle = 'Staf Yayasan';
        } elseif ($user->hasRole('staff_unit')) {
            $roleTitle = 'Staf Administrasi';
        } elseif ($user->hasRole('koordinator_sarpar')) {
            $roleTitle = 'Koordinator Sarpras';
        } elseif ($user->hasRole('finance')) {
            $roleTitle = 'Staf Keuangan';
        } elseif ($user->hasRole('humas_unit')) {
            $roleTitle = 'Humas Unit';
        } elseif ($user->hasRole('bk')) {
            $roleTitle = 'Guru Bimbingan Konseling';
        } elseif ($teacher) {
            $roleTitle = $teacher->homeroom_class ? 'Wali Kelas' : ($teacher->title ?? 'Guru Pengajar');
        }

        $userData = [
            'role_title' => $roleTitle,
            'is_teacher' => (bool) $teacher,
            'today_date' => now()->locale('id')->translatedFormat('l, j F Y'),
            'attendance_status' => $attendanceStatus,
        ];
        
        return Inertia::render('Yayasan/Dashboard/Index', [
             'unitsCount' => $unitsCount,
             'studentsCount' => $studentsCount,
             'activeYear' => \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first(),
             'upcomingEvents' => $upcomingEvents,
             'teacherData' => $teacherData,
             'userData' => $userData,
        ]);
    }

    public function switch(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
        ]);

        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $hasRoleInUnit = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('team_id', $request->unit_id)
                ->exists();
            if (!$hasRoleInUnit) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk berpindah ke unit ini.');
            }
        }

        session(['active_unit_id' => $request->unit_id]);

        return redirect()->back()->with('success', 'Unit aktif berhasil diubah.');
    }

    /**
     * Display the specified unit profile.
     */
    public function show(Unit $unit)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Peran Anda tidak memiliki izin untuk melihat unit ini.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan']) && $unit->id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses data unit lain.');
        }

        // Eager load principal details
        $unit->load('principal.teacher_profile', 'principal.staff');

        // Count related data for stats
        $studentsCount = \App\Modules\Academic\Models\Student::where('unit_id', $unit->id)->count();
        $teachersCount = \App\Modules\Academic\Models\Teacher::where('unit_id', $unit->id)->count();
        $classroomsCount = \App\Modules\Academic\Models\Classroom::where('unit_id', $unit->id)->count();

        return Inertia::render('Yayasan/Units/Show', [
            'unit' => $unit,
            'stats' => [
                'students' => $studentsCount,
                'teachers' => $teachersCount,
                'classrooms' => $classroomsCount,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya Admin Yayasan yang dapat membuat unit baru.');
        }

        // Fetch all users with kepala_sekolah role (bypass Spatie team scope)
        $principals = \App\Models\User::whereIn('id', function($query) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'kepala_sekolah')
                ->where('model_has_roles.model_type', \App\Models\User::class);
        })->get();

        return Inertia::render('Yayasan/Units/Create', [
            'principals' => $principals,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya Admin Yayasan yang dapat menambahkan unit.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:units,code',
            'category' => 'required|string|in:TK,SD,SMP,SMA,SMK,Lainnya',
            'level' => 'required|string|in:Nasional,Internasional,Plus',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|max:2048',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'late_tolerance_minutes' => 'required|integer|min:0',
            'principal_id' => [
                'nullable',
                'unique:units,principal_id',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $hasRole = \DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_id', $value)
                        ->where('model_has_roles.model_type', \App\Models\User::class)
                        ->where('roles.name', 'kepala_sekolah')
                        ->exists();
                    if (!$hasRole) {
                        $fail('User yang dipilih harus memiliki peran Kepala Sekolah.');
                    }
                }
            ],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('unit-logos', 'public');
        }

        Unit::create($validated);

        return redirect()->route('yayasan.units.index')->with('success', 'Unit pendidikan berhasil ditambahkan.');
    }

    public function edit(Unit $unit)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Peran Anda tidak memiliki izin untuk mengedit unit ini.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan']) && $unit->id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit unit lain.');
        }

        // Fetch all users with kepala_sekolah role (bypass Spatie team scope)
        $principals = \App\Models\User::whereIn('id', function($query) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'kepala_sekolah')
                ->where('model_has_roles.model_type', \App\Models\User::class);
        })->get();

        return Inertia::render('Yayasan/Units/Edit', [
            'unit' => $unit,
            'principals' => $principals,
        ]);
    }

    public function update(Request $request, Unit $unit)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Peran Anda tidak memiliki izin untuk mengubah data unit ini.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan']) && $unit->id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah data unit lain.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:units,code,' . $unit->id,
            'category' => 'required|string|in:TK,SD,SMP,SMA,SMK,Lainnya',
            'level' => 'required|string|in:Nasional,Internasional,Plus',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|max:2048',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'late_tolerance_minutes' => 'required|integer|min:0',
            'principal_id' => [
                'nullable',
                'unique:units,principal_id,' . $unit->id,
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $hasRole = \DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_id', $value)
                        ->where('model_has_roles.model_type', \App\Models\User::class)
                        ->where('roles.name', 'kepala_sekolah')
                        ->exists();
                    if (!$hasRole) {
                        $fail('User yang dipilih harus memiliki peran Kepala Sekolah.');
                    }
                }
            ],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($unit->logo) {
                Storage::disk('public')->delete($unit->logo);
            }
            $validated['logo'] = $request->file('logo')->store('unit-logos', 'public');
        } else {
            unset($validated['logo']);
        }

        $unit->update($validated);

        return redirect()->route('yayasan.units.index')->with('success', 'Data unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya Admin Yayasan yang dapat menghapus unit.');
        }

        // Delete logo if exists
        if ($unit->logo) {
            Storage::disk('public')->delete($unit->logo);
        }

        $unit->delete();

        return redirect()->route('yayasan.units.index')->with('success', 'Unit pendidikan berhasil dihapus.');
    }
}
