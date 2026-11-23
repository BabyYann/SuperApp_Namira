<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StudentController extends Controller
{
    private function authorizeStudentManagement()
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_unit', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menambah, mengubah, atau menghapus data siswa.');
        }
    }

    public function index()
    {
        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan']);
        $canManage = $isGlobalAdmin || ($user && $user->hasAnyRole(['admin_unit', 'staff_unit', 'kepala_sekolah']));
        
        // Teacher Profile
        $teacherProfile = $user ? ($user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first()) : null;

        // Strict Unit Scoping
        if ($isGlobalAdmin) {
            $unitId = request('unit_id') ?: session('active_unit_id');
            if (!$unitId) {
                $unitId = \App\Modules\Yayasan\Models\Unit::first()?->id;
            }
        } else {
            // Strictly enforce unit belonging to teacher or current session
            $unitId = $teacherProfile?->unit_id ?: session('active_unit_id');
        }

        $activeUnit = \App\Modules\Yayasan\Models\Unit::find($unitId);
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();

        // Check if user is homeroom teacher (Walas) in this unit
        $homeroomClass = null;
        if ($teacherProfile) {
            $homeroomClass = \App\Modules\Academic\Models\Classroom::where('homeroom_teacher_id', $teacherProfile->id)
                ->where('unit_id', $unitId)
                ->first();
        }

        // Determine active scope:
        // Default for Walas is 'homeroom' (prioritizing their own class)
        $requestedScope = request('scope');
        $requestedClassroomId = request('classroom_id');

        if ($homeroomClass) {
            if (!$requestedScope && !$requestedClassroomId && !request()->has('search')) {
                $activeScope = 'homeroom';
            } elseif ($requestedScope === 'all') {
                $activeScope = 'all';
            } elseif ($requestedClassroomId) {
                $activeScope = ($requestedClassroomId == $homeroomClass->id) ? 'homeroom' : 'all';
            } else {
                $activeScope = $requestedScope ?: 'homeroom';
            }
        } else {
            $activeScope = 'all';
        }

        // Self-heal once per hour per unit+year (avoids running write UPDATE on every GET visit)
        if ($activeYear && $unitId) {
            $healKey = "students_year_healed_{$unitId}_{$activeYear->id}";
            if (!\Illuminate\Support\Facades\Cache::has($healKey)) {
                Student::where('unit_id', $unitId)
                    ->where(function ($q) use ($activeYear) {
                        $q->whereNull('academic_year_id')
                          ->orWhere(function ($sub) use ($activeYear) {
                              $sub->whereNotNull('classroom_id')
                                  ->where('academic_year_id', '!=', $activeYear->id);
                          });
                    })
                    ->update(['academic_year_id' => $activeYear->id]);
                \Illuminate\Support\Facades\Cache::put($healKey, true, now()->addHour());
            }
        }

        $studentsQuery = Student::with(['user', 'classroom.homeroomTeacher', 'academicYear'])
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when(request('gender'), function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when(request('academic_year_id'), function ($query, $yearId) use ($activeYear) {
                if ($activeYear && $yearId == $activeYear->id) {
                    $query->where(function ($q) use ($yearId) {
                        $q->where('academic_year_id', $yearId)
                          ->orWhereNull('academic_year_id')
                          ->orWhereNotNull('classroom_id');
                    });
                } else {
                    $query->where('academic_year_id', $yearId);
                }
            });

        // Apply classroom scoping
        if ($activeScope === 'homeroom' && $homeroomClass) {
            $studentsQuery->where('classroom_id', $homeroomClass->id);
        } elseif ($requestedClassroomId) {
            $studentsQuery->where('classroom_id', $requestedClassroomId);
        }

        $students = $studentsQuery->latest()
            ->paginate(30)
            ->withQueryString();

        // Classrooms in this unit
        $classrooms = \App\Modules\Academic\Models\Classroom::with('homeroomTeacher')
             ->where('unit_id', $unitId)
             ->orderBy('level')
             ->orderBy('name')
             ->get();
        
        $academicYears = \App\Modules\Yayasan\Models\AcademicYear::orderBy('id', 'desc')->get();

        $homeroomStudentsCount = $homeroomClass ? Student::where('classroom_id', $homeroomClass->id)->count() : 0;
        $totalUnitStudentsCount = Student::where('unit_id', $unitId)->count();

        return Inertia::render('Academic/Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'canManage' => $canManage,
            'isTeacher' => (bool)$teacherProfile,
            'isHomeroom' => (bool)$homeroomClass,
            'homeroomClass' => $homeroomClass ? [
                'id' => $homeroomClass->id,
                'name' => $homeroomClass->name,
                'level' => $homeroomClass->level,
                'student_count' => $homeroomStudentsCount,
            ] : null,
            'activeScope' => $activeScope,
            'activeUnit' => $activeUnit ? [
                'id' => $activeUnit->id,
                'name' => $activeUnit->name,
                'code' => $activeUnit->code,
            ] : null,
            'totalUnitStudents' => $totalUnitStudentsCount,
            'filters' => [
                'search' => request('search', ''),
                'gender' => request('gender', ''),
                'classroom_id' => $activeScope === 'homeroom' ? ($homeroomClass?->id ?? '') : ($requestedClassroomId ?: ''),
                'academic_year_id' => request('academic_year_id', ''),
                'scope' => $activeScope,
            ],
        ]);
    }

    public function import(Request $request)
    {
        $this->authorizeStudentManagement();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Read and validate header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'File CSV kosong.');
        }

        $headerText = strtolower(implode(' ', $header));
        if (!str_contains($headerText, 'nama') && !str_contains($headerText, 'name') && !str_contains($headerText, 'nis')) {
            fclose($handle);
            return redirect()->back()->with('error', 'Format header CSV tidak valid. Pastikan terdapat kolom Nama, Email, dan NIS.');
        }

        $count = 0;
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
        \DB::transaction(function () use ($handle, $unitId, $activeYear, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                // Expected CSV Format: Name, Email, NIS, Gender (L/P)
                // Adjust index based on CSV structure. Assuming: 0=Name, 1=Email, 2=NIS, 3=Gender
                if (count($row) < 4) continue;

                $name = $row[0];
                $email = $row[1];
                $nis = $row[2];
                $gender = strtoupper($row[3]);

                // Basic Validation
                if (!$email || User::where('email', $email)->exists()) continue;

                // Create User
                $password = $nis ? $nis : 'siswa123';
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);

                setPermissionsTeamId($unitId);
                $user->assignRole('siswa');

                // Create Student Profile
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'academic_year_id' => $activeYear?->id,
                    'full_name' => $name,
                    'nis' => $nis,
                    'gender' => in_array($gender, ['L', 'P']) ? $gender : 'L',
                ]);
                
                $count++;
            }
        });

        fclose($handle);

        return redirect()->back()->with('success', "$count Siswa berhasil diimport.");
    }

    public function importVa(Request $request)
    {
        $this->authorizeStudentManagement();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $unitId = session('active_unit_id');
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header if present (Assuming row 1 is header)
        // Heuristic: Check if first row contains "NIS" or "VA"
        $firstRow = fgetcsv($handle);
        if ($firstRow && !is_numeric($firstRow[0])) {
            // It's likely a header, do nothing (we already consumed it)
        } else {
            // It's data, rewind so we don't miss it
            rewind($handle);
        }

        $count = 0;
        $failed = 0;

        DB::transaction(function () use ($handle, $unitId, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                // Expected CSV Format: NIS, VA Number
                if (count($row) < 2) continue;

                $nis = trim($row[0]);
                $vaNumber = trim($row[1]);

                if (empty($nis) || empty($vaNumber)) continue;

                $student = Student::where('unit_id', $unitId)
                    ->where('nis', $nis)
                    ->first();

                if ($student) {
                    $student->update(['va_number' => $vaNumber]);
                    $count++;
                }
            }
        });

        fclose($handle);

        return redirect()->back()->with('success', "$count data VA Siswa berhasil diperbarui.");
    }

    public function show(Student $student)
    {
        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan']);
        $teacherProfile = $user ? ($user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first()) : null;
        $userUnitId = $teacherProfile?->unit_id ?: session('active_unit_id');

        if (!$isGlobalAdmin && $userUnitId && $student->unit_id != $userUnitId) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk data siswa di unit lain.');
        }

        // Gather all user roles (global + student's unit) reliably without Spatie team cache mismatch
        $roleNames = $user ? \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->where(function ($q) use ($student) {
                $q->whereNull('model_has_roles.team_id')
                    ->orWhere('model_has_roles.team_id', $student->unit_id);
            })
            ->pluck('roles.name')
            ->unique()
            ->values()
            ->all() : [];

        $hasAnyRole = fn (array $allowed): bool => !empty(array_intersect($roleNames, $allowed));

        $canViewFinance = $hasAnyRole([
            'super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan',
            'admin_unit', 'kepala_sekolah', 'finance', 'staff_admin_keuangan',
        ]);

        $student->load(['user', 'classroom.homeroomTeacher', 'academicYear', 'unit']);

        $isHomeroomOfStudent = $teacherProfile && $student->classroom
            && (int) $student->classroom->homeroom_teacher_id === (int) $teacherProfile->id;

        $canViewCounselingNotes = $isHomeroomOfStudent || $hasAnyRole([
            'super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan',
            'admin_unit', 'kepala_sekolah', 'bk', 'counseling', 'koordinator_kesiswaan',
        ]);

        $canManageQuran = $hasAnyRole([
            'super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan',
            'admin_unit', 'kepala_sekolah', 'koordinator_kurikulum', 'koordinator_alquran',
        ]);

        $canAccessQuran = $canManageQuran || $hasAnyRole(['guru_alquran', 'teacher', 'wali_kelas']);

        // 1. Finance (strictly gated so unauthorized roles never receive financial payload)
        $financeData = null;
        if ($canViewFinance) {
            $unpaidBills = $student->bills()
                ->with('financeType')
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date')
                ->get();

            $recentTransactions = $student->transactions()
                ->latest('transaction_date')
                ->take(10)
                ->get();

            $totalArrears = $unpaidBills->sum(fn ($bill) => max(0, $bill->final_amount - $bill->paid_amount));
            $totalPaid = $student->transactions()->sum('amount');

            $financeData = [
                'va_number' => $student->va_number,
                'total_arrears' => $totalArrears,
                'total_paid' => $totalPaid,
                'bills' => $unpaidBills,
                'transactions' => $recentTransactions,
            ];
        } else {
            // Mask VA number when user cannot view finance
            $student->va_number = null;
        }

        // 2. Program Al-Qur'an (Ummi) 360 summary
        $quranProfile = app(\App\Modules\Quran\Services\QuranStudentProfileService::class)->forStudent($student, false);
        $canPrintQuranReport = (bool) (
            ($quranProfile['enabled'] ?? false)
            && $canAccessQuran
            && ($canManageQuran || !($quranProfile['is_report_locked'] ?? false))
        );

        // 3. Attendance & Recent Gate Check-ins
        $attendanceStats = \App\Models\StudentAttendance::where('student_id', $student->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $attendanceSummary = [
            'present' => (int) ($attendanceStats['H'] ?? 0),
            'sick' => (int) ($attendanceStats['S'] ?? 0),
            'permission' => (int) ($attendanceStats['I'] ?? 0),
            'alpha' => (int) ($attendanceStats['A'] ?? 0),
        ];

        $recentCheckins = \App\Modules\Academic\Models\StudentCheckin::where('student_id', $student->id)
            ->latest('checkin_date')
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'date' => \Carbon\Carbon::parse($c->checkin_date)->format('d M Y'),
                'time' => $c->checkin_time ? substr($c->checkin_time, 0, 5) : '-',
                'status' => $c->status,
            ])
            ->values();

        // 4. Class Promotion History
        $classPromotions = \App\Modules\Academic\Models\ClassPromotion::with([
            'fromClassroom', 'toClassroom', 'fromAcademicYear', 'toAcademicYear',
        ])
            ->where('student_id', $student->id)
            ->where(function ($q) {
                $q->whereNull('is_rolled_back')->orWhere('is_rolled_back', false);
            })
            ->orderByDesc('promoted_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'status' => $p->status,
                'status_label' => $p->status_label,
                'from_classroom' => $p->fromClassroom?->name ?? '-',
                'to_classroom' => $p->toClassroom?->name,
                'from_year' => $p->fromAcademicYear?->name ?? '-',
                'to_year' => $p->toAcademicYear?->name ?? '-',
                'promoted_at' => $p->promoted_at?->format('d M Y') ?? '-',
                'notes' => $p->notes,
            ])
            ->values();

        // 5. Extracurriculars
        $extracurriculars = \App\Modules\Extracurricular\Models\ExtracurricularMember::where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['extracurricular.room', 'extracurricular.instructors.user', 'grade'])
            ->get()
            ->map(fn ($m) => [
                'id' => $m->extracurricular?->id,
                'name' => $m->extracurricular?->name ?? '-',
                'category' => $m->extracurricular?->category_label ?? '-',
                'schedule' => $m->extracurricular?->formatted_schedule ?? '-',
                'location' => $m->extracurricular?->room?->name ?: ($m->extracurricular?->location_name ?: '-'),
                'coach' => $m->extracurricular?->instructors->pluck('user.name')->filter()->join(', ') ?: '-',
                'grade' => $m->grade?->predicate,
                'grade_description' => $m->grade?->description,
            ])
            ->values();

        // 6. Counseling: Achievements, Violations & Role-Gated Private Sessions
        $violations = \App\Modules\Counseling\Models\Violation::where('student_id', $student->id)
            ->with('category')
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'date' => $v->date ? \Carbon\Carbon::parse($v->date)->format('d M Y') : '-',
                'category' => $v->category?->name ?? 'Pelanggaran',
                'points' => (int) $v->points,
                'description' => $v->description,
            ])
            ->values();

        $totalViolationPoints = (int) \App\Modules\Counseling\Models\Violation::where('student_id', $student->id)->sum('points');
        $counselingLevel = $totalViolationPoints > 50 ? 'BAHAYA' : ($totalViolationPoints > 20 ? 'WASPADA' : 'AMAN');

        $achievements = \App\Modules\Counseling\Models\Achievement::where('student_id', $student->id)
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'date' => $a->date ? \Carbon\Carbon::parse($a->date)->format('d M Y') : '-',
                'title' => $a->title,
                'level' => $a->level,
                'description' => $a->description,
            ])
            ->values();

        $counselingSessions = [];
        if ($canViewCounselingNotes) {
            $counselingSessions = \App\Modules\Counseling\Models\CounselingSession::withoutGlobalScope('unit')
                ->where('student_id', $student->id)
                ->with('counselor')
                ->latest('date')
                ->take(10)
                ->get()
                ->map(fn ($cs) => [
                    'id' => $cs->id,
                    'date' => $cs->date?->format('d M Y') ?? '-',
                    'method' => $cs->method,
                    'status' => $cs->status,
                    'counselor' => $cs->counselor?->name ?? '-',
                    'notes' => $cs->notes,
                    'follow_up_action' => $cs->follow_up_action,
                ])
                ->values();
        }

        return Inertia::render('Academic/Students/Show', [
            'student' => $student,
            'permissions' => [
                'can_view_finance' => $canViewFinance,
                'can_view_counseling_notes' => $canViewCounselingNotes,
                'can_print_quran_report' => $canPrintQuranReport,
                'is_global_admin' => $isGlobalAdmin,
            ],
            'quranProfile' => $quranProfile,
            'attendanceSummary' => $attendanceSummary,
            'recentCheckins' => $recentCheckins,
            'classPromotions' => $classPromotions,
            'extracurriculars' => $extracurriculars,
            'counselingSummary' => [
                'total_points' => $totalViolationPoints,
                'status' => $counselingLevel,
                'violations' => $violations,
                'achievements' => $achievements,
                'sessions' => $counselingSessions,
            ],
            'finance' => $financeData,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStudentManagement();

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', 
            'nis' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'photo' => 'nullable|image|max:10240',
            'pob' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
        ]);

        try {
            \DB::transaction(function () use ($validated, $unitId, $request) {
                // 1. Find or Create User
                $user = User::where('email', $validated['email'])->first();

                if (!$user) {
                    // Generate password from NIS or default
                    $password = $validated['nis'] ? $validated['nis'] : 'siswa123';
                    $user = User::create([
                        'name' => $validated['full_name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($password), 
                        'email_verified_at' => now(),
                    ]);
                }
                
                if (!$user->hasRole('siswa')) {
                    setPermissionsTeamId($unitId);
                    $user->assignRole('siswa');
                }

                // 2. Handle Upload
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('students', 'public');
                }

                // Get active academic year
                $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();

                // 3. Create Student Profile
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'classroom_id' => $validated['classroom_id'] ?? null,
                    'academic_year_id' => $activeYear?->id,
                    'full_name' => $validated['full_name'],
                    'nis' => $validated['nis'],
                    'nisn' => $validated['nisn'],
                    'va_number' => $request->va_number, // Optional
                    'gender' => $validated['gender'],
                    'pob' => $validated['pob'] ?? null,
                    'dob' => $validated['dob'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'parent_name' => $validated['parent_name'] ?? null,
                    'parent_phone' => $validated['parent_phone'] ?? null,
                    'guardian_name' => $validated['guardian_name'] ?? null,
                    'guardian_phone' => $validated['guardian_phone'] ?? null,
                    'photo' => $photoPath,
                ]);
            });

            // Automated WhatsApp Welcome Notification to parents
            try {
                $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
                $unitName = $unit->name ?? 'Namira School';

                if (!empty($validated['parent_phone'])) {
                    $password = $validated['nis'] ? $validated['nis'] : 'siswa123';
                    $message = "📝 *Pemberitahuan Pendaftaran Akun Siswa*\n\n"
                        . "Yth. Orang Tua/Wali dari *{$validated['full_name']}*.\n\n"
                        . "Selamat, ananda telah terdaftar sebagai siswa di *{$unitName}*.\n"
                        . "Kami juga telah membuatkan akun portal akademik untuk memantau kehadiran, nilai, dan tagihan ananda.\n\n"
                        . "• *Website Portal*: " . url('/login') . "\n"
                        . "• *Username (Email)*: {$validated['email']}\n"
                        . "• *Password*: {$password}\n\n"
                        . "Silakan simpan informasi login ini dengan baik dan ubah password setelah login pertama kali.\n\n"
                        . "Terima kasih.\n-- *{$unitName}*";

                    \App\Helpers\WhatsAppHelper::send($validated['parent_phone'], $message);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send automated WA student registration welcome: " . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan siswa. Silakan coba lagi.');
        }
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudentManagement();

        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);
        if (!$isGlobalAdmin && $student->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengedit siswa di unit lain.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'va_number' => 'nullable|string|max:30',
            'gender' => 'required|in:L,P',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'photo' => 'nullable|image|max:10240',
            'pob' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
        ]);

        $student->full_name = $validated['full_name'];
        $student->nis = $validated['nis'];
        $student->nisn = $validated['nisn'];
        $student->va_number = $request->va_number;
        $student->gender = $validated['gender'];
        $student->classroom_id = $validated['classroom_id'] ?? null;
        $student->pob = $validated['pob'] ?? null;
        $student->dob = $validated['dob'] ?? null;
        $student->address = $validated['address'] ?? null;
        $student->parent_name = $validated['parent_name'] ?? null;
        $student->parent_phone = $validated['parent_phone'] ?? null;
        $student->guardian_name = $validated['guardian_name'] ?? null;
        $student->guardian_phone = $validated['guardian_phone'] ?? null;

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                \Storage::disk('public')->delete($student->photo);
            }
            $student->photo = $request->file('photo')->store('students', 'public');
        }

        if (!$student->academic_year_id) {
            $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
            $student->academic_year_id = $activeYear?->id;
        }

        $student->save();
        $student->user->update(['name' => $validated['full_name']]);

        return redirect()->back()->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Student $student)
    {
        $this->authorizeStudentManagement();

        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);
        if (!$isGlobalAdmin && $student->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus siswa di unit lain.');
        }

        if ($student->photo) {
            \Storage::disk('public')->delete($student->photo);
        }

        $student->user->delete();
        $student->delete();

        return redirect()->back()->with('success', 'Siswa dihapus.');
    }

    /**
     * Bulk update academic year for multiple students
     */
    public function bulkUpdateYear(Request $request)
    {
        $this->authorizeStudentManagement();

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $unitId = session('active_unit_id');
        
        $count = Student::whereIn('id', $request->student_ids)
            ->where('unit_id', $unitId)
            ->update(['academic_year_id' => $request->academic_year_id]);

        return redirect()->back()->with('success', "$count siswa berhasil diupdate tahun ajarannya.");
    }

    /**
     * Import from Excel (XLSX)
     */
    public function importExcel(Request $request)
    {
        $this->authorizeStudentManagement();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'classroom_id' => 'nullable|exists:classrooms,id',
        ]);

        $unitId = session('active_unit_id');
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
        
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $header = array_shift($rows);
        if (!$header) {
            return redirect()->back()->with('error', 'File Excel kosong.');
        }

        $headerText = strtolower(implode(' ', array_filter($header)));
        if (!str_contains($headerText, 'nama') && !str_contains($headerText, 'name') && !str_contains($headerText, 'nis')) {
            return redirect()->back()->with('error', 'Format header Excel tidak valid. Pastikan baris pertama adalah judul kolom seperti Nama, Email, NIS.');
        }

        $count = 0;
        $errors = [];

        \DB::transaction(function () use ($rows, $unitId, $activeYear, $request, &$count, &$errors) {
            foreach ($rows as $index => $row) {
                // Expected format: Name, Email, NIS, NISN, Gender (L/P)
                if (empty($row[0]) || empty($row[1])) continue;

                $name = trim($row[0]);
                $email = trim($row[1]);
                $nis = trim($row[2] ?? '');
                $nisn = trim($row[3] ?? '');
                $gender = strtoupper(trim($row[4] ?? 'L'));

                // Skip if email exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Baris " . ($index + 2) . ": Email $email sudah terdaftar.";
                    continue;
                }

                // Create User
                $password = $nis ?: 'siswa123';
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);

                setPermissionsTeamId($unitId);
                $user->assignRole('siswa');

                // Create Student
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'academic_year_id' => $activeYear->id,
                    'classroom_id' => $request->classroom_id,
                    'full_name' => $name,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'gender' => in_array($gender, ['L', 'P']) ? $gender : 'L',
                ]);

                $count++;
            }
        });

        $message = "$count siswa berhasil diimport.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " baris dilewati.";
        }

        return redirect()->back()->with('success', $message);
    }
}

