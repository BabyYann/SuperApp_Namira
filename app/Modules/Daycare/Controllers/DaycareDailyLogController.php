<?php

namespace App\Modules\Daycare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Daycare\Models\DaycareDailyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DaycareDailyLogController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'log_time' => 'required|date_format:H:i',
            'category' => 'required|in:meal,snack,milk,nap_start,nap_end,diaper,activity,mood,medication,incident',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount_ml' => 'nullable|integer|min:0',
            'portion_eaten' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('daycare/logs', 'public');
        }

        $validated['student_id'] = $student->id;
        $validated['caregiver_id'] = auth()->id();

        // Default Titles if empty
        if (empty($validated['title'])) {
            $titles = [
                'meal' => 'Makan Utama',
                'snack' => 'Makan Camilan',
                'milk' => 'Minum Susu / Air',
                'nap_start' => 'Mulai Tidur Siang',
                'nap_end' => 'Bangun Tidur Siang',
                'diaper' => 'Ganti Popok / Toilet',
                'activity' => 'Aktivitas & Bermain',
                'mood' => 'Kondisi / Suasana Hati',
                'medication' => 'Minum Obat / Suplemen',
                'incident' => 'Catatan Khusus / Kejadian',
            ];
            $validated['title'] = $titles[$validated['category']] ?? 'Catatan Pengasuhan';
        }

        DaycareDailyLog::create($validated);

        return redirect()->back()->with('success', 'Catatan timeline pengasuhan berhasil ditambahkan.');
    }

    public function destroy(DaycareDailyLog $log)
    {
        if ($log->photo) {
            Storage::disk('public')->delete($log->photo);
        }
        $log->delete();

        return redirect()->back()->with('success', 'Catatan timeline berhasil dihapus.');
    }
}
