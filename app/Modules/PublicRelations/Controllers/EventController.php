<?php

namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Modules\Yayasan\Models\Unit;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        
        $query = Event::with(['author', 'unit'])->latest();
        
        // For global admin: see all. Others: only see their unit's events
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $query->where('unit_id', $unitId);
        }

        // Apply search filter if exists
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $events = $query->paginate(10)->withQueryString();

        return Inertia::render('PublicRelations/Events/Index', [
            'events' => $events,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        $units = [];
        $user = auth()->user();
        
        if ($user->hasRole('super_admin_yayasan') || $user->hasRole('admin_yayasan')) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $user->unit_id)->get();
        }

        return Inertia::render('PublicRelations/Events/Form', [
            'units' => $units
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:upcoming,completed,cancelled',
            'image' => 'nullable|image|max:2048'
        ]);

        // Unit isolation: non-global admin cannot post to other units
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $validated['unit_id'] != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat membuat acara untuk unit lain.');
        }

        $event = new Event();
        $event->unit_id = $validated['unit_id'];
        $event->title = $validated['title'];
        $event->description = $validated['description'];
        $event->location = $validated['location'];
        $event->start_date = $validated['start_date'];
        $event->end_date = $validated['end_date'];
        $event->status = $validated['status'];
        $event->author_id = auth()->id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $event->image_path = 'storage/' . $path;
        }

        $event->save();

        return redirect()->route('public-relations.events.index')->with('success', 'Acara berhasil ditambahkan.');
    }

    public function edit(Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $event->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit acara unit lain.');
        }

        $units = [];
        if ($user->hasRole('super_admin_yayasan') || $user->hasRole('admin_yayasan')) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $user->unit_id)->get();
        }

        return Inertia::render('PublicRelations/Events/Form', [
            'event' => $event,
            'units' => $units
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $event->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah acara unit lain.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:upcoming,completed,cancelled',
            'image' => 'nullable|image|max:2048'
        ]);

        // Prevent re-assigning to a different unit
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $validated['unit_id'] != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan acara ke unit lain.');
        }

        $event->unit_id = $validated['unit_id'];
        $event->title = $validated['title'];
        $event->description = $validated['description'];
        $event->location = $validated['location'];
        $event->start_date = $validated['start_date'];
        $event->end_date = $validated['end_date'];
        $event->status = $validated['status'];

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $event->image_path));
            }
            $path = $request->file('image')->store('events', 'public');
            $event->image_path = 'storage/' . $path;
        }

        $event->save();

        return redirect()->route('public-relations.events.index')->with('success', 'Acara berhasil diperbarui.');
    }

    public function destroy(Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $event->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus acara unit lain.');
        }

        if ($event->image_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $event->image_path));
        }

        $event->delete();

        return redirect()->route('public-relations.events.index')->with('success', 'Acara berhasil dihapus.');
    }
}
