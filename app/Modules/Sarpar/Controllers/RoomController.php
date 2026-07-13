<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Room;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Academic\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        // Get sarpar rooms with inventory count
        $rooms = Room::withCount('inventories')
            ->where('unit_id', $unitId)
            ->orderBy('building')
            ->orderBy('name')
            ->get()
            ->map(function ($room) {
                $room->type = 'room';
                $room->editable = true;
                return $room;
            });

        // Get classrooms with inventory count
        $classrooms = Classroom::where('unit_id', $unitId)
            ->orderBy('name')
            ->get()
            ->map(function ($classroom) {
                // Count inventories in this classroom
                $inventoryCount = Inventory::where('classroom_id', $classroom->id)->count();
                return [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'building' => null,
                    'floor' => null,
                    'capacity' => $classroom->capacity ?? null,
                    'description' => 'Kelas dari modul Akademik',
                    'inventories_count' => $inventoryCount,
                    'type' => 'classroom',
                    'editable' => false,
                ];
            });

        return Inertia::render('Sarpar/Rooms/Index', [
            'rooms' => $rooms,
            'classrooms' => $classrooms,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'building' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            Room::create([
                ...$validated,
                'unit_id' => $unitId,
            ]);
            return redirect()->back()->with('success', 'Ruangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah ruangan.');
        }
    }

    public function update(Request $request, Room $room)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($room->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'building' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $room->update($validated);
            return redirect()->back()->with('success', 'Ruangan berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui ruangan.');
        }
    }

    public function destroy(Room $room)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_sarpar'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($room->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        if ($room->inventories()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus ruangan yang masih memiliki inventaris.');
        }

        try {
            $room->delete();
            return redirect()->back()->with('success', 'Ruangan dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus ruangan.');
        }
    }
}

