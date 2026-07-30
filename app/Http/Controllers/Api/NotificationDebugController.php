<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\FcmService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Temporary Debug Controller for FCM/Notification Diagnostics
 * REMOVE after notification system is confirmed working.
 */
class NotificationDebugController extends Controller
{
    /**
     * Full system diagnostic: checks Firebase config, device tokens, DB records.
     */
    public function diagnose(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Firebase config (from env)
        $firebaseConfig = [
            'FIREBASE_API_KEY'         => env('FIREBASE_API_KEY') ? '✅ Set' : '❌ MISSING',
            'FIREBASE_AUTH_DOMAIN'     => env('FIREBASE_AUTH_DOMAIN') ? '✅ Set' : '❌ MISSING',
            'FIREBASE_PROJECT_ID'      => env('FIREBASE_PROJECT_ID') ? '✅ Set (' . env('FIREBASE_PROJECT_ID') . ')' : '❌ MISSING',
            'FIREBASE_SENDER_ID'       => env('FIREBASE_SENDER_ID') ? '✅ Set' : '❌ MISSING',
            'FIREBASE_APP_ID'          => env('FIREBASE_APP_ID') ? '✅ Set' : '❌ MISSING',
            'FIREBASE_VAPID_KEY'       => env('FIREBASE_VAPID_KEY') ? '✅ Set' : '❌ MISSING (CRITICAL - needed for browser push tokens)',
            'FIREBASE_STORAGE_BUCKET'  => env('FIREBASE_STORAGE_BUCKET') ? '✅ Set' : '⚠️ Not set (optional)',
        ];

        // 2. Firebase service account file
        $serviceAccountPath = storage_path('app/firebase-service-account.json');
        $serviceAccountStatus = file_exists($serviceAccountPath)
            ? '✅ Exists at ' . $serviceAccountPath
            : '❌ MISSING at ' . $serviceAccountPath . ' (FCM server-side push will fail)';

        // 3. Device tokens for current user
        $myTokens = UserDeviceToken::where('user_id', $user->id)->get(['token', 'device_type', 'created_at']);

        // 4. Total device tokens in system
        $totalTokens = UserDeviceToken::count();
        $tokensByUser = UserDeviceToken::select('user_id', DB::raw('count(*) as token_count'))
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->get()
            ->map(fn($t) => [
                'user' => optional($t->user)->name . ' (' . optional($t->user)->email . ')',
                'tokens' => $t->token_count,
            ]);

        // 5. Admin users and whether they have tokens
        $adminRoles = ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'];
        $adminUsers = User::whereHas('roles', fn($q) => $q->whereIn('name', $adminRoles))->get(['id', 'name', 'email']);
        $adminStatus = $adminUsers->map(function ($admin) {
            $tokens = UserDeviceToken::where('user_id', $admin->id)->count();
            return [
                'name'   => $admin->name,
                'email'  => $admin->email,
                'tokens' => $tokens,
                'status' => $tokens > 0 ? '✅ Has ' . $tokens . ' token(s)' : '❌ No device tokens - FCM push will NOT reach this user',
            ];
        });

        // 6. Recent notifications in DB for current user
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'message', 'type', 'is_read', 'created_at']);

        // 7. Total notifications in DB
        $totalNotifications = Notification::count();
        $myNotifications = Notification::where('user_id', $user->id)->count();

        return response()->json([
            'current_user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_has_roles.model_id', $user->id)
                    ->pluck('roles.name'),
            ],
            'firebase_env' => $firebaseConfig,
            'firebase_service_account' => $serviceAccountStatus,
            'my_device_tokens' => $myTokens,
            'total_device_tokens_in_system' => $totalTokens,
            'tokens_by_user' => $tokensByUser,
            'admin_users_status' => $adminStatus,
            'my_notifications_in_db' => $myNotifications,
            'total_notifications_in_db' => $totalNotifications,
            'my_recent_notifications' => $recentNotifications,
        ]);
    }

    /**
     * Force send test notification directly to all admin users (with DB record).
     */
    public function forceTestToAdmin(Request $request): JsonResponse
    {
        $sender = $request->user();

        $adminUsers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin_yayasan', 'admin_yayasan']);
        })->get();

        if ($adminUsers->isEmpty()) {
            return response()->json(['error' => 'No admin users found!'], 422);
        }

        $results = [];
        foreach ($adminUsers as $admin) {
            try {
                NotificationDispatcher::sendToUser(
                    $admin,
                    '📢 [FORCE TEST] Notifikasi dari ' . $sender->name,
                    "FORCE TEST: Dikirim dari {$sender->name} ({$sender->email}) ke Admin pada " . now()->format('H:i:s'),
                    'test',
                    ['sender_id' => $sender->id, 'force_test' => true]
                );
                $results[] = ['user' => $admin->email, 'status' => '✅ Notification sent'];
            } catch (\Exception $e) {
                $results[] = ['user' => $admin->email, 'status' => '❌ Error: ' . $e->getMessage()];
            }
        }

        return response()->json([
            'message' => 'Force test complete. Check lonceng notifikasi di akun Admin.',
            'results' => $results,
        ]);
    }

    /**
     * Test FCM service directly with raw token.
     */
    public function testFcmDirect(Request $request): JsonResponse
    {
        $request->validate(['token' => 'nullable|string']);

        $user = $request->user();
        $token = $request->token;

        if (!$token) {
            // Use first stored token for current user
            $deviceToken = UserDeviceToken::where('user_id', $user->id)->first();
            if (!$deviceToken) {
                return response()->json([
                    'error' => 'No device token found for your account. Please ensure browser notification permission is granted and reload the page to register your token.',
                    'suggestion' => 'Open browser console and check for FCM errors in AuthenticatedLayout setup.',
                ], 422);
            }
            $token = $deviceToken->token;
        }

        try {
            $fcm = app(FcmService::class);
            $fcm->sendNotification([$token], '🔔 FCM Direct Test', 'Jika muncul notifikasi ini, FCM server-side bekerja!', ['test' => 'direct']);
            return response()->json(['success' => true, 'token_used' => substr($token, 0, 20) . '...', 'message' => 'FCM sendNotification called. Check device for push notification.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
