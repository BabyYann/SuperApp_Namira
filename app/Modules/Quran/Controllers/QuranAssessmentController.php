<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuranAssessmentController extends Controller
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
        $semester = $request->input('semester', $activeYear->semester);
        if (!in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = $activeYear->semester;
        }

        $canManage = $this->access->canManage($user, $unit->id);
        $isGlobalAdmin = $this->access->isGlobalAdmin($user);
        $setting = QuranSetting::where('unit_id', $unit->id)->first();
        $isLocked = !$canManage && (bool) ($setting?->lock_input_nilai ?? false);

        $roleFilter = $request->input('role_filter', 'all');

        $groupsQuery = QuranGroup::with([
                'level:id,name,code,sort_order',
                'session:id,name,start_time,end_time,sort_order',
                'teacher:id,name',
                'assistantTeacher:id,name',
            ])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true);

        if (!$canManage) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('assistant_teacher_id', $user->id);
            });
        }

        if ($roleFilter === 'main') {
            $groupsQuery->where('teacher_id', $user->id);
        } elseif ($roleFilter === 'assistant') {
            $groupsQuery->where('assistant_teacher_id', $user->id);
        }

        $groups = $groupsQuery->get()
            ->sortBy([
                fn ($g) => $g->is_inclusive ? 998 : ($g->level?->sort_order ?? 999),
                fn ($g) => $g->session?->sort_order ?? 999,
                fn ($g) => $g->name,
            ])
            ->values();

        $selectedGroupId = $request->integer('group_id') ?: $groups->first()?->id;
        $selectedGroup = $groups->firstWhere('id', $selectedGroupId) ?? $groups->first();

        // Lightweight aggregate counts per group (joins on qe.level_id so both regular & inclusive multi-jilid groups match accurately)
        $groupIds = $groups->pluck('id')->all();
        $memberCountsByGroup = empty($groupIds)
            ? collect()
            : DB::table('quran_enrollments')
                ->whereIn('group_id', $groupIds)
                ->where('status', 'active')
                ->select('group_id', DB::raw('COUNT(*) as total'))
                ->groupBy('group_id')
                ->pluck('total', 'group_id');

        $filledCountsByGroup = empty($groupIds)
            ? collect()
            : DB::table('quran_enrollments as qe')
                ->join('quran_assessments as qa', function ($join) use ($unit, $activeYear, $semester) {
                    $join->on('qa.student_id', '=', 'qe.student_id')
                        ->on('qa.level_id', '=', 'qe.level_id')
                        ->where('qa.unit_id', '=', $unit->id)
                        ->where('qa.academic_year_id', '=', $activeYear->id)
                        ->where('qa.semester', '=', $semester);
                })
                ->whereIn('qe.group_id', $groupIds)
                ->where('qe.status', 'active')
                ->select('qe.group_id', DB::raw('COUNT(DISTINCT qe.student_id) as total'))
                ->groupBy('qe.group_id')
                ->pluck('total', 'qe.group_id');

        // Load all active grade-scoped rubrics for this unit
        $allGradeItems = QuranRubricItem::where('unit_id', $unit->id)
            ->where('scope', QuranRubricItem::SCOPE_GRADE)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $usesGradeRubrics = $allGradeItems->isNotEmpty();

        $gradeRubricsByGrade = [];
        foreach ($allGradeItems->groupBy('grade_level') as $gradeKey => $items) {
            $bucket = [
                'hadist' => [],
                'doa_harian' => [],
                'niat_sholat' => [],
                'bacaan_sholat' => [],
            ];
            foreach ($items as $it) {
                if (isset($bucket[$it->category])) {
                    $bucket[$it->category][] = [
                        'id' => $it->id,
                        'title' => $it->title,
                        'category' => $it->category,
                        'scope' => 'grade',
                        'grade_level' => (string) $gradeKey,
                    ];
                }
            }
            $gradeRubricsByGrade[(string) $gradeKey] = $bucket;
        }

        $emptyLevelBucket = [
            'materi' => [],
            'surah_pendek' => [],
            'doa_harian' => [],
            'hadist' => [],
        ];

        $levelRubrics = $emptyLevelBucket;
        $levelRubricsByLevel = [];
        $studentsPayload = [];

        if ($selectedGroup) {
            $selectedGroup->load([
                'activeEnrollments' => fn ($q) => $q->with([
                    'level:id,name,code,sort_order',
                    'student' => fn ($sq) => $sq->select('id', 'nis', 'nisn', 'full_name', 'classroom_id')->with('classroom:id,name,level'),
                ]),
            ]);

            $members = $selectedGroup->activeEnrollments
                ->filter(fn ($e) => $e->student !== null)
                ->sortBy([
                    fn ($e) => $selectedGroup->is_inclusive ? ($e->level?->sort_order ?? 999) : 0,
                    fn ($e) => strtolower($e->student->full_name),
                ])
                ->values();

            $neededLevelIds = $members
                ->pluck('level_id')
                ->push($selectedGroup->level_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $itemsByLevel = empty($neededLevelIds)
                ? collect()
                : QuranRubricItem::where('unit_id', $unit->id)
                    ->where('scope', QuranRubricItem::SCOPE_LEVEL)
                    ->where('is_active', true)
                    ->whereIn('level_id', $neededLevelIds)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('level_id');

            foreach ($neededLevelIds as $lvlId) {
                $bucket = $emptyLevelBucket;
                foreach ($itemsByLevel->get($lvlId, collect()) as $item) {
                    if ($usesGradeRubrics && in_array($item->category, [QuranRubricItem::CATEGORY_DOA, QuranRubricItem::CATEGORY_HADIST], true)) {
                        continue;
                    }
                    if (isset($bucket[$item->category])) {
                        $bucket[$item->category][] = [
                            'id' => $item->id,
                            'title' => $item->title,
                            'category' => $item->category,
                            'scope' => 'level',
                        ];
                    }
                }
                $levelRubricsByLevel[(string) $lvlId] = $bucket;
            }

            if ($selectedGroup->level_id && isset($levelRubricsByLevel[(string) $selectedGroup->level_id])) {
                $levelRubrics = $levelRubricsByLevel[(string) $selectedGroup->level_id];
            }

            $memberStudentIds = $members->pluck('student_id')->all();
            $assessmentsByStudent = empty($memberStudentIds)
                ? collect()
                : QuranAssessment::where('unit_id', $unit->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('semester', $semester)
                    ->whereIn('student_id', $memberStudentIds)
                    ->get()
                    ->keyBy('student_id');

            foreach ($members as $enrollment) {
                $st = $enrollment->student;
                $studentLevelId = $enrollment->level_id ?? $selectedGroup->level_id;
                $studentLevelName = $enrollment->level?->name ?? $selectedGroup->level?->name ?? '-';
                $studentLevelSort = $enrollment->level?->sort_order ?? $selectedGroup->level?->sort_order ?? 999;

                $gradeLevel = $this->setup->extractGradeLevel($st->classroom?->level, $st->classroom?->name);
                $assessment = $assessmentsByStudent->get($st->id);
                $sameLevel = $assessment !== null && $studentLevelId !== null && (int) $assessment->level_id === (int) $studentLevelId;
                $rawSaved = $assessment?->scores ?? [];

                $gradeMap = [];

                // 1. Hydrate student's own level-scoped items (only if saved under the same level)
                foreach ($itemsByLevel->get($studentLevelId, collect()) as $item) {
                    if ($usesGradeRubrics && in_array($item->category, [QuranRubricItem::CATEGORY_DOA, QuranRubricItem::CATEGORY_HADIST], true)) {
                        continue;
                    }
                    $val = $sameLevel
                        ? ($rawSaved[(string) $item->id]['grade'] ?? $rawSaved[$item->id]['grade'] ?? '-')
                        : '-';
                    $gradeMap[(string) $item->id] = in_array($val, ['A', 'B', 'C', 'D', '-'], true) ? $val : '-';
                }

                // 2. Hydrate grade-scoped items (PRESERVED even if student promoted Jilid mid-semester!)
                if ($usesGradeRubrics && $gradeLevel !== null && isset($gradeRubricsByGrade[$gradeLevel])) {
                    foreach ($gradeRubricsByGrade[$gradeLevel] as $catItems) {
                        foreach ($catItems as $gItem) {
                            $idStr = (string) $gItem['id'];
                            $val = $rawSaved[$idStr]['grade'] ?? $rawSaved[$gItem['id']]['grade'] ?? '-';
                            $gradeMap[$idStr] = in_array($val, ['A', 'B', 'C', 'D', '-'], true) ? $val : '-';
                        }
                    }
                }

                $studentsPayload[] = [
                    'student_id' => $st->id,
                    'full_name' => $st->full_name,
                    'nis' => $st->nis,
                    'classroom_name' => $st->classroom?->name ?? '-',
                    'grade_level' => $gradeLevel,
                    'level_id' => $studentLevelId,
                    'level_name' => $studentLevelName,
                    'level_sort_order' => $studentLevelSort,
                    'is_main_teacher' => $selectedGroup->teacher_id === $user->id,
                    'has_saved_assessment' => $sameLevel,
                    'previous_level_mismatch' => $assessment !== null && !$sameLevel,
                    'grades' => $gradeMap,
                    'teacher_note' => $sameLevel ? ($assessment->teacher_note ?? '') : '',
                ];
            }
        }

        return Inertia::render('Quran/Assessments', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $this->access->getSwitchableUnits($user, $unit)->map->only(['id', 'name', 'code']),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'semester', 'is_active']),
            'activeYear' => $activeYear->only(['id', 'name', 'semester', 'is_active']),
            'semester' => $semester,
            'canManage' => $canManage,
            'isGlobalAdmin' => $isGlobalAdmin,
            'isLocked' => $isLocked,
            'roleFilter' => $roleFilter,
            'usesGradeRubrics' => $usesGradeRubrics,
            'groups' => $groups->map(function ($g) use ($memberCountsByGroup, $filledCountsByGroup, $user) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'is_inclusive' => (bool) $g->is_inclusive,
                    'level_id' => $g->level_id,
                    'level_name' => $g->is_inclusive ? 'Multi-Jilid (Inklusi)' : ($g->level?->name ?? '-'),
                    'session_name' => $g->session?->name,
                    'teacher_name' => $g->teacher?->name ?? '-',
                    'assistant_name' => $g->assistantTeacher?->name,
                    'my_role' => $g->teacher_id === $user->id ? 'main' : ($g->assistant_teacher_id === $user->id ? 'assistant' : 'manager'),
                    'members_count' => (int) ($memberCountsByGroup[$g->id] ?? 0),
                    'filled_count' => (int) ($filledCountsByGroup[$g->id] ?? 0),
                ];
            }),
            'selectedGroup' => $selectedGroup ? [
                'id' => $selectedGroup->id,
                'name' => $selectedGroup->name,
                'is_inclusive' => (bool) $selectedGroup->is_inclusive,
                'level_id' => $selectedGroup->level_id,
                'level_name' => $selectedGroup->is_inclusive ? 'Multi-Jilid (Inklusi)' : ($selectedGroup->level?->name ?? '-'),
                'session_name' => $selectedGroup->session?->name,
                'teacher_name' => $selectedGroup->teacher?->name ?? '-',
            ] : null,
            'rubrics' => $levelRubrics,
            'levelRubricsByLevel' => $levelRubricsByLevel,
            'gradeRubricsByGrade' => $gradeRubricsByGrade,
            'students' => $studentsPayload,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        $canManage = $this->access->canManage($user, $unit->id);
        $setting = QuranSetting::where('unit_id', $unit->id)->first();

        if (!$canManage && ($setting?->lock_input_nilai ?? false)) {
            abort(403, 'Periode pengisian nilai sedang ditutup oleh koordinator/admin.');
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:quran_groups,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'entries' => 'required|array|min:1',
            'entries.*.student_id' => 'required|integer|exists:students,id',
            'entries.*.grades' => 'nullable|array',
            'entries.*.teacher_note' => 'nullable|string|max:300',
        ]);

        $group = QuranGroup::with([
                'level:id,name',
                'activeEnrollments.student.classroom:id,name,level',
            ])
            ->where('unit_id', $unit->id)
            ->findOrFail($validated['group_id']);

        if (!$canManage && $group->teacher_id !== $user->id && $group->assistant_teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses mengisi nilai kelompok ini.');
        }

        $neededLevelIds = $group->activeEnrollments
            ->pluck('level_id')
            ->push($group->level_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $levelRubricsByLevelId = empty($neededLevelIds)
            ? collect()
            : QuranRubricItem::where('unit_id', $group->unit_id)
                ->where('scope', QuranRubricItem::SCOPE_LEVEL)
                ->where('is_active', true)
                ->whereIn('level_id', $neededLevelIds)
                ->get()
                ->groupBy('level_id')
                ->map(fn ($items) => $items->keyBy('id'));

        $gradeRubricMap = QuranRubricItem::where('unit_id', $group->unit_id)
            ->where('scope', QuranRubricItem::SCOPE_GRADE)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $enrollmentsByStudent = $group->activeEnrollments->keyBy('student_id');
        $savedCount = 0;

        DB::transaction(function () use ($validated, $group, $levelRubricsByLevelId, $gradeRubricMap, $enrollmentsByStudent, $user, &$savedCount) {
            foreach ($validated['entries'] as $entry) {
                $studentId = (int) $entry['student_id'];
                $enrollment = $enrollmentsByStudent->get($studentId);
                if (!$enrollment) {
                    continue;
                }

                $studentLevelId = (int) ($enrollment->level_id ?: $group->level_id);
                if ($studentLevelId <= 0) {
                    continue;
                }

                $studentLevelRubricMap = $levelRubricsByLevelId->get($studentLevelId, collect());
                $studentGrade = $this->setup->extractGradeLevel(
                    $enrollment->student?->classroom?->level,
                    $enrollment->student?->classroom?->name
                );

                $structuredScores = [];
                foreach (($entry['grades'] ?? []) as $rubricId => $grade) {
                    $rId = (int) $rubricId;
                    $rubric = $studentLevelRubricMap->get($rId);

                    if (!$rubric) {
                        $candidate = $gradeRubricMap->get($rId);
                        if ($candidate && ($studentGrade === null || (string) $candidate->grade_level === (string) $studentGrade)) {
                            $rubric = $candidate;
                        }
                    }

                    if (!$rubric) {
                        continue;
                    }

                    $cleanGrade = in_array($grade, ['A', 'B', 'C', 'D', '-'], true) ? $grade : '-';
                    $structuredScores[(string) $rubric->id] = [
                        'rubric_item_id' => $rubric->id,
                        'scope' => $rubric->scope ?? QuranRubricItem::SCOPE_LEVEL,
                        'grade_level' => $rubric->grade_level,
                        'category' => $rubric->category,
                        'title' => $rubric->title,
                        'sort_order' => $rubric->sort_order,
                        'grade' => $cleanGrade,
                    ];
                }

                QuranAssessment::updateOrCreate(
                    [
                        'unit_id' => $group->unit_id,
                        'student_id' => $studentId,
                        'academic_year_id' => $validated['academic_year_id'],
                        'semester' => $validated['semester'],
                    ],
                    [
                        'level_id' => $studentLevelId,
                        'group_id' => $group->id,
                        'teacher_id' => $group->teacher_id,
                        'scores' => $structuredScores,
                        'teacher_note' => trim((string) ($entry['teacher_note'] ?? '')) ?: null,
                        'updated_by' => $user->id,
                    ]
                );

                $savedCount++;
            }
        });

        $levelLabel = $group->is_inclusive ? 'Multi-Jilid Inklusi' : ($group->level?->name ?? '-');

        return back()->with('success', "Berhasil menyimpan nilai untuk {$savedCount} siswa di {$group->name} ({$levelLabel}).");
    }
}
