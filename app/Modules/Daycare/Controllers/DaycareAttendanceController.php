<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareAttendance;
use App\Modules\Daycare\Models\DaycareAuthorizedPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DaycareAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $unitId = session('active_unit_id');
        $date = $request->query('date', now()->toDateString());

        $children = Student::with(['daycareProfile', 'authorizedPickups'])
            ->where('unit_id', $unitId)
            ->get();

        $attendances = DaycareAttendance::with('authorizedPickup')
            ->whereIn('student_id', $children->pluck('id'))
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        $children->transform(function ($c) use ($attendances) {
            $c->attendance = $attendances->get($c->id);
            return $c;
        });

        return Inertia::render('Daycare/Attendance/Index', [
            'date' => $date,
            'children' => $children,
        ]);
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'dropped_off_by' => 'required|string|max:255',
            'check_in_temp' => 'nullable|numeric|between:34,42',
            'check_in_condition' => 'required|string|max:255',
            'check_in_notes' => 'nullable|string|max:500',
            'check_in_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('check_in_photo')) {
            $validated['check_in_photo'] = $request->file('check_in_photo')->store('daycare/checkins', 'public');
        }

        $validated['recorded_by'] = auth()->id();

        DaycareAttendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'date' => $validated['date'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Kedatangan Ananda berhasil dicatat (Check-in).');
    }

    public function checkOut(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'check_out_time' => 'required|date_format:H:i',
            'picked_up_by' => 'required|string|max:255',
            'authorized_pickup_id' => 'nullable|exists:daycare_authorized_pickups,id',
            'check_out_notes' => 'nullable|string|max:500',
            'check_out_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('check_out_photo')) {
            $validated['check_out_photo'] = $request->file('check_out_photo')->store('daycare/checkouts', 'public');
        }

        DaycareAttendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'date' => $validated['date'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Kepulangan Ananda berhasil dicatat (Check-out).');
    }
}
