<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\ViolationCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ViolationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ViolationCategory::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by Unit Scope
        $query->where('unit_id', session('active_unit_id'));
        
        $categories = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Counseling/Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'bk'])) {
            abort(403, 'Akses Ditolak: Hanya Guru BK atau Admin yang dapat mengelola Kategori Pelanggaran.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:ringan,sedang,berat',
            'default_points' => 'required|integer|min:0',
        ]);

        ViolationCategory::create([
            'unit_id' => session('active_unit_id'), // Auto-assign to current unit
            'name' => $request->name,
            'type' => $request->type,
            'default_points' => $request->default_points,
        ]);

        return redirect()->back()->with('success', 'Kategori pelanggaran berhasil ditambahkan.');
    }

    public function update(Request $request, ViolationCategory $category)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'bk'])) {
            abort(403, 'Akses Ditolak: Hanya Guru BK atau Admin yang dapat mengelola Kategori Pelanggaran.');
        }

        if ($category->unit_id != session('active_unit_id')) abort(403);
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:ringan,sedang,berat',
            'default_points' => 'required|integer|min:0',
        ]);

        $category->update([
            'name' => $request->name,
            'type' => $request->type,
            'default_points' => $request->default_points,
        ]);

        return redirect()->back()->with('success', 'Kategori pelanggaran berhasil diperbarui.');
    }

    public function destroy(ViolationCategory $category)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'bk'])) {
            abort(403, 'Akses Ditolak: Hanya Guru BK atau Admin yang dapat menghapus Kategori Pelanggaran.');
        }

        if ($category->unit_id != session('active_unit_id')) abort(403);
        $category->delete();
        return redirect()->back()->with('success', 'Kategori pelanggaran dihapus.');
    }
}
