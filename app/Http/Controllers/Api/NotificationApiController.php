<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Models\Notification;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    use HasUnitScope;

    // GET /notifications
    public function index(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $items = Notification::where('user_id', $request->user()->id)
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->latest()
            ->paginate($request->get('per_page', 20))
            ->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'data' => $n->data,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json($items);
    }

    // POST /notifications/:id/read
    public function markRead(Request $request, $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notifikasi ditandai dibaca']);
    }

    // POST /notifications/read-all
    public function markAllRead(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        Notification::where('user_id', $request->user()->id)
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca']);
    }
}
