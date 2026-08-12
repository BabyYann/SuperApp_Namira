<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\Transfer;
use App\Modules\Sarpar\Models\Disposal;
use App\Modules\Sarpar\Models\Room;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetActionController extends Controller
{
    /**
     * Process Mutasi (Transfer) Barang
     */
    public function transfer(Request $request, Inventory $inventory)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses mutasi barang.');
        }

        $validated = $request->validate([
            'to_unit_id' => 'nullable|exists:units,id',
            'to_room_id' => 'nullable|exists:sarpar_rooms,id',
            'reason' => 'required|string|max:1000',
        ]);

        $fromUnitId = $inventory->unit_id;
        $fromRoomId = $inventory->room_id;

        $toUnitId = $validated['to_unit_id'] ?? $fromUnitId;
        $toRoomId = $validated['to_room_id'] ?? null;

        try {
            $transfer = Transfer::create([
                'inventory_id' => $inventory->id,
                'from_unit_id' => $fromUnitId,
                'to_unit_id' => $toUnitId,
                'from_room_id' => $fromRoomId,
                'to_room_id' => $toRoomId,
                'reason' => $validated['reason'],
                'transferred_by' => auth()->id(),
            ]);

            // Update Inventory Location
            $inventory->update([
                'unit_id' => $toUnitId,
                'room_id' => $toRoomId,
            ]);

            return redirect()->back()->with('success', 'Mutasi barang berhasil diproses. Berita Acara dapat diunduh.')
                ->with('transfer_id', $transfer->id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses mutasi: ' . $e->getMessage());
        }
    }

    /**
     * Process Penghapusan / Afkir Barang
     */
    public function disposal(Request $request, Inventory $inventory)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses penghapusan barang.');
        }

        $validated = $request->validate([
            'disposal_type' => 'required|in:rusak_berat,hilang,dijual,hibah',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $disposal = Disposal::create([
                'inventory_id' => $inventory->id,
                'unit_id' => $inventory->unit_id,
                'disposal_type' => $validated['disposal_type'],
                'reason' => $validated['reason'],
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
            ]);

            // Update Inventory status
            $inventory->update([
                'status' => 'dihapus',
                'condition' => $validated['disposal_type'] === 'rusak_berat' ? 'rusak_berat' : $inventory->condition,
            ]);

            return redirect()->back()->with('success', 'Penghapusan inventaris berhasil dicatat. Berita Acara dapat diunduh.')
                ->with('disposal_id', $disposal->id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses penghapusan: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF Berita Acara Mutasi
     */
    public function transferPdf(Transfer $transfer)
    {
        $transfer->load(['inventory', 'fromUnit', 'toUnit', 'fromRoom', 'toRoom', 'transferredBy']);

        $pdf = Pdf::loadView('sarpar.pdf.transfer_ba', [
            'transfer' => $transfer,
            'dateFormatted' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Berita_Acara_Mutasi_' . $transfer->inventory->code . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Download PDF Berita Acara Penghapusan
     */
    public function disposalPdf(Disposal $disposal)
    {
        $disposal->load(['inventory', 'unit', 'requestedBy', 'approvedBy']);

        $pdf = Pdf::loadView('sarpar.pdf.disposal_ba', [
            'disposal' => $disposal,
            'dateFormatted' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Berita_Acara_Penghapusan_' . $disposal->inventory->code . '.pdf';
        return $pdf->download($filename);
    }
}
