<?php

namespace App\Modules\Academic\Services\Promotion;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassPromotion;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Academic\Exceptions\PromotionValidationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionExecutionService
{
    protected PromotionValidationEngine $validationEngine;

    public function __construct(PromotionValidationEngine $validationEngine)
    {
        $this->validationEngine = $validationEngine;
    }

    /**
     * Execute class promotions for multiple students.
     *
     * @param int $fromClassroomId
     * @param int $fromAcademicYearId
     * @param int|null $toClassroomId
     * @param int $toAcademicYearId
     * @param array $promotions
     * @param int $executorId
     * @return int
     * @throws PromotionValidationException|\Exception
     */
    public function executePromotion(
        int $fromClassroomId,
        int $fromAcademicYearId,
        ?int $toClassroomId,
        int $toAcademicYearId,
        array $promotions,
        int $executorId
    ): int {
        $startTime = microtime(true);
        $executor = User::findOrFail($executorId);
        $fromClassroom = Classroom::findOrFail($fromClassroomId);
        $toClassroom = $toClassroomId ? Classroom::find($toClassroomId) : null;
        $fromAcademicYear = AcademicYear::findOrFail($fromAcademicYearId);
        $toAcademicYear = AcademicYear::findOrFail($toAcademicYearId);

        $totalStudents = count($promotions);

        // 1. Log: Promotion Started
        activity()
            ->causedBy($executor)
            ->withProperties([
                'academic_year' => "{$fromAcademicYear->name} -> {$toAcademicYear->name}",
                'from_classroom' => $fromClassroom->name,
                'to_classroom' => $toClassroom?->name ?? 'N/A',
                'total_students' => $totalStudents,
                'executor' => $executor->name,
            ])
            ->log('Class Promotion: Started');

        DB::beginTransaction();
        try {
            // 2. Validate students who are being promoted or graduating
            $studentIdsToValidate = collect($promotions)
                ->whereIn('status', ['naik', 'lulus'])
                ->pluck('student_id')
                ->toArray();

            $validationErrors = $this->validationEngine->validateMultiple($studentIdsToValidate);
            if (!empty($validationErrors)) {
                throw new PromotionValidationException($validationErrors);
            }

            $now = Carbon::now();
            $eligibleCount = 0;
            $blockedCount = 0;

            // 3. Process promotions
            foreach ($promotions as $promo) {
                $studentId = $promo['student_id'];
                $status = $promo['status'];
                $notes = $promo['notes'] ?? null;

                $student = Student::findOrFail($studentId);

                // Validation summary to record in history
                $valSummary = 'Eligible';
                if ($status === 'tinggal') {
                    $valSummary = 'Blocked / Kept Back';
                    $blockedCount++;
                } else {
                    $eligibleCount++;
                }

                // Create promotion history record
                ClassPromotion::create([
                    'student_id' => $studentId,
                    'from_classroom_id' => $fromClassroomId,
                    'to_classroom_id' => $status === 'naik' ? $toClassroomId : null,
                    'from_academic_year_id' => $fromAcademicYearId,
                    'to_academic_year_id' => $toAcademicYearId,
                    'status' => $status,
                    'notes' => $notes,
                    'promoted_by' => $executorId,
                    'promoted_at' => $now,
                    'validation_summary' => $valSummary,
                    'is_rolled_back' => false,
                ]);

                // Update student attributes based on status
                if ($status === 'naik' && $toClassroom) {
                    $student->update([
                        'classroom_id' => $toClassroom->id,
                        'academic_year_id' => $toAcademicYearId,
                    ]);
                } elseif ($status === 'tinggal') {
                    $student->update([
                        'academic_year_id' => $toAcademicYearId,
                    ]);
                } elseif ($status === 'lulus') {
                    $student->update([
                        'classroom_id' => null,
                        'academic_year_id' => $toAcademicYearId,
                    ]);
                } else {
                    // pindah / keluar
                    $student->update([
                        'classroom_id' => null,
                    ]);
                }
            }

            DB::commit();

            $duration = round(microtime(true) - $startTime, 4);

            // 4. Log: Promotion Completed
            activity()
                ->causedBy($executor)
                ->withProperties([
                    'academic_year' => "{$fromAcademicYear->name} -> {$toAcademicYear->name}",
                    'from_classroom' => $fromClassroom->name,
                    'to_classroom' => $toClassroom?->name ?? 'N/A',
                    'total_students' => $totalStudents,
                    'eligible' => $eligibleCount,
                    'blocked' => $blockedCount,
                    'duration_seconds' => $duration,
                    'executor' => $executor->name,
                ])
                ->log('Class Promotion: Completed');

            return $totalStudents;

        } catch (PromotionValidationException $e) {
            DB::rollBack();
            $duration = round(microtime(true) - $startTime, 4);

            // Log: Promotion Failed due to validation
            activity()
                ->causedBy($executor)
                ->withProperties([
                    'academic_year' => "{$fromAcademicYear->name} -> {$toAcademicYear->name}",
                    'from_classroom' => $fromClassroom->name,
                    'to_classroom' => $toClassroom?->name ?? 'N/A',
                    'total_students' => $totalStudents,
                    'errors' => $e->getValidationErrors(),
                    'duration_seconds' => $duration,
                    'executor' => $executor->name,
                ])
                ->log('Class Promotion: Failed');

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $duration = round(microtime(true) - $startTime, 4);

            // Log: Promotion Failed due to general exception
            activity()
                ->causedBy($executor)
                ->withProperties([
                    'academic_year' => "{$fromAcademicYear->name} -> {$toAcademicYear->name}",
                    'from_classroom' => $fromClassroom->name,
                    'to_classroom' => $toClassroom?->name ?? 'N/A',
                    'total_students' => $totalStudents,
                    'error_message' => $e->getMessage(),
                    'duration_seconds' => $duration,
                    'executor' => $executor->name,
                ])
                ->log('Class Promotion: Failed');

            throw $e;
        }
    }

