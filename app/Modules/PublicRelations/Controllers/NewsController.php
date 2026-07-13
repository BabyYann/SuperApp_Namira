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
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = News::with(['author', 'unit'])->latest();
        
        // Filter for humas_unit: only see their unit's news
        if ($user->hasRole('humas_unit')) {
            $query->where('unit_id', $user->unit_id);
        }

        // Apply search filter if exists
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $news = $query->paginate(10)->withQueryString();

        return Inertia::render('PublicRelations/News/Index', [
            'news' => $news,
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
            // For unit admins and humas, they only see their own unit
            $units = Unit::where('id', $user->unit_id)->get();
        }

        return Inertia::render('PublicRelations/News/Form', [
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
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'image' => 'nullable|image|max:2048'
        ]);

        // Unit isolation
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat membuat berita untuk unit lain.');
        }

        $news = new News();
        $news->unit_id = $validated['unit_id'];
        $news->title = $validated['title'];
        $news->content = $validated['content'];
        $news->status = $validated['status'];
        $news->author_id = auth()->id();

        if ($request->hasFile('image')) {
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'news', 800, 80);
            $news->image_path = $path;
        }

        if ($validated['status'] === 'published') {
            $news->published_at = now();
        }

        $news->save();

        return redirect()->route('public-relations.news.index')->with('success', 'Berita berhasil ditambahkan.');
    }

    public function edit(News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit berita unit lain.');
        }

        $units = [];
        if ($user->hasRole('super_admin_yayasan') || $user->hasRole('admin_yayasan')) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $unitId)->get();
        }

        return Inertia::render('PublicRelations/News/Form', [
            'news' => $news,
            'units' => $units
        ]);
    }

    public function update(Request $request, News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        // Unit isolation: check ownership before any changes
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah berita unit lain.');
        }

        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'image' => 'nullable|image|max:2048'
        ]);

        // Unit isolation: prevent re-assigning to other unit
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memindahkan berita ke unit lain.');
        }

        $news->unit_id = $validated['unit_id'];
        $news->title = $validated['title'];
        $news->content = $validated['content'];
        $news->status = $validated['status'];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($news->image_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $news->image_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'news', 800, 80);
            $news->image_path = $path;
        }

        if ($validated['status'] === 'published' && !$news->published_at) {
            $news->published_at = now();
        } elseif ($validated['status'] === 'draft') {
            $news->published_at = null;
        }

        $news->save();

        return redirect()->route('public-relations.news.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(News $news)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $news->unit_id != $unitId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus berita unit lain.');
        }

        if ($news->image_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $news->image_path));
        }

        $news->delete();

        return redirect()->route('public-relations.news.index')->with('success', 'Berita berhasil dihapus.');
    }
}
