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
    public function index(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        $query = Testimonial::with('unit')->latest();

        if ($user->hasRole('humas_unit')) {
            $query->where('unit_id', $unitId);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('quote', 'like', '%' . $request->search . '%');
            });
        }

        $testimonials = $query->paginate(10)->withQueryString();
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Testimonials/Index', [
            'testimonials' => $testimonials,
            'units' => $units,
            'filters' => $request->only(['search', 'unit_id']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Testimonials/Form', [
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        $validated = $request->validate([
            'unit_id'       => 'required|exists:units,id',
            'name'          => 'required|string|max:255',
            'role_or_title' => 'required|string|max:100',
            'quote'         => 'required|string|max:1000',
            'photo'         => 'nullable|image|max:2048',
            'is_active'     => 'boolean',
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $testimonial = new Testimonial();
        $testimonial->unit_id = $validated['unit_id'];
        $testimonial->name = $validated['name'];
        $testimonial->role_or_title = $validated['role_or_title'];
        $testimonial->quote = $validated['quote'];
        $testimonial->is_active = $request->input('is_active', true);
        $testimonial->created_by = $user->id;

        if ($request->hasFile('photo')) {
            $path = ImageHelper::uploadAndConvert($request->file('photo'), 'testimonials', 300, 80);
            $testimonial->photo_path = $path;
        }

        $testimonial->save();

        return redirect()->route('public-relations.testimonials.index')
            ->with('success', 'Testimoni berhasil ditambahkan.');
    }

    public function edit(Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/Testimonials/Form', [
            'testimonial' => $testimonial,
            'units' => $units,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'unit_id'       => 'required|exists:units,id',
            'name'          => 'required|string|max:255',
            'role_or_title' => 'required|string|max:100',
            'quote'         => 'required|string|max:1000',
            'photo'         => 'nullable|image|max:2048',
            'is_active'     => 'boolean',
        ]);

        $testimonial->unit_id = $validated['unit_id'];
        $testimonial->name = $validated['name'];
        $testimonial->role_or_title = $validated['role_or_title'];
        $testimonial->quote = $validated['quote'];
        $testimonial->is_active = $request->input('is_active', true);

        if ($request->hasFile('photo')) {
            if ($testimonial->photo_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->photo_path));
            }
            $path = ImageHelper::uploadAndConvert($request->file('photo'), 'testimonials', 300, 80);
            $testimonial->photo_path = $path;
        }

        $testimonial->save();

        return redirect()->route('public-relations.testimonials.index')
            ->with('success', 'Testimoni berhasil diperbarui.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $testimonial->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        if ($testimonial->photo_path) {
            Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->photo_path));
        }

        $testimonial->delete();

        return redirect()->route('public-relations.testimonials.index')
            ->with('success', 'Testimoni berhasil dihapus.');
    }
}
