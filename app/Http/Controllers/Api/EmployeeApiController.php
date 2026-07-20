<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Models\EmployeeAttendance;
use App\Modules\Employee\Models\Staff;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeApiController extends Controller
{
    use HasUnitScope;

    // GET /employee/staff
    public function staff(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $query = Staff::with('user')
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId));

        if ($search = $request->get('search')) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $items = $query->paginate($request->get('per_page', 15))
            ->through(fn ($s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'nip' => $s->nip,
                'position' => $s->position,
                'phone' => $s->phone,
                'unit' => $s->unit?->name,
                'is_active' => $s->is_active,
            ]);

        return response()->json($items);
    }

    // GET /employee/attendance
    public function attendance(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $staffIds = Staff::when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->pluck('user_id')
            ->toArray();

        $records = EmployeeAttendance::with('user')
            ->whereIn('user_id', $staffIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->user?->name,
                'date' => $a->date,
                'status' => $a->status,
                'check_in' => $a->check_in_time,
                'check_out' => $a->check_out_time,
                'late_minutes' => $a->late_minutes,
            ]);

        return response()->json(['data' => $records]);
    }
}
