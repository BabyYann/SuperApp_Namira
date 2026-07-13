<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Yayasan\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * If maintenance mode is ON, block all users EXCEPT super_admin_yayasan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin always passes through
        if ($request->user() && $request->user()->hasRole('super_admin_yayasan')) {
            return $next($request);
        }

        // Check maintenance mode from cache
        $settings = Cache::get('system_settings', []);
        $maintenanceMode = $settings['maintenance_mode'] ?? SystemSetting::getSetting('maintenance_mode', '0');

        if ($maintenanceMode == '1') {
            $message = $settings['maintenance_message'] 
                ?? SystemSetting::getSetting('maintenance_message', 'Sistem sedang dalam pemeliharaan.');

            // Return the maintenance page for ALL request types (Inertia or normal)
            return response($this->renderMaintenancePage($message), 503);
        }

        return $next($request);
    }

    private function renderMaintenancePage(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang Maintenance - SuperApp Namira</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; display: flex; align-items: center; justify-content: center; color: white; }
        .container { text-align: center; padding: 2rem; max-width: 520px; }
        .icon { margin-bottom: 1.5rem; display: flex; justify-content: center; }
        .icon svg { width: 72px; height: 72px; color: #5eead4; }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 1rem; background: linear-gradient(to right, #5eead4, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p { color: #94a3b8; font-size: 1.1rem; line-height: 1.7; }
        .badge { display: inline-block; margin-top: 2rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem 1.25rem; border-radius: 9999px; font-size: 0.85rem; color: #64748b; }
        .pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon pulse">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.049.58.025 1.193-.14 1.743" />
            </svg>
        </div>
        <h1>Sedang Maintenance</h1>
        <p>{$message}</p>
        <span class="badge">SuperApp Namira — Yayasan Namira</span>
    </div>
</body>
</html>
HTML;
    }
}

