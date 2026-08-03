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
     * @param array $roles Spatie role names (e.g. ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'])
     * @param int|null $unitId Unit ID to filter unit-scoped users (null means global / all units)
     * @param string $title Notification Title
     * @param string $message Notification Body / Content
     * @param string $type Category type (e.g. 'public_relations', 'employee', 'counseling', 'sarpar', 'finance')
     * @param array $data Additional payload data (URL, entity_id, etc.)
     * @return void
     */
    public static function sendToRoles(array $roles, ?int $unitId, string $title, string $message, string $type, array $data = []): void
    {
        $globalRoleNames = ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan'];

        $targetGlobalRoles = array_intersect($roles, $globalRoleNames);
        $targetUnitRoles   = array_diff($roles, $globalRoleNames);

        $usersToNotify = collect();

        // 1. Fetch Global Roles via raw DB subquery to BYPASS Spatie team scope.
        //    Using whereHas('roles') goes through Spatie's scoped relationship which adds
        //    a team_id filter — causing super_admin_yayasan (team_id=NULL) to be excluded.
        if (!empty($targetGlobalRoles)) {
            $globalUsers = User::whereIn('id', function ($q) use ($targetGlobalRoles) {
                $q->select('model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('roles.name', array_values($targetGlobalRoles))
                    ->where('model_has_roles.model_type', \App\Models\User::class);
            })->get();
            $usersToNotify = $usersToNotify->concat($globalUsers);
        }

        // 2. Fetch Unit-Scoped Roles via raw DB subquery (also bypasses Spatie scope).
        if (!empty($targetUnitRoles)) {
            $query = User::whereIn('id', function ($q) use ($targetUnitRoles, $unitId) {
                $q->select('model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('roles.name', array_values($targetUnitRoles))
                    ->where('model_has_roles.model_type', \App\Models\User::class);

                if ($unitId) {
                    $q->where(function ($sub) use ($unitId) {
                        $sub->where('model_has_roles.team_id', $unitId)
                            ->orWhereNull('model_has_roles.team_id');
                    });
                }
            });

            $unitUsers = $query->get();
            $usersToNotify = $usersToNotify->concat($unitUsers);
        }

        // Deduplicate users
        $uniqueUsers = $usersToNotify->unique('id');

        foreach ($uniqueUsers as $user) {
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
