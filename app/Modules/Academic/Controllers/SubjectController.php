<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::query()
            ->with(['unit'])
            // Scope by Unit (assuming global scope or manual check, implementing manual for safety)
            ->when(session('active_unit_id'), function ($query, $unitId) {
                $query->where('unit_id', $unitId);
            })
            // Search
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            // Filter Group
            ->when($request->group, function ($query, $group) {
                $query->where('group', $group);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Academic/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => $request->only(['search', 'group']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk menambah mata pelajaran.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'group' => 'nullable|string|in:A,B,C,Mulok,Lainnya',
        ]);

        // Auto-assign Unit ID from session
        $validated['unit_id'] = session('active_unit_id');

        Subject::create($validated);

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah mata pelajaran.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($subject->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'group' => 'nullable|string|in:A,B,C,Mulok,Lainnya',
        ]);

        $subject->update($validated);

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus mata pelajaran.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($subject->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $subject->delete();

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}
