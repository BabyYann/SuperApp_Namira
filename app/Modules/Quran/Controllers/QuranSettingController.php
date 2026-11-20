<?php

namespace App\Modules\Quran\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranAccessService;
use App\Modules\Quran\Services\QuranSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QuranSettingController extends Controller
{
    public function __construct(
        private QuranAccessService $access,
        private QuranSetupService $setup
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403, 'Hanya Koordinator Al-Qur\'an / Admin yang dapat membuka pengaturan rapor.');
        }

        $unit = $this->access->resolveUnit($request, $user);
        $this->setup->ensureUnitDefaults($unit);

        $setting = QuranSetting::where('unit_id', $unit->id)->firstOrFail();
        $isGlobalAdmin = $this->access->isGlobalAdmin($user);

        return Inertia::render('Quran/Settings', [
            'unit' => $unit->only(['id', 'name', 'code']),
            'eligibleUnits' => $isGlobalAdmin ? $this->access->getEligibleUnits()->map->only(['id', 'name', 'code']) : [],
            'canManage' => true,
            'isGlobalAdmin' => $isGlobalAdmin,
            'setting' => $setting,
            'unitLogoUrl' => $this->setup->resolveUnitLogoUrl($unit),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$this->access->canManage($user)) {
            abort(403);
        }

        $unit = $this->access->resolveUnit($request, $user);
        $this->setup->ensureUnitDefaults($unit);
        $setting = QuranSetting::where('unit_id', $unit->id)->firstOrFail();

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_address' => 'nullable|string|max:255',
            'city_name' => 'nullable|string|max:100',
            'kepala_sekolah' => 'nullable|string|max:255',
            'kepala_tpq' => 'nullable|string|max:255',
            'kepala_tpq_title' => 'nullable|string|max:255',
            'lock_input_nilai' => 'boolean',
            'lock_cetak_rapor' => 'boolean',
            'allow_teacher_promote' => 'boolean',
            'report_date' => 'nullable|date',
            'ttd_kepala_tpq_file' => 'nullable|image|max:2048',
            'ttd_kepala_sekolah_file' => 'nullable|image|max:2048',
            'remove_ttd_kepala_tpq' => 'nullable|boolean',
            'remove_ttd_kepala_sekolah' => 'nullable|boolean',
            'ttd_tpq_width' => 'nullable|integer|min:40|max:240',
            'ttd_tpq_offset_x' => 'nullable|integer|min:-80|max:80',
            'ttd_tpq_offset_y' => 'nullable|integer|min:-80|max:80',
            'ttd_kepsek_width' => 'nullable|integer|min:40|max:240',
            'ttd_kepsek_offset_x' => 'nullable|integer|min:-80|max:80',
            'ttd_kepsek_offset_y' => 'nullable|integer|min:-80|max:80',
        ]);

        $ttdTpq = $setting->ttd_kepala_tpq;
        if (!empty($validated['remove_ttd_kepala_tpq'])) {
            if ($ttdTpq && !str_starts_with($ttdTpq, 'data:')) {
                Storage::disk('public')->delete($ttdTpq);
            }
            $ttdTpq = null;
        }
        if ($request->hasFile('ttd_kepala_tpq_file')) {
            if ($ttdTpq && !str_starts_with($ttdTpq, 'data:')) {
                Storage::disk('public')->delete($ttdTpq);
            }
            $ttdTpq = $request->file('ttd_kepala_tpq_file')->store('quran/signatures', 'public');
        }

        $ttdKepsek = $setting->ttd_kepala_sekolah;
        if (!empty($validated['remove_ttd_kepala_sekolah'])) {
            if ($ttdKepsek && !str_starts_with($ttdKepsek, 'data:')) {
                Storage::disk('public')->delete($ttdKepsek);
            }
            $ttdKepsek = null;
        }
        if ($request->hasFile('ttd_kepala_sekolah_file')) {
            if ($ttdKepsek && !str_starts_with($ttdKepsek, 'data:')) {
                Storage::disk('public')->delete($ttdKepsek);
            }
            $ttdKepsek = $request->file('ttd_kepala_sekolah_file')->store('quran/signatures', 'public');
        }

        $setting->update([
            'school_name' => $validated['school_name'],
            'school_address' => $validated['school_address'] ?? null,
            'city_name' => $validated['city_name'] ?? 'Kraksaan',
            'kepala_sekolah' => $validated['kepala_sekolah'] ?? null,
            'kepala_tpq' => $validated['kepala_tpq'] ?? null,
            'kepala_tpq_title' => $validated['kepala_tpq_title'] ?? ('Kepala TPQ Ummi ' . $unit->name),
            'ttd_kepala_tpq' => $ttdTpq,
            'ttd_kepala_sekolah' => $ttdKepsek,
            'ttd_config' => [
                'tpq_width' => (int) ($validated['ttd_tpq_width'] ?? 100),
                'tpq_offset_x' => (int) ($validated['ttd_tpq_offset_x'] ?? 0),
                'tpq_offset_y' => (int) ($validated['ttd_tpq_offset_y'] ?? 0),
                'kepsek_width' => (int) ($validated['ttd_kepsek_width'] ?? 100),
                'kepsek_offset_x' => (int) ($validated['ttd_kepsek_offset_x'] ?? 0),
                'kepsek_offset_y' => (int) ($validated['ttd_kepsek_offset_y'] ?? 0),
            ],
            'lock_input_nilai' => (bool) ($validated['lock_input_nilai'] ?? false),
            'lock_cetak_rapor' => (bool) ($validated['lock_cetak_rapor'] ?? false),
            'allow_teacher_promote' => (bool) ($validated['allow_teacher_promote'] ?? true),
            'report_date' => $validated['report_date'] ?? null,
        ]);

        return back()->with('success', 'Pengaturan rapor Program Al-Qur\'an berhasil disimpan.');
    }
}
