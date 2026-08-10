<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareDevelopmentalJournal;
use Illuminate\Http\Request;

class DaycareJournalController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'period_month' => 'required|string|max:7', // e.g. "2026-08"
            'gross_motor' => 'nullable|string|max:1000',
            'fine_motor' => 'nullable|string|max:1000',
            'language_communication' => 'nullable|string|max:1000',
            'cognitive' => 'nullable|string|max:1000',
            'socio_emotional' => 'nullable|string|max:1000',
            'independence' => 'nullable|string|max:1000',
            'caregiver_summary' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,published',
        ]);

        $validated['student_id'] = $student->id;
        $validated['created_by'] = auth()->id();

        DaycareDevelopmentalJournal::updateOrCreate(
            [
                'student_id' => $student->id,
                'period_month' => $validated['period_month'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Jurnal perkembangan anak berhasil disimpan.');
    }
}
