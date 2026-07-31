<?php
namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UniversityDestination;
use App\Modules\Yayasan\Models\Unit;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UniversityDestinationController extends Controller
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
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        $baseQuery = UniversityDestination::query();

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $baseQuery->where('unit_id', $unitId);
        } elseif ($request->filled('unit_id')) {
            $baseQuery->where('unit_id', $request->unit_id);
        }

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('approval_status', 'pending')->count(),
            'published' => (clone $baseQuery)->where('approval_status', 'published')->count(),
            'draft' => (clone $baseQuery)->where('approval_status', 'draft')->count(),
            'rejected' => (clone $baseQuery)->where('approval_status', 'rejected')->count(),
        ];

        $query = (clone $baseQuery)->with(['unit', 'approver'])->latest();

        if ($request->filled('approval_status') && in_array($request->approval_status, ['pending', 'published', 'draft', 'rejected'])) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        $destinations = $query->paginate(15)->withQueryString();
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Index', [
            'destinations' => $destinations,
            'units' => $units,
            'counts' => $counts,
            'is_approver' => $isApprover,
            'filters' => $request->only(['search', 'type', 'unit_id', 'approval_status']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Form', [
            'units' => $units,
            'is_approver' => $isApprover,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        $validated = $request->validate([
            'unit_id'    => 'required|exists:units,id',
            'name'       => 'required|string|max:255',
            'city'       => 'required|string|max:255',
            'country'    => 'required|string|max:100',
            'type'       => 'required|in:indonesia,overseas,lokal',
            'visit_type' => 'required|in:kunjungan,alumni',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
            'visit_date' => 'nullable|date',
            'description'=> 'nullable|string|max:1000',
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'is_active'  => 'boolean',
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $validated['created_by'] = $user->id;
        $validated['approval_status'] = $approvalStatus;

        if ($approvalStatus === 'published') {
            $validated['approved_by'] = $user->id;
            $validated['approved_at'] = now();
        }

        UniversityDestination::create($validated);

        if ($approvalStatus === 'pending') {
            $unitName = Unit::find($validated['unit_id'])->name ?? 'Unit';
            NotificationDispatcher::sendToRoles(
                ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'],
                null,
                'Pengajuan Destinasi Universitas Baru',
                "{$unitName} mengajukan destinasi baru: \"{$validated['name']}\" ({$validated['city']}). Butuh verifikasi.",
                'public_relations',
                []
            );
        }

        $msg = $approvalStatus === 'pending'
            ? 'Destinasi berhasil ditambahkan dan diajukan untuk verifikasi.'
            : 'Destinasi berhasil ditambahkan.';

        return redirect()->route('public-relations.university-destinations.index')->with('success', $msg);
    }

    public function edit(UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Form', [
            'destination' => $universityDestination->load(['unit', 'approver']),
            'units' => $units,
            'is_approver' => $isApprover,
        ]);
    }

    public function update(Request $request, UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'unit_id'    => 'required|exists:units,id',
            'name'       => 'required|string|max:255',
            'city'       => 'required|string|max:255',
            'country'    => 'required|string|max:100',
            'type'       => 'required|in:indonesia,overseas,lokal',
            'visit_type' => 'required|in:kunjungan,alumni',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
            'visit_date' => 'nullable|date',
            'description'=> 'nullable|string|max:1000',
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'is_active'  => 'boolean',
        ]);

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $validated['approval_status'] = $approvalStatus;

        if ($approvalStatus === 'published' && !$universityDestination->approved_at) {
            $validated['approved_by'] = $user->id;
            $validated['approved_at'] = now();
        }

        $universityDestination->update($validated);

        if ($approvalStatus === 'pending') {
            $unitName = Unit::find($universityDestination->unit_id)->name ?? 'Unit';
            NotificationDispatcher::sendToRoles(
                ['super_admin_yayasan', 'admin_yayasan', 'humas_yayasan'],
                null,
                'Pengajuan Destinasi Universitas Diperbarui',
                "{$unitName} memperbarui destinasi: \"{$universityDestination->name}\". Butuh verifikasi.",
                'public_relations',
                []
            );
        }

        $msg = $approvalStatus === 'pending'
            ? 'Destinasi diperbarui dan diajukan untuk verifikasi.'
            : 'Destinasi berhasil diperbarui.';

        return redirect()->route('public-relations.university-destinations.index')->with('success', $msg);
    }

    public function approve(UniversityDestination $universityDestination)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $universityDestination->approval_status = 'published';
        $universityDestination->rejection_note = null;
        $universityDestination->approved_by = $user->id;
        $universityDestination->approved_at = now();
        $universityDestination->save();

        $creator = $universityDestination->creator;
        if ($creator) {
            NotificationDispatcher::sendToUser(
                $creator,
                'Destinasi Universitas Diterbitkan',
                "Destinasi \"{$universityDestination->name}\" telah disetujui dan diterbitkan.",
                'public_relations',
                []
            );
        }

        return redirect()->back()->with('success', 'Destinasi berhasil diverifikasi & diterbitkan!');
    }

    public function reject(Request $request, UniversityDestination $universityDestination)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $universityDestination->approval_status = 'rejected';
        $universityDestination->rejection_note = $validated['rejection_note'];
        $universityDestination->approved_by = $user->id;
        $universityDestination->save();

        $creator = $universityDestination->creator;
        if ($creator) {
            NotificationDispatcher::sendToUser(
                $creator,
                'Destinasi Universitas Perlu Revisi',
                "Destinasi \"{$universityDestination->name}\" perlu revisi. Catatan: {$validated['rejection_note']}",
                'public_relations',
                []
            );
        }

        return redirect()->back()->with('success', 'Destinasi dikembalikan untuk perbaikan.');
    }

    public function destroy(UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $universityDestination->delete();

        return redirect()->route('public-relations.university-destinations.index')->with('success', 'Destinasi berhasil dihapus.');
    }
}
