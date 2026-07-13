<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Yayasan\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class CheckFeatureEnabled
{
    /**
     * Handle an incoming request.
     * Blocks access to a module if its feature flag is disabled.
     * Super Admin always bypasses this check.
     *
     * Usage in routes: ->middleware('feature:feature_finance')
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        // Super Admin always passes through
        if ($request->user() && $request->user()->hasRole('super_admin_yayasan')) {
            return $next($request);
        }

        // Check feature flag from cache
        $settings = Cache::get('system_settings', []);
        $featureEnabled = $settings[$featureKey] ?? SystemSetting::getSetting($featureKey, '1');

        if ($featureEnabled === '0' || $featureEnabled === 0) {
            $moduleName = str_replace('feature_', '', $featureKey);
            $moduleName = ucwords(str_replace('_', ' ', $moduleName));

            // If Inertia request, redirect back with error
            if ($request->header('X-Inertia')) {
                return redirect()->route('dashboard')->with('error', 
                    "Modul {$moduleName} sedang dinonaktifkan oleh administrator."
                );
            }

            abort(403, "Modul {$moduleName} sedang dinonaktifkan oleh administrator.");
        }

        return $next($request);
    }
}
