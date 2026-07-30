<?php
namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;

class TestimonialController extends Controller
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

        $baseQuery = Testimonial::query();

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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('quote', 'like', '%' . $request->search . '%');
            });
        }

        $testimonials = $query->paginate(10)->withQueryString();
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', session('active_unit_id'))->get();

        return Inertia::render('PublicRelations/Testimonials/Index', [
            'testimonials' => $testimonials,
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

        return Inertia::render('PublicRelations/Testimonials/Form', [
            'units' => $units,
            'is_approver' => $isApprover,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$request->filled('unit_id')) {
            $request->merge(['unit_id' => $unitId]);
        }

        if (!$request->hasFile('photo') || $request->file('photo') === null) {
            $request->offsetUnset('photo');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'role_or_title' => 'required|string|max:255',
            'quote' => 'required|string',
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'photo' => 'nullable|image|max:2048',
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $testimonial = new Testimonial();
        $testimonial->unit_id = $validated['unit_id'];
        $testimonial->name = $validated['name'];
        $testimonial->role_or_title = $validated['role_or_title'];
        $testimonial->quote = $validated['quote'];
        $testimonial->approval_status = $approvalStatus;
        $testimonial->created_by = $user->id;

        if ($approvalStatus === 'published') {
            $testimonial->approved_by = $user->id;
            $testimonial->approved_at = now();
        }

        if ($request->hasFile('photo')) {
            $path = ImageHelper::uploadAndConvert($request->file('photo'), 'testimonials', 300, 80);
            $testimonial->photo_path = $path;
        }

        $testimonial->save();

        $msg = $approvalStatus === 'pending'
            ? 'Testimoni berhasil ditambahkan dan diajukan untuk verifikasi.'
            : 'Testimoni berhasil ditambahkan.';

        return redirect()->route('public-relations.testimonials.index')->with('success', $msg);
    }

    public function edit(Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Testimonials/Form', [
            'testimonial' => $testimonial->load('approver'),
            'units' => $units,
            'is_approver' => $isApprover,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        if (!$request->filled('unit_id')) {
            $request->merge(['unit_id' => $unitId]);
        }

        if (!$request->hasFile('photo') || $request->file('photo') === null) {
            $request->offsetUnset('photo');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'role_or_title' => 'required|string|max:255',
            'quote' => 'required|string',
            'approval_status' => 'required|in:draft,pending,published,rejected',
            'photo' => 'nullable|image|max:2048',
        ]);

        $approvalStatus = $validated['approval_status'];
        if (!$isApprover && in_array($approvalStatus, ['published', 'rejected'])) {
            $approvalStatus = 'pending';
        }

        $testimonial->unit_id = $validated['unit_id'];
        $testimonial->name = $validated['name'];
        $testimonial->role_or_title = $validated['role_or_title'];
        $testimonial->quote = $validated['quote'];
        $testimonial->approval_status = $approvalStatus;

        if ($approvalStatus === 'published' && !$testimonial->approved_at) {
            $testimonial->approved_by = $user->id;
            $testimonial->approved_at = now();
        }

        if ($request->hasFile('photo')) {
            if ($testimonial->photo_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->photo_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('photo'), 'testimonials', 300, 80);
            $testimonial->photo_path = $path;
        }

        $testimonial->save();

        $msg = $approvalStatus === 'pending'
            ? 'Testimoni diperbarui dan diajukan untuk verifikasi.'
            : 'Testimoni berhasil diperbarui.';

        return redirect()->route('public-relations.testimonials.index')->with('success', $msg);
    }

    public function approve(Testimonial $testimonial)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $testimonial->approval_status = 'published';
        $testimonial->rejection_note = null;
        $testimonial->approved_by = $user->id;
        $testimonial->approved_at = now();
        $testimonial->save();

        return redirect()->back()->with('success', 'Testimoni berhasil diverifikasi & diterbitkan!');
    }

    public function reject(Request $request, Testimonial $testimonial)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang verifikasi.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $testimonial->approval_status = 'rejected';
        $testimonial->rejection_note = $validated['rejection_note'];
        $testimonial->approved_by = $user->id;
        $testimonial->save();

        return redirect()->back()->with('success', 'Testimoni dikembalikan untuk perbaikan.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        if ($testimonial->photo_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->photo_path));
        }

        $testimonial->delete();

        return redirect()->route('public-relations.testimonials.index')->with('success', 'Testimoni berhasil dihapus.');
    }
}
