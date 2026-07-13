<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\UsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageLogController extends Controller
{
    /**
     * Record usage for consumable inventory
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:sarpar_inventories,id',
            'quantity_used' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        if ($inventory->item_type !== 'consumable') {
            return redirect()->back()->with('error', 'Hanya barang habis pakai yang bisa dicatat penggunaannya.');
        }

        if ($validated['quantity_used'] > $inventory->quantity) {
            return redirect()->back()->with('error', 'Jumlah penggunaan melebihi stok tersedia.');
        }

        try {
            UsageLog::create([
                'inventory_id' => $validated['inventory_id'],
                'used_by' => auth()->id(),
                'quantity_used' => $validated['quantity_used'],
                'used_date' => now(),
                'purpose' => $validated['purpose'],
                'notes' => $validated['notes'],
            ]);

            // Decrease inventory quantity
            $inventory->decrement('quantity', $validated['quantity_used']);

            // Check if low stock
            $message = 'Penggunaan berhasil dicatat.';
            if ($inventory->fresh()->is_low_stock) {
                $message .= ' ⚠️ Stok menipis!';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencatat penggunaan.');
        }
    }
}
