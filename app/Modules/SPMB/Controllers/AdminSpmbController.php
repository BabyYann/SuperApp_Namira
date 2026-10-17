<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AdminSpmbController extends Controller
{
    /**
     * Check authorization for SPMB admin access.
     */
    protected function authorizeSpmb(?int $targetUnitId = null)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola SPMB.');
        }

        // Enforce unit scope if user is unit-scoped (admin_unit, kepala_sekolah, panitia_spmb)
        if ($targetUnitId && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $activeUnitId = session('active_unit_id');
            if ($activeUnitId && (int)$activeUnitId !== (int)$targetUnitId) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola pendaftar unit lain.');
            }
        }
    }

    /**
     * Display list of applicants with search, filters, and statistics.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        // Determine target unit
        $targetUnit = null;
        if ($request->filled('unit_id')) {
            $targetUnit = Unit::find($request->unit_id);
        }

        if (!$targetUnit && session()->has('active_unit_id')) {
            $targetUnit = Unit::find(session('active_unit_id'));
        }

        if (!$targetUnit) {
            $targetUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        }

        if (!$targetUnit) {
            $targetUnit = Unit::firstOrFail();
        }

        // Enforce unit scope if user is not yayasan admin
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $query = SpmbApplicant::with(['unit', 'user'])
            ->where('unit_id', $targetUnit->id);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('parent_phone', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('mother_name', 'like', "%{$search}%")
                    ->orWhere('previous_school', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Statistics
        $stats = [
            'total' => SpmbApplicant::where('unit_id', $targetUnit->id)->count(),
            'internal_tk' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('category', 'internal_tk')->count(),
            'external_umum' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('category', 'eksternal_umum')->count(),
            'submitted' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('status', 'submitted')->count(),
            'verified' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('status', ['verified', 'scheduled'])->count(),
            'accepted' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('status', ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])->count(),
            'enrolled' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('status', 'enrolled')->count(),
        ];

        $applicants = $query->latest()->paginate(15)->withQueryString();

        $availableUnits = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) 
            ? Unit::select('id', 'name', 'code')->get() 
            : [];

        return Inertia::render('SPMB/Admin/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status', 'unit_id']),
            'unit' => $targetUnit,
            'availableUnits' => $availableUnits,
        ]);
    }

    /**
     * Show applicant detail for verification, scheduling, and evaluation.
     */
    public function show(SpmbApplicant $applicant)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $applicant->load([
            'unit',
            'user',
            'student',
            'registrationPaymentVerifier',
            'scheduler',
            'evaluator',
            'admissionPaymentVerifier',
            'settlementPaymentVerifier',
        ]);

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        return Inertia::render('SPMB/Admin/Show', [
            'applicant' => $applicant,
            'setting' => $setting,
        ]);
    }

    /**
     * Verify initial registration payment (QRIS).
     */
    public function verifyPayment(Request $request, SpmbApplicant $applicant)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $applicant->update([
            'status' => 'verified',
            'registration_payment_verified_at' => now(),
            'registration_payment_verified_by' => Auth::id(),
        ]);

        return back()->with('success', "Pembayaran pendaftaran calon siswa {$applicant->full_name} berhasil diverifikasi.");
    }

    /**
     * Set Observation and Psychotest schedules.
     */
    public function setSchedule(Request $request, SpmbApplicant $applicant)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $request->validate([
            'observation_date' => 'required|date',
            'observation_time' => 'required|string|max:50',
            'observation_location' => 'required|string|max:100',
            'psychotest_date' => 'nullable|date',
            'psychotest_time' => 'nullable|string|max:50',
            'psychotest_location' => 'nullable|string|max:100',
            'schedule_notes' => 'nullable|string|max:500',
        ]);

        $applicant->update([
            'observation_date' => $request->observation_date,
            'observation_time' => $request->observation_time,
            'observation_location' => $request->observation_location,
            'psychotest_date' => $request->psychotest_date,
            'psychotest_time' => $request->psychotest_time,
            'psychotest_location' => $request->psychotest_location,
            'schedule_notes' => $request->schedule_notes,
            'scheduled_at' => now(),
            'scheduled_by' => Auth::id(),
            'status' => 'scheduled',
        ]);

        $msg = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp')
            ? 'Jadwal Seleksi Akademik dan Observasi'
            : 'Jadwal Observasi dan Psikotes';

        return back()->with('success', "{$msg} untuk {$applicant->full_name} berhasil diterbitkan.");
    }

    /**
     * Save evaluation results and upload observation report document.
     */
    public function saveEvaluation(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $request->validate([
            'evaluation_result' => 'required|in:accepted,reserve,rejected',
            'evaluation_notes' => 'nullable|string|max:1000',
            'evaluation_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'bahasa_indonesia_score' => 'nullable|numeric|min:0|max:100',
            'matematika_score' => 'nullable|numeric|min:0|max:100',
            'wawancara_notes' => 'nullable|string|max:500',
            'mengaji_notes' => 'nullable|string|max:500',
        ]);

        $data = [
            'evaluation_result' => $request->evaluation_result,
            'evaluation_notes' => $request->evaluation_notes,
            'evaluated_at' => now(),
            'evaluated_by' => Auth::id(),
            'status' => $request->evaluation_result,
        ];

        if ($request->hasAny(['bahasa_indonesia_score', 'matematika_score', 'wawancara_notes', 'mengaji_notes'])) {
            $data['academic_score_details'] = [
                'bahasa_indonesia' => $request->bahasa_indonesia_score,
                'matematika' => $request->matematika_score,
                'wawancara' => $request->wawancara_notes,
                'mengaji' => $request->mengaji_notes,
                'evaluator_id' => Auth::id(),
                'evaluated_at' => now()->toIso8601String(),
            ];
        }

        if ($request->hasFile('evaluation_document')) {
            $unitSlug = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp') ? 'smp-namira' : 'sd-namira';
            $docPath = $spmbService->storeApplicantDocument(
                $request->file('evaluation_document'),
                'HASIL_EVALUASI',
                $applicant->registration_number,
                $unitSlug
            );
            $data['evaluation_document_path'] = $docPath;
        }

        // If accepted, set default admission fee from setting if not already set
        if ($request->evaluation_result === 'accepted' && !$applicant->total_admission_fee) {
            $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();
            if ($setting && $setting->admission_fee_total) {
                $data['total_admission_fee'] = $setting->admission_fee_total;
                $data['min_down_payment'] = $setting->admission_fee_total * ($setting->min_down_payment_percentage / 100);
            }
        }

        $applicant->update($data);

        $resultLabel = match ($request->evaluation_result) {
            'accepted' => 'Diterima (Lulus)',
            'reserve' => 'Cadangan',
            'rejected' => 'Belum Diterima',
            default => $request->evaluation_result,
        };

        return back()->with('success', "Hasil evaluasi {$applicant->full_name} berhasil disimpan dengan status: {$resultLabel}.");
    }

    /**
     * Input or update Virtual Account Bank Jatim & fee terms for accepted applicant.
     */
    public function setVirtualAccount(Request $request, SpmbApplicant $applicant)
    {
        $this->authorizeSpmb();

        $request->validate([
            'virtual_account_number' => 'required|string|max:50',
            'total_admission_fee' => 'required|numeric|min:0',
            'min_down_payment' => 'required|numeric|min:0',
            'down_payment_deadline' => 'nullable|date',
            'full_payment_deadline' => 'nullable|date',
        ]);

        $applicant->update([
            'virtual_account_number' => $request->virtual_account_number,
            'total_admission_fee' => $request->total_admission_fee,
            'min_down_payment' => $request->min_down_payment,
            'down_payment_deadline' => $request->down_payment_deadline,
            'full_payment_deadline' => $request->full_payment_deadline,
        ]);

        return back()->with('success', "Nomor Virtual Account Bank Jatim untuk {$applicant->full_name} berhasil disimpan.");
    }

    /**
     * Verify re-registration payment (Termin 60% or 100%).
     */
    public function verifyAdmissionPayment(Request $request, SpmbApplicant $applicant)
    {
        $this->authorizeSpmb();

        $request->validate([
            'paid_admission_amount' => 'required|numeric|min:0',
            'admission_payment_status' => 'required|in:partial,paid',
        ]);

        $status = $request->admission_payment_status === 'paid' ? 'fully_paid' : 'partial_paid';

        $updateData = [
            'paid_admission_amount' => $request->paid_admission_amount,
            'admission_payment_status' => $request->admission_payment_status,
            'status' => $status,
        ];

        if ($request->admission_payment_status === 'paid') {
            if (Schema::hasColumn('spmb_applicants', 'settlement_payment_verified_at')) {
                $updateData['settlement_payment_verified_at'] = now();
                $updateData['settlement_payment_verified_by'] = Auth::id();
            }
            if (!$applicant->admission_payment_verified_at) {
                $updateData['admission_payment_verified_at'] = now();
                $updateData['admission_payment_verified_by'] = Auth::id();
            }
        } else {
            $updateData['admission_payment_verified_at'] = now();
            $updateData['admission_payment_verified_by'] = Auth::id();
        }

        if (Schema::hasColumn('spmb_applicants', 'payment_history')) {
            $history = $applicant->payment_history ?? [];
            foreach ($history as &$item) {
                if (($request->admission_payment_status === 'partial' && ($item['type'] ?? '') === 'termin_1')
                    || ($request->admission_payment_status === 'paid' && in_array($item['type'] ?? '', ['termin_2', 'pelunasan']))) {
                    $item['status'] = 'verified';
                    $item['verified_at'] = now()->toIso8601String();
                    $item['verifier_name'] = Auth::user()->name;
                }
            }
            $updateData['payment_history'] = $history;
        }

        $applicant->update($updateData);

        $msg = $request->admission_payment_status === 'paid' ? 'Pelunasan 100% Berhasil' : 'Pembayaran Termin 1 (60%) Berhasil';

        return back()->with('success', "Status daftar ulang {$applicant->full_name} berhasil diperbarui: {$msg}.");
    }

    /**
     * Enroll applicant to active student in Academic module.
     */
    public function enrollStudent(SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb($applicant->unit_id);

        if (!in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid'])) {
            return back()->withErrors(['error' => 'Hanya calon siswa yang telah diterima atau telah daftar ulang yang dapat dipindahkan ke Data Siswa Aktif.']);
        }

        $student = $spmbService->enrollToAcademicStudent($applicant, Auth::user());
        $unitName = $applicant->unit ? $applicant->unit->name : 'Namira';

        return back()->with('success', "Calon siswa {$applicant->full_name} berhasil resmi dipindahkan menjadi Siswa Aktif {$unitName} dengan NIS: {$student->nis}.");
    }

    /**
     * SPMB Settings and Panitia Management.
     */
    public function settings(Request $request)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = null;
        if ($request->filled('unit_id')) {
            $targetUnit = Unit::find($request->unit_id);
        }
        if (!$targetUnit && session()->has('active_unit_id')) {
            $targetUnit = Unit::find(session('active_unit_id'));
        }
        if (!$targetUnit) {
            $targetUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        }
        if (!$targetUnit) {
            $targetUnit = Unit::firstOrFail();
        }

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $targetUnit->id],
            [
                'name' => 'Pendaftaran Jalur Inden ' . $targetUnit->name,
                'academic_year' => '2026/2027',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => '0291008899',
                'bank_account_holder' => 'YAYASAN NAMIRA PROBOLINGGO',
                'admission_fee_total' => 8500000,
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );

        // List teachers in target unit
        $teachers = Teacher::with('user')
            ->where('unit_id', $targetUnit->id)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->user_id,
                    'name' => $t->user ? $t->user->name : $t->name,
                    'email' => $t->user ? $t->user->email : '-',
                    'niy' => $t->niy,
                ];
            });

        // List assigned panitia
        $panitiaRole = Role::firstOrCreate(['name' => 'panitia_spmb', 'guard_name' => 'web']);
        
        $assignedPanitia = User::whereHas('roles', function ($q) {
            $q->where('name', 'panitia_spmb');
        })->get(['id', 'name', 'email']);

        $availableUnits = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) 
            ? Unit::select('id', 'name', 'code')->get() 
            : [];

        return Inertia::render('SPMB/Admin/Settings', [
            'setting' => $setting,
            'unit' => $targetUnit,
            'teachers' => $teachers,
            'assignedPanitia' => $assignedPanitia,
            'availableUnits' => $availableUnits,
        ]);
    }

    /**
     * Update SPMB Settings.
     */
    public function updateSettings(Request $request, SpmbService $spmbService)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = null;
        if ($request->filled('unit_id')) {
            $targetUnit = Unit::find($request->unit_id);
        }
        if (!$targetUnit && session()->has('active_unit_id')) {
            $targetUnit = Unit::find(session('active_unit_id'));
        }
        if (!$targetUnit) {
            $targetUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        }
        if (!$targetUnit) {
            $targetUnit = Unit::firstOrFail();
        }

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $setting = SpmbSetting::where('unit_id', $targetUnit->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string|max:50',
            'quota' => 'required|integer|min:1',
            'registration_fee' => 'required|numeric|min:0',
            'admission_fee_total' => 'nullable|numeric|min:0',
            'min_down_payment_percentage' => 'required|integer|min:1|max:100',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:150',
            'contact_whatsapp' => 'nullable|string|max:25',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
            'selection_date' => 'nullable|date',
            'announcement_date' => 'nullable|date',
            'announcement_released' => 'nullable|boolean',
            'qris_image' => 'nullable|file|image|max:10240',
        ]);

        $data = $request->only([
            'name', 'academic_year', 'quota', 'registration_fee',
            'admission_fee_total', 'min_down_payment_percentage',
            'bank_name', 'bank_account_number', 'bank_account_holder',
            'contact_whatsapp', 'notes', 'is_active',
            'selection_date', 'announcement_date', 'announcement_released'
        ]);

        if ($request->hasFile('qris_image')) {
            $path = $request->file('qris_image')->store('spmb/qris', 'public');
            $data['qris_image_path'] = $path;
        }

        $setting->update($data);

        return back()->with('success', 'Pengaturan SPMB berhasil diperbarui.');
    }

    /**
     * Assign panitia_spmb role to a teacher/user.
     */
    public function assignPanitia(Request $request)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $targetUnit = null;
        if ($request->filled('unit_id')) {
            $targetUnit = Unit::find($request->unit_id);
        }
        if (!$targetUnit && session()->has('active_unit_id')) {
            $targetUnit = Unit::find(session('active_unit_id'));
        }
        if (!$targetUnit) {
            $targetUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        }
        if (!$targetUnit) {
            $targetUnit = Unit::firstOrFail();
        }

        $targetUser = User::findOrFail($request->user_id);

        $role = Role::firstOrCreate(['name' => 'panitia_spmb', 'guard_name' => 'web']);

        if (!$targetUser->hasRole('panitia_spmb')) {
            setPermissionsTeamId($targetUnit->id);
            $targetUser->assignRole('panitia_spmb');
        }

        return back()->with('success', "Ustadz/Ustadzah {$targetUser->name} berhasil ditugaskan sebagai Panitia SPMB {$targetUnit->name}.");
    }

    /**
     * Remove panitia_spmb role from a user.
     */
    public function removePanitia(Request $request, User $user)
    {
        $authUser = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = null;
        if ($request->filled('unit_id')) {
            $targetUnit = Unit::find($request->unit_id);
        }
        if (!$targetUnit && session()->has('active_unit_id')) {
            $targetUnit = Unit::find(session('active_unit_id'));
        }
        if (!$targetUnit) {
            $targetUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        }
        if ($targetUnit) {
            setPermissionsTeamId($targetUnit->id);
        }
        
        $user->removeRole('panitia_spmb');

        return back()->with('success', "Penugasan Panitia SPMB untuk {$user->name} berhasil dicabut.");
    }
}
