<?php

namespace App\Http\Middleware;

use App\Modules\Yayasan\Models\Unit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDaycareUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Foundation-level leadership can inspect Daycare regardless of current active unit
        if ($user->hasAnyRole([
            'super_admin_yayasan',
            'admin_yayasan',
            'pembina_yayasan',
            'pengawas_yayasan',
            'staff_yayasan',
        ])) {
            return $next($request);
        }

        $unitId = session('active_unit_id');
        $unit = $unitId ? Unit::find($unitId) : null;

        if (!$unit || (!$unit->isDaycare() && !$unit->hasFeature('daycare'))) {
            abort(403, 'Akses Ditolak: Modul Daycare hanya tersedia untuk unit Daycare/TPA.');
        }

        return $next($request);
    }
}
