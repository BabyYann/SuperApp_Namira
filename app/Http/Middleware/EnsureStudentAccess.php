<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnsureStudentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Direct DB Check: Does this user have 'siswa' or 'student' role in ANY team, or is a parent linked to an active Student?
        $studentRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->whereIn('roles.name', ['siswa', 'student'])
            ->select('model_has_roles.team_id')
            ->first();

        if (!$studentRole) {
            $linkedStudent = DB::table('students')
                ->where('user_id', $user->id)
                ->select('unit_id as team_id')
                ->first();
            if ($linkedStudent) {
                $studentRole = $linkedStudent;
            }
        }

        if ($studentRole) {
            // Found it! Force the context.
            if (!session('active_unit_id')) {
                 session(['active_unit_id' => $studentRole->team_id]);
            }
            
            // Set Spatie Context
            setPermissionsTeamId($studentRole->team_id);
            
            // Allow access (User is a student)
            return $next($request);
        }

        // Not a student? Forbidden.
        abort(403, 'Akses ditolak. Akun ini tidak terdaftar sebagai Siswa.');
    }
}
