<?php
namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;

class BannerController extends Controller
{
    private function authorizeAdmin()
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Hanya Admin Yayasan yang dapat mengelola Banner Slider.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = Banner::orderBy('order_weight', 'asc')->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $banners = $query->paginate(15)->withQueryString();

        return Inertia::render('PublicRelations/Banners/Index', [
            'banners' => $banners,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $this->authorizeAdmin();
        return Inertia::render('PublicRelations/Banners/Form');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'title'        => 'nullable|string|max:255',
            'image'        => 'required|image|max:3072',
            'order_weight' => 'integer|min:0',
            'is_active'    => 'boolean',
        ]);

        $isActive = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);

        if ($isActive) {
            $activeCount = Banner::where('is_active', true)->count();
            if ($activeCount >= 10) {
                return back()->withErrors(['is_active' => 'Maksimal banner aktif adalah 10 foto. Silakan non-aktifkan banner lain terlebih dahulu.']);
            }
        }

        $banner = new Banner();
        $banner->title = $validated['title'] ?? null;
        $banner->order_weight = $validated['order_weight'] ?? 0;
        $banner->is_active = $isActive;
        $banner->created_by = auth()->id();

        if ($request->hasFile('image')) {
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'banners', 1600, 80);
            $banner->image_path = $path;
        }

        $banner->save();

        return redirect()->route('public-relations.banners.index')
            ->with('success', 'Banner baru berhasil ditambahkan.');
    }

    public function edit(Banner $banner)
    {
        $this->authorizeAdmin();
        return Inertia::render('PublicRelations/Banners/Form', [
            'banner' => $banner,
        ]);
    }

    public function update(Request $request, Banner $banner)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'title'        => 'nullable|string|max:255',
            'image'        => 'nullable|image|max:3072',
            'order_weight' => 'integer|min:0',
            'is_active'    => 'boolean',
        ]);

        $isActive = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);

        if ($isActive && !$banner->is_active) {
            $activeCount = Banner::where('is_active', true)->count();
            if ($activeCount >= 10) {
                return back()->withErrors(['is_active' => 'Maksimal banner aktif adalah 10 foto. Silakan non-aktifkan banner lain terlebih dahulu.']);
            }
        }

        $banner->title = $validated['title'] ?? null;
        $banner->order_weight = $validated['order_weight'] ?? 0;
        $banner->is_active = $isActive;

        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $banner->image_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('image'), 'banners', 1600, 80);
            $banner->image_path = $path;
        }

        $banner->save();

        return redirect()->route('public-relations.banners.index')
            ->with('success', 'Banner berhasil diperbarui.');
    }

    public function destroy(Banner $banner)
    {
        $this->authorizeAdmin();

        if ($banner->image_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $banner->image_path));
        }

        $banner->delete();

        return redirect()->route('public-relations.banners.index')
            ->with('success', 'Banner berhasil dihapus.');
    }
}
