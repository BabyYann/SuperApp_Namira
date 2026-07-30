<?php

namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Modules\Yayasan\Models\Unit;
use App\Helpers\ImageHelper;

class EventController extends Controller
{
    private function isApprover($user): bool
    {
        return $user->hasAnyRole([
            'super_admin_yayasan',
            'admin_yayasan',
            'humas_yayasan'
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isApprover = $this->isApprover($user);

        $baseQuery = Event::query();

        // For global admin/verifier: see all. Others: only see their unit's events
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $unitId = session('active_unit_id');
            $baseQuery->where('unit_id', $unitId);
        }

        // Calculate counts
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('approval_status', 'pending')->count(),
            'published' => (clone $baseQuery)->where('approval_status', 'published')->count(),
            'draft' => (clone $baseQuery)->where('approval_status', 'draft')->count(),
            'rejected' => (clone $baseQuery)->where('approval_status', 'rejected')->count(),
        ];

        $query = (clone $baseQuery)->with(['author', 'approver', 'unit'])->latest();

        // Apply approval status filter
        if ($request->filled('approval_status') && in_array($request->approval_status, ['pending', 'published', 'draft', 'rejected'])) {
            $query->where('approval_status', $request->approval_status);
        }

        // Apply search filter if exists
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $events = $query->paginate(10)->withQueryString();

        return Inertia::render('PublicRelations/Events/Index', [
            'events' => $events,
            'counts' => $counts,
            'is_approver' => $isApprover,
            'filters' => $request->only('search', 'approval_status')
        ]);
    }

    public function create()
    {
        $units = [];
        $user = auth()->user();
        $isApprover = $this->isApprover($user);

        $unitId = session('active_unit_id');
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $unitId)->get();
        }

        return Inertia::render('PublicRelations/Events/Form', [
            'units' => $units,
            'is_approver' => $isApprover
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:upcoming,completed,cancelled',
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|max:2048'
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $validated['unit_id'] != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat membuat acara untuk unit lain.');
        }

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && $approvalStatus === 'published') {
            $approvalStatus = 'pending';
        }

        $event = new Event();
        $event->unit_id = $validated['unit_id'];
        $event->title = $validated['title'];
        $event->description = $validated['description'];
        $event->location = $validated['location'];
        $event->start_date = $validated['start_date'];
        $event->end_date = $validated['end_date'];
        $event->status = $validated['status'];
        $event->approval_status = $approvalStatus;
        $event->author_id = auth()->id();

        if ($approvalStatus === 'published') {
            $event->approved_by = auth()->id();
            $event->approved_at = now();
        }

        if ($request->hasFile('image')) {
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'events', 800, 80);
            $event->image_path = $path;
        }

        $event->save();

        $unitName = Unit::find($event->unit_id)->name ?? 'Unit';
        if ($approvalStatus === 'pending') {
            \App\Services\NotificationDispatcher::sendToRoles(
                ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'],
                null,
                '📅 Pengajuan Agenda Acara Baru',
                "{$unitName} mengajukan acara baru: \"{$event->title}\". Butuh verifikasi.",
                'public_relations',
                ['event_id' => $event->id]
            );
        } else {
            \App\Services\NotificationDispatcher::sendToRoles(
                ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan', 'admin_unit'],
                $event->unit_id,
                '📅 Agenda Acara Baru Diterbitkan',
                "Acara baru diterbitkan di {$unitName}: \"{$event->title}\".",
                'public_relations',
                ['event_id' => $event->id]
            );
        }

        $msg = $approvalStatus === 'pending'
            ? 'Acara berhasil diajukan untuk verifikasi.'
            : 'Acara berhasil ditambahkan.';

        return redirect()->route('public-relations.events.index')->with('success', $msg);
    }

    public function edit(Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $event->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit acara unit lain.');
        }

        $units = [];
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $unitId)->get();
        }

        return Inertia::render('PublicRelations/Events/Form', [
            'event' => $event->load('approver'),
            'units' => $units,
            'is_approver' => $isApprover
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $event->unit_id != $unitId) {
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
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|max:2048'
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $validated['unit_id'] != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan acara ke unit lain.');
        }

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && $approvalStatus === 'published') {
            $approvalStatus = 'pending';
        }

        $event->unit_id = $validated['unit_id'];
        $event->title = $validated['title'];
        $event->description = $validated['description'];
        $event->location = $validated['location'];
        $event->start_date = $validated['start_date'];
        $event->end_date = $validated['end_date'];
        $event->status = $validated['status'];
        $event->approval_status = $approvalStatus;

        if (in_array($approvalStatus, ['pending', 'published'])) {
            $event->rejection_note = null;
        }

        if ($approvalStatus === 'published') {
            $event->approved_by = auth()->id();
            $event->approved_at = now();
        }

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $event->image_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'events', 800, 80);
            $event->image_path = $path;
        }

        $event->save();

        $msg = $approvalStatus === 'pending'
            ? 'Acara berhasil diperbarui dan diajukan untuk verifikasi.'
            : 'Acara berhasil diperbarui.';

        return redirect()->route('public-relations.events.index')->with('success', $msg);
    }

    public function approve(Event $event)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui acara.');
        }

        $event->approval_status = 'published';
        $event->rejection_note = null;
        $event->approved_by = auth()->id();
        $event->approved_at = now();
        $event->save();

        if ($event->author_id) {
            $author = User::find($event->author_id);
            if ($author) {
                \App\Services\NotificationDispatcher::sendToUser(
                    $author,
                    '✅ Acara Disetujui & Diterbitkan',
                    "Acara \"{$event->title}\" telah disetujui dan diterbitkan.",
                    'public_relations',
                    ['event_id' => $event->id]
                );
            }
        }

        return redirect()->back()->with('success', 'Acara berhasil diverifikasi & diterbitkan!');
    }

    public function reject(Request $request, Event $event)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak acara.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $event->approval_status = 'rejected';
        $event->rejection_note = $validated['rejection_note'];
        $event->approved_by = auth()->id();
        $event->save();

        if ($event->author_id) {
            $author = User::find($event->author_id);
            if ($author) {
                \App\Services\NotificationDispatcher::sendToUser(
                    $author,
                    '❌ Acara Ditolak / Perlu Perbaikan',
                    "Acara \"{$event->title}\" dikembalikan untuk perbaikan: {$validated['rejection_note']}",
                    'public_relations',
                    ['event_id' => $event->id]
                );
            }
        }

        return redirect()->back()->with('success', 'Acara dikembalikan untuk perbaikan.');
    }

    public function destroy(Event $event)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $event->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus acara unit lain.');
        }

        if ($event->image_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $event->image_path));
        }

        $event->delete();

        return redirect()->route('public-relations.events.index')->with('success', 'Acara berhasil dihapus.');
    }
}
