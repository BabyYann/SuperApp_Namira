<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\Category;
use App\Modules\Sarpar\Models\Room;
use App\Modules\Sarpar\Models\Loan;
use App\Modules\Sarpar\Models\MaintenanceLog;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SarparApiController extends Controller
{
    use HasUnitScope;

    public function dashboard(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $itemQuery = Inventory::where('unit_id', $unitId);

        $totalAssets = (clone $itemQuery)->where('item_type', 'asset')->count();
        $totalConsumables = (clone $itemQuery)->where('item_type', 'consumable')->count();
        $totalValue = (clone $itemQuery)->sum(\DB::raw('quantity * unit_price'));
        $lowStockCount = (clone $itemQuery)->where('item_type', 'consumable')
            ->whereRaw('quantity <= min_stock')->count();

        $pendingMaintenance = MaintenanceLog::where('status', 'pending')
            ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId))
            ->with('inventory:id,name')
            ->limit(5)
            ->get();

        $activeLoans = Loan::where('status', 'borrowed')
            ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId))
            ->with(['inventory:id,name', 'borrower:id,name'])
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_assets' => $totalAssets,
                'total_consumables' => $totalConsumables,
                'total_items' => $totalAssets + $totalConsumables,
                'total_value' => (float) $totalValue,
                'low_stock_count' => $lowStockCount,
                'pending_maintenance_count' => MaintenanceLog::where('status', 'pending')
                    ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId))->count(),
                'active_loans_count' => Loan::where('status', 'borrowed')
                    ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId))->count(),
            ],
            'pending_maintenance' => $pendingMaintenance,
            'active_loans' => $activeLoans,
        ]);
    }

    public function inventories(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = Inventory::with(['category:id,name', 'room:id,name', 'classroom:id,name'])
            ->where('unit_id', $unitId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($itemType = $request->input('item_type')) {
            $query->where('item_type', $itemType);
        }

        if ($condition = $request->input('condition')) {
            $query->where('condition', $condition);
        }

        $inventories = $query->latest()->paginate($request->input('per_page', 30));

        $categories = Category::withCount('inventories')->orderBy('name')->get();

        return response()->json([
            'data' => $inventories->getCollection(),
            'current_page' => $inventories->currentPage(),
            'last_page' => $inventories->lastPage(),
            'total' => $inventories->total(),
            'per_page' => $inventories->perPage(),
            'categories' => $categories,
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::withCount('inventories')->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    public function rooms(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $rooms = Room::where('unit_id', $unitId)
            ->withCount('inventories')
            ->orderBy('building')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $rooms]);
    }

    public function loans(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = Loan::with(['inventory:id,name', 'borrower:id,name', 'processedBy:id,name'])
            ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId));

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('inventory', fn ($iq) => $iq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('borrower', fn ($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        $loans = $query->latest('loan_date')->paginate($request->input('per_page', 30));

        return response()->json([
            'data' => $loans->getCollection(),
            'current_page' => $loans->currentPage(),
            'last_page' => $loans->lastPage(),
            'total' => $loans->total(),
        ]);
    }

    public function maintenance(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = MaintenanceLog::with(['inventory:id,name', 'reporter:id,name', 'handler:id,name'])
            ->whereHas('inventory', fn ($q) => $q->where('unit_id', $unitId));

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $logs = $query->latest('reported_date')->paginate($request->input('per_page', 30));

        return response()->json([
            'data' => $logs->getCollection(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }
}
