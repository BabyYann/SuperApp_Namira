<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranEnrollment;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranLevelHistory;
use App\Modules\Quran\Models\QuranSession;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuranGroupController extends Controller
{
    public function __construct(
        private QuranAccessService $access,
        private QuranSetupService $setup
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        $this->setup->ensureUnitDefaults($unit);

        $activeYear = $this->access->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        $canManage = $this->access->canManage($user);
        $isGlobalAdmin = $this->access->isGlobalAdmin($user);

        $levels = QuranLevel::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sessions = QuranSession::where('unit_id', $unit->id)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();

        $groupsQuery = QuranGroup::with([
                'level:id,name,code,sort_order',
                'session:id,name,start_time,end_time,days,target_note',
                'teacher:id,name,email,phone',
                'assistantTeacher:id,name,email,phone',
                'activeEnrollments' => function ($q) {
                    $q->with(['student' => function ($sq) {
                        $sq->select('id', 'nis', 'nisn', 'full_name', 'gender', 'classroom_id', 'status')
                           ->with('classroom:id,name');
                    }])->orderBy('id');
                },
            ])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true);

        // Regular Guru Al-Qur'an only sees their own groups by default unless manager
        if (!$canManage) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('assistant_teacher_id', $user->id);
            });
        }

        if ($request->filled('session_id') && $request->session_id !== 'all') {
            if ($request->session_id === 'none') {
                $groupsQuery->whereNull('session_id');
            } else {
                $groupsQuery->where('session_id', $request->integer('session_id'));
            }
        }

        if ($request->filled('level_id') && $request->level_id !== 'all') {
            $groupsQuery->where('level_id', $request->integer('level_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $groupsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('room_name', 'like', "%{$search}%")
                    ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('level', fn ($lq) => $lq->where('name', 'like', "%{$search}%"));
            });
        }

        $groups = $groupsQuery
            ->get()
            ->sortBy([
                fn ($g) => $g->session?->sort_order ?? 999,
                fn ($g) => $g->level?->sort_order ?? 999,
                fn ($g) => $g->name,
            ])
            ->values();

        // Count assessments filled this semester per group
        $assessedStudentIds = QuranAssessment::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeYear->semester)
            ->pluck('student_id')
            ->flip();

        $groups->transform(function ($group) use ($assessedStudentIds, $user) {
            $members = $group->activeEnrollments
                ->filter(fn ($e) => $e->student !== null)
                ->sortBy(fn ($e) => strtolower($e->student->full_name))
                ->values();

            $assessedCount = $members->filter(fn ($e) => $assessedStudentIds->has($e->student_id))->count();

            return [
                'id' => $group->id,
                'name' => $group->name,
                'level_id' => $group->level_id,
                'level' => $group->level,
                'session_id' => $group->session_id,
                'session' => $group->session,
                'teacher_id' => $group->teacher_id,
                'teacher' => $group->teacher,
                'assistant_teacher_id' => $group->assistant_teacher_id,
                'assistant_teacher' => $group->assistantTeacher,
                'room_name' => $group->room_name,
                'max_quota' => $group->max_quota,
                'is_my_group' => $group->teacher_id === $user->id || $group->assistant_teacher_id === $user->id,
                'my_role' => $group->teacher_id === $user->id ? 'main' : ($group->assistant_teacher_id === $user->id ? 'assistant' : null),
                'members_count' => $members->count(),
                'assessed_count' => $assessedCount,
                'members' => $members->map(fn ($e) => [
                    'enrollment_id' => $e->id,
                    'student_id' => $e->student_id,
                    'full_name' => $e->student->full_name,
                    'nis' => $e->student->nis,
                    'nisn' => $e->student->nisn,
                    'gender' => $e->student->gender,
                    'classroom_name' => $e->student->classroom?->name ?? '-',
                    'started_at' => $e->started_at?->format('Y-m-d'),
                    'is_assessed' => $assessedStudentIds->has($e->student_id),
                ])->all(),
            ];
        });

        // Overall unit statistics
        $totalActiveStudents = Student::where('unit_id', $unit->id)
            ->where('status', 'active')
            ->count();

        $activeEnrollments = QuranEnrollment::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->get(['student_id', 'level_id']);

        $plottedStudentIds = $activeEnrollments->pluck('student_id')->unique()->values();
        $levelDistribution = $levels->map(function ($lvl) use ($activeEnrollments) {
            return [
                'id' => $lvl->id,
                'name' => $lvl->name,
                'code' => $lvl->code,
                'count' => $activeEnrollments->where('level_id', $lvl->id)->count(),
            ];
        });

        $promotionsThisSemester = QuranLevelHistory::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeYear->semester)
            ->where('change_type', QuranLevelHistory::TYPE_PROMOTED)
            ->count();

        // Data for quick plotting modal (only loaded for managers)
        $classrooms = Classroom::where('unit_id', $unit->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        $allUnitStudents = $canManage
            ? Student::where('unit_id', $unit->id)
                ->where('status', 'active')
                ->with('classroom:id,name')
                ->orderBy('full_name')
                ->get(['id', 'nis', 'full_name', 'gender', 'classroom_id'])
                ->map(function ($st) use ($activeEnrollments) {
                    $enr = $activeEnrollments->firstWhere('student_id', $st->id);

                    return [
                        'id' => $st->id,
                        'nis' => $st->nis,
                        'full_name' => $st->full_name,
                        'gender' => $st->gender,
                        'classroom_id' => $st->classroom_id,
                        'classroom_name' => $st->classroom?->name ?? 'Tanpa Kelas',
                        'is_plotted' => $enr !== null,
                    ];
                })
            : collect();

        return Inertia::render('Quran/Index', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $isGlobalAdmin ? $this->access->getEligibleUnits()->map->only(['id', 'name', 'code']) : [],
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'semester', 'is_active']),
            'activeYear' => $activeYear->only(['id', 'name', 'semester', 'is_active']),
            'canManage' => $canManage,
            'isGlobalAdmin' => $isGlobalAdmin,
            'levels' => $levels,
            'sessions' => $sessions,
            'groups' => $groups,
            'teachers' => $this->access->getEligibleTeachers($unit->id),
            'classrooms' => $classrooms,
            'allUnitStudents' => $allUnitStudents,
            'levelDistribution' => $levelDistribution,
            'stats' => [
                'total_students' => $totalActiveStudents,
                'plotted_students' => $plottedStudentIds->count(),
                'unplotted_students' => max(0, $totalActiveStudents - $plottedStudentIds->count()),
                'total_groups' => $groups->count(),
                'assessed_students' => $assessedStudentIds->count(),
                'promotions_semester' => $promotionsThisSemester,
            ],
            'filters' => [
                'session_id' => $request->input('session_id', 'all'),
                'level_id' => $request->input('level_id', 'all'),
                'search' => $request->input('search', ''),
                'unit_id' => $unit->id,
                'academic_year_id' => $activeYear->id,
            ],
        ]);
    }

    public function storeSession(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat mengelola sesi.');
        }

        $unit = $this->access->resolveUnit($request, $user);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'nullable|string|max:10',
            'end_time' => 'nullable|string|max:10',
            'days' => 'nullable|string|max:100',
            'target_note' => 'nullable|string|max:150',
            'sort_order' => 'nullable|integer|min:1|max:99',
        ]);

        $nextOrder = (QuranSession::where('unit_id', $unit->id)->max('sort_order') ?? 0) + 1;

        QuranSession::create([
            'unit_id' => $unit->id,
            'name' => $validated['name'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'days' => $validated['days'] ?? 'Senin - Jumat',
            'target_note' => $validated['target_note'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $nextOrder,
            'is_active' => true,
        ]);

        return back()->with('success', 'Sesi jadwal mengaji berhasil ditambahkan.');
    }

    public function updateSession(Request $request, QuranSession $session)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($session->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'nullable|string|max:10',
            'end_time' => 'nullable|string|max:10',
            'days' => 'nullable|string|max:100',
            'target_note' => 'nullable|string|max:150',
            'sort_order' => 'nullable|integer|min:1|max:99',
        ]);

        $session->update($validated);

        return back()->with('success', 'Sesi jadwal berhasil diperbarui.');
    }

    public function destroySession(Request $request, QuranSession $session)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($session->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $session->delete();

        return back()->with('success', 'Sesi jadwal berhasil dihapus.');
    }

    public function seedDefaultSessions(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);

        $defaults = [
            ['name' => 'Sesi 1 (Pagi)', 'start_time' => '07:30', 'end_time' => '08:30', 'days' => 'Senin - Jumat', 'target_note' => 'Kelas 1 & 2', 'sort_order' => 1],
            ['name' => 'Sesi 2 (Menengah)', 'start_time' => '09:30', 'end_time' => '10:30', 'days' => 'Senin - Jumat', 'target_note' => 'Kelas 3 & 4', 'sort_order' => 2],
            ['name' => 'Sesi 3 (Siang)', 'start_time' => '12:30', 'end_time' => '13:30', 'days' => 'Senin - Jumat', 'target_note' => 'Kelas 5 & 6', 'sort_order' => 3],
        ];

        foreach ($defaults as $def) {
            QuranSession::firstOrCreate(
                ['unit_id' => $unit->id, 'name' => $def['name']],
                array_merge($def, ['unit_id' => $unit->id, 'is_active' => true])
            );
        }

        return back()->with('success', 'Template 3 sesi (Pagi, Menengah, Siang) berhasil dibuat.');
    }

    public function storeGroup(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat membuat kelompok.');
        }

        $unit = $this->access->resolveUnit($request, $user);
        $activeYear = $this->access->resolveAcademicYear($request->integer('academic_year_id') ?: null);

        $validated = $request->validate([
            'level_id' => 'required|exists:quran_levels,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'name' => 'required|string|max:120',
            'teacher_id' => 'required|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:teacher_id',
            'room_name' => 'nullable|string|max:100',
            'max_quota' => 'nullable|integer|min:1|max:200',
        ]);

        // Ensure level belongs to unit
        QuranLevel::where('unit_id', $unit->id)->findOrFail($validated['level_id']);

        if (!empty($validated['session_id'])) {
            QuranSession::where('unit_id', $unit->id)->findOrFail($validated['session_id']);
        }

        QuranGroup::create([
            'unit_id' => $unit->id,
            'academic_year_id' => $activeYear->id,
            'level_id' => $validated['level_id'],
            'session_id' => $validated['session_id'] ?: null,
            'name' => $validated['name'],
            'teacher_id' => $validated['teacher_id'],
            'assistant_teacher_id' => $validated['assistant_teacher_id'] ?: null,
            'room_name' => $validated['room_name'] ?? null,
            'max_quota' => $validated['max_quota'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Kelompok mengaji berhasil ditambahkan.');
    }

    public function updateGroup(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($group->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'level_id' => 'required|exists:quran_levels,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'name' => 'required|string|max:120',
            'teacher_id' => 'required|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:teacher_id',
            'room_name' => 'nullable|string|max:100',
            'max_quota' => 'nullable|integer|min:1|max:200',
        ]);

        QuranLevel::where('unit_id', $group->unit_id)->findOrFail($validated['level_id']);

        DB::transaction(function () use ($group, $validated) {
            $oldLevelId = $group->level_id;
            $group->update([
                'level_id' => $validated['level_id'],
                'session_id' => $validated['session_id'] ?: null,
                'name' => $validated['name'],
                'teacher_id' => $validated['teacher_id'],
                'assistant_teacher_id' => $validated['assistant_teacher_id'] ?: null,
                'room_name' => $validated['room_name'] ?? null,
                'max_quota' => $validated['max_quota'] ?? null,
            ]);

            if ((int) $oldLevelId !== (int) $validated['level_id']) {
                $group->activeEnrollments()->update(['level_id' => $validated['level_id']]);
            }
        });

        return back()->with('success', 'Kelompok mengaji berhasil diperbarui.');
    }

    public function destroyGroup(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($group->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $activeCount = $group->activeEnrollments()->count();
        if ($activeCount > 0) {
            return back()->with('error', "Kelompok masih memiliki {$activeCount} siswa aktif. Pindahkan atau keluarkan siswa terlebih dahulu.");
        }

        $group->delete();

        return back()->with('success', 'Kelompok mengaji berhasil dihapus.');
    }

    /**
     * Bulk enroll students into a Quran group (initial plotting or direct move).
     */
    public function enrollStudents(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat melakukan plotting siswa.');
        }

        $unit = $this->access->resolveUnit($request, $user);
        if ($group->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'started_at' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $activeYear = $group->academicYear ?: $this->access->resolveAcademicYear();
        $startedAt = $validated['started_at'] ?? now()->toDateString();
        $addedCount = 0;

        DB::transaction(function () use ($group, $validated, $activeYear, $startedAt, $user, &$addedCount) {
            $students = Student::where('unit_id', $group->unit_id)
                ->whereIn('id', $validated['student_ids'])
                ->get();

            foreach ($students as $student) {
                $existingActive = QuranEnrollment::where('unit_id', $group->unit_id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('student_id', $student->id)
                    ->where('status', QuranEnrollment::STATUS_ACTIVE)
                    ->first();

                if ($existingActive && $existingActive->group_id === $group->id) {
                    continue;
                }

                $fromLevelId = null;
                $fromGroupId = null;
                $fromTeacherId = null;
                $changeType = QuranLevelHistory::TYPE_INITIAL;

                if ($existingActive) {
                    $existingGroup = $existingActive->group;
                    $fromLevelId = $existingActive->level_id;
                    $fromGroupId = $existingActive->group_id;
                    $fromTeacherId = $existingGroup?->teacher_id;
                    $changeType = $existingActive->level_id === $group->level_id
                        ? QuranLevelHistory::TYPE_TRANSFERRED
                        : QuranLevelHistory::TYPE_PROMOTED;

                    $existingActive->update([
                        'status' => $changeType === QuranLevelHistory::TYPE_PROMOTED
                            ? QuranEnrollment::STATUS_PROMOTED
                            : QuranEnrollment::STATUS_TRANSFERRED,
                        'ended_at' => $startedAt,
                    ]);
                }

                QuranEnrollment::create([
                    'unit_id' => $group->unit_id,
                    'academic_year_id' => $activeYear->id,
                    'student_id' => $student->id,
                    'group_id' => $group->id,
                    'level_id' => $group->level_id,
                    'status' => QuranEnrollment::STATUS_ACTIVE,
                    'started_at' => $startedAt,
                    'notes' => $validated['notes'] ?? null,
                ]);

                QuranLevelHistory::create([
                    'unit_id' => $group->unit_id,
                    'academic_year_id' => $activeYear->id,
                    'semester' => $activeYear->semester,
                    'student_id' => $student->id,
                    'from_level_id' => $fromLevelId,
                    'to_level_id' => $group->level_id,
                    'from_group_id' => $fromGroupId,
                    'to_group_id' => $group->id,
                    'from_teacher_id' => $fromTeacherId,
                    'to_teacher_id' => $group->teacher_id,
                    'change_type' => $changeType,
                    'changed_at' => $startedAt,
                    'notes' => $validated['notes'] ?? ($changeType === QuranLevelHistory::TYPE_INITIAL ? 'Plotting awal kelompok' : 'Pemindahan kelompok'),
                    'recorded_by' => $user->id,
                ]);

                $addedCount++;
            }
        });

        return back()->with('success', "{$addedCount} siswa berhasil dimasukkan ke kelompok {$group->name}.");
    }

    public function removeEnrollment(Request $request, QuranEnrollment $enrollment)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($enrollment->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $enrollment->delete();

        return back()->with('success', 'Siswa berhasil dikeluarkan dari kelompok.');
    }
}
