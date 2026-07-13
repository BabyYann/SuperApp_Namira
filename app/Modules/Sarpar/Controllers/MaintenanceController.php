<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        $logs = MaintenanceLog::with(['inventory', 'reporter', 'handler'])
            ->whereHas('inventory', fn($q) => $q->where('unit_id', $unitId))
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($q, $search) {
                $q->whereHas('inventory', fn($inv) => $inv->where('name', 'like', "%{$search}%"))
                  ->orWhere('issue', 'like', "%{$search}%");
            })
            ->latest('reported_date')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Sarpar/Maintenance/Index', [
            'logs' => $logs,
            'filters' => request()->only(['status', 'search']),
        ]);
    }

    /**
     * Report new maintenance issue (by Teacher or Koordinator)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:sarpar_inventories,id',
            'issue' => 'required|string|max:1000',
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);
        
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        try {
            MaintenanceLog::create([
                'inventory_id' => $validated['inventory_id'],
                'reported_by' => auth()->id(),
                'issue' => $validated['issue'],
                'reported_date' => now(),
                'status' => 'pending',
            ]);

            // Update inventory condition if not already marked
            if ($inventory->condition === 'baik') {
                $inventory->update(['condition' => 'rusak_ringan']);
            }

            return redirect()->back()->with('success', 'Laporan kerusakan berhasil dikirim.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim laporan.');
        }
    }

    /**
     * Handle maintenance (by Koordinator)
     */
    public function handle(Request $request, MaintenanceLog $log)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($log->inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $validated = $request->validate([
            'action_taken' => 'required|string|max:1000',
            'cost' => 'nullable|integer|min:0',
            'resolved' => 'required|boolean',
            'inventory_condition' => 'required|in:baik,rusak_ringan,rusak_berat',
        ]);

        try {
            $log->update([
                'handled_by' => auth()->id(),
                'action_taken' => $validated['action_taken'],
                'cost' => $validated['cost'],
                'status' => $validated['resolved'] ? 'resolved' : 'in_progress',
                'resolved_date' => $validated['resolved'] ? now() : null,
            ]);

            // Update inventory condition and status
            $log->inventory->update([
                'condition' => $validated['inventory_condition'],
                'status' => $validated['resolved'] ? 'tersedia' : 'diperbaiki',
            ]);

            return redirect()->back()->with('success', 'Status perbaikan diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui status.');
        }
    }

    /**
     * Cancel maintenance request
     */
    public function cancel(MaintenanceLog $log)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($log->inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        if ($log->status === 'resolved') {
            return redirect()->back()->with('error', 'Tidak dapat membatalkan laporan yang sudah selesai.');
        }

        try {
            $log->update(['status' => 'cancelled']);
            return redirect()->back()->with('success', 'Laporan dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan laporan.');
        }
    }
}
