<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\Violation;
use App\Modules\Counseling\Models\ViolationCategory;
use App\Modules\Counseling\Models\CounselingSession;
use App\Modules\Counseling\Models\Achievement;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CounselingApiController extends Controller
{
    use HasUnitScope;

    public function violations(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = Violation::with([
            'student:id,full_name',
            'student.classroom:id,name',
            'category:id,name,type',
            'reporter:id,name',
        ])->where('unit_id', $unitId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        $violations = $query->latest('date')->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $violations->getCollection(),
            'current_page' => $violations->currentPage(),
            'last_page' => $violations->lastPage(),
            'total' => $violations->total(),
            'per_page' => $violations->perPage(),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = ViolationCategory::where('unit_id', $unitId);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $categories->getCollection(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'total' => $categories->total(),
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = CounselingSession::with([
            'student:id,full_name',
            'student.classroom:id,name',
            'counselor:id,name',
            'violation.category:id,name',
        ])->where('unit_id', $unitId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        $sessions = $query->orderByDesc('date')->orderByDesc('time')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $sessions->getCollection(),
            'current_page' => $sessions->currentPage(),
            'last_page' => $sessions->lastPage(),
            'total' => $sessions->total(),
        ]);
    }

    public function sessionDetail(Request $request, int $id): JsonResponse
    {
        $session = CounselingSession::with([
            'student:id,full_name',
            'student.classroom:id,name',
            'violation.category:id,name',
        ])->where('unit_id', $this->resolveUnitId($request))->findOrFail($id);

        return response()->json([
            'id' => $session->id,
            'student_name' => $session->student?->full_name,
            'student_classroom' => $session->student->classroom?->name,
            'date' => $session->date,
            'time' => $session->time,
            'method' => $session->method,
            'status' => $session->status,
            'notes' => $session->notes,
            'follow_up_action' => $session->follow_up_action,
            'violation' => $session->violation?->category?->name,
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);

        $query = Achievement::with([
            'student:id,full_name',
            'student.classroom:id,name',
            'creator:id,name',
        ])->where('unit_id', $unitId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($level = $request->input('level')) {
            $query->where('level', $level);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        $achievements = $query->latest('date')->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $achievements->getCollection(),
            'current_page' => $achievements->currentPage(),
            'last_page' => $achievements->lastPage(),
            'total' => $achievements->total(),
        ]);
    }
}
