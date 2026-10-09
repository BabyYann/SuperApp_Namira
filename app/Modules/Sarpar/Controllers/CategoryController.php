<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('inventories')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sarpar/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'admin_unit', 'koordinator_sarpar', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola kategori.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:5|unique:sarpar_categories,code',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            Category::create($validated);
            return redirect()->back()->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah kategori.');
        }
    }

    public function update(Request $request, Category $category)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'admin_unit', 'koordinator_sarpar', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola kategori.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:5|unique:sarpar_categories,code,' . $category->id,
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $category->update($validated);
            return redirect()->back()->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui kategori.');
        }
    }

    public function destroy(Category $category)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'admin_unit', 'koordinator_sarpar', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus kategori.');
        }

        if ($category->inventories()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki inventaris.');
        }

        try {
            $category->delete();
            return redirect()->back()->with('success', 'Kategori dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus kategori.');
        }
    }
}
