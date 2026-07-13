<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinanceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceTypeController extends Controller
{
    public function index(Request $request)
    {
        // Scope by Unit (assuming global scope or manual check)
        // If FinanceType is strictly per unit, we should filter by session('active_unit_id') if needed
        // For now, let's assume it is per unit as defined in migration
        
        $types = FinanceType::query()
            ->with('unit')
            ->when(session('active_unit_id'), function ($query, $unitId) {
                $query->where('unit_id', $unitId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Finance/Types/Index', [
            'types' => $types,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menambah jenis biaya.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,once,yearly',
            'is_active' => 'boolean',
        ]);

        $validated['unit_id'] = session('active_unit_id');

        FinanceType::create($validated);

        return redirect()->back()->with('success', 'Jenis Biaya berhasil ditambahkan.');
    }

    public function update(Request $request, FinanceType $financeType)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengubah jenis biaya.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $financeType->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah data jenis biaya dari unit lain.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,once,yearly',
            'is_active' => 'boolean',
        ]);

        $financeType->update($validated);

        return redirect()->back()->with('success', 'Jenis Biaya berhasil diperbarui.');
    }

    public function destroy(FinanceType $financeType)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus jenis biaya.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $financeType->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus data jenis biaya dari unit lain.');
        }

        $financeType->delete();

        return redirect()->back()->with('success', 'Jenis Biaya berhasil dihapus.');
    }
}
