<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class CheckUnitScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user has any global role (by role name OR team_id is null)
            $globalRoleNames = ['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'pengawas_yayasan', 'humas_yayasan'];

            $hasGlobalRole = \DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', get_class($user))
                ->where(function ($q) use ($globalRoleNames) {
                    $q->whereNull('model_has_roles.team_id')
                      ->orWhereIn('roles.name', $globalRoleNames);
                })
                ->exists();

            if (!Session::has('active_unit_id') || Session::get('active_unit_id') === null) {
                if ($hasGlobalRole) {
                    // Start with first unit if available for data viewing context
                    $firstUnit = \App\Modules\Yayasan\Models\Unit::first();
                    Session::put('active_unit_id', $firstUnit ? $firstUnit->id : null);
                } else {
                    // Get the first role with a team_id
                    $firstTeamId = \DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', get_class($user))
                        ->whereNotNull('team_id')
                        ->value('team_id');
                    
                    if ($firstTeamId) {
                        Session::put('active_unit_id', $firstTeamId);
                    }
                }
            }

            // Apply Spatie Team Scope ONLY for non-global roles
            if ($hasGlobalRole) {
                setPermissionsTeamId(null);
            } else if (Session::has('active_unit_id')) {
                $unitId = Session::get('active_unit_id');
                setPermissionsTeamId($unitId);
            }
        }

        return $next($request);
    }
}
