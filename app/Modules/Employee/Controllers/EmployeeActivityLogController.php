<?php

namespace App\Modules\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Models\User;
use App\Modules\Employee\Models\EmployeeActivityLog;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class EmployeeActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id') ?? $user->unit_id;

        $date = $request->input('date', date('Y-m-d'));

        $query = EmployeeActivityLog::where('user_id', $user->id)
            ->whereDate('activity_date', $date)
            ->latest('activity_time')
            ->latest();

        $todayActivities = $query->get();

        $stats = [
            'total_today' => $todayActivities->count(),
            'categories' => $todayActivities->groupBy('category')->map->count(),
        ];

        // Historical recent logs
        $historyLogs = EmployeeActivityLog::where('user_id', $user->id)
            ->latest('activity_date')
            ->latest('id')
            ->take(20)
            ->get();

        return Inertia::render('Employee/ActivityLogs/Index', [
            'todayActivities' => $todayActivities,
            'historyLogs' => $historyLogs,
            'stats' => $stats,
            'selectedDate' => $date,
            'categories' => [
                'tugas_tambahan' => 'Tugas Tambahan',
                'kebersihan' => 'Kebersihan & Kerapihan',
                'keamanan' => 'Keamanan & Ketertiban',
                'pemeliharaan' => 'Pemeliharaan Sarpar',
                'layanan_admin' => 'Layanan Admin & Operasional',
                'piket' => 'Tugas Piket Sekolah',
                'lainnya' => 'Kegiatan Lainnya',
            ]
        ]);
    }

    public function feed(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'kepala_sekolah', 'staff_yayasan'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses feed giat tugas.');
        }

        $inputUnitId = $request->input('unit_id');
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan'])) {
            $unitId = ($inputUnitId === 'all') ? null : ($inputUnitId ?: null);
        } else {
            $unitId = session('active_unit_id');
        }

        $query = EmployeeActivityLog::with(['user', 'unit'])->latest('activity_date')->latest();

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('date')) {
            $query->whereDate('activity_date', $request->date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($u) use ($request) {
                      $u->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $feedItems = $query->paginate(15)->withQueryString();
        $units = Unit::all();

        return Inertia::render('Yayasan/ActivityLogs/Feed', [
            'feedItems' => $feedItems,
            'units' => $units,
            'filters' => [
                'unit_id' => $unitId ?: 'all',
                'category' => $request->input('category', ''),
                'date' => $request->input('date', ''),
                'search' => $request->input('search', ''),
            ],
            'categories' => [
                'tugas_tambahan' => 'Tugas Tambahan',
                'kebersihan' => 'Kebersihan & Kerapihan',
                'keamanan' => 'Keamanan & Ketertiban',
                'pemeliharaan' => 'Pemeliharaan Sarpar',
                'layanan_admin' => 'Layanan Admin & Operasional',
                'piket' => 'Tugas Piket Sekolah',
                'lainnya' => 'Kegiatan Lainnya',
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id') ?? $user->unit_id;

        if (!$request->hasFile('photo')) {
            $request->request->remove('photo');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:tugas_tambahan,kebersihan,keamanan,pemeliharaan,layanan_admin,piket,lainnya',
            'activity_date' => 'required|date',
            'activity_time' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'location_name' => 'nullable|string|max:255',
        ]);

        $log = new EmployeeActivityLog();
        $log->user_id = $user->id;
        $log->unit_id = $unitId;
        $log->title = $validated['title'];
        $log->category = $validated['category'];
        $log->activity_date = $validated['activity_date'];
        $log->activity_time = $validated['activity_time'] ?? date('H:i:s');
        $log->description = $validated['description'] ?? null;
        $log->location_name = $validated['location_name'] ?? null;

        if ($request->hasFile('photo')) {
            $path = ImageHelper::uploadAndConvert($request->file('photo'), 'activity_logs', 800, 80);
            $log->photo_path = $path;
        }

        $log->save();

        \App\Services\NotificationDispatcher::sendToRoles(
            ['admin_unit', 'kepala_sekolah', 'super_admin_yayasan'],
            $log->unit_id,
            '📋 Giat Tugas Baru SDM',
            "{$user->name} mencatat giat tugas baru: \"{$log->title}\".",
            'employee',
            ['log_id' => $log->id]
        );

        return redirect()->back()->with('success', 'Giat Tugas berhasil dicatat!');
    }

    public function destroy(EmployeeActivityLog $activityLog)
    {
        $user = auth()->user();

        if ($activityLog->user_id !== $user->id && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        if ($activityLog->photo_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $activityLog->photo_path));
        }

        $activityLog->delete();

        return redirect()->back()->with('success', 'Giat Tugas berhasil dihapus.');
    }
}
