<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Category;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\MaintenanceLog;
use App\Modules\Sarpar\Models\Loan;
use App\Modules\Sarpar\Models\UsageLog;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        // Total counts
        $totalAssets = Inventory::where('unit_id', $unitId)
            ->where('item_type', 'asset')
            ->count();
        
        $totalConsumables = Inventory::where('unit_id', $unitId)
            ->where('item_type', 'consumable')
            ->count();

        $totalItems = $totalAssets + $totalConsumables;

        // Total value
        $totalValue = Inventory::where('unit_id', $unitId)
            ->sum(DB::raw('quantity * COALESCE(unit_price, 0)'));

        // Low stock items
        $lowStockItems = Inventory::where('unit_id', $unitId)
            ->where('item_type', 'consumable')
            ->whereNotNull('min_stock')
            ->whereRaw('quantity <= min_stock')
            ->with('category')
            ->limit(10)
            ->get();

        // Items by condition
        $conditionStats = Inventory::where('unit_id', $unitId)
            ->select('condition', DB::raw('count(*) as count'))
            ->groupBy('condition')
            ->pluck('count', 'condition')
            ->toArray();

        // Items by category
        $categoryStats = Inventory::where('unit_id', $unitId)
            ->join('sarpar_categories', 'sarpar_inventories.category_id', '=', 'sarpar_categories.id')
            ->select('sarpar_categories.name as category', DB::raw('count(*) as count'))
            ->groupBy('sarpar_categories.id', 'sarpar_categories.name')
            ->get();

        // Items by funding source
        $fundingStats = Inventory::where('unit_id', $unitId)
            ->select('funding_source', DB::raw('count(*) as count'), DB::raw('SUM(quantity * COALESCE(unit_price, 0)) as value'))
            ->groupBy('funding_source')
            ->get();

        // Status counts
        $statusStats = Inventory::where('unit_id', $unitId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Pending maintenance
        $pendingMaintenance = MaintenanceLog::whereHas('inventory', fn($q) => $q->where('unit_id', $unitId))
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['inventory', 'reporter'])
            ->orderBy('reported_date', 'desc')
            ->limit(5)
            ->get();

        // Active loans
        $activeLoans = Loan::whereHas('inventory', fn($q) => $q->where('unit_id', $unitId))
            ->where('status', 'borrowed')
            ->with(['inventory', 'borrower'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Recent activity (last 10 usage logs)
        $recentUsage = UsageLog::whereHas('inventory', fn($q) => $q->where('unit_id', $unitId))
            ->with(['inventory', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Sarpar/Dashboard', [
            'stats' => [
                'totalItems' => $totalItems,
                'totalAssets' => $totalAssets,
                'totalConsumables' => $totalConsumables,
                'totalValue' => $totalValue,
                'lowStockCount' => $lowStockItems->count(),
                'pendingMaintenanceCount' => $pendingMaintenance->count(),
                'activeLoansCount' => $activeLoans->count(),
            ],
            'lowStockItems' => $lowStockItems,
            'conditionStats' => $conditionStats,
            'categoryStats' => $categoryStats,
            'fundingStats' => $fundingStats,
            'statusStats' => $statusStats,
            'pendingMaintenance' => $pendingMaintenance,
            'activeLoans' => $activeLoans,
            'recentUsage' => $recentUsage,
        ]);
    }
}
