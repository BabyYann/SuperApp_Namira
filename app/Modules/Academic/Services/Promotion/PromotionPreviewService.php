<?php

namespace App\Modules\Academic\Services\Promotion;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Counseling\Models\Violation;

class PromotionPreviewService
{
    protected PromotionValidationEngine $validationEngine;

    public function __construct(PromotionValidationEngine $validationEngine)
    {
        $this->validationEngine = $validationEngine;
    }

    /**
     * Generate promotion preview data and summary stats.
     *
     * @param int $fromClassroomId
     * @param int $fromAcademicYearId
     * @return array
     */
    public function getPreviewData(int $fromClassroomId, int $fromAcademicYearId): array
    {
        $fromClassroom = Classroom::findOrFail($fromClassroomId);

        // Get students in source classroom and academic year
        $students = Student::where('classroom_id', $fromClassroomId)
            ->where('academic_year_id', $fromAcademicYearId)
            ->orderBy('full_name')
            ->get();

        $studentIds = $students->pluck('id')->toArray();

        // Avoid N+1 queries by pre-fetching bills and violations for all student IDs
        $bills = StudentBill::whereIn('student_id', $studentIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->groupBy('student_id');

        $violations = Violation::whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $context = [
            'bills' => $bills,
            'violations' => $violations
        ];

        $studentData = [];
        $totalStudents = count($students);
        $eligibleStudents = 0;
        $blockedStudents = 0;
        $warningStudents = 0;
        $blockedByBill = 0;
        $blockedByBk = 0;
        $blockedMultipleReason = 0;

        foreach ($students as $student) {
            $validation = $this->validationEngine->validateStudentDetailed($student, $context);
            $status = $validation['status'];
            $studentViolations = $validation['violations'];

            if ($status === 'eligible') {
                $eligibleStudents++;
            } elseif ($status === 'blocked') {
                $blockedStudents++;

                $hasBill = false;
                $hasBk = false;
                foreach ($studentViolations as $v) {
                    if ($v['code'] === 'OUTSTANDING_BILL') {
                        $hasBill = true;
                    }
                    if ($v['code'] === 'DISCIPLINARY_POINT') {
                        $hasBk = true;
                    }
                }

                if ($hasBill && $hasBk) {
                    $blockedMultipleReason++;
                    $blockedByBill++;
                    $blockedByBk++;
                } elseif ($hasBill) {
                    $blockedByBill++;
                } elseif ($hasBk) {
                    $blockedByBk++;
                }
            } elseif ($status === 'warning') {
                $warningStudents++;
            }

            $studentData[] = [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'nama' => $student->full_name,
                'classroom' => $fromClassroom->name,
                'status' => $status,
                'violations' => $studentViolations
            ];
        }

        $summary = [
            'total_students' => $totalStudents,
            'eligible_students' => $eligibleStudents,
            'blocked_students' => $blockedStudents,
            'warning_students' => $warningStudents,
            'blocked_by_bill' => $blockedByBill,
            'blocked_by_bk' => $blockedByBk,
            'blocked_multiple_reason' => $blockedMultipleReason
        ];

        return [
            'students' => $studentData,
            'summary' => $summary
        ];
    }
}
