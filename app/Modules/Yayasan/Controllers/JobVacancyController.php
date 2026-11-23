<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobVacancyController extends Controller
{
    private function resolveAccess(): array
    {
        $user = auth()->user();
        $isGlobalWrite = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan']);
        $isGlobalRead = $isGlobalWrite || $user->hasAnyRole(['pembina_yayasan', 'pengawas_yayasan']);
        $isUnitWrite = !$isGlobalRead && $user->hasAnyRole(['humas_unit', 'admin_unit']);

        $activeUnitId = session('active_unit_id');
        if (!$activeUnitId && $isUnitWrite) {
            $activeUnitId = \Illuminate\Support\Facades\DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_id', $user->id)
                ->whereIn('r.name', ['humas_unit', 'admin_unit'])
                ->whereNotNull('mhr.team_id')
                ->value('mhr.team_id');
        }

        return [
            'canView' => $isGlobalRead || $isUnitWrite,
            'canWrite' => $isGlobalWrite || $isUnitWrite,
            'isUnitScoped' => !$isGlobalRead,
            'activeUnitId' => $activeUnitId ? (int) $activeUnitId : null,
        ];
    }

    public function index(Request $request)
    {
        $access = $this->resolveAccess();
        if (!$access['canView']) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengelola lowongan karir.');
        }

        $query = JobVacancy::with(['unit', 'creator'])->withCount('applicants');

        if ($access['isUnitScoped']) {
            if (!$access['activeUnitId']) {
                abort(403, 'Akses Ditolak: Unit aktif tidak ditemukan.');
            }
            $query->where('unit_id', $access['activeUnitId']);
        } elseif ($request->filled('unit') && $request->unit !== 'all') {
            $query->where('unit_id', $request->unit);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vacancies = $query->latest()->paginate(15)->withQueryString();
        $units = $access['isUnitScoped']
            ? Unit::where('id', $access['activeUnitId'])->get()
            : Unit::all();
        $activeUnit = $access['activeUnitId'] ? Unit::find($access['activeUnitId']) : null;

        return Inertia::render('Yayasan/Recruitment/Vacancies', [
            'vacancies' => $vacancies,
            'units' => $units,
            'isUnitScoped' => $access['isUnitScoped'],
            'activeUnitId' => $access['activeUnitId'],
            'activeUnitName' => $activeUnit?->name,
            'canWrite' => $access['canWrite'],
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
        $access = $this->resolveAccess();
        if (!$access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && !$access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Unit aktif tidak ditemukan.');
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

        // Unit-scoped Humas/Admin Unit can only publish vacancies for their own active unit
        $targetUnitId = $access['isUnitScoped']
            ? $access['activeUnitId']
            : ($validated['unit_id'] ?: null);

        JobVacancy::create([
            'title' => $validated['title'],
            'unit_id' => $targetUnitId,
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
        $access = $this->resolveAccess();
        if (!$access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && (int) $jobVacancy->unit_id !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat mengubah lowongan milik unit Anda.');
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

        $targetUnitId = $access['isUnitScoped']
            ? $access['activeUnitId']
            : ($validated['unit_id'] ?: null);

        $jobVacancy->update([
            'title' => $validated['title'],
            'unit_id' => $targetUnitId,
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
        $access = $this->resolveAccess();
        if (!$access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && (int) $jobVacancy->unit_id !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat menghapus lowongan milik unit Anda.');
        }

        $jobVacancy->delete();

        return redirect()->back()->with('success', 'Lowongan Kerja berhasil dihapus.');
    }
}
