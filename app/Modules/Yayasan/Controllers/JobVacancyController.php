<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobVacancyController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya Yayasan yang dapat mengelola lowongan karir.');
        }

        $query = JobVacancy::with(['unit', 'creator'])->withCount('applicants');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('unit') && $request->unit !== 'all') {
            $query->where('unit_id', $request->unit);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vacancies = $query->latest()->paginate(15)->withQueryString();
        $units = Unit::all();

        return Inertia::render('Yayasan/Recruitment/Vacancies', [
            'vacancies' => $vacancies,
            'units' => $units,
            'filters' => $request->only(['search', 'unit', 'status']),
            'categories' => [
                'teacher' => 'Tenaga Pendidik (Guru)',
                'staff' => 'Tenaga Kependidikan (Staf)',
                'operational' => 'Operasional & Sarpar',
                'other' => 'Lainnya',
            ],
            'types' => [
                'full_time' => 'Penuh Waktu (Full Time)',
                'part_time' => 'Paruh Waktu (Part Time)',
                'contract' => 'Kontrak',
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'category' => 'required|string|in:teacher,staff,operational,other',
            'type' => 'required|string|in:full_time,part_time,contract',
            'quota' => 'required|integer|min:1',
            'deadline' => 'nullable|date',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'status' => 'required|in:open,closed',
        ]);

        $vacancy = JobVacancy::create([
            'title' => $validated['title'],
            'unit_id' => $validated['unit_id'] ?: null,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'quota' => $validated['quota'],
            'deadline' => $validated['deadline'] ?: null,
            'description' => $validated['description'],
            'requirements' => $validated['requirements'],
            'status' => $validated['status'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Lowongan Kerja baru berhasil dipublikasikan!');
    }

    public function update(Request $request, JobVacancy $jobVacancy)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'category' => 'required|string|in:teacher,staff,operational,other',
            'type' => 'required|string|in:full_time,part_time,contract',
            'quota' => 'required|integer|min:1',
            'deadline' => 'nullable|date',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'status' => 'required|in:open,closed',
        ]);

        $jobVacancy->update([
            'title' => $validated['title'],
            'unit_id' => $validated['unit_id'] ?: null,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'quota' => $validated['quota'],
            'deadline' => $validated['deadline'] ?: null,
            'description' => $validated['description'],
            'requirements' => $validated['requirements'],
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Data Lowongan Kerja berhasil diperbarui!');
    }

    public function destroy(JobVacancy $jobVacancy)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        $jobVacancy->delete();

        return redirect()->back()->with('success', 'Lowongan Kerja berhasil dihapus.');
    }
}
