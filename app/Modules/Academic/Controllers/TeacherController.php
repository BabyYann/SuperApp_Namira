<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index()
    {
        // Scope by active unit
        $unitId = session('active_unit_id');
        
        $teachers = Teacher::with('user')
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->when(request('gender'), function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Statistics
        $totalTeachers = Teacher::where('unit_id', $unitId)->count();
        $countL = Teacher::where('unit_id', $unitId)->where('gender', 'L')->count();
        $countP = Teacher::where('unit_id', $unitId)->where('gender', 'P')->count();

        return Inertia::render('Academic/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => request()->only(['search', 'gender']),
            'stats' => [
                'total' => $totalTeachers,
                'male' => $countL,
                'female' => $countP,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menambah data guru.');
        }

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;
        
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048', // Max 2MB
        ]);

        try {
            \DB::transaction(function () use ($validated, $unitId, $request) {
                // 1. Find or Create User
                $user = User::where('email', $validated['email'])->first();

                if (!$user) {
                    // Generate password from NIP or default
                    $password = $validated['nip'] ? $validated['nip'] : 'guru123';
                    $user = User::create([
                        'name' => $validated['full_name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($password), 
                    ]);
                }
                
                // Assign role if not exists
                if (!$user->hasRole('teacher')) {
                    setPermissionsTeamId($unitId);
                    $user->assignRole('teacher');
                }

                // 2. Handle File Upload
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('teachers', 'public');
                }

                // 3. Create Teacher Profile
                Teacher::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $validated['full_name'],
                    'nip' => $validated['nip'],
                    'gender' => $validated['gender'],
                    'phone' => $validated['phone'],
                    'photo' => $photoPath,
                ]);
            });

            return redirect()->back()->with('success', 'Guru berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan guru. Silakan coba lagi.');
        }
    }

    public function show(Teacher $teacher)
    {
        if ($teacher->unit_id != session('active_unit_id')) {
            abort(403);
        }

        $teacher->load(['user', 'classroom']);
        
        // Fetch Teaching Schedule
        $schedules = \App\Modules\Academic\Models\ClassSchedule::with(['classroom', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');
            
        // Fetch Attendance Summary (Mock or Actual if implemented)
        $attendanceHistory = \App\Models\EmployeeAttendance::where('user_id', $teacher->user_id)
            ->latest('date')
            ->limit(30)
            ->get();
        
        return Inertia::render('Academic/Teachers/Show', [
            'teacher' => $teacher,
            'schedules' => $schedules,
            'attendanceHistory' => $attendanceHistory,
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat mengubah data guru.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $teacher->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah data guru dari unit lain.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
        ]);

        $teacher->full_name = $validated['full_name'];
        $teacher->nip = $validated['nip'];
        $teacher->gender = $validated['gender'];
        $teacher->phone = $validated['phone'];

        // Handle File Upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($teacher->photo) {
                \Storage::disk('public')->delete($teacher->photo);
            }
            $teacher->photo = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->save();
        
        // Update User name
        $teacher->user->update(['name' => $validated['full_name']]);

        return redirect()->back()->with('success', 'Data guru diperbarui.');
    }

    public function destroy(Teacher $teacher)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menghapus data guru.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $teacher->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus data guru dari unit lain.');
        }

        if ($teacher->photo) {
            \Storage::disk('public')->delete($teacher->photo);
        }

        $teacher->user->delete();
        $teacher->delete();

        return redirect()->back()->with('success', 'Guru dihapus.');
    }
}
