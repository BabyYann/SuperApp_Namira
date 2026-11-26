<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranEnrollment;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QuranReportController extends Controller
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
        $isLocked = !$canManage && (bool) ($setting?->lock_cetak_rapor ?? false);

        $groupsQuery = QuranGroup::with(['level:id,name,sort_order', 'session:id,name', 'teacher:id,name'])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true);

        if (!$canManage) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('assistant_teacher_id', $user->id);
            });
        }

        $groups = $groupsQuery->get()->sortBy([
            fn ($g) => $g->level?->sort_order ?? 999,
            fn ($g) => $g->name,
        ])->values();

        $allowedGroupIds = $groups->pluck('id')->toArray();

        $enrollments = QuranEnrollment::with([
                'student' => fn ($q) => $q->select('id', 'nis', 'nisn', 'full_name', 'classroom_id')->with('classroom:id,name,level'),
                'level:id,name,sort_order',
                'group' => fn ($q) => $q->with(['teacher:id,name', 'session:id,name']),
            ])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->when(!$canManage, fn ($q) => $q->whereIn('group_id', $allowedGroupIds ?: [-1]))
            ->get();

        $enrolledStudentIds = $enrollments->pluck('student_id')->unique()->all();

        $assessments = empty($enrolledStudentIds)
            ? collect()
            : QuranAssessment::with(['level:id,name', 'teacher:id,name', 'group:id,name'])
                ->where('unit_id', $unit->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('semester', $semester)
                ->whereIn('student_id', $enrolledStudentIds)
                ->get()
                ->keyBy('student_id');

        $rows = $enrollments
            ->filter(fn ($e) => $e->student !== null)
            ->map(function ($e) use ($assessments) {
                $st = $e->student;
                $assessment = $assessments->get($st->id);

                $assessedCount = 0;
                if ($assessment && is_array($assessment->scores)) {
                    foreach ($assessment->scores as $sc) {
                        $grade = is_array($sc) ? ($sc['grade'] ?? '-') : $sc;
                        if ($grade && $grade !== '-') {
                            $assessedCount++;
                        }
                    }
                }

                return [
                    'student_id' => $st->id,
                    'full_name' => $st->full_name,
                    'nis' => $st->nis,
                    'nisn' => $st->nisn,
                    'classroom_id' => $st->classroom_id,
                    'classroom_name' => $st->classroom?->name ?? '-',
                    'level_id' => $assessment?->level_id ?? $e->level_id,
                    'level_name' => $assessment?->level?->name ?? ($e->level?->name ?? '-'),
                    'group_id' => $e->group_id,
                    'group_name' => $e->group?->name ?? '-',
                    'session_name' => $e->group?->session?->name ?? '-',
                    'teacher_id' => $assessment?->teacher_id ?? $e->group?->teacher_id,
                    'teacher_name' => $assessment?->teacher?->name ?? ($e->group?->teacher?->name ?? '-'),
                    'assessed_count' => $assessedCount,
                    'is_ready' => $assessedCount > 0,
                    'updated_at' => $assessment?->updated_at?->format('d M Y H:i'),
                ];
            });

        if ($request->filled('group_id') && $request->group_id !== 'all') {
            $rows = $rows->filter(fn ($r) => (string) $r['group_id'] === (string) $request->group_id);
        }
        if ($request->filled('level_id') && $request->level_id !== 'all') {
            $rows = $rows->filter(fn ($r) => (string) $r['level_id'] === (string) $request->level_id);
        }
        if ($request->filled('teacher_id') && $request->teacher_id !== 'all') {
            $rows = $rows->filter(fn ($r) => (string) $r['teacher_id'] === (string) $request->teacher_id);
        }
        if ($request->filled('classroom_id') && $request->classroom_id !== 'all') {
            $rows = $rows->filter(fn ($r) => (string) $r['classroom_id'] === (string) $request->classroom_id);
        }
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $rows = $rows->filter(fn ($r) =>
                str_contains(strtolower($r['full_name']), $search) ||
                str_contains(strtolower((string) $r['nisn']), $search) ||
                str_contains(strtolower((string) $r['nis']), $search)
            );
        }

        $rows = $rows->sortBy(fn ($r) => strtolower($r['full_name']))->values();

        $levels = QuranLevel::where('unit_id', $unit->id)->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
        $classrooms = Classroom::where('unit_id', $unit->id)->orderBy('level')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Quran/Reports', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $this->access->getSwitchableUnits($user, $unit)->map->only(['id', 'name', 'code']),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'semester', 'is_active']),
            'activeYear' => $activeYear->only(['id', 'name', 'semester', 'is_active']),
            'semester' => $semester,
            'canManage' => $canManage,
            'isGlobalAdmin' => $isGlobalAdmin,
            'isLocked' => $isLocked,
            'groups' => $groups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'is_inclusive' => (bool) $g->is_inclusive,
                'level_name' => $g->is_inclusive ? 'Multi-Jilid (Inklusi)' : ($g->level?->name ?? '-'),
                'teacher_name' => $g->teacher?->name,
            ]),
            'levels' => $levels,
            'classrooms' => $classrooms,
            'teachers' => $canManage ? $this->access->getEligibleTeachers($unit->id) : [],
            'students' => $rows,
            'filters' => [
                'group_id' => $request->input('group_id', 'all'),
                'level_id' => $request->input('level_id', 'all'),
                'teacher_id' => $request->input('teacher_id', 'all'),
                'classroom_id' => $request->input('classroom_id', 'all'),
                'search' => $request->input('search', ''),
                'unit_id' => $unit->id,
                'academic_year_id' => $activeYear->id,
            ],
        ]);
    }

    private function buildReportData(Request $request): array
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
        $setting = QuranSetting::where('unit_id', $unit->id)->first();

        if (!$canManage && ($setting?->lock_cetak_rapor ?? false)) {
            abort(403, 'Fitur cetak rapor sedang ditutup oleh koordinator/admin.');
        }

        $studentIds = [];
        if ($request->filled('student_ids')) {
            $raw = $request->input('student_ids');
            $studentIds = is_array($raw)
                ? array_map('intval', $raw)
                : array_filter(array_map('intval', explode(',', (string) $raw)));
        } elseif ($request->filled('group_id')) {
            $studentIds = QuranEnrollment::where('unit_id', $unit->id)
                ->where('group_id', $request->integer('group_id'))
                ->where('status', QuranEnrollment::STATUS_ACTIVE)
                ->pluck('student_id')
                ->toArray();
        }

        if (empty($studentIds)) {
            abort(400, 'Pilih minimal 1 siswa untuk mencetak rapor.');
        }

        if (!$canManage) {
            $myGroupIds = QuranGroup::where('unit_id', $unit->id)
                ->where('academic_year_id', $activeYear->id)
                ->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('assistant_teacher_id', $user->id);
                })
                ->pluck('id')
                ->toArray();

            $allowedStudentIds = QuranEnrollment::where('unit_id', $unit->id)
                ->whereIn('group_id', $myGroupIds ?: [-1])
                ->where('status', QuranEnrollment::STATUS_ACTIVE)
                ->pluck('student_id')
                ->toArray();

            $studentIds = array_values(array_intersect($studentIds, $allowedStudentIds));
            if (empty($studentIds)) {
                abort(403, 'Anda hanya dapat mencetak rapor siswa di kelompok binaan Anda.');
            }
        }

        $students = Student::where('unit_id', $unit->id)
            ->whereIn('id', $studentIds)
            ->with('classroom:id,name,level')
            ->orderBy('full_name')
            ->get();

        $enrollments = QuranEnrollment::with(['level', 'group.teacher'])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', QuranEnrollment::STATUS_ACTIVE)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $assessments = QuranAssessment::with(['level', 'teacher', 'group.teacher'])
            ->where('unit_id', $unit->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $semester)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $reportDate = $setting?->report_date
            ? Carbon::parse($setting->report_date)->locale('id')->translatedFormat('d F Y')
            : now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y');

        $reports = [];

        foreach ($students as $student) {
            $assessment = $assessments->get($student->id);
            $enrollment = $enrollments->get($student->id);

            $level = $assessment?->level ?? $enrollment?->level;
            $levelName = $level?->name ?? '-';
            $gradeLevel = $this->setup->extractGradeLevel($student->classroom?->level, $student->classroom?->name);
            $teacherName = $assessment?->teacher?->name
                ?? $assessment?->group?->teacher?->name
                ?? $enrollment?->group?->teacher?->name
                ?? '-';

            $materiRows = [];
            $surahRows = [];
            $doaRows = [];
            $hadistRows = [];
            $niatSholatRows = [];
            $bacaanSholatRows = [];

            if ($assessment && is_array($assessment->scores)) {
                $scoreItems = collect($assessment->scores)
                    ->filter(fn ($s) => is_array($s) && !empty($s['grade']) && $s['grade'] !== '-')
                    ->sortBy('sort_order');

                foreach ($scoreItems as $item) {
                    $grade = strtoupper(trim((string) $item['grade']));
                    $title = (string) ($item['title'] ?? '-');
                    $cat = (string) ($item['category'] ?? QuranRubricItem::CATEGORY_MATERI);

                    $row = [
                        'title' => $title,
                        'grade' => $grade,
                        'narrative' => $this->setup->buildNarrative($grade, $title, $cat),
                    ];

                    match ($cat) {
                        QuranRubricItem::CATEGORY_SURAH => $surahRows[] = $row,
                        QuranRubricItem::CATEGORY_DOA => $doaRows[] = $row,
                        QuranRubricItem::CATEGORY_HADIST => $hadistRows[] = $row,
                        QuranRubricItem::CATEGORY_NIAT_SHOLAT => $niatSholatRows[] = $row,
                        QuranRubricItem::CATEGORY_BACAAN_SHOLAT => $bacaanSholatRows[] = $row,
                        default => $materiRows[] = $row,
                    };
                }
            }

            $hasHafalan = (
                count($surahRows)
                + count($doaRows)
                + count($hadistRows)
                + count($niatSholatRows)
                + count($bacaanSholatRows)
            ) > 0;

            $reports[] = [
                'student_name' => $student->full_name,
                'nis' => $student->nis ?: '-',
                'nisn' => $student->nisn ?: '-',
                'classroom_name' => $student->classroom?->name ?? '-',
                'grade_level' => $gradeLevel,
                'level_name' => $levelName,
                'teacher_name' => $teacherName,
                'materi_rows' => $materiRows,
                'surah_rows' => $surahRows,
                'doa_rows' => $doaRows,
                'hadist_rows' => $hadistRows,
                'niat_sholat_rows' => $niatSholatRows,
                'bacaan_sholat_rows' => $bacaanSholatRows,
                'has_hafalan' => $hasHafalan,
                'teacher_note' => $assessment?->teacher_note ?? '',
            ];
        }

        return [
            'unit' => $unit,
            'setting' => $setting,
            'activeYear' => $activeYear,
            'semesterLabel' => ucfirst($semester),
            'reportDate' => $reportDate,
            'unitLogoUrl' => $this->setup->resolveUnitLogoUrl($unit),
            'ummiLogoUrl' => asset('images/logo-ummi.png'),
            'reports' => $reports,
        ];
    }

    public function print(Request $request)
    {
        $data = $this->buildReportData($request);
        $data['isPdf'] = false;

        return view('quran.report_print', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildReportData($request);
        $data['isPdf'] = true;

        $toBase64 = function (?string $pathOrUrl): ?string {
            if (!$pathOrUrl) {
                return null;
            }
            if (str_starts_with($pathOrUrl, 'data:')) {
                return $pathOrUrl;
            }
            $localPath = null;
            if (file_exists($pathOrUrl)) {
                $localPath = $pathOrUrl;
            } else {
                $parsed = parse_url($pathOrUrl, PHP_URL_PATH);
                if ($parsed && file_exists(public_path(ltrim($parsed, '/')))) {
                    $localPath = public_path(ltrim($parsed, '/'));
                }
            }
            if (!$localPath || !is_file($localPath)) {
                return null;
            }
            $mime = mime_content_type($localPath) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
        };

        $data['unitLogoUrl'] = $toBase64($data['unitLogoUrl']) ?: $data['unitLogoUrl'];
        $data['ummiLogoUrl'] = $toBase64(public_path('images/logo-ummi.png')) ?: $data['ummiLogoUrl'];
        $data['ttdTpqSrc'] = $toBase64($data['setting']?->ttd_kepala_tpq_url);
        $data['ttdKepsekSrc'] = $toBase64($data['setting']?->ttd_kepala_sekolah_url);

        $firstReport = $data['reports'][0] ?? null;
        $filename = count($data['reports']) === 1 && $firstReport
            ? 'Rapor_Ummi_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $firstReport['student_name']) . '_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $firstReport['level_name']) . '.pdf'
            : 'Rapor_Ummi_Massal_' . count($data['reports']) . '_Siswa.pdf';

        return Pdf::loadView('quran.report_print', $data)
            ->setPaper([0, 0, 609.45, 935.43], 'portrait')
            ->stream($filename);
    }
}
