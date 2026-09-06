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

        $unitName = $student->unit->name ?? 'Namira Daycare';
        $formattedDate = \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y');
        $checkinTime = $attendance && $attendance->check_in_time ? substr($attendance->check_in_time, 0, 5) : '-';
        $checkoutTime = $attendance && $attendance->check_out_time ? substr($attendance->check_out_time, 0, 5) : '-';

        $waMessage = "Assalamu'alaikum Ayah/Bunda dari *{$student->full_name}*,\n\n"
            . "Berikut ringkasan harian ananda di Daycare pada *{$formattedDate}*:\n"
            . "🕒 Kehadiran: Datang {$checkinTime} WIB | Pulang {$checkoutTime} WIB\n"
            . "😴 Tidur Siang: {$totalNapMinutes} menit\n"
            . "🍼 Konsumsi Susu: {$milkTotalMl} ml\n"
            . "🍱 Jadwal Makan/Snack: {$mealsCount} kali\n\n"
            . "Terima kasih atas kepercayaannya.\n-- *{$unitName}*";

        $waLink = !empty($student->parent_phone) 
            ? \App\Helpers\WhatsAppHelper::generateLink($student->parent_phone, $waMessage) 
            : null;

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
            'wa_link' => $waLink,
        ]);
    }
}
