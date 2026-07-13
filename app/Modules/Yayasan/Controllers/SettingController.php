<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Modules\Yayasan\Models\SystemSetting;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = SystemSetting::all()->keyBy('key')->map(function($s) { return $s->value; });

        $users = collect();
        if ($request->filled('search')) {
            $users = User::with('roles')
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->take(10)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url,
                        // Pluck role names, optionally you can check team_id if you use teams
                        'roles' => $user->roles->pluck('name'), 
                    ];
                });
        }

        $availableRoles = Role::whereIn('name', [
            'admin_unit', 'koordinator_kurikulum', 'wali_kelas', 'staff_admin_keuangan', 'koordinator_sarpar', 'teacher', 'siswa', 'bk'
        ])->get()->map(function($role) {
            return [
                'name' => $role->name,
                'label' => ucwords(str_replace('_', ' ', $role->name))
            ];
        });

        // Fetch recent 20 activity logs
        $activityLogs = \Spatie\Activitylog\Models\Activity::with('causer')
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'Sistem',
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('d M Y, H:i'),
                ];
            });

        return Inertia::render('Yayasan/Settings/Index', [
            'settings' => $settings,
            'users' => $users,
            'availableRoles' => $availableRoles,
            'activityLogs' => $activityLogs,
            'filters' => ['search' => $request->search],
        ]);
    }

    public function updateGlobal(Request $request)
    {
        // Validate file uploads
        $request->validate([
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'app_favicon' => 'nullable|image|mimes:png,ico,svg|max:512',
        ]);

        // Whitelist of allowed text setting keys
        $allowedKeys = [
            'app_name', 'contact_email', 'contact_phone', 'address', 'maintenance_message',
            'waha_url', 'waha_api_key', 'waha_session'
        ];

        $settingsData = $request->except(['app_logo', 'app_favicon', '_token', '_method']);
        foreach ($settingsData as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                SystemSetting::setSetting($key, $value);
            }
        }

        // Handle File Uploads — delete old file first
        if ($request->hasFile('app_logo')) {
            $oldLogo = SystemSetting::getSetting('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('app_logo')->store('settings', 'public');
            SystemSetting::setSetting('app_logo', $path, 'image');
        }

        if ($request->hasFile('app_favicon')) {
            $oldFavicon = SystemSetting::getSetting('app_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $path = $request->file('app_favicon')->store('settings', 'public');
            SystemSetting::setSetting('app_favicon', $path, 'image');
        }

        // Clear Cache
        Cache::forget('system_settings');

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['updated_keys' => array_keys($settingsData)])
            ->log('Memperbarui pengaturan global sistem');

        return redirect()->back()->with('success', 'Pengaturan global berhasil diperbarui.');
    }

    public function toggleUserRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
            'status' => 'required|boolean'
        ]);

        $user = User::findOrFail($request->user_id);
        
        if ($request->status) {
            $user->assignRole($request->role);
            $action = 'Memberikan';
        } else {
            $user->removeRole($request->role);
            $action = 'Mencabut';
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['role' => $request->role, 'status' => $request->status])
            ->log("{$action} hak akses {$request->role} untuk user {$user->name}");

        return redirect()->back()->with('success', 'Hak akses berhasil diubah.');
    }

    public function toggleFeature(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|in:0,1',
        ]);

        $allowedKeys = [
            'maintenance_mode',
            'feature_finance',
            'feature_sarpar',
            'feature_counseling',
            'feature_academic',
            'feature_employee',
            'feature_student_login',
        ];

        if (!in_array($request->key, $allowedKeys)) {
            return redirect()->back()->withErrors(['key' => 'Key tidak diizinkan.']);
        }

        SystemSetting::setSetting($request->key, $request->value, 'boolean', 
            str_starts_with($request->key, 'feature_') ? 'features' : 'system'
        );

        // Clear cache so changes take effect immediately
        Cache::forget('system_settings');

        $statusLabel = $request->value == '1' ? 'Mengaktifkan' : 'Menonaktifkan';
        
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['key' => $request->key, 'value' => $request->value])
            ->log("{$statusLabel} fitur/pengaturan: {$request->key}");

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function wahaQueueStats()
    {
        $stats = [
            'pending' => \App\Modules\Yayasan\Models\WhatsAppQueue::where('status', 'pending')->count(),
            'sending' => \App\Modules\Yayasan\Models\WhatsAppQueue::where('status', 'sending')->count(),
            'sent' => \App\Modules\Yayasan\Models\WhatsAppQueue::where('status', 'sent')->count(),
            'failed' => \App\Modules\Yayasan\Models\WhatsAppQueue::where('status', 'failed')->count(),
            'cancelled' => \App\Modules\Yayasan\Models\WhatsAppQueue::where('status', 'cancelled')->count(),
            'is_paused' => \Illuminate\Support\Facades\Cache::get('waha_queue_status', 'active') === 'paused',
        ];
        return response()->json($stats);
    }

    public function wahaQueueList(Request $request)
    {
        $query = \App\Modules\Yayasan\Models\WhatsAppQueue::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('id', 'desc')->paginate(10);
        return response()->json($list);
    }

    public function toggleWahaQueue(Request $request)
    {
        $request->validate(['status' => 'required|in:active,paused']);
        \Illuminate\Support\Facades\Cache::put('waha_queue_status', $request->status);
        
        $statusLabel = $request->status === 'paused' ? 'Menjeda' : 'Melanjutkan';
        activity()
            ->causedBy(auth()->user())
            ->log("{$statusLabel} antrean pengiriman WhatsApp");

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    public function cancelWahaMessage(Request $request)
    {
        $request->validate(['id' => 'required|exists:whatsapp_messages_queue,id']);
        $msg = \App\Modules\Yayasan\Models\WhatsAppQueue::findOrFail($request->id);
        
        if (in_array($msg->status, ['pending', 'failed'])) {
            $msg->update(['status' => 'cancelled']);
            
            activity()
                ->causedBy(auth()->user())
                ->log("Membatalkan pengiriman WhatsApp ID {$msg->id}");

            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Hanya antrean pending/failed yang dapat dibatalkan.'], 400);
    }

    public function retryWahaMessage(Request $request)
    {
        $request->validate(['id' => 'required|exists:whatsapp_messages_queue,id']);
        $msg = \App\Modules\Yayasan\Models\WhatsAppQueue::findOrFail($request->id);
        
        if (in_array($msg->status, ['failed', 'cancelled'])) {
            $msg->update([
                'status' => 'pending',
                'retry_count' => 0,
                'next_attempt_at' => null,
                'error_message' => null,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->log("Mengantrekan kembali pesan WhatsApp ID {$msg->id}");

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Hanya pesan gagal/batal yang dapat dikirim ulang.'], 400);
    }
}
