<?php

namespace App\Modules\Academic\Services\Promotion\Rules;

use App\Modules\Academic\Models\Student;

interface PromotionValidationRule
{
    /**
     * Validate the student for promotion.
     *
     * @param Student $student
     * @param array $context
     * @return RuleResult
     */
    public function validate(Student $student, array $context = []): RuleResult;
}
