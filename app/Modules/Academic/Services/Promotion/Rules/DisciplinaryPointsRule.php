<?php

namespace App\Modules\Academic\Services\Promotion\Rules;

use App\Modules\Academic\Models\Student;
use App\Modules\Counseling\Models\Violation;
use App\Modules\Yayasan\Models\SystemSetting; // SystemSetting model

class DisciplinaryPointsRule implements PromotionValidationRule
{
    /**
     * Validate the student has not exceeded disciplinary points limit.
     *
     * @param Student $student
     * @param array $context
     * @return RuleResult
     */
    public function validate(Student $student, array $context = []): RuleResult
    {
        // Get threshold from system_settings if exists, otherwise default to 100
        $threshold = 100;
        
        try {
            // Check system settings for custom BK threshold
            $setting = SystemSetting::where('key', 'bk_max_points')->first();
            if ($setting && is_numeric($setting->value)) {
                $threshold = (int) $setting->value;
            }
        } catch (\Exception $e) {
            // Fallback to default threshold if table/model does not exist or fails
        }

        $studentViolations = isset($context['violations'])
            ? ($context['violations'][$student->id] ?? collect())
            : Violation::where('student_id', $student->id)->get();

        $totalPoints = $studentViolations->sum('points');

        if ($totalPoints >= $threshold) {
            return RuleResult::fail(
                'DisciplinaryPointsRule',
                "Akumulasi skor pelanggaran siswa ({$totalPoints} poin) telah mencapai atau melebihi batas toleransi ({$threshold} poin)"
            );
        }

        if ($totalPoints >= ($threshold * 0.75)) {
            return RuleResult::warning(
                'DisciplinaryPointsRule',
                "Skor pelanggaran siswa mendekati batas toleransi ({$totalPoints} dari {$threshold} poin)"
            );
        }

        return RuleResult::pass('DisciplinaryPointsRule');
    }
}
