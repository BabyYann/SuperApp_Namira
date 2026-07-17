<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Models\FinanceType;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceApiController extends Controller
{
    private function getUnitId(Request $request): ?int
    {
        $sessionUnit = session('active_unit_id');
        if ($sessionUnit) return (int) $sessionUnit;

        $user = $request->user();
        $firstTeamId = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->whereNotNull('team_id')
            ->value('team_id');

        return $firstTeamId ? (int) $firstTeamId : null;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $unitId = $this->getUnitId($request);
        $user = $request->user();
        $isGlobalAdmin = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        $billQuery = StudentBill::query();
        $transactionQuery = Transaction::query();

        if ($unitId && !$isGlobalAdmin) {
            $billQuery->whereHas('student', fn ($q) => $q->where('unit_id', $unitId));
            $transactionQuery->whereHas('student', fn ($q) => $q->where('unit_id', $unitId));
        }

        $totalBills = (clone $billQuery)->sum('final_amount');
        $totalPaid = (clone $billQuery)->sum('paid_amount');
        $totalArrears = $totalBills - $totalPaid;

        $incomeThisMonth = (clone $transactionQuery)
            ->whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $monthlyIncome = Transaction::query()
            ->when($unitId && !$isGlobalAdmin, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('unit_id', $unitId)))
            ->where('transaction_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as month_key, SUM(amount) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key')
            ->toArray();

        $chartLabels = [];
        $chartData = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $label = now()->subMonths($i)->format('M Y');
            $chartLabels[] = $label;
            $chartData[] = (float) ($monthlyIncome[$key] ?? 0);
        }

        $recentTransactions = Transaction::with(['student:id,full_name', 'student.classroom:id,name', 'financeAccount:id,bank_name,account_number'])
            ->when($unitId && !$isGlobalAdmin, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('unit_id', $unitId)))
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'code' => $t->transaction_code,
                'amount' => (float) $t->amount,
                'payment_method' => $t->payment_method,
                'date' => $t->transaction_date?->format('Y-m-d H:i'),
                'student' => $t->student?->full_name,
                'classroom' => $t->student->classroom?->name ?? '-',
                'bank' => $t->financeAccount?->bank_name,
            ]);

        return response()->json([
            'stats' => [
                'total_bills' => (float) $totalBills,
                'total_paid' => (float) $totalPaid,
                'total_arrears' => (float) $totalArrears,
                'income_this_month' => (float) $incomeThisMonth,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData,
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function bills(Request $request): JsonResponse
    {
        $unitId = $this->getUnitId($request);
        $user = $request->user();
        $isGlobalAdmin = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        $query = StudentBill::with(['student:id,full_name,nis', 'financeType:id,name'])
            ->when($unitId && !$isGlobalAdmin, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('unit_id', $unitId)));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                  ->orWhereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bills = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $bills->getCollection(),
            'current_page' => $bills->currentPage(),
            'last_page' => $bills->lastPage(),
            'total' => $bills->total(),
            'per_page' => $bills->perPage(),
        ]);
    }

    public function billDetail(Request $request, int $id): JsonResponse
    {
        $bill = StudentBill::with([
            'student:id,full_name,nis,classroom_id',
            'student.classroom:id,name',
            'financeType:id,name',
            'transactions:id,transaction_code,amount,transaction_date,payment_method',
        ])->findOrFail($id);

        return response()->json($bill);
    }

    public function transactions(Request $request): JsonResponse
    {
        $unitId = $this->getUnitId($request);
        $user = $request->user();
        $isGlobalAdmin = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);

        $query = Transaction::with(['student:id,full_name,nis', 'financeAccount:id,bank_name,account_number'])
            ->when($unitId && !$isGlobalAdmin, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('unit_id', $unitId)));

        $transactions = $query->latest('transaction_date')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $transactions->getCollection(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'total' => $transactions->total(),
            'per_page' => $transactions->perPage(),
        ]);
    }
}
