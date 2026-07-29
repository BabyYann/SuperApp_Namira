<?php

namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;

class PartnerController extends Controller
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

        $baseQuery = Partner::query();

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $unitId = session('active_unit_id');
            $baseQuery->where('unit_id', $unitId);
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

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $partners = $query->paginate(10)->withQueryString();
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', session('active_unit_id'))->get();

        return Inertia::render('PublicRelations/Partners/Index', [
            'partners' => $partners,
            'units' => $units,
            'counts' => $counts,
            'is_approver' => $isApprover,
            'filters' => $request->only(['search', 'unit_id', 'approval_status']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Partners/Form', [
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
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'logo' => 'required|image|max:2048',
            'website_url' => 'nullable|url|max:255',
            'approval_status' => 'required|in:draft,pending,published,rejected',
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $partner = new Partner();
        $partner->unit_id = $validated['unit_id'];
        $partner->name = $validated['name'];
        $partner->website_url = $validated['website_url'] ?? null;
        $partner->approval_status = $approvalStatus;
        $partner->created_by = $user->id;

        if ($approvalStatus === 'published') {
            $partner->approved_by = $user->id;
            $partner->approved_at = now();
        }

        if ($request->hasFile('logo')) {
            $path = ImageHelper::uploadAndConvert($request->file('logo'), 'partners', 200, 80);
            $partner->logo_path = $path;
        }

        $partner->save();

        $msg = $approvalStatus === 'pending'
            ? 'Mitra berhasil ditambahkan dan diajukan untuk verifikasi.'
            : 'Mitra berhasil ditambahkan.';

        return redirect()->route('public-relations.partners.index')->with('success', $msg);
    }

    public function edit(Partner $partner)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $partner->unit_id && $partner->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Partners/Form', [
            'partner' => $partner->load('approver'),
            'units' => $units,
            'is_approver' => $isApprover,
        ]);
    }

    public function update(Request $request, Partner $partner)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $partner->unit_id && $partner->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'website_url' => 'nullable|url|max:255',
            'approval_status' => 'required|in:draft,pending,published,rejected',
        ]);

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $partner->unit_id = $validated['unit_id'];
        $partner->name = $validated['name'];
        $partner->website_url = $validated['website_url'] ?? null;
        $partner->approval_status = $approvalStatus;

        if ($approvalStatus === 'published' && !$partner->approved_at) {
            $partner->approved_by = $user->id;
            $partner->approved_at = now();
        }

        if ($request->hasFile('logo')) {
            if ($partner->logo_path) {
                $oldPath = str_replace('storage/', '', $partner->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = ImageHelper::uploadAndConvert($request->file('logo'), 'partners', 200, 80);
            $partner->logo_path = $path;
        }

        $partner->save();

        $msg = $approvalStatus === 'pending'
            ? 'Data mitra diperbarui dan diajukan untuk verifikasi.'
            : 'Data mitra berhasil diperbarui.';

        return redirect()->route('public-relations.partners.index')->with('success', $msg);
    }

    public function approve(Partner $partner)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $partner->approval_status = 'published';
        $partner->rejection_note = null;
        $partner->approved_by = $user->id;
        $partner->approved_at = now();
        $partner->save();

        return redirect()->back()->with('success', 'Mitra berhasil diverifikasi & diterbitkan!');
    }

    public function reject(Request $request, Partner $partner)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $partner->approval_status = 'rejected';
        $partner->rejection_note = $validated['rejection_note'];
        $partner->approved_by = $user->id;
        $partner->save();

        return redirect()->back()->with('success', 'Mitra dikembalikan untuk perbaikan.');
    }

    public function destroy(Partner $partner)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $partner->unit_id && $partner->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        if ($partner->logo_path) {
            $oldPath = str_replace('storage/', '', $partner->logo_path);
            Storage::disk('public')->delete($oldPath);
        }
        $partner->delete();

        return redirect()->route('public-relations.partners.index')->with('success', 'Mitra berhasil dihapus.');
    }
}
