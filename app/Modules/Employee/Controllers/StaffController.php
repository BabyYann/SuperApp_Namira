<?php

namespace App\Modules\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Employee\Models\Staff;
use App\Modules\Yayasan\Models\Unit;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        $staff = Staff::with('user')
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
            
        // Stats
        $stats = [
            'total' => Staff::where('unit_id', $unitId)->count(),
            'active' => Staff::where('unit_id', $unitId)->where('is_active', true)->count(),
            'inactive' => Staff::where('unit_id', $unitId)->where('is_active', false)->count(),
        ];

        return Inertia::render('Employee/Staff/Index', [
            'staff' => $staff,
            'filters' => request()->only(['search']),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menambah data staf.');
        }

        $unitId = session('active_unit_id') ?? Unit::first()->id;

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:10240',
        ]);

        DB::transaction(function () use ($validated, $unitId, $request) {
            // 1. Find or Create User
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Generate password from NIP or default
                $password = $validated['nip'] ? $validated['nip'] : 'staf123';
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($password),
                ]);
            }

            // Assign role 'staff' if not exists
            if (!$user->hasRole('staff')) {
                setPermissionsTeamId($unitId);
                $user->assignRole('staff');
            }

            // 2. Handle File Upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('staff', 'public');
            }

            // 3. Create Staff Profile
            Staff::create([
                'user_id' => $user->id,
                'unit_id' => $unitId,
                'full_name' => $validated['full_name'],
                'nip' => $validated['nip'],
                'gender' => $validated['gender'],
                'phone' => $validated['phone'],
                'position' => $validated['position'],
                'photo' => $photoPath,
                'is_active' => true,
            ]);
        });

        $unitName = Unit::find($unitId)->name ?? 'Unit';
        NotificationDispatcher::sendToRoles(
            ['admin_unit', 'super_admin_yayasan', 'admin_yayasan'],
            $unitId,
            '👤 Data Staf Baru Ditambahkan',
            "{$validated['full_name']} telah ditambahkan sebagai staf baru di {$unitName}.",
            'employee',
            []
        );

        return redirect()->back()->with('success', 'Data staf berhasil ditambahkan.');
    }

    public function show(Staff $staff)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat profil staf.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $staff->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses data staf dari unit lain.');
        }

        $staff->load(['user']);

        // Fetch Attendance Summary (Placeholder for now)
        // Can be implemented later linking to EmployeeAttendance
        $attendanceHistory = \App\Models\EmployeeAttendance::where('user_id', $staff->user_id)
            ->latest('date')
            ->limit(30)
            ->get();
        
        return Inertia::render('Employee/Staff/Show', [
            'staff' => $staff,
            'attendanceHistory' => $attendanceHistory,
        ]);
    }

    public function update(Request $request, Staff $staff)
    {
        // Notice: In Laravel Route Model Binding, variable name $staff matches route {staff}
        // But for consistency with resource controller, we use type hinting Staff $staff
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat mengubah data staf.');
        }

        if ($staff->unit_id != session('active_unit_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:10240',
        ]);

        $staff->full_name = $validated['full_name'];
        $staff->nip = $validated['nip'];
        $staff->gender = $validated['gender'];
        $staff->phone = $validated['phone'];
        $staff->position = $validated['position'];

        if ($request->hasFile('photo')) {
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $staff->photo = $request->file('photo')->store('staff', 'public');
        }

        $staff->save();
        $staff->user->update(['name' => $validated['full_name']]);

        return redirect()->back()->with('success', 'Data staf diperbarui.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menghapus data staf.');
        }

        $staff = Staff::find($id);
        if (!$staff) {
            return redirect()->back()->with('success', 'Data staf sudah berhasil dihapus sebelumnya.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $staff->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus data staf dari unit lain.');
        }

        \DB::transaction(function () use ($staff) {
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }

            $user = $staff->user;
            $staff->delete();

            if ($user) {
                // Periksa apakah user memiliki profil lain (guru/siswa) atau peran non-staff
                $hasOtherProfiles = \DB::table('teachers')->where('user_id', $user->id)->exists()
                    || \DB::table('students')->where('user_id', $user->id)->exists();

                $nonStaffRoles = $user->roles()->whereNotIn('name', ['staff_unit', 'staff_yayasan'])->count();

                if (!$hasOtherProfiles && $nonStaffRoles === 0) {
                    try {
                        $user->delete();
                    } catch (\Throwable $e) {
                        $user->removeRole('staff_unit');
                        $user->removeRole('staff_yayasan');
                    }
                } else {
                    $user->removeRole('staff_unit');
                    $user->removeRole('staff_yayasan');
                }
            }
        });

        return redirect()->back()->with('success', 'Data staf berhasil dihapus.');
    }
}
