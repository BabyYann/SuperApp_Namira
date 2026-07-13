<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\Loan;
use App\Modules\Academic\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        $loans = Loan::with(['inventory', 'borrower', 'processedBy'])
            ->whereHas('inventory', fn($q) => $q->where('unit_id', $unitId))
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($q, $search) {
                $q->whereHas('inventory', fn($inv) => $inv->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('borrower', fn($usr) => $usr->where('name', 'like', "%{$search}%"));
            })
            ->latest('loan_date')
            ->paginate(30)
            ->withQueryString();

        // Get available inventories for lending
        $inventories = Inventory::where('unit_id', $unitId)
            ->where('status', 'tersedia')
            ->where('condition', '!=', 'rusak_berat')
            ->orderBy('name')
            ->get();

        // Get teachers in this unit
        $teachers = Teacher::where('unit_id', $unitId)
            ->with('user')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Sarpar/Loans/Index', [
            'loans' => $loans,
            'inventories' => $inventories,
            'teachers' => $teachers,
            'filters' => request()->only(['status', 'search']),
        ]);
    }

    /**
     * Create new loan (by Koordinator)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:sarpar_inventories,id',
            'borrower_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);
        
        if ($inventory->unit_id != session('active_unit_id')) {
            abort(403);
        }

        if ($inventory->status !== 'tersedia') {
            return redirect()->back()->with('error', 'Inventaris tidak tersedia untuk dipinjam.');
        }

        if ($validated['quantity'] > $inventory->quantity) {
            return redirect()->back()->with('error', 'Jumlah melebihi stok tersedia.');
        }

        try {
            Loan::create([
                'inventory_id' => $validated['inventory_id'],
                'borrower_id' => $validated['borrower_id'],
                'processed_by' => auth()->id(),
                'quantity' => $validated['quantity'],
                'loan_date' => now(),
                'due_date' => $validated['due_date'],
                'status' => 'borrowed',
                'notes' => $validated['notes'],
            ]);

            // Update inventory status
            $inventory->update(['status' => 'dipinjam']);

            return redirect()->back()->with('success', 'Peminjaman berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencatat peminjaman.');
        }
    }

    /**
     * Return borrowed item
     */
    public function return(Request $request, Loan $loan)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses pengembalian peminjaman.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $loan->inventory->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses data peminjaman dari unit lain.');
        }

        if ($loan->status !== 'borrowed' && $loan->status !== 'overdue') {
            return redirect()->back()->with('error', 'Peminjaman sudah dikembalikan.');
        }

        $validated = $request->validate([
            'condition' => 'required|in:baik,rusak_ringan,rusak_berat',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $loan->update([
                'status' => 'returned',
                'return_date' => now(),
                'notes' => $validated['notes'] ?? $loan->notes,
            ]);

            // Update inventory status and condition
            $loan->inventory->update([
                'status' => 'tersedia',
                'condition' => $validated['condition'],
            ]);

            return redirect()->back()->with('success', 'Pengembalian berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencatat pengembalian.');
        }
    }

    /**
     * Mark item as lost
     */
    public function markLost(Loan $loan)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses status hilang peminjaman.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $loan->inventory->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses data peminjaman dari unit lain.');
        }

        if ($loan->status === 'returned' || $loan->status === 'lost') {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        try {
            $loan->update(['status' => 'lost']);
            $loan->inventory->update(['status' => 'dihapus']);

            return redirect()->back()->with('success', 'Inventaris ditandai sebagai hilang.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah status.');
        }
    }
}
