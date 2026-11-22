<?php

namespace App\Modules\Quran\Services;

use App\Modules\Academic\Models\Student;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranEnrollment;
use App\Modules\Quran\Models\QuranLevelHistory;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Support\Facades\Schema;

class QuranStudentProfileService
{
    public function forStudent(?Student $student, bool $isParentPortal = false): ?array
    {
        if (!$student) {
            return null;
        }

        // Guard against environments where Quran tables aren't migrated yet
        if (!Schema::hasTable('quran_enrollments')) {
            return null;
        }

        $unit = $student->relationLoaded('unit') ? $student->unit : $student->unit()->first();
        if (!$unit || $unit->isDaycare() || !$unit->hasFeature('quran')) {
            return [
                'enabled' => false,
            ];
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        $setting = QuranSetting::where('unit_id', $unit->id)->first();
        $isReportLocked = (bool) ($setting?->lock_cetak_rapor ?? false);

        // Active enrollment (prefer active academic year, fallback to latest active enrollment)
        $activeEnrollment = null;
        if ($activeYear) {
            $activeEnrollment = QuranEnrollment::with([
                'group.level',
                'group.session',
                'group.teacher',
                'group.assistantTeacher',
                'academicYear',
            ])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('status', QuranEnrollment::STATUS_ACTIVE)
                ->latest('id')
                ->first();
        }

        if (!$activeEnrollment) {
            $activeEnrollment = QuranEnrollment::with([
                'group.level',
                'group.session',
                'group.teacher',
                'group.assistantTeacher',
                'academicYear',
            ])
                ->where('student_id', $student->id)
                ->where('status', QuranEnrollment::STATUS_ACTIVE)
                ->latest('id')
                ->first();
        }

        // Complete promotion / movement timeline across semesters & academic years
        $histories = QuranLevelHistory::with([
            'fromLevel',
            'toLevel',
            'fromGroup.teacher',
            'toGroup.teacher',
            'academicYear',
            'recorder',
        ])
            ->where('student_id', $student->id)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'change_type' => $h->change_type,
                'changed_at' => $h->changed_at?->format('d M Y') ?? '-',
                'academic_year' => $h->academicYear?->name ?? '-',
                'from_level' => $h->fromLevel?->name,
                'to_level' => $h->toLevel?->name ?? '-',
                'from_group' => $h->fromGroup?->name,
                'to_group' => $h->toGroup?->name,
                'from_teacher' => $h->fromGroup?->teacher?->name,
                'to_teacher' => $h->toGroup?->teacher?->name,
                'exam_score' => $h->exam_score,
                'notes' => $h->notes,
                'recorded_by' => $h->recorder?->name ?? 'Sistem',
            ])
            ->values();

        $promotionsTotal = $histories->where('change_type', QuranLevelHistory::TYPE_PROMOTED)->count();
        $promotionsActiveYear = $activeYear
            ? QuranLevelHistory::where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('change_type', QuranLevelHistory::TYPE_PROMOTED)
                ->count()
            : $promotionsTotal;

        // Assessments summary (for parents, hide draft details while lock_cetak_rapor is ON)
        $rawAssessments = QuranAssessment::with(['level', 'group.teacher', 'academicYear'])
            ->where('student_id', $student->id)
            ->orderByDesc('academic_year_id')
            ->orderByRaw("CASE WHEN semester = 'genap' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('id')
            ->get();

        $categoryLabels = [
            'materi' => 'Materi Pokok',
            'surah_pendek' => 'Surah Pendek',
            'hadist' => 'Hadist',
            'doa_harian' => 'Doa Harian',
            'niat_sholat' => 'Niat Sholat & Bersuci',
            'bacaan_sholat' => 'Bacaan Sholat',
        ];

        $assessments = $rawAssessments->map(function (QuranAssessment $a) use ($categoryLabels, $isParentPortal, $isReportLocked) {
            $scores = is_array($a->scores) ? $a->scores : [];
            $gradeCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            $groupedCategories = [];

            foreach ($scores as $entry) {
                $grade = strtoupper(trim((string) ($entry['grade'] ?? '')));
                if (!in_array($grade, ['A', 'B', 'C', 'D'], true)) {
                    continue;
                }
                $gradeCounts[$grade]++;

                $cat = $entry['category'] ?? 'materi';
                if (!isset($groupedCategories[$cat])) {
                    $groupedCategories[$cat] = [
                        'key' => $cat,
                        'label' => $categoryLabels[$cat] ?? ucfirst(str_replace('_', ' ', $cat)),
                        'items' => [],
                    ];
                }
                $groupedCategories[$cat]['items'][] = [
                    'title' => $entry['title'] ?? '-',
                    'grade' => $grade,
                ];
            }

            $totalAssessed = array_sum($gradeCounts);
            $hideDetails = $isParentPortal && $isReportLocked;

            return [
                'id' => $a->id,
                'academic_year_id' => $a->academic_year_id,
                'academic_year' => $a->academicYear?->name ?? '-',
                'semester' => $a->semester,
                'semester_label' => $a->semester === 'genap' ? 'Semester Genap' : 'Semester Ganjil',
                'level_name' => $a->level?->name ?? '-',
                'group_name' => $a->group?->name ?? '-',
                'teacher_name' => $a->group?->teacher?->name ?? '-',
                'assessed_count' => $totalAssessed,
                'grade_counts' => $hideDetails ? null : $gradeCounts,
                'categories' => $hideDetails ? [] : array_values($groupedCategories),
                'teacher_note' => $hideDetails ? null : $a->teacher_note,
                'is_locked_for_parent' => $hideDetails,
                'updated_at' => $a->updated_at?->format('d M Y'),
            ];
        })->values();

        return [
            'enabled' => true,
            'is_report_locked' => $isReportLocked,
            'current' => $activeEnrollment ? [
                'enrollment_id' => $activeEnrollment->id,
                'level_id' => $activeEnrollment->group?->level?->id,
                'level_name' => $activeEnrollment->group?->level?->name ?? '-',
                'level_code' => $activeEnrollment->group?->level?->code ?? '',
                'group_id' => $activeEnrollment->group?->id,
                'group_name' => $activeEnrollment->group?->name ?? '-',
                'room_name' => $activeEnrollment->group?->room_name,
                'session_name' => $activeEnrollment->group?->session?->name,
                'session_time' => ($activeEnrollment->group?->session?->start_time && $activeEnrollment->group?->session?->end_time)
                    ? substr($activeEnrollment->group->session->start_time, 0, 5) . '–' . substr($activeEnrollment->group->session->end_time, 0, 5)
                    : null,
                'session_days' => $activeEnrollment->group?->session?->days,
                'teacher_name' => $activeEnrollment->group?->teacher?->name ?? '-',
                'assistant_name' => $activeEnrollment->group?->assistantTeacher?->name,
                'academic_year' => $activeEnrollment->academicYear?->name ?? '-',
                'started_at' => $activeEnrollment->started_at?->format('d M Y'),
            ] : null,
            'stats' => [
                'promotions_total' => $promotionsTotal,
                'promotions_active_year' => $promotionsActiveYear,
                'assessments_count' => $assessments->count(),
            ],
            'histories' => $histories,
            'assessments' => $assessments,
        ];
    }
}
