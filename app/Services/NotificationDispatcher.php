<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationDispatcher
{
    /**
     * Dispatch notification (In-App DB record + FCM Push) to specific roles.
     *
     * @param array $roles Spatie role names (e.g. ['super_admin_yayasan', 'admin_yayasan'])
     * @param int|null $unitId Unit ID to filter unit-scoped users (null means global / all units)
     * @param string $title Notification Title
     * @param string $message Notification Body / Content
     * @param string $type Category type (e.g. 'public_relations', 'employee', 'counseling', 'sarpar', 'finance')
     * @param array $data Additional payload data (URL, entity_id, etc.)
     * @return void
     */
    public static function sendToRoles(array $roles, ?int $unitId, string $title, string $message, string $type, array $data = []): void
    {
        $originalTeamId = getPermissionsTeamId();

        // 1. Fetch Global Role Users (team_id = null)
        setPermissionsTeamId(null);
        $globalUsers = User::role($roles)->get();

        // 2. Fetch Unit-Scoped Role Users if unitId provided
        $unitUsers = collect();
        $targetUnitId = $unitId ?? session('active_unit_id');
        if ($targetUnitId) {
            setPermissionsTeamId($targetUnitId);
            $unitUsers = User::role($roles)->get();
        }

        // Restore original team ID
        setPermissionsTeamId($originalTeamId);

        $allUsers = $globalUsers->concat($unitUsers)->unique('id');

        foreach ($allUsers as $user) {
            self::sendToUser($user, $title, $message, $type, $data);
        }
    }

    /**
     * Dispatch notification (In-App DB record + FCM Push) to a single user.
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @return void
     */
    public static function sendToUser(User $user, string $title, string $message, string $type, array $data = []): void
    {
        try {
            // 1. Create In-App Notification Record
            Notification::create([
                'user_id' => $user->id,
                'unit_id' => $user->unit_id ?? session('active_unit_id'),
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'is_read' => false,
            ]);

            // 2. Dispatch FCM Push Notification to User Devices
            app(FcmService::class)->sendToUser($user, $title, $message, $data);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("NotificationDispatcher error for user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Dispatch notification to a collection or array of users.
     *
     * @param Collection|array $users
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @return void
     */
    public static function sendToUsers($users, string $title, string $message, string $type, array $data = []): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                self::sendToUser($user, $title, $message, $type, $data);
            }
        }
    }
}
