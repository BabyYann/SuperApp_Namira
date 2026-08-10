<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareChildProfile;
use App\Modules\Daycare\Models\DaycareAuthorizedPickup;
use App\Modules\Daycare\Models\DaycareAttendance;
use App\Modules\Daycare\Models\DaycareDailyLog;
use App\Modules\Daycare\Models\DaycareGrowthRecord;
use App\Modules\Daycare\Models\DaycareDevelopmentalJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DaycareChildrenController extends Controller
{
    public function index(Request $request)
    {
        $unitId = session('active_unit_id');

        $query = Student::with(['daycareProfile', 'authorizedPickups'])
            ->where(function ($q) use ($unitId) {
                if ($unitId) {
                    $q->where('unit_id', $unitId);
                }
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('parent_name', 'like', "%{$search}%");
            });
        }

        $children = $query->latest()->paginate(12)->withQueryString();

        // Get today's attendance status for all children in the page
        $todayStr = now()->toDateString();
        $todayAttendances = DaycareAttendance::whereIn('student_id', $children->pluck('id'))
            ->where('date', $todayStr)
            ->get()
            ->keyBy('student_id');

        $children->getCollection()->transform(function ($child) use ($todayAttendances) {
            $child->today_attendance = $todayAttendances->get($child->id);
            return $child;
        });

        // Quick Stats
        $totalChildren = Student::where('unit_id', $unitId)->count();
        $checkedInToday = DaycareAttendance::where('date', $todayStr)
            ->whereIn('student_id', function ($q) use ($unitId) {
                $q->select('id')->from('students')->where('unit_id', $unitId);
            })
            ->whereNotNull('check_in_time')
            ->count();

        return Inertia::render('Daycare/Children/Index', [
            'children' => $children,
            'filters' => $request->only(['search']),
            'stats' => [
                'total' => $totalChildren,
                'checked_in_today' => $checkedInToday,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Daycare/Children/Create');
    }

    public function store(Request $request)
    {
        $unitId = session('active_unit_id');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'dob' => 'required|date',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:2048',
            'blood_type' => 'nullable|string|max:5',
            'allergies' => 'nullable|string|max:1000',
            'special_conditions' => 'nullable|string|max:1000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'routine_notes' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
        }

        $validated['unit_id'] = $unitId;
        $validated['nis'] = 'DC-' . strtoupper(\Str::random(6));

        $student = Student::create($validated);

        // Create Daycare Child Profile
        DaycareChildProfile::create([
            'student_id' => $student->id,
            'nickname' => $validated['nickname'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'allergies' => $validated['allergies'] ?? null,
            'special_conditions' => $validated['special_conditions'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'routine_notes' => $validated['routine_notes'] ?? null,
        ]);

        // Automatically add parents as default authorized pickups
        DaycareAuthorizedPickup::create([
            'student_id' => $student->id,
            'name' => $validated['parent_name'],
            'relationship' => 'Orang Tua / Wali Utama',
            'phone' => $validated['parent_phone'],
            'is_active' => true,
        ]);

        return redirect()->route('daycare.children.show', $student->id)->with('success', 'Data Ananda Daycare berhasil didaftarkan.');
    }

    public function show(Student $student, Request $request)
    {
        $student->load(['daycareProfile', 'authorizedPickups']);

        $date = $request->query('date', now()->toDateString());

        // Today's Attendance
        $attendance = DaycareAttendance::where('student_id', $student->id)
            ->where('date', $date)
            ->first();

        // Today's Daily Logs Timeline
        $logs = DaycareDailyLog::with('caregiver')
            ->where('student_id', $student->id)
            ->where('date', $date)
            ->orderBy('log_time', 'asc')
            ->get();

        // Growth Records History
        $growthRecords = DaycareGrowthRecord::with('measuredBy')
            ->where('student_id', $student->id)
            ->orderBy('measurement_date', 'desc')
            ->limit(12)
            ->get();

        // Developmental Journals History
        $developmentalJournals = DaycareDevelopmentalJournal::where('student_id', $student->id)
            ->orderBy('period_month', 'desc')
            ->get();

        return Inertia::render('Daycare/Children/Show', [
            'student' => $student,
            'selectedDate' => $date,
            'attendance' => $attendance,
            'logs' => $logs,
            'growthRecords' => $growthRecords,
            'developmentalJournals' => $developmentalJournals,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'dob' => 'required|date',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:2048',
            'blood_type' => 'nullable|string|max:5',
            'allergies' => 'nullable|string|max:1000',
            'special_conditions' => 'nullable|string|max:1000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'routine_notes' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $student->update($validated);

        DaycareChildProfile::updateOrCreate(
            ['student_id' => $student->id],
            [
                'nickname' => $validated['nickname'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
                'special_conditions' => $validated['special_conditions'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'routine_notes' => $validated['routine_notes'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Profil Ananda berhasil diperbarui.');
    }

    public function storePickup(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('daycare/pickups', 'public');
        }

        $validated['student_id'] = $student->id;

        DaycareAuthorizedPickup::create($validated);

        return redirect()->back()->with('success', 'Wali penjemput terotorisasi berhasil ditambahkan.');
    }

    public function destroyPickup(DaycareAuthorizedPickup $pickup)
    {
        if ($pickup->photo) {
            Storage::disk('public')->delete($pickup->photo);
        }
        $pickup->delete();

        return redirect()->back()->with('success', 'Wali penjemput berhasil dihapus.');
    }
}
