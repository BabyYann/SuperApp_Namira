<?php
namespace App\Modules\PublicRelations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UniversityDestination;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UniversityDestinationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        $query = UniversityDestination::with('unit')->latest();

        if ($user->hasRole('humas_unit')) {
            $query->where('unit_id', $unitId);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
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
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Index', [
            'destinations' => $destinations,
            'units' => $units,
            'filters' => $request->only(['search', 'type', 'unit_id']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');
        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Form', [
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        $validated = $request->validate([
            'unit_id'     => 'required|exists:units,id',
            'name'        => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'country'     => 'required|string|max:100',
            'type'        => 'required|in:indonesia,overseas,lokal',
            'visit_type'  => 'required|in:kunjungan,alumni',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
            'visit_date'  => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $request->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $validated['created_by'] = $user->id;
        UniversityDestination::create($validated);

        return redirect()->route('public-relations.university-destinations.index')
            ->with('success', 'Destinasi berhasil ditambahkan.');
    }

    public function edit(UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $units = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            ? Unit::all() : Unit::where('id', $unitId)->get();

        return Inertia::render('PublicRelations/UniversityDestinations/Form', [
            'destination' => $universityDestination->load('unit'),
            'units' => $units,
        ]);
    }

    public function update(Request $request, UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'unit_id'     => 'required|exists:units,id',
            'name'        => 'required|string|max:255',
            'city'        => 'required|string|max:255',
            'country'     => 'required|string|max:100',
            'type'        => 'required|in:indonesia,overseas,lokal',
            'visit_type'  => 'required|in:kunjungan,alumni',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
            'visit_date'  => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $universityDestination->update($validated);

        return redirect()->route('public-relations.university-destinations.index')
            ->with('success', 'Destinasi berhasil diperbarui.');
    }

    public function destroy(UniversityDestination $universityDestination)
    {
        $user = auth()->user();
        $unitId = session('active_unit_id');

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $universityDestination->unit_id != $unitId) {
            abort(403, 'Akses Ditolak.');
        }

        $universityDestination->delete();

        return redirect()->route('public-relations.university-destinations.index')
            ->with('success', 'Destinasi berhasil dihapus.');
    }
}
