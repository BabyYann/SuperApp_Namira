<?php

namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::latest();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $partners = $query->paginate(10)->withQueryString();

        return Inertia::render('PublicRelations/Partners/Index', [
            'partners' => $partners,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        return Inertia::render('PublicRelations/Partners/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'required|image|max:2048',
            'website_url' => 'nullable|url|max:255',
        ]);

        $partner = new Partner();
        $partner->name = $validated['name'];
        $partner->website_url = $validated['website_url'] ?? null;

        if ($request->hasFile('logo')) {
            $path = ImageHelper::uploadAndConvert($request->file('logo'), 'partners', 200, 80);
            $partner->logo_path = $path;
        }

        $partner->save();

        return redirect()->route('public-relations.partners.index')->with('success', 'Mitra berhasil ditambahkan.');
    }

    public function edit(Partner $partner)
    {
        return Inertia::render('PublicRelations/Partners/Form', [
            'partner' => $partner
        ]);
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'website_url' => 'nullable|url|max:255',
        ]);

        $partner->name = $validated['name'];
        $partner->website_url = $validated['website_url'] ?? null;

        if ($request->hasFile('logo')) {
            if ($partner->logo_path) {
                $oldPath = str_replace('storage/', '', $partner->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = ImageHelper::uploadAndConvert($request->file('logo'), 'partners', 200, 80);
            $partner->logo_path = $path;
        }

        $partner->save();

        return redirect()->route('public-relations.partners.index')->with('success', 'Data mitra berhasil diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        if ($partner->logo_path) {
            $oldPath = str_replace('storage/', '', $partner->logo_path);
            Storage::disk('public')->delete($oldPath);
        }
        $partner->delete();

        return redirect()->route('public-relations.partners.index')->with('success', 'Mitra berhasil dihapus.');
    }
}
