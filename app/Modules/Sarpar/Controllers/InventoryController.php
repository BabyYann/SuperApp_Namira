<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Category;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\Room;
use App\Modules\Sarpar\Services\InventoryCodeGenerator;
use App\Modules\Sarpar\Exports\InventoryExport;
use App\Modules\Yayasan\Models\Unit;
use App\Modules\Academic\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        $inventories = Inventory::with(['category', 'room', 'classroom'])
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when(request('category_id'), fn($q, $catId) => $q->where('category_id', $catId))
            ->when(request('funding_source'), fn($q, $source) => $q->where('funding_source', $source))
            ->when(request('item_type'), fn($q, $type) => $q->where('item_type', $type))
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->when(request('condition'), fn($q, $condition) => $q->where('condition', $condition))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $rooms = Room::where('unit_id', $unitId)->orderBy('name')->get();
        $classrooms = Classroom::where('unit_id', $unitId)->orderBy('name')->get();

        return Inertia::render('Sarpar/Inventories/Index', [
            'inventories' => $inventories,
            'categories' => $categories,
            'rooms' => $rooms,
            'classrooms' => $classrooms,
            'filters' => request()->only(['search', 'category_id', 'funding_source', 'item_type', 'status', 'condition']),
        ]);
    }

    public function export()
    {
        $unitId = session('active_unit_id');
        $filters = request()->only(['category_id', 'funding_source', 'item_type', 'status', 'condition']);
        $format = request('format', 'csv');
        
        $exporter = new InventoryExport($unitId, $filters);
        
        if ($format === 'pdf') {
            $data = $exporter->export();
            return $this->generatePdf($data);
        }
        
        $csv = $exporter->toCsv();
        $filename = 'inventaris_' . date('Y-m-d_His') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function generatePdf($data)
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Laporan Inventaris</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; }
                h1 { text-align: center; color: #0d9488; margin-bottom: 5px; }
                .date { text-align: center; color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background: #0d9488; color: white; padding: 8px; text-align: left; border: 1px solid #0d9488; }
                td { padding: 6px 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #999; }
            </style>
        </head>
        <body>
            <h1>📦 Laporan Data Inventaris</h1>
            <p class="date">Tanggal: ' . date('d F Y H:i') . '</p>
            <table>
                <tr>';
        
        foreach ($data['headers'] as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';
        
        foreach ($data['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                // Format currency for price columns
                if ($i == 9 || $i == 10) {
                    $cell = 'Rp ' . number_format((float)$cell, 0, ',', '.');
                }
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>
            <p class="footer">Total: ' . $data['count'] . ' item | Dicetak dari SuperApp Namira</p>
        </body>
        </html>';
        
        $filename = 'inventaris_' . date('Y-m-d_His') . '.html';
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function show(Inventory $inventory)
    {
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $inventory->load([
            'category', 
            'room', 
            'classroom',
            'maintenanceLogs.reporter', 
            'maintenanceLogs.handler', 
            'loans.borrower',
            'usageLogs.user'
        ]);

        return Inertia::render('Sarpar/Inventories/Show', [
            'inventory' => $inventory,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola inventaris.');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:sarpar_categories,id',
            'room_id' => 'nullable|exists:sarpar_rooms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'funding_source' => 'required|in:BOS,YYS',
            'item_type' => 'required|in:asset,consumable',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year_acquired' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'quantity' => 'required|integer|min:1',
            'min_stock' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|integer|min:0',
            'condition' => 'required|in:baik,rusak_ringan,rusak_berat',
            'photo' => 'required|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        $unitId = session('active_unit_id');
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($validated['room_id']) {
                $room = Room::findOrFail($validated['room_id']);
                if ($room->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Ruangan tidak sesuai dengan unit Anda.');
                }
            }
            if ($validated['classroom_id']) {
                $classroom = Classroom::findOrFail($validated['classroom_id']);
                if ($classroom->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Kelas tidak sesuai dengan unit Anda.');
                }
            }
        } else {
            if ($validated['room_id']) {
                $room = Room::findOrFail($validated['room_id']);
                $unitId = $room->unit_id;
            } elseif ($validated['classroom_id']) {
                $classroom = Classroom::findOrFail($validated['classroom_id']);
                $unitId = $classroom->unit_id;
            }
        }

        $unit = Unit::findOrFail($unitId);

        try {
            $category = Category::findOrFail($validated['category_id']);
            
            // Generate unique code
            $code = InventoryCodeGenerator::generate(
                $unit,
                $category,
                $validated['funding_source'],
                $validated['year_acquired']
            );

            // Handle photo upload
            $photoPath = $request->file('photo')->store('sarpar/inventories', 'public');

            Inventory::create([
                ...$validated,
                'unit_id' => $unitId,
                'code' => $code,
                'status' => 'tersedia',
                'photo' => $photoPath,
            ]);

            return redirect()->back()->with('success', 'Inventaris berhasil ditambahkan dengan kode: ' . $code);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah inventaris: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Inventory $inventory)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola inventaris.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $inventory->unit_id;
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:sarpar_categories,id',
            'room_id' => 'nullable|exists:sarpar_rooms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'min_stock' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|integer|min:0',
            'condition' => 'required|in:baik,rusak_ringan,rusak_berat',
            'status' => 'required|in:tersedia,dipinjam,diperbaiki,dihapus',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($validated['room_id']) {
                $room = Room::findOrFail($validated['room_id']);
                if ($room->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Ruangan tidak sesuai dengan unit Anda.');
                }
            }
            if ($validated['classroom_id']) {
                $classroom = Classroom::findOrFail($validated['classroom_id']);
                if ($classroom->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Kelas tidak sesuai dengan unit Anda.');
                }
            }
        }

        try {
            // Handle photo upload
            if ($request->hasFile('photo')) {
                if ($inventory->photo) {
                    \Storage::disk('public')->delete($inventory->photo);
                }
                $validated['photo'] = $request->file('photo')->store('sarpar/inventories', 'public');
            }

            $inventory->update($validated);
            return redirect()->back()->with('success', 'Inventaris berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui inventaris.');
        }
    }

    public function destroy(Inventory $inventory)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola inventaris.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($inventory->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        // Check for active loans
        if ($inventory->loans()->where('status', 'borrowed')->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus inventaris yang sedang dipinjam.');
        }

        try {
            if ($inventory->photo) {
                \Storage::disk('public')->delete($inventory->photo);
            }
            $inventory->delete();
            return redirect()->back()->with('success', 'Inventaris dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus inventaris.');
        }
    }
}

