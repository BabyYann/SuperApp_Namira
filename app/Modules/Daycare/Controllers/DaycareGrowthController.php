<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareGrowthRecord;
use Illuminate\Http\Request;

class DaycareGrowthController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'measurement_date' => 'required|date',
            'weight_kg' => 'required|numeric|between:1,50',
            'height_cm' => 'required|numeric|between:30,150',
            'head_circumference_cm' => 'nullable|numeric|between:20,80',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['student_id'] = $student->id;
        $validated['measured_by'] = auth()->id();

        DaycareGrowthRecord::create($validated);

        return redirect()->back()->with('success', 'Data pengukuran pertumbuhan berhasil dicatat.');
    }
}
