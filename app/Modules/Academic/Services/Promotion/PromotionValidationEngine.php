<?php

namespace App\Modules\Academic\Services\Promotion;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Services\Promotion\Rules\PromotionValidationRule;
use App\Modules\Academic\Services\Promotion\Rules\OutstandingBillsRule;
use App\Modules\Academic\Services\Promotion\Rules\DisciplinaryPointsRule;

class PromotionValidationEngine
{
    /**
     * List of registered validation rules.
     *
     * @var array<PromotionValidationRule>
     */
    protected array $rules = [];

    public function __construct()
    {
        // Register default concrete rules
        $this->registerRule(new OutstandingBillsRule());
        $this->registerRule(new DisciplinaryPointsRule());
    }

    /**
     * Register a new validation rule to the engine.
     *
     * @param PromotionValidationRule $rule
     * @return self
     */
    public function registerRule(PromotionValidationRule $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Validate a single student.
     * Returns array of error messages.
     *
     * @param Student $student
     * @param array $context
     * @return array<string>
     */
    public function validateStudent(Student $student, array $context = []): array
    {
        $errors = [];

        foreach ($this->rules as $rule) {
            $result = $rule->validate($student, $context);
            if (!$result->isValid()) {
                $errors[] = $result->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Validate multiple student IDs.
     * Returns a map of student ID => error messages.
     *
     * @param array<int> $studentIds
     * @param array $context
     * @return array<int, array>
     */
    public function validateMultiple(array $studentIds, array $context = []): array
    {
        $students = Student::whereIn('id', $studentIds)->get();
        $validationMap = [];

        foreach ($students as $student) {
            $errors = $this->validateStudent($student, $context);
            if (!empty($errors)) {
                $validationMap[$student->id] = [
                    'student_name' => $student->full_name,
                    'errors' => $errors
                ];
            }
        }

        return $validationMap;
    }

    /**
     * Validate a single student and return detailed status and violations.
     *
     * @param Student $student
     * @param array $context
     * @return array
     */
    public function validateStudentDetailed(Student $student, array $context = []): array
    {
        $status = 'eligible';
        $violations = [];

        foreach ($this->rules as $rule) {
            $result = $rule->validate($student, $context);
            if (!$result->isValid()) {
                $status = 'blocked';
                $code = ($rule instanceof OutstandingBillsRule) ? 'OUTSTANDING_BILL' : 'DISCIPLINARY_POINT';
                $violations[] = [
                    'code' => $code,
                    'message' => $result->getMessage()
                ];
            } elseif ($result->isWarning()) {
                // If not already blocked, status becomes warning
                if ($status !== 'blocked') {
                    $status = 'warning';
                }
                $code = ($rule instanceof OutstandingBillsRule) ? 'BILL_WARNING' : 'BK_WARNING';
                $violations[] = [
                    'code' => $code,
                    'message' => $result->getMessage()
                ];
            }
        }

        return [
            'status' => $status,
            'violations' => $violations
        ];
    }
}
