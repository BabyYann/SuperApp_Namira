<?php

namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Modules\Yayasan\Models\Unit;
use App\Helpers\ImageHelper;

class NewsController extends Controller
{
    private function isApprover($user): bool
    {
        return $user->hasAnyRole([
            'super_admin_yayasan',
            'admin_yayasan',
            'pengawas_yayasan',
            'humas_yayasan',
            'kepala_sekolah'
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isApprover = $this->isApprover($user);

        $baseQuery = News::query();

        // Unit scope for non-global admin/verifier
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $unitId = session('active_unit_id');
            $baseQuery->where('unit_id', $unitId);
        }

        // Calculate status counts for filter tabs
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'published' => (clone $baseQuery)->where('status', 'published')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        $query = (clone $baseQuery)->with(['author', 'approver', 'unit'])->latest();

        // Apply status tab filter
        if ($request->filled('status') && in_array($request->status, ['pending', 'published', 'draft', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // Apply search filter if exists
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $news = $query->paginate(10)->withQueryString();

        return Inertia::render('PublicRelations/News/Index', [
            'news' => $news,
            'counts' => $counts,
            'is_approver' => $isApprover,
            'filters' => $request->only('search', 'status')
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

        return Inertia::render('PublicRelations/News/Form', [
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
            'content' => 'required|string',
            'status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|max:2048'
        ]);

        // Unit isolation
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat membuat berita untuk unit lain.');
        }

        // Non-approvers cannot publish directly -> force to pending if published requested
        $status = $validated['status'];
        if (!$isApprover && $status === 'published') {
            $status = 'pending';
        }

        $news = new News();
        $news->unit_id = $validated['unit_id'];
        $news->title = $validated['title'];
        $news->content = $validated['content'];
        $news->status = $status;
        $news->author_id = auth()->id();

        if ($status === 'published') {
            $news->published_at = now();
            $news->approved_by = auth()->id();
            $news->approved_at = now();
        }

        if ($request->hasFile('image')) {
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'news', 800, 80);
            $news->image_path = $path;
        }

        $news->save();

        $msg = $status === 'pending'
            ? 'Berita berhasil diajukan untuk verifikasi.'
            : 'Berita berhasil disimpan.';

        return redirect()->route('public-relations.news.index')->with('success', $msg);
    }

    public function edit(News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit berita unit lain.');
        }

        $units = [];
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan'])) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $unitId)->get();
        }

        return Inertia::render('PublicRelations/News/Form', [
            'news' => $news->load('approver'),
            'units' => $units,
            'is_approver' => $isApprover
        ]);
    }

    public function update(Request $request, News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $isApprover = $this->isApprover($user);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah berita unit lain.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|max:2048'
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan berita ke unit lain.');
        }

        $status = $validated['status'];
        if (!$isApprover && $status === 'published') {
            $status = 'pending';
        }

        $news->unit_id = $validated['unit_id'];
        $news->title = $validated['title'];
        $news->content = $validated['content'];
        $news->status = $status;

        // Clear rejection note if resubmitted
        if (in_array($status, ['pending', 'published'])) {
            $news->rejection_note = null;
        }

        if ($status === 'published' && !$news->published_at) {
            $news->published_at = now();
            $news->approved_by = auth()->id();
            $news->approved_at = now();
        }

        if ($request->hasFile('image')) {
            if ($news->image_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $news->image_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'news', 800, 80);
            $news->image_path = $path;
        }

        $news->save();

        $msg = $status === 'pending' 
            ? 'Berita berhasil diperbarui dan diajukan untuk verifikasi.' 
            : 'Berita berhasil diperbarui.';

        return redirect()->route('public-relations.news.index')->with('success', $msg);
    }

    public function approve(News $news)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui berita.');
        }

        $news->status = 'published';
        $news->rejection_note = null;
        $news->approved_by = auth()->id();
        $news->approved_at = now();
        if (!$news->published_at) {
            $news->published_at = now();
        }
        $news->save();

        return redirect()->back()->with('success', 'Berita berhasil diverifikasi & diterbitkan!');
    }

    public function reject(Request $request, News $news)
    {
        $user = auth()->user();

        if (!$this->isApprover($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak berita.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $news->status = 'rejected';
        $news->rejection_note = $validated['rejection_note'];
        $news->approved_by = auth()->id();
        $news->save();

        return redirect()->back()->with('success', 'Berita dikembalikan untuk perbaikan.');
    }

    public function destroy(News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan', 'humas_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus berita unit lain.');
        }

        if ($news->image_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $news->image_path));
        }

        $news->delete();

        return redirect()->route('public-relations.news.index')->with('success', 'Berita berhasil dihapus.');
    }
}
