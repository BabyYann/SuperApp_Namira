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
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $canManage = $this->access->canManage($user, $unit->id);
        $isGlobalAdmin = $this->access->isGlobalAdmin($user);
        $unitSetting = QuranSetting::where('unit_id', $unit->id)->first();
        $allowSelfPlotting = $unitSetting ? (bool) ($unitSetting->allow_teacher_self_plotting ?? true) : true;
        $allowTeacherPromote = $unitSetting ? (bool) ($unitSetting->allow_teacher_promote ?? true) : true;

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
                'session:id,name,start_time,end_time,days,target_note,sort_order',
                'teacher:id,name,email,phone',
                'assistantTeacher:id,name,email,phone',
                'activeEnrollments' => function ($q) {
                    $q->with([
                        'level:id,name,code,sort_order',
                        'student' => function ($sq) {
                            $sq->select('id', 'nis', 'nisn', 'full_name', 'gender', 'classroom_id')
                                ->with('classroom:id,name');
                        },
                    ])->orderBy('id');
                },
            ])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true);

        // Regular Guru Al-Qur'an & cross-unit Pavlov teachers only see their own groups unless unit manager
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
            if ($request->level_id === 'inclusive') {
                $groupsQuery->where('is_inclusive', true);
            } else {
                $filterLevelId = $request->integer('level_id');
                $groupsQuery->where(function ($q) use ($filterLevelId) {
                    $q->where('level_id', $filterLevelId)
                        ->orWhere(function ($iq) use ($filterLevelId) {
                            $iq->where('is_inclusive', true)
                                ->whereHas('activeEnrollments', fn ($eq) => $eq->where('level_id', $filterLevelId));
                        });
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLower = strtolower($search);
            $groupsQuery->where(function ($q) use ($search, $searchLower) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('room_name', 'like', "%{$search}%")
                    ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('level', fn ($lq) => $lq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('activeEnrollments.student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));

                if (str_contains('inklusi', $searchLower) || str_contains('abk', $searchLower) || str_contains('khusus', $searchLower)) {
                    $q->orWhere('is_inclusive', true);
                }
            });
        }

        $groups = $groupsQuery
            ->get()
            ->sortBy([
                fn ($g) => $g->session?->sort_order ?? 999,
                fn ($g) => $g->is_inclusive ? 998 : ($g->level?->sort_order ?? 999),
                fn ($g) => $g->name,
            ])
            ->values();

        // Detect cross-unit Pavlov origins for displayed teachers in a single batched lookup
        $teacherUserIds = $groups
            ->flatMap(fn ($g) => [$g->teacher_id, $g->assistant_teacher_id])
            ->filter()
            ->unique()
            ->values()
            ->all();
        $crossUnitOrigins = $this->access->getCrossUnitTeacherOrigins($teacherUserIds, $unit->id);

        // Count assessments filled this semester per group
        $assessedStudentIds = QuranAssessment::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeYear->semester)
            ->pluck('student_id')
            ->flip();

        $groups->transform(function ($group) use ($assessedStudentIds, $user, $canManage, $allowSelfPlotting, $allowTeacherPromote, $crossUnitOrigins) {
            $members = $group->activeEnrollments
                ->filter(fn ($e) => $e->student !== null)
                ->sortBy([
                    fn ($e) => $group->is_inclusive ? ($e->level?->sort_order ?? 999) : 0,
                    fn ($e) => strtolower($e->student->full_name),
                ])
                ->values();

            $assessedCount = $members->filter(fn ($e) => $assessedStudentIds->has($e->student_id))->count();
            $isMyGroup = $group->teacher_id === $user->id || $group->assistant_teacher_id === $user->id;

            $levelCounts = [];
            if ($group->is_inclusive) {
                $levelCounts = $members
                    ->filter(fn ($e) => $e->level !== null)
                    ->groupBy('level_id')
                    ->map(fn ($items) => [
                        'id' => $items->first()->level->id,
                        'name' => $items->first()->level->name,
                        'code' => $items->first()->level->code,
                        'sort_order' => $items->first()->level->sort_order,
                        'count' => $items->count(),
                    ])
                    ->sortBy('sort_order')
                    ->values()
                    ->all();
            }

            $teacherOrigin = $group->teacher_id ? ($crossUnitOrigins[$group->teacher_id] ?? null) : null;
            $assistantOrigin = $group->assistant_teacher_id ? ($crossUnitOrigins[$group->assistant_teacher_id] ?? null) : null;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'is_inclusive' => (bool) $group->is_inclusive,
                'level_id' => $group->level_id,
                'level' => $group->level ? [
                    'id' => $group->level->id,
                    'name' => $group->level->name,
                    'code' => $group->level->code,
                    'sort_order' => $group->level->sort_order,
                ] : null,
                'level_counts' => $levelCounts,
                'session_id' => $group->session_id,
                'session' => $group->session ? [
                    'id' => $group->session->id,
                    'name' => $group->session->name,
                    'start_time' => $group->session->start_time,
                    'end_time' => $group->session->end_time,
                    'days' => $group->session->days,
                    'target_note' => $group->session->target_note,
                ] : null,
                'teacher_id' => $group->teacher_id,
                // Serialize teacher & assistant as plain arrays to prevent User::$appends (profile_photo_url) N+1
                'teacher' => $group->teacher ? [
                    'id' => $group->teacher->id,
                    'name' => $group->teacher->name,
                    'email' => $group->teacher->email,
                    'phone' => $group->teacher->phone,
                    'is_cross_unit' => $teacherOrigin !== null,
                    'origin_unit_name' => $teacherOrigin['unit_name'] ?? null,
                    'origin_unit_code' => $teacherOrigin['unit_code'] ?? null,
                ] : null,
                'assistant_teacher_id' => $group->assistant_teacher_id,
                'assistant_teacher' => $group->assistantTeacher ? [
                    'id' => $group->assistantTeacher->id,
                    'name' => $group->assistantTeacher->name,
                    'email' => $group->assistantTeacher->email,
                    'phone' => $group->assistantTeacher->phone,
                    'is_cross_unit' => $assistantOrigin !== null,
                    'origin_unit_name' => $assistantOrigin['unit_name'] ?? null,
                    'origin_unit_code' => $assistantOrigin['unit_code'] ?? null,
                ] : null,
                'room_name' => $group->room_name,
                'max_quota' => $group->max_quota,
                'is_my_group' => $isMyGroup,
                'can_plot' => $canManage || ($allowSelfPlotting && $isMyGroup),
                'can_edit_member_level' => (bool) $group->is_inclusive && ($canManage || ($isMyGroup && ($allowSelfPlotting || $allowTeacherPromote))),
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
                    'level_id' => $e->level_id,
                    'level_name' => $e->level?->name ?? ($group->level?->name ?? '-'),
                    'started_at' => $e->started_at?->format('Y-m-d'),
                    'is_assessed' => $assessedStudentIds->has($e->student_id),
                ])->all(),
            ];
        });

        // Overall unit statistics (lightweight SQL aggregates instead of hydrating all enrollments on every tab click)
        $totalActiveStudents = Student::where('unit_id', $unit->id)->count();

        $levelCounts = QuranEnrollment::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->select('level_id', DB::raw('COUNT(*) as total'))
            ->groupBy('level_id')
            ->pluck('total', 'level_id');

        $plottedStudentCount = (int) QuranEnrollment::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->distinct('student_id')
            ->count('student_id');

        $inclusiveGroupsCount = QuranGroup::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->where('is_inclusive', true)
            ->when(!$canManage, fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->where('teacher_id', $user->id)
                    ->orWhere('assistant_teacher_id', $user->id);
            }))
            ->count();

        $levelDistribution = $levels->map(function ($lvl) use ($levelCounts) {
            return [
                'id' => $lvl->id,
                'name' => $lvl->name,
                'code' => $lvl->code,
                'count' => (int) ($levelCounts[$lvl->id] ?? 0),
            ];
        });

        $promotionsThisSemester = QuranLevelHistory::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeYear->semester)
            ->where('change_type', QuranLevelHistory::TYPE_PROMOTED)
            ->count();

        // Data for quick plotting drawer — loaded on-demand via Inertia::optional() only when user opens Plotting
        $canPlotAny = $canManage || ($allowSelfPlotting && $groups->contains(fn ($g) => !empty($g['is_my_group'])));

        $classrooms = $canPlotAny
            ? Classroom::where('unit_id', $unit->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get(['id', 'name', 'level'])
            : collect();

        $allUnitStudentsResolver = function () use ($canPlotAny, $unit, $activeYear, $canManage, $user) {
            if (!$canPlotAny) {
                return [];
            }

            $activeEnrollmentsByStudent = QuranEnrollment::with([
                    'group:id,name,teacher_id,assistant_teacher_id',
                    'level:id,name',
                ])
                ->where('unit_id', $unit->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('status', QuranEnrollment::STATUS_ACTIVE)
                ->get(['student_id', 'level_id', 'group_id'])
                ->keyBy('student_id');

            return Student::where('unit_id', $unit->id)
                ->with('classroom:id,name')
                ->orderBy('full_name')
                ->get(['id', 'nis', 'full_name', 'gender', 'classroom_id'])
                ->map(function ($st) use ($activeEnrollmentsByStudent, $canManage, $user) {
                    $enr = $activeEnrollmentsByStudent->get($st->id);
                    $currentGroup = $enr?->group;
                    $isInMyGroup = $currentGroup !== null
                        && ($currentGroup->teacher_id === $user->id || $currentGroup->assistant_teacher_id === $user->id);

                    return [
                        'id' => $st->id,
                        'nis' => $st->nis,
                        'full_name' => $st->full_name,
                        'gender' => $st->gender,
                        'classroom_id' => $st->classroom_id,
                        'classroom_name' => $st->classroom?->name ?? 'Tanpa Kelas',
                        'is_plotted' => $enr !== null,
                        'current_group_id' => $enr?->group_id,
                        'current_group_name' => $currentGroup?->name,
                        'current_level_id' => $enr?->level_id,
                        'current_level_name' => $enr?->level?->name,
                        'is_in_my_group' => $isInMyGroup,
                        'locked_by_other' => !$canManage && $enr !== null && !$isInMyGroup,
                    ];
                })
                ->values()
                ->all();
        };

        return Inertia::render('Quran/Index', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $this->access->getSwitchableUnits($user, $unit)->map->only(['id', 'name', 'code']),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'semester', 'is_active']),
            'activeYear' => $activeYear->only(['id', 'name', 'semester', 'is_active']),
            'canManage' => $canManage,
            'allowSelfPlotting' => $allowSelfPlotting,
            'isGlobalAdmin' => $isGlobalAdmin,
            'levels' => $levels,
            'sessions' => $sessions,
            'groups' => $groups,
            'teachers' => $canManage ? $this->access->getEligibleTeachers($unit->id) : [],
            'classrooms' => $classrooms,
            'allUnitStudents' => Inertia::optional($allUnitStudentsResolver),
            'levelDistribution' => $levelDistribution,
            'stats' => [
                'total_students' => $totalActiveStudents,
                'plotted_students' => $plottedStudentCount,
                'unplotted_students' => max(0, $totalActiveStudents - $plottedStudentCount),
                'total_groups' => $groups->count(),
                'inclusive_groups' => $inclusiveGroupsCount,
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
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat mengelola sesi.');
        }

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
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403);
        }
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
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403);
        }
        if ($session->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $session->delete();

        return back()->with('success', 'Sesi jadwal berhasil dihapus.');
    }

    public function seedDefaultSessions(Request $request)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403);
        }

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
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat membuat kelompok.');
        }

        $activeYear = $this->access->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        $isInclusive = $request->boolean('is_inclusive');

        $validated = $request->validate([
            'is_inclusive' => 'nullable|boolean',
            'level_id' => ($isInclusive ? 'nullable' : 'required') . '|exists:quran_levels,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'name' => 'required|string|max:120',
            'teacher_id' => 'required|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:teacher_id',
            'room_name' => 'nullable|string|max:100',
            'max_quota' => 'nullable|integer|min:1|max:200',
        ]);

        if (!$isInclusive) {
            QuranLevel::where('unit_id', $unit->id)->findOrFail($validated['level_id']);
        }

        if (!empty($validated['session_id'])) {
            QuranSession::where('unit_id', $unit->id)->findOrFail($validated['session_id']);
        }

        QuranGroup::create([
            'unit_id' => $unit->id,
            'academic_year_id' => $activeYear->id,
            'level_id' => $isInclusive ? null : $validated['level_id'],
            'is_inclusive' => $isInclusive,
            'session_id' => ($validated['session_id'] ?? null) ?: null,
            'name' => $validated['name'],
            'teacher_id' => $validated['teacher_id'],
            'assistant_teacher_id' => ($validated['assistant_teacher_id'] ?? null) ?: null,
            'room_name' => $validated['room_name'] ?? null,
            'max_quota' => $validated['max_quota'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', $isInclusive
            ? 'Kelompok Inklusi (Multi-Jilid) berhasil ditambahkan.'
            : 'Kelompok mengaji berhasil ditambahkan.');
    }

    public function updateGroup(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403);
        }
        if ($group->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $isInclusive = $request->boolean('is_inclusive');

        $validated = $request->validate([
            'is_inclusive' => 'nullable|boolean',
            'level_id' => ($isInclusive ? 'nullable' : 'required') . '|exists:quran_levels,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'name' => 'required|string|max:120',
            'teacher_id' => 'required|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:teacher_id',
            'room_name' => 'nullable|string|max:100',
            'max_quota' => 'nullable|integer|min:1|max:200',
        ]);

        if (!$isInclusive) {
            QuranLevel::where('unit_id', $group->unit_id)->findOrFail($validated['level_id']);
        }

        if (!empty($validated['session_id'])) {
            QuranSession::where('unit_id', $group->unit_id)->findOrFail($validated['session_id']);
        }

        DB::transaction(function () use ($group, $validated, $isInclusive) {
            $oldLevelId = $group->level_id;
            $wasInclusive = (bool) $group->is_inclusive;

            $group->update([
                'level_id' => $isInclusive ? null : $validated['level_id'],
                'is_inclusive' => $isInclusive,
                'session_id' => ($validated['session_id'] ?? null) ?: null,
                'name' => $validated['name'],
                'teacher_id' => $validated['teacher_id'],
                'assistant_teacher_id' => ($validated['assistant_teacher_id'] ?? null) ?: null,
                'room_name' => $validated['room_name'] ?? null,
                'max_quota' => $validated['max_quota'] ?? null,
            ]);

            // Only sync all members' level_id for regular single-jilid groups
            if (!$isInclusive && ($wasInclusive || (int) $oldLevelId !== (int) $validated['level_id'])) {
                $group->activeEnrollments()->update(['level_id' => $validated['level_id']]);
            }
        });

        return back()->with('success', 'Kelompok mengaji berhasil diperbarui.');
    }

    public function destroyGroup(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if (!$this->access->canManage($user, $unit->id)) {
            abort(403);
        }
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
     * For inclusive (multi-jilid) groups, accepts per-student `student_levels[student_id] => level_id`
     * or a batch fallback `level_id`.
     */
    public function enrollStudents(Request $request, QuranGroup $group)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if ($group->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $canManage = $this->access->canManage($user, $unit->id);
        $isMyGroup = $group->teacher_id === $user->id || $group->assistant_teacher_id === $user->id;
        $unitSetting = QuranSetting::where('unit_id', $group->unit_id)->first();
        $allowSelfPlotting = $unitSetting ? (bool) ($unitSetting->allow_teacher_self_plotting ?? true) : true;

        if (!$canManage) {
            if (!$allowSelfPlotting) {
                abort(403, 'Plotting mandiri pengampu sedang dikunci oleh Koordinator Al-Qur\'an.');
            }
            if (!$isMyGroup) {
                abort(403, 'Anda hanya dapat melakukan plotting siswa pada kelompok yang Anda ampu.');
            }
        }

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'level_id' => 'nullable|integer|exists:quran_levels,id',
            'student_levels' => 'nullable|array',
            'student_levels.*' => 'nullable|integer|exists:quran_levels,id',
            'started_at' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $validUnitLevelIds = QuranLevel::where('unit_id', $group->unit_id)
            ->pluck('id')
            ->flip();

        $activeYear = $group->academicYear ?: $this->access->resolveAcademicYear();
        $startedAt = $validated['started_at'] ?? now()->toDateString();
        $addedCount = 0;
        $skippedLocked = [];

        DB::transaction(function () use ($group, $validated, $validUnitLevelIds, $activeYear, $startedAt, $user, $canManage, &$addedCount, &$skippedLocked) {
            $students = Student::where('unit_id', $group->unit_id)
                ->whereIn('id', $validated['student_ids'])
                ->get();

            $studentLevelsMap = $validated['student_levels'] ?? [];
            $defaultLevelId = isset($validated['level_id']) ? (int) $validated['level_id'] : null;

            foreach ($students as $student) {
                $existingActive = QuranEnrollment::with('group:id,name,teacher_id,assistant_teacher_id')
                    ->where('unit_id', $group->unit_id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('student_id', $student->id)
                    ->where('status', QuranEnrollment::STATUS_ACTIVE)
                    ->first();

                if ($existingActive && $existingActive->group_id === $group->id) {
                    continue;
                }

                // Anti-steal guardrail: pengampu cannot pull students already enrolled in another teacher's group
                if ($existingActive && !$canManage) {
                    $existingGroup = $existingActive->group;
                    $ownsExisting = $existingGroup !== null
                        && ($existingGroup->teacher_id === $user->id || $existingGroup->assistant_teacher_id === $user->id);

                    if (!$ownsExisting) {
                        $skippedLocked[] = $student->full_name . ($existingGroup ? " ({$existingGroup->name})" : '');
                        continue;
                    }
                }

                if ($group->is_inclusive) {
                    $pickedLevelId = (int) ($studentLevelsMap[$student->id]
                        ?? $studentLevelsMap[(string) $student->id]
                        ?? $defaultLevelId
                        ?? $existingActive?->level_id
                        ?? 0);

                    if ($pickedLevelId <= 0 || !$validUnitLevelIds->has($pickedLevelId)) {
                        throw ValidationException::withMessages([
                            'level_id' => "Pilih jilid yang valid untuk ananda {$student->full_name} di kelompok inklusi.",
                        ]);
                    }
                    $targetLevelId = $pickedLevelId;
                } else {
                    $targetLevelId = (int) $group->level_id;
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
                    $changeType = (int) $existingActive->level_id === $targetLevelId
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
                    'level_id' => $targetLevelId,
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
                    'to_level_id' => $targetLevelId,
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

        if ($addedCount === 0 && !empty($skippedLocked)) {
            $preview = implode(', ', array_slice($skippedLocked, 0, 3));
            $more = count($skippedLocked) > 3 ? ' (+' . (count($skippedLocked) - 3) . ' lainnya)' : '';

            return back()->with('error', "Siswa sudah terdaftar di kelompok pengampu lain dan tidak dapat ditarik langsung: {$preview}{$more}.");
        }

        if (!empty($skippedLocked)) {
            return back()->with('success', "{$addedCount} siswa berhasil dimasukkan ke {$group->name}. " . count($skippedLocked) . ' siswa dilewati karena sudah di kelompok pengampu lain.');
        }

        return back()->with('success', "{$addedCount} siswa berhasil dimasukkan ke kelompok {$group->name}.");
    }

    /**
     * Inline update of an individual student's Jilid inside an Inclusive (Multi-Jilid) group.
     */
    public function updateEnrollmentLevel(Request $request, QuranEnrollment $enrollment)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if ($enrollment->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $enrollment->load(['group', 'level', 'student', 'academicYear']);
        $group = $enrollment->group;
        if (!$group || !$group->is_inclusive) {
            abort(422, 'Pengubahan jilid langsung per siswa hanya berlaku untuk kelompok Inklusi (Multi-Jilid).');
        }

        $canManage = $this->access->canManage($user, $unit->id);
        $isMyGroup = $group->teacher_id === $user->id || $group->assistant_teacher_id === $user->id;
        $unitSetting = QuranSetting::where('unit_id', $enrollment->unit_id)->first();
        $allowSelfPlotting = $unitSetting ? (bool) ($unitSetting->allow_teacher_self_plotting ?? true) : true;
        $allowPromote = $unitSetting ? (bool) ($unitSetting->allow_teacher_promote ?? true) : true;

        if (!$canManage && !($isMyGroup && ($allowSelfPlotting || $allowPromote))) {
            abort(403, 'Anda tidak memiliki akses mengubah jilid siswa pada kelompok ini.');
        }

        $validated = $request->validate([
            'level_id' => 'required|integer|exists:quran_levels,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $newLevel = QuranLevel::where('unit_id', $enrollment->unit_id)->findOrFail($validated['level_id']);

        if ((int) $enrollment->level_id === (int) $newLevel->id) {
            return back()->with('success', 'Jilid siswa tidak berubah.');
        }

        $activeYear = $enrollment->academicYear ?: $this->access->resolveAcademicYear();
        $oldLevel = $enrollment->level;

        DB::transaction(function () use ($enrollment, $group, $oldLevel, $newLevel, $activeYear, $validated, $user) {
            $changeType = ($oldLevel && $newLevel->sort_order > $oldLevel->sort_order)
                ? QuranLevelHistory::TYPE_PROMOTED
                : QuranLevelHistory::TYPE_TRANSFERRED;

            $enrollment->update([
                'level_id' => $newLevel->id,
            ]);

            QuranLevelHistory::create([
                'unit_id' => $enrollment->unit_id,
                'academic_year_id' => $activeYear->id,
                'semester' => $activeYear->semester,
                'student_id' => $enrollment->student_id,
                'from_level_id' => $oldLevel?->id,
                'to_level_id' => $newLevel->id,
                'from_group_id' => $group->id,
                'to_group_id' => $group->id,
                'from_teacher_id' => $group->teacher_id,
                'to_teacher_id' => $group->teacher_id,
                'change_type' => $changeType,
                'changed_at' => now()->toDateString(),
                'notes' => $validated['notes'] ?? 'Penyesuaian jilid di kelompok inklusi',
                'recorded_by' => $user->id,
            ]);
        });

        $studentName = $enrollment->student?->full_name ?? 'Siswa';

        return back()->with('success', "Jilid ananda {$studentName} diperbarui ke {$newLevel->name}.");
    }

    public function removeEnrollment(Request $request, QuranEnrollment $enrollment)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        if ($enrollment->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $canManage = $this->access->canManage($user, $unit->id);
        $group = $enrollment->group;
        $isMyGroup = $group && ($group->teacher_id === $user->id || $group->assistant_teacher_id === $user->id);
        $unitSetting = QuranSetting::where('unit_id', $enrollment->unit_id)->first();
        $allowSelfPlotting = $unitSetting ? (bool) ($unitSetting->allow_teacher_self_plotting ?? true) : true;

        if (!$canManage) {
            if (!$allowSelfPlotting) {
                abort(403, 'Plotting mandiri pengampu sedang dikunci oleh Koordinator Al-Qur\'an.');
            }
            if (!$isMyGroup) {
                abort(403, 'Anda hanya dapat mengeluarkan siswa dari kelompok yang Anda ampu.');
            }
        }

        $enrollment->delete();

        return back()->with('success', 'Siswa berhasil dikeluarkan dari kelompok.');
    }
}
