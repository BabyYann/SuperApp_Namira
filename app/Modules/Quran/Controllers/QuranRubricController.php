<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuranRubricController extends Controller
{
    public function __construct(
        private QuranAccessService $access,
        private QuranSetupService $setup
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $unit = $this->access->resolveUnit($request, $user);
        $this->setup->ensureUnitDefaults($unit);

        $canManage = $this->access->canManage($user);
        $isGlobalAdmin = $this->access->isGlobalAdmin($user);

        $levels = QuranLevel::with([
                'rubricItems' => fn ($q) => $q->where('scope', QuranRubricItem::SCOPE_LEVEL)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->where('unit_id', $unit->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $gradeItems = QuranRubricItem::where('unit_id', $unit->id)
            ->where('scope', QuranRubricItem::SCOPE_GRADE)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('grade_level');

        $isSd = $this->setup->isSdUnit($unit);
        $defaultGrades = $isSd ? ['1', '2', '3', '4', '5', '6'] : ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $existingGrades = $gradeItems->keys()->filter()->values()->all();
        $availableGrades = collect(array_unique(array_merge($defaultGrades, $existingGrades)))
            ->sort(fn ($a, $b) => (int) $a <=> (int) $b)
            ->values()
            ->all();

        $gradeRubrics = [];
        foreach ($availableGrades as $g) {
            $gradeRubrics[(string) $g] = ($gradeItems->get((string) $g) ?? collect())->values()->all();
        }

        return Inertia::render('Quran/Rubrics', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $isGlobalAdmin ? $this->access->getEligibleUnits()->map->only(['id', 'name', 'code']) : [],
            'canManage' => $canManage,
            'isGlobalAdmin' => $isGlobalAdmin,
            'levels' => $levels,
            'availableGrades' => $availableGrades,
            'gradeRubrics' => $gradeRubrics,
            'usesGradeRubrics' => $isSd || $gradeItems->isNotEmpty(),
            'categories' => QuranRubricItem::CATEGORIES,
        ]);
    }

    public function storeLevel(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('quran_levels', 'name')->where('unit_id', $unit->id),
            ],
            'code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:1|max:99',
        ]);

        $nextOrder = (QuranLevel::where('unit_id', $unit->id)->max('sort_order') ?? 0) + 1;

        QuranLevel::create([
            'unit_id' => $unit->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 4)),
            'sort_order' => $validated['sort_order'] ?? $nextOrder,
            'is_active' => true,
        ]);

        return back()->with('success', 'Tingkat / Jilid baru berhasil ditambahkan.');
    }

    public function updateLevel(Request $request, QuranLevel $level)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($level->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('quran_levels', 'name')->where('unit_id', $level->unit_id)->ignore($level->id),
            ],
            'code' => 'nullable|string|max:20',
            'sort_order' => 'required|integer|min:1|max:99',
            'is_active' => 'boolean',
        ]);

        $level->update($validated);

        return back()->with('success', 'Data jilid berhasil diperbarui.');
    }

    public function storeItem(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);

        $validated = $request->validate([
            'scope' => 'nullable|in:level,grade',
            'level_id' => 'required_unless:scope,grade|nullable|exists:quran_levels,id',
            'grade_level' => 'required_if:scope,grade|nullable|string|max:20',
            'category' => 'required|in:materi,surah_pendek,doa_harian,hadist,niat_sholat,bacaan_sholat',
            'title' => 'required|string|max:255',
        ]);

        $scope = $validated['scope'] ?? QuranRubricItem::SCOPE_LEVEL;

        if ($scope === QuranRubricItem::SCOPE_GRADE) {
            $gradeLevel = trim((string) $validated['grade_level']);
            $nextOrder = (QuranRubricItem::where('unit_id', $unit->id)
                ->where('scope', QuranRubricItem::SCOPE_GRADE)
                ->where('grade_level', $gradeLevel)
                ->where('category', $validated['category'])
                ->max('sort_order') ?? 0) + 1;

            QuranRubricItem::create([
                'unit_id' => $unit->id,
                'scope' => QuranRubricItem::SCOPE_GRADE,
                'level_id' => null,
                'grade_level' => $gradeLevel,
                'category' => $validated['category'],
                'title' => trim($validated['title']),
                'sort_order' => $nextOrder,
                'is_active' => true,
            ]);
        } else {
            $level = QuranLevel::where('unit_id', $unit->id)->findOrFail($validated['level_id']);

            $nextOrder = (QuranRubricItem::where('level_id', $level->id)
                ->where('scope', QuranRubricItem::SCOPE_LEVEL)
                ->where('category', $validated['category'])
                ->max('sort_order') ?? 0) + 1;

            QuranRubricItem::create([
                'unit_id' => $level->unit_id,
                'scope' => QuranRubricItem::SCOPE_LEVEL,
                'level_id' => $level->id,
                'grade_level' => null,
                'category' => $validated['category'],
                'title' => trim($validated['title']),
                'sort_order' => $nextOrder,
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'Item rubrik berhasil ditambahkan.');
    }

    public function updateItem(Request $request, QuranRubricItem $item)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($item->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:1|max:999',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update([
            'title' => trim($validated['title']),
            'sort_order' => $validated['sort_order'] ?? $item->sort_order,
            'is_active' => $validated['is_active'] ?? $item->is_active,
        ]);

        return back()->with('success', 'Item rubrik berhasil diperbarui.');
    }

    public function destroyItem(Request $request, QuranRubricItem $item)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }
        $unit = $this->access->resolveUnit($request, $user);
        if ($item->unit_id !== $unit->id && !$this->access->isGlobalAdmin($user)) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', 'Item rubrik berhasil dihapus.');
    }
}
