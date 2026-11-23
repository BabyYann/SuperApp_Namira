<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UniversityDestination;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use App\Modules\Sarpar\Models\SarparAsset;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Employee\Models\AttendanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PembinaDashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedUnitId = $request->query('unit_id', session('active_unit_id'));
        $activeUnits = $request->attributes->get('resolved_all_units') ?? Unit::orderBy('id')->get();

        // 1. Student Statistics (single GROUP BY instead of extra Unit::withCount + Student::count)
        $studentCountsByUnit = DB::table('students')
            ->select('unit_id', DB::raw('COUNT(*) as total'))
            ->groupBy('unit_id')
            ->pluck('total', 'unit_id');

        $totalStudents = $selectedUnitId
            ? (int) ($studentCountsByUnit[(int) $selectedUnitId] ?? 0)
            : (int) $studentCountsByUnit->sum();

        $studentsPerUnit = $activeUnits->map(function ($unit) use ($studentCountsByUnit) {
            return [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'count' => (int) ($studentCountsByUnit[$unit->id] ?? 0),
            ];
        })->values();

        // 2. Staff & Educator Statistics (single aggregate query instead of 7x Spatie role lookups)
        $roleStats = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN r.name IN ('teacher', 'wali_kelas') THEN mhr.model_id END) as teachers_cnt,
                COUNT(DISTINCT CASE WHEN r.name IN ('staff_yayasan', 'staff_unit', 'admin_unit', 'staff_admin_keuangan', 'finance') THEN mhr.model_id END) as staff_cnt
            ")
            ->first();

        $totalTeachers = (int) ($roleStats->teachers_cnt ?? 0);
        $totalStaff = (int) ($roleStats->staff_cnt ?? 0);
        $totalUsers = User::count();

        // 3. Finance Executive Overview (1 aggregate query instead of 2 cloned SUM queries)
        $totalPaidBills = 0;
        $totalUnpaidBills = 0;
        $paymentPercentage = 100;

        try {
            $billQuery = StudentBill::query();
            if ($selectedUnitId) {
                $billQuery->whereHas('student', fn($q) => $q->where('unit_id', $selectedUnitId));
            }

            $billAgg = $billQuery->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_sum, COALESCE(SUM(final_amount), 0) as final_sum')->first();
            $totalPaidBills = (float) ($billAgg->paid_sum ?? 0);
            $totalBillsAmount = (float) ($billAgg->final_sum ?? 0);
            $totalUnpaidBills = max(0, $totalBillsAmount - $totalPaidBills);
            $paymentPercentage = $totalBillsAmount > 0 ? round(($totalPaidBills / $totalBillsAmount) * 100, 1) : 100;
        } catch (\Throwable $e) {
            // Fallback if table doesn't exist
        }

        // Monthly trends for cashflow
        $recentTransactions = [];
        try {
            $trxQuery = Transaction::query();
            if ($selectedUnitId) {
                $trxQuery->whereHas('student', fn($q) => $q->where('unit_id', $selectedUnitId));
            }
            $recentTransactions = $trxQuery->latest('created_at')->take(5)->get();
        } catch (\Throwable $e) {
            // Fallback if table doesn't exist
        }

        // 4. Asset / Sarpar Overview
        $totalAssetCount = 0;
        $totalAssetValue = 0;
        try {
            $totalAssetCount = SarparAsset::count();
            $totalAssetValue = SarparAsset::sum('purchase_price') ?? 0;
        } catch (\Throwable $e) {
            // Fallback
        }

        // 5. University Destinations & Alumni Accomplishments
        $topDestinations = [];
        try {
            $topDestinations = UniversityDestination::with('unit')
                ->where('is_active', true)
                ->latest('visit_date')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            // Fallback
        }

        // 6. Employee Today Attendance Overview
        $todayAttendanceCount = 0;
        try {
            $today = date('Y-m-d');
            $todayAttendanceCount = AttendanceLog::whereDate('scanned_at', $today)->distinct('user_id')->count('user_id');
        } catch (\Throwable $e) {
            // Fallback
        }
        $attendancePercentage = $totalUsers > 0 ? round(($todayAttendanceCount / max($totalUsers, 1)) * 100, 1) : 0;

        return Inertia::render('Yayasan/Pembina/Dashboard', [
            'activeUnits' => $activeUnits,
            'selectedUnitId' => $selectedUnitId,
            'kpi' => [
                'totalStudents' => $totalStudents,
                'studentsPerUnit' => $studentsPerUnit,
                'totalTeachers' => $totalTeachers,
                'totalStaff' => $totalStaff,
                'totalPaidBills' => $totalPaidBills,
                'totalUnpaidBills' => $totalUnpaidBills,
                'paymentPercentage' => $paymentPercentage,
                'totalAssetCount' => $totalAssetCount,
                'totalAssetValue' => $totalAssetValue,
                'todayAttendanceCount' => $todayAttendanceCount,
                'attendancePercentage' => $attendancePercentage,
            ],
            'topDestinations' => $topDestinations,
            'recentTransactions' => $recentTransactions,
            'pembinaInfo' => [
                'name' => 'Nabila Faza, S.E',
                'email' => 'nabilahfaza28@gmail.com',
                'role' => 'Pembina Yayasan',
            ]
        ]);
    }
}