    /**
     * Rollback a class promotion.
     *
     * @param ClassPromotion $promotion
     * @param int $executorId
     * @return bool
     * @throws \Exception
     */
    public function rollbackPromotion(ClassPromotion $promotion, int $executorId): bool
    {
        $executor = User::findOrFail($executorId);

        // Check if already rolled back
        if ($promotion->is_rolled_back) {
            throw new \Exception('Promosi ini sudah dibatalkan sebelumnya.');
        }

        // Verify if this is the latest promotion for the student
        $latestPromotion = ClassPromotion::where('student_id', $promotion->student_id)
            ->orderBy('promoted_at', 'desc')
            ->first();

        if (!$latestPromotion || $latestPromotion->id !== $promotion->id) {
            throw new \Exception('Hanya promosi terakhir yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $student = Student::findOrFail($promotion->student_id);

            // Restore student's previous state
            $student->update([
                'classroom_id' => $promotion->from_classroom_id,
                'academic_year_id' => $promotion->from_academic_year_id,
            ]);

            // Mark the promotion record as rolled back (don't delete)
            $promotion->update([
                'is_rolled_back' => true,
                'rolled_back_by' => $executorId,
                'rolled_back_at' => Carbon::now(),
            ]);

            DB::commit();

            // Log: Rollback Executed
            activity()
                ->causedBy($executor)
                ->performedOn($promotion)
                ->withProperties([
                    'student_name' => $student->full_name,
                    'from_classroom' => $promotion->fromClassroom?->name ?? 'N/A',
                    'to_classroom' => $promotion->toClassroom?->name ?? 'N/A',
                    'executor' => $executor->name,
                ])
                ->log('Class Promotion: Rollback Executed');

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            // Log: Rollback Failed
            activity()
                ->causedBy($executor)
                ->performedOn($promotion)
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'executor' => $executor->name,
                ])
                ->log('Class Promotion: Rollback Failed');

            throw $e;
        }
    }
}
