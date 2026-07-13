<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\CounselingSession;
use App\Modules\Academic\Models\Student;
use App\Modules\Counseling\Models\Violation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CounselingSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = CounselingSession::query()
            ->with(['student.classroom', 'counselor', 'violation.category'])
            ->where('unit_id', session('active_unit_id'));

        if ($request->search) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nis', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($s) => [
                'id' => $s->id,
                'student_name' => $s->student->full_name,
                'student_classroom' => $s->student->classroom->name ?? '-',
                'counselor_name' => $s->counselor->name,
                'date' => $s->date->format('Y-m-d'),
                'time' => $s->time->format('H:i'),
                'method' => $s->method,
                'status' => $s->status,
                'violation' => $s->violation ? $s->violation->category->name : null,
                'notes_preview' => \Illuminate\Support\Str::limit($s->notes, 50),
                'can_action' => Auth::user()->hasAnyRole(['super_admin_yayasan', 'admin_unit']) || $s->counselor_id === Auth::id(),
            ]);

        return Inertia::render('Counseling/Session/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'start_date', 'end_date', 'status']),
        ]);
    }

    public function create()
    {
        $unitId = session('active_unit_id');
        
        $classrooms = \App\Modules\Academic\Models\Classroom::where('unit_id', $unitId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $students = Student::where('unit_id', $unitId)
            ->select('id', 'full_name as label', 'classroom_id')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Counseling/Session/Create', [
            'classrooms' => $classrooms,
            'students' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'time' => 'required',
            'method' => 'required|in:Offline,Online,Home Visit',
            'violation_id' => 'nullable|exists:violations,id',
            'notes' => 'nullable|string',
        ]);

        // Unit isolation: verify student belongs to active unit
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $student = Student::findOrFail($request->student_id);
            if ($student->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Siswa berada pada unit lain.');
            }
        }

        $validated['unit_id'] = session('active_unit_id');
        $validated['counselor_id'] = Auth::id();
        $validated['status'] = 'Scheduled';

        CounselingSession::create($validated);

        return redirect()->route('counseling.sessions.index')
            ->with('success', 'Sesi konseling berhasil dijadwalkan.');
    }

    public function edit(CounselingSession $session)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $session->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Data sesi konseling ini berada di unit lain.');
        }

        $session->load(['student.classroom', 'violation.category']);
         
         return Inertia::render('Counseling/Session/Edit', [
             'session' => [
                 'id' => $session->id,
                 'student_id' => $session->student_id,
                 'student_name' => $session->student->full_name,
                 'student_classroom' => $session->student->classroom->name ?? '-',
                 'date' => $session->date->format('Y-m-d'),
                 'time' => $session->time->format('H:i'),
                 'method' => $session->method,
                 'status' => $session->status,
                 'notes' => $session->notes,
                 'follow_up_action' => $session->follow_up_action,
                 'violation_id' => $session->violation_id,
             ]
         ]);
    }

    public function update(Request $request, CounselingSession $session)
    {
        // Authorization Check
        if (!Auth::user()->hasAnyRole(['super_admin_yayasan', 'admin_unit']) && $session->counselor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Unit isolation
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $session->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Data sesi konseling ini berada di unit lain.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'method' => 'required|in:Offline,Online,Home Visit',
            'status' => 'required|in:Scheduled,Completed,Cancelled',
            'notes' => 'nullable|string',
            'follow_up_action' => 'nullable|string',
        ]);

        $session->update($validated);

        return redirect()->route('counseling.sessions.index')
            ->with('success', 'Sesi konseling berhasil diperbarui.');
    }

    public function destroy(CounselingSession $session)
    {
        // Authorization Check
        if (!Auth::user()->hasAnyRole(['super_admin_yayasan', 'admin_unit']) && $session->counselor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Unit isolation
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $session->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Data sesi konseling ini berada di unit lain.');
        }

        $session->delete();
        return redirect()->back()->with('success', 'Sesi konseling dihapus.');
    }
}
