<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UniversityDestination;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use App\Modules\Sarpar\Models\SarparAsset;
use App\Modules\Employee\Models\AttendanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PembinaDashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedUnitId = $request->query('unit_id', session('active_unit_id'));
        $activeUnits = Unit::all();

        // 1. Student Statistics
        $studentQuery = Student::query();
        if ($selectedUnitId) {
            $studentQuery->where('unit_id', $selectedUnitId);
        }
        $totalStudents = $studentQuery->count();

        $studentsPerUnit = Unit::withCount('students')->get()->map(function ($unit) {
            return [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'count' => $unit->students_count,
            ];
        });

        // 2. Staff & Educator Statistics
        $totalTeachers = User::role(['teacher', 'wali_kelas'])->count();
        $totalStaff = User::role(['staff_yayasan', 'staff_unit', 'admin_unit', 'staff_admin_keuangan', 'finance'])->count();
        $totalUsers = User::count();

        // 3. Finance Executive Overview (SPP Collection & Arrears)
        $totalPaidBills = 0;
        $totalUnpaidBills = 0;
        $paymentPercentage = 100;

        if (DB::getSchemaBuilder()->hasTable('student_bills')) {
            $billQuery = DB::table('student_bills');
            if ($selectedUnitId) {
                $billQuery->where('unit_id', $selectedUnitId);
            }

            $totalPaidBills = (clone $billQuery)->where('status', 'paid')->sum('amount') ?? 0;
            $totalUnpaidBills = (clone $billQuery)->whereIn('status', ['unpaid', 'partially_paid'])->sum('amount') ?? 0;
            $totalBills = $totalPaidBills + $totalUnpaidBills;
            $paymentPercentage = $totalBills > 0 ? round(($totalPaidBills / $totalBills) * 100, 1) : 100;
        }

        // Monthly trends for cashflow
        $recentTransactions = [];
        if (DB::getSchemaBuilder()->hasTable('bank_transactions')) {
            $recentTransactions = DB::table('bank_transactions')
                ->select('type', DB::raw('SUM(amount) as total_amount'), DB::raw('MONTH(transaction_date) as month_num'))
                ->whereYear('transaction_date', date('Y'))
                ->groupBy('type', DB::raw('MONTH(transaction_date)'))
                ->get();
        }

        // 4. Asset / Sarpar Overview
        $totalAssetCount = 0;
        $totalAssetValue = 0;
        if (DB::getSchemaBuilder()->hasTable('sarpar_assets')) {
            $totalAssetCount = SarparAsset::count();
            $totalAssetValue = SarparAsset::sum('purchase_price') ?? 0;
        }

        // 5. University Destinations & Alumni Accomplishments
        $topDestinations = [];
        if (DB::getSchemaBuilder()->hasTable('university_destinations')) {
            $topDestinations = UniversityDestination::with('unit')
                ->where('is_active', true)
                ->latest('visit_date')
                ->take(6)
                ->get();
        }

        // 6. Employee Today Attendance Overview
        $todayAttendanceCount = 0;
        if (DB::getSchemaBuilder()->hasTable('attendance_logs')) {
            $today = date('Y-m-d');
            $todayAttendanceCount = AttendanceLog::whereDate('scanned_at', $today)->distinct('user_id')->count('user_id');
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
