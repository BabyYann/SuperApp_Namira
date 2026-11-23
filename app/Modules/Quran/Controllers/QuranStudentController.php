<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Quran\Models\QuranEnrollment;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranLevelHistory;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuranStudentController extends Controller
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
        $setting = QuranSetting::where('unit_id', $unit->id)->first();
        $canPromote = $canManage || ($setting?->allow_teacher_promote ?? true);

        $levels = QuranLevel::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $allGroups = QuranGroup::with(['level:id,name,sort_order', 'session:id,name,start_time,end_time', 'teacher:id,name'])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->get()
            ->sortBy([
                fn ($g) => $g->level?->sort_order ?? 999,
                fn ($g) => $g->name,
            ])
            ->values();

        $myGroupIds = $allGroups
            ->filter(fn ($g) => $g->teacher_id === $user->id || $g->assistant_teacher_id === $user->id)
            ->pluck('id')
            ->toArray();

        $enrollments = QuranEnrollment::with([
                'level:id,name,code,sort_order',
                'group' => fn ($q) => $q->with(['session:id,name,start_time,end_time', 'teacher:id,name', 'assistantTeacher:id,name']),
            ])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->get()
            ->keyBy('student_id');

        $historyCounts = QuranLevelHistory::where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeYear->semester)
            ->where('change_type', QuranLevelHistory::TYPE_PROMOTED)
            ->select('student_id', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $studentsQuery = Student::where('unit_id', $unit->id)
            ->select('id', 'nis', 'nisn', 'full_name', 'gender', 'classroom_id')
            ->with('classroom:id,name');

        if (!$canManage) {
            $myStudentIds = $enrollments
                ->filter(fn ($e) => in_array($e->group_id, $myGroupIds, true))
                ->keys()
                ->toArray();
            $studentsQuery->whereIn('id', $myStudentIds ?: [-1]);
        }

        if ($request->filled('classroom_id') && $request->classroom_id !== 'all') {
            $studentsQuery->where('classroom_id', $request->integer('classroom_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->orderBy('full_name')->get()->map(function ($st) use ($enrollments, $historyCounts, $myGroupIds, $canManage, $canPromote) {
            $enr = $enrollments->get($st->id);
            $isMine = $enr && in_array($enr->group_id, $myGroupIds, true);

            return [
                'id' => $st->id,
                'nis' => $st->nis,
                'nisn' => $st->nisn,
                'full_name' => $st->full_name,
                'gender' => $st->gender,
                'classroom_id' => $st->classroom_id,
                'classroom_name' => $st->classroom?->name ?? '-',
                'enrollment_id' => $enr?->id,
                'level_id' => $enr?->level_id,
                'level_name' => $enr?->level?->name ?? 'Belum Diplot',
                'level_sort_order' => $enr?->level?->sort_order ?? 0,
                'group_id' => $enr?->group_id,
                'group_name' => $enr?->group?->name ?? '-',
                'session_name' => $enr?->group?->session?->name ?? '-',
                'teacher_name' => $enr?->group?->teacher?->name ?? '-',
                'started_at' => $enr?->started_at?->format('Y-m-d'),
                'promotions_count' => (int) ($historyCounts[$st->id] ?? 0),
                'can_promote' => $canManage || ($canPromote && $isMine),
            ];
        });

        // Apply level / group / status filter in collection cleanly
        $levelFilter = $request->input('level_id', 'all');
        if ($levelFilter === 'unplotted') {
            $students = $students->filter(fn ($s) => $s['level_id'] === null)->values();
        } elseif ($levelFilter !== 'all') {
            $students = $students->filter(fn ($s) => (string) $s['level_id'] === (string) $levelFilter)->values();
        }

        $groupFilter = $request->input('group_id', 'all');
        if ($groupFilter !== 'all') {
            $students = $students->filter(fn ($s) => (string) $s['group_id'] === (string) $groupFilter)->values();
        }

        $classrooms = Classroom::where('unit_id', $unit->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return Inertia::render('Quran/Students', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $isGlobalAdmin ? $this->access->getEligibleUnits()->map->only(['id', 'name', 'code']) : [],
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'semester', 'is_active']),
            'activeYear' => $activeYear->only(['id', 'name', 'semester', 'is_active']),
            'canManage' => $canManage,
            'isGlobalAdmin' => $isGlobalAdmin,
            'levels' => $levels,
            'groups' => $allGroups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'level_id' => $g->level_id,
                'level_name' => $g->level?->name,
                'session_name' => $g->session?->name,
                'teacher_id' => $g->teacher_id,
                'teacher_name' => $g->teacher?->name ?? '-',
            ]),
            'classrooms' => $classrooms,
            'students' => $students->values(),
            'filters' => [
                'classroom_id' => $request->input('classroom_id', 'all'),
                'level_id' => $levelFilter,
                'group_id' => $groupFilter,
                'search' => $request->input('search', ''),
                'unit_id' => $unit->id,
                'academic_year_id' => $activeYear->id,
            ],
        ]);
    }

    /**
     * Promote student to next jilid or transfer to another group & teacher with full history tracking.
     */
    public function promote(Request $request, Student $student)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);

        if ($student->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403, 'Siswa tidak berada di unit aktif Anda.');
        }

        $activeYear = $this->access->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        $canManage = $this->access->canManage($user);
        $setting = QuranSetting::where('unit_id', $student->unit_id)->first();

        $currentEnrollment = QuranEnrollment::with('group')
            ->where('unit_id', $student->unit_id)
            ->where('academic_year_id', $activeYear->id)
            ->where('student_id', $student->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->first();

        if (!$canManage) {
            if (!($setting?->allow_teacher_promote ?? true)) {
                abort(403, 'Kenaikan jilid / pindah kelompok dikunci hanya untuk Koordinator Al-Qur\'an / Admin.');
            }
            $isMyStudent = $currentEnrollment && $currentEnrollment->group && (
                $currentEnrollment->group->teacher_id === $user->id ||
                $currentEnrollment->group->assistant_teacher_id === $user->id
            );
            if (!$isMyStudent) {
                abort(403, 'Anda hanya dapat menaikkan jilid siswa di kelompok binaan Anda.');
            }
        }

        $validated = $request->validate([
            'target_group_id' => 'required|exists:quran_groups,id',
            'change_type' => 'required|in:promoted,transferred,initial',
            'changed_at' => 'required|date',
            'exam_score' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
        ]);

        $targetGroup = QuranGroup::with('level')
            ->where('unit_id', $student->unit_id)
            ->findOrFail($validated['target_group_id']);

        if ($currentEnrollment && $currentEnrollment->group_id === $targetGroup->id) {
            return back()->with('error', 'Siswa sudah berada di kelompok tujuan tersebut.');
        }

        DB::transaction(function () use ($student, $activeYear, $currentEnrollment, $targetGroup, $validated, $user) {
            $fromLevelId = $currentEnrollment?->level_id;
            $fromGroupId = $currentEnrollment?->group_id;
            $fromTeacherId = $currentEnrollment?->group?->teacher_id;

            $changeType = $validated['change_type'];
            if (!$currentEnrollment) {
                $changeType = QuranLevelHistory::TYPE_INITIAL;
            }

            if ($currentEnrollment) {
                $currentEnrollment->update([
                    'status' => $changeType === QuranLevelHistory::TYPE_PROMOTED
                        ? QuranEnrollment::STATUS_PROMOTED
                        : QuranEnrollment::STATUS_TRANSFERRED,
                    'ended_at' => $validated['changed_at'],
                ]);
            }

            QuranEnrollment::create([
                'unit_id' => $student->unit_id,
                'academic_year_id' => $activeYear->id,
                'student_id' => $student->id,
                'group_id' => $targetGroup->id,
                'level_id' => $targetGroup->level_id,
                'status' => QuranEnrollment::STATUS_ACTIVE,
                'started_at' => $validated['changed_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            QuranLevelHistory::create([
                'unit_id' => $student->unit_id,
                'academic_year_id' => $activeYear->id,
                'semester' => $activeYear->semester,
                'student_id' => $student->id,
                'from_level_id' => $fromLevelId,
                'to_level_id' => $targetGroup->level_id,
                'from_group_id' => $fromGroupId,
                'to_group_id' => $targetGroup->id,
                'from_teacher_id' => $fromTeacherId,
                'to_teacher_id' => $targetGroup->teacher_id,
                'change_type' => $changeType,
                'changed_at' => $validated['changed_at'],
                'exam_score' => $validated['exam_score'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $user->id,
            ]);
        });

        $actionLabel = $validated['change_type'] === 'promoted' ? 'dinaikkan ke' : 'dipindahkan ke';

        return back()->with('success', "Ananda {$student->full_name} berhasil {$actionLabel} {$targetGroup->level->name} ({$targetGroup->name}).");
    }

    /**
     * JSON endpoint returning chronological level history for a student.
     */
    public function history(Request $request, Student $student)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);

        if ($student->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $histories = QuranLevelHistory::with([
                'academicYear:id,name,semester',
                'fromLevel:id,name',
                'toLevel:id,name',
                'fromGroup:id,name',
                'toGroup:id,name',
                'fromTeacher:id,name',
                'toTeacher:id,name',
                'recorder:id,name',
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'change_type' => $h->change_type,
                'changed_at' => $h->changed_at?->format('d M Y'),
                'changed_at_raw' => $h->changed_at?->format('Y-m-d'),
                'academic_year' => $h->academicYear ? ($h->academicYear->name . ' (' . ucfirst($h->semester) . ')') : ucfirst($h->semester),
                'from_level' => $h->fromLevel?->name,
                'to_level' => $h->toLevel?->name,
                'from_group' => $h->fromGroup?->name,
                'to_group' => $h->toGroup?->name,
                'from_teacher' => $h->fromTeacher?->name,
                'to_teacher' => $h->toTeacher?->name,
                'exam_score' => $h->exam_score,
                'notes' => $h->notes,
                'recorded_by' => $h->recorder?->name ?? 'Sistem',
            ]);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nis' => $student->nis,
                'classroom_name' => $student->classroom?->name ?? '-',
            ],
            'histories' => $histories,
        ]);
    }
}
