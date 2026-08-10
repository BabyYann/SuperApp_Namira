<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareAttendance;
use App\Modules\Daycare\Models\DaycareDailyLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DaycareReportController extends Controller
{
    public function dailyReport(Student $student, Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $student->load(['daycareProfile', 'unit']);

        $attendance = DaycareAttendance::with('authorizedPickup')
            ->where('student_id', $student->id)
            ->where('date', $date)
            ->first();

        $logs = DaycareDailyLog::with('caregiver')
            ->where('student_id', $student->id)
            ->where('date', $date)
            ->orderBy('log_time', 'asc')
            ->get();

        // Calculate Totals & Summaries for 1-Page Parent Report
        $napStart = $logs->firstWhere('category', 'nap_start');
        $napEnd = $logs->firstWhere('category', 'nap_end');

        $totalNapMinutes = 0;
        if ($napStart && $napEnd) {
            $start = \Carbon\Carbon::parse($napStart->log_time);
            $end = \Carbon\Carbon::parse($napEnd->log_time);
            $totalNapMinutes = max(0, $end->diffInMinutes($start));
        }

        $mealsCount = $logs->whereIn('category', ['meal', 'snack'])->count();
        $milkTotalMl = $logs->where('category', 'milk')->sum('amount_ml');

        return Inertia::render('Daycare/Reports/DailyReport', [
            'student' => $student,
            'date' => $date,
            'attendance' => $attendance,
            'logs' => $logs,
            'summary' => [
                'total_nap_minutes' => $totalNapMinutes,
                'meals_count' => $mealsCount,
                'milk_total_ml' => $milkTotalMl,
            ],
        ]);
    }
}
