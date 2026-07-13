<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLocation::with('unit')->latest();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        $locations = $query->paginate(10)->withQueryString();
        $units = Unit::all();

        return Inertia::render('Yayasan/AttendanceLocations/Index', [
            'locations' => $locations,
            'units' => $units,
            'filters' => $request->only(['search', 'unit_id']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola lokasi absensi.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:10',
            'is_active' => 'boolean',
        ]);

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $validated['unit_id'] != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menambahkan lokasi absensi untuk unit lain.');
        }

        AttendanceLocation::create($validated);

        return redirect()->back()->with('success', 'Lokasi absensi berhasil ditambahkan.');
    }

    public function update(Request $request, AttendanceLocation $attendanceLocation)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola lokasi absensi.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $attendanceLocation->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah lokasi absensi unit lain.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:10',
            'is_active' => 'boolean',
        ]);

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $validated['unit_id'] != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan lokasi absensi ke unit lain.');
        }

        $attendanceLocation->update($validated);

        return redirect()->back()->with('success', 'Lokasi absensi berhasil diperbarui.');
    }

    public function destroy(AttendanceLocation $attendanceLocation)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus lokasi absensi.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $attendanceLocation->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus lokasi absensi unit lain.');
        }

        $attendanceLocation->delete();
        return redirect()->back()->with('success', 'Lokasi absensi berhasil dihapus.');
    }
}
