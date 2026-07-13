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
            
            if (!Session::has('active_unit_id') || Session::get('active_unit_id') === null) {
                // 1. Check for global roles (team_id is null on the pivot table)
                $hasGlobalRole = \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->whereNull('team_id')
                    ->exists();
                
                if ($hasGlobalRole) {
                    // Start with first unit if available, instead of null, for convenience
                    $firstUnit = \App\Modules\Yayasan\Models\Unit::first();
                    Session::put('active_unit_id', $firstUnit ? $firstUnit->id : null);
                } else {
                    // 2. Check for unit-specific roles
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
            
            // Apply Spatie Team Scope
            // ONLY if the user does NOT have a global role.
            // If user is global (e.g. Super Admin or Global Teacher), let Spatie check for global permissions (team_id = null).
            // But we still keep 'active_unit_id' in session for Data Filtering in Controllers.
            
            $hasGlobalRole = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->whereNull('team_id')
                ->exists();

            if (Session::has('active_unit_id') && !$hasGlobalRole) {
                $unitId = Session::get('active_unit_id');
                // Pass team id to Spatie Permission
                setPermissionsTeamId($unitId);
            }
        }

        return $next($request);
    }
}
