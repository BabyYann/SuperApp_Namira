<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses dashboard keuangan.');
        }

        $unitId = session('active_unit_id');

        // 1. Summary Stats
        $totalBills = StudentBill::whereHas('student', fn($q) => $q->where('unit_id', $unitId))
            ->sum('final_amount');
            
        $totalPaid = StudentBill::whereHas('student', fn($q) => $q->where('unit_id', $unitId))
            ->sum('paid_amount');
            
        $totalArrears = $totalBills - $totalPaid;

        // Income This Month
        $incomeThisMonth = Transaction::whereHas('student', fn($q) => $q->where('unit_id', $unitId))
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        // 2. Monthly Trend (Last 12 Months)
        $minDate = now()->subMonths(11)->startOfMonth();
        $monthlyIncome = Transaction::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->whereHas('student', fn($q) => $q->where('unit_id', $unitId))
            ->where('transaction_date', '>=', $minDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->month => $item->total];
            });

        // Fill gaps
        $chartData = [];
        $chartLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $chartLabels[] = $date->translatedFormat('M Y');
            $chartData[] = $monthlyIncome[$key] ?? 0;
        }

        // 3. Recent Transactions
        $recentTransactions = Transaction::with(['student.classroom', 'financeAccount'])
            ->whereHas('student', fn($q) => $q->where('unit_id', $unitId))
            ->latest('transaction_date')
            ->take(5)
            ->get();

        return Inertia::render('Finance/Dashboard', [
            'stats' => [
                'total_bills' => $totalBills,
                'total_paid' => $totalPaid,
                'total_arrears' => $totalArrears,
                'income_this_month' => $incomeThisMonth,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ],
            'recent_transactions' => $recentTransactions
        ]);
    }
}
