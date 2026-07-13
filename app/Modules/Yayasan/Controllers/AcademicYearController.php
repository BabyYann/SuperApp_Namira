<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::latest()->get();

        return Inertia::render('Yayasan/AcademicYears/Index', [
            'academicYears' => $academicYears,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya pihak Yayasan yang dapat mengelola Tahun Akademik.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'semester' => 'required|in:ganjil,genap',
            'is_active' => 'boolean',
        ]);

        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        AcademicYear::create($validated);

        return redirect()->back()->with('success', 'Tahun Akademik berhasil dibuat.');
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya pihak Yayasan yang dapat mengelola Tahun Akademik.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'semester' => 'required|in:ganjil,genap',
            'is_active' => 'boolean',
        ]);

        if ($validated['is_active']) {
            AcademicYear::where('id', '!=', $academicYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $academicYear->update($validated);

        return redirect()->back()->with('success', 'Tahun Akademik berhasil diperbarui.');
    }
    
    public function setAsActive(AcademicYear $academicYear)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya pihak Yayasan yang dapat mengubah Tahun Akademik aktif.');
        }

        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);
         
        return redirect()->back()->with('success', 'Tahun Akademik aktif diperbarui.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya pihak Yayasan yang dapat menghapus Tahun Akademik.');
        }

        $academicYear->delete();

        return redirect()->back()->with('success', 'Tahun Akademik dihapus.');
    }
}
