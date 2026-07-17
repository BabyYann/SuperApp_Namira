<?php

namespace App\Http\Controllers\Api\Traits;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;

trait HasUnitScope
{
    private function resolveUnitId(Request $request): ?int
    {
        $headerUnit = $request->header('X-Active-Unit');
        if ($headerUnit) {
            return (int) $headerUnit;
        }

        $sessionUnit = session('active_unit_id');
        if ($sessionUnit) {
            return (int) $sessionUnit;
        }

        $user = $request->user();
        $hasGlobalRole = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan']);

        if ($hasGlobalRole) {
            $firstUnit = Unit::first();
            return $firstUnit?->id;
        }

        $firstTeamId = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->whereNotNull('team_id')
            ->value('team_id');

        return $firstTeamId ? (int) $firstTeamId : null;
    }
}
