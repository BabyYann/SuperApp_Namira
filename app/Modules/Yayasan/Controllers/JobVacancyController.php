<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobApplicant;
use App\Models\JobVacancy;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class JobVacancyController extends Controller
{
    private function resolveAccess(): array
    {
        $user = auth()->user();
        $isGlobalWrite = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan', 'staff_yayasan']);
        $isGlobalRead = $isGlobalWrite || $user->hasAnyRole(['pembina_yayasan', 'pengawas_yayasan']);
        $unitWriteRoles = ['humas_unit', 'admin_unit', 'kepala_sekolah'];
        $isUnitWrite = ! $isGlobalRead && $user->hasAnyRole($unitWriteRoles);

        $activeUnitId = session('active_unit_id');
        if (! $activeUnitId && $isUnitWrite) {
            $activeUnitId = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_id', $user->id)
                ->whereIn('r.name', $unitWriteRoles)
                ->whereNotNull('mhr.team_id')
                ->value('mhr.team_id');
        }

        return [
            'canView' => $isGlobalRead || $isUnitWrite,
            'canWrite' => $isGlobalWrite || $isUnitWrite,
            'isUnitScoped' => ! $isGlobalRead,
            'activeUnitId' => $activeUnitId ? (int) $activeUnitId : null,
        ];
    }

    public function index(Request $request)
    {
        $access = $this->resolveAccess();
        if (! $access['canView']) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengelola lowongan karir.');
        }

        $baseQuery = JobVacancy::query();

        if ($access['isUnitScoped']) {
            if (! $access['activeUnitId']) {
                abort(403, 'Akses Ditolak: Unit aktif tidak ditemukan.');
            }
            $baseQuery->where('unit_id', $access['activeUnitId']);
        }

        $query = (clone $baseQuery)->with(['unit', 'creator'])->withCount('applicants');

        if (! $access['isUnitScoped'] && $request->filled('unit') && $request->unit !== 'all') {
            if ($request->unit === 'yayasan') {
                $query->whereNull('unit_id');
            } else {
                $query->where('unit_id', (int) $request->unit);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('requirements', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['open', 'closed'], true)) {
            $query->where('status', $request->status);
        }

        $vacancies = $query->latest()->paginate(15)->withQueryString();

        $totalVacancies = (clone $baseQuery)->count();
        $openVacancies = (clone $baseQuery)->where('status', 'open')->count();
        $closedVacancies = max(0, $totalVacancies - $openVacancies);
        $totalApplicants = JobApplicant::whereIn('job_vacancy_id', (clone $baseQuery)->select('id'))->count();

        $units = $access['isUnitScoped']
            ? Unit::where('id', $access['activeUnitId'])->get()
            : Unit::orderBy('name')->get();
        $activeUnit = $access['activeUnitId'] ? Unit::find($access['activeUnitId']) : null;

        return Inertia::render('Yayasan/Recruitment/Vacancies', [
            'vacancies' => $vacancies,
            'units' => $units,
            'stats' => [
                'total' => $totalVacancies,
                'open' => $openVacancies,
                'closed' => $closedVacancies,
                'applicants' => $totalApplicants,
            ],
            'isUnitScoped' => $access['isUnitScoped'],
            'activeUnitId' => $access['activeUnitId'],
            'activeUnitName' => $activeUnit?->name,
            'canWrite' => $access['canWrite'],
            'filters' => $request->only(['search', 'unit', 'status']),
            'categories' => [
                'teacher' => 'Tenaga Pendidik (Guru)',
                'staff' => 'Tenaga Kependidikan (Staf)',
                'operational' => 'Operasional & Sarpras',
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
        if (! $access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && ! $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Unit aktif tidak ditemukan.');
        }

        $hasPoster = $request->hasFile('poster');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'category' => 'required|string|in:teacher,staff,operational,other',
            'type' => 'required|string|in:full_time,part_time,contract',
            'quota' => 'required|integer|min:1|max:500',
            'deadline' => 'nullable|date',
            'contact_whatsapp' => 'nullable|string|max:30',
            'placement_address' => 'nullable|string|max:500',
            'description' => $hasPoster ? 'nullable|string' : 'required|string',
            'requirements' => $hasPoster ? 'nullable|string' : 'required|string',
            'status' => 'required|in:open,closed',
        ], [
            'title.required' => 'Judul posisi lowongan wajib diisi.',
            'poster.image' => 'File poster harus berupa gambar (JPG, PNG, atau WEBP).',
            'poster.max' => 'Ukuran file poster maksimal 10MB.',
            'description.required' => 'Deskripsi pekerjaan wajib diisi jika tidak mengunggah poster lowongan.',
            'requirements.required' => 'Kualifikasi & persyaratan wajib diisi jika tidak mengunggah poster lowongan.',
        ]);

        // Unit-scoped Humas / Admin Unit / Kepala Sekolah can only publish vacancies for their own active unit
        $targetUnitId = $access['isUnitScoped']
            ? $access['activeUnitId']
            : (! empty($validated['unit_id']) ? (int) $validated['unit_id'] : null);

        $posterPath = $hasPoster
            ? $request->file('poster')->store('recruitment/posters', 'public')
            : null;

        JobVacancy::create([
            'title' => trim($validated['title']),
            'unit_id' => $targetUnitId,
            'poster_path' => $posterPath,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'quota' => (int) $validated['quota'],
            'deadline' => ! empty($validated['deadline']) ? $validated['deadline'] : null,
            'contact_whatsapp' => ! empty($validated['contact_whatsapp']) ? trim($validated['contact_whatsapp']) : null,
            'placement_address' => ! empty($validated['placement_address']) ? trim($validated['placement_address']) : null,
            'description' => ! empty($validated['description']) ? $validated['description'] : null,
            'requirements' => ! empty($validated['requirements']) ? $validated['requirements'] : null,
            'status' => $validated['status'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Lowongan Kerja baru berhasil dipublikasikan!');
    }

    public function update(Request $request, JobVacancy $jobVacancy)
    {
        $access = $this->resolveAccess();
        if (! $access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && (int) $jobVacancy->unit_id !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat mengubah lowongan milik unit Anda.');
        }

        $willHavePoster = $request->hasFile('poster')
            || (! $request->boolean('remove_poster') && ! empty($jobVacancy->poster_path));

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'remove_poster' => 'nullable|boolean',
            'category' => 'required|string|in:teacher,staff,operational,other',
            'type' => 'required|string|in:full_time,part_time,contract',
            'quota' => 'required|integer|min:1|max:500',
            'deadline' => 'nullable|date',
            'contact_whatsapp' => 'nullable|string|max:30',
            'placement_address' => 'nullable|string|max:500',
            'description' => $willHavePoster ? 'nullable|string' : 'required|string',
            'requirements' => $willHavePoster ? 'nullable|string' : 'required|string',
            'status' => 'required|in:open,closed',
        ], [
            'title.required' => 'Judul posisi lowongan wajib diisi.',
            'poster.image' => 'File poster harus berupa gambar (JPG, PNG, atau WEBP).',
            'poster.max' => 'Ukuran file poster maksimal 10MB.',
            'description.required' => 'Deskripsi pekerjaan wajib diisi jika lowongan tidak memiliki poster.',
            'requirements.required' => 'Kualifikasi & persyaratan wajib diisi jika lowongan tidak memiliki poster.',
        ]);

        $targetUnitId = $access['isUnitScoped']
            ? $access['activeUnitId']
            : (! empty($validated['unit_id']) ? (int) $validated['unit_id'] : null);

        $posterPath = $jobVacancy->poster_path;
        if ($request->hasFile('poster')) {
            if ($posterPath && Storage::disk('public')->exists($posterPath)) {
                Storage::disk('public')->delete($posterPath);
            }
            $posterPath = $request->file('poster')->store('recruitment/posters', 'public');
        } elseif ($request->boolean('remove_poster') && $posterPath) {
            if (Storage::disk('public')->exists($posterPath)) {
                Storage::disk('public')->delete($posterPath);
            }
            $posterPath = null;
        }

        $jobVacancy->update([
            'title' => trim($validated['title']),
            'unit_id' => $targetUnitId,
            'poster_path' => $posterPath,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'quota' => (int) $validated['quota'],
            'deadline' => ! empty($validated['deadline']) ? $validated['deadline'] : null,
            'contact_whatsapp' => ! empty($validated['contact_whatsapp']) ? trim($validated['contact_whatsapp']) : null,
            'placement_address' => ! empty($validated['placement_address']) ? trim($validated['placement_address']) : null,
            'description' => ! empty($validated['description']) ? $validated['description'] : null,
            'requirements' => ! empty($validated['requirements']) ? $validated['requirements'] : null,
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Data Lowongan Kerja berhasil diperbarui!');
    }

    public function toggleStatus(JobVacancy $jobVacancy)
    {
        $access = $this->resolveAccess();
        if (! $access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && (int) $jobVacancy->unit_id !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat mengubah status lowongan milik unit Anda.');
        }

        $nextStatus = $jobVacancy->status === 'open' ? 'closed' : 'open';
        $jobVacancy->update(['status' => $nextStatus]);

        $label = $nextStatus === 'open' ? 'dibuka kembali' : 'ditutup';

        return redirect()->back()->with('success', "Lowongan \"{$jobVacancy->title}\" berhasil {$label}.");
    }

    public function destroy(JobVacancy $jobVacancy)
    {
        $access = $this->resolveAccess();
        if (! $access['canWrite']) {
            abort(403, 'Akses Ditolak.');
        }

        if ($access['isUnitScoped'] && (int) $jobVacancy->unit_id !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat menghapus lowongan milik unit Anda.');
        }

        if ($jobVacancy->poster_path && Storage::disk('public')->exists($jobVacancy->poster_path)) {
            Storage::disk('public')->delete($jobVacancy->poster_path);
        }

        $jobVacancy->delete();

        return redirect()->back()->with('success', 'Lowongan Kerja berhasil dihapus.');
    }
}
