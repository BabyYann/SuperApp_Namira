<?php

namespace App\Modules\Academic\Services\Promotion\Rules;

use App\Modules\Academic\Models\Student;
use App\Modules\Finance\Models\StudentBill;

class OutstandingBillsRule implements PromotionValidationRule
{
    /**
     * Validate the student has no outstanding/unpaid bills.
     *
     * @param Student $student
     * @param array $context
     * @return RuleResult
     */
    public function validate(Student $student, array $context = []): RuleResult
    {
        $unpaidBills = isset($context['bills'])
            ? ($context['bills'][$student->id] ?? collect())
            : StudentBill::where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->get();

        if ($unpaidBills->isNotEmpty()) {
            $billDetails = $unpaidBills->map(function ($bill) {
                return "{$bill->description} (Sisa: Rp " . number_format($bill->final_amount - $bill->paid_amount, 0, ',', '.') . ")";
            })->join(', ');

            return RuleResult::fail(
                'OutstandingBillsRule',
                "Siswa memiliki tagihan tertunggak: {$billDetails}"
            );
        }

        return RuleResult::pass('OutstandingBillsRule');
    }
}
