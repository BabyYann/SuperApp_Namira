<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    // GET /notifications
    public function index(Request $request): JsonResponse
    {
        $items = Notification::where('user_id', $request->user()->id)
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

    // POST /notifications/test-trigger
    public function testTrigger(Request $request): JsonResponse
    {
        $user = $request->user();
        \App\Services\NotificationDispatcher::sendToUser(
            $user,
            '🧪 Tes Notifikasi Saya Sukses!',
            "Halo {$user->name}, notifikasi pribadi berhasil terkirim!",
            'test',
            ['test' => true]
        );

        return response()->json(['message' => 'Tes notifikasi berhasil dikirim!']);
    }

    // POST /notifications/test-trigger-admin
    public function testTriggerAdmin(Request $request): JsonResponse
    {
        $sender = $request->user();
        \App\Services\NotificationDispatcher::sendToRoles(
            ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'],
            null,
            '📢 Uji Coba Notifikasi dari ' . $sender->name,
            "Pengguna {$sender->name} ({$sender->email}) mengirim tes notifikasi lintas-role ke Admin Yayasan.",
            'test',
            ['sender_id' => $sender->id]
        );

        return response()->json(['message' => 'Tes notifikasi ke Admin berhasil dikirim!']);
    }

    // POST /notifications/read-all
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca']);
    }
}
