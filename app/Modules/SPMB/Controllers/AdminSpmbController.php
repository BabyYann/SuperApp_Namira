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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $globalRoles = ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'humas_yayasan', 'staff_yayasan'];
        $unitRoles = ['admin_unit', 'kepala_sekolah', 'panitia_spmb', 'humas_unit'];

        if (!$user->hasAnyRole(array_merge($globalRoles, $unitRoles))) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola SPMB.');
        }

        // Enforce unit scope if user is unit-scoped (admin_unit, kepala_sekolah, panitia_spmb, humas_unit)
        if ($targetUnitId && !$user->hasAnyRole($globalRoles)) {
            $activeUnitId = session('active_unit_id');
            if ($activeUnitId && (int)$activeUnitId !== (int)$targetUnitId) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola pendaftar unit lain.');
            }
        }
    }

    /**
     * Check if a Unit is a valid SPMB school unit (SD, SD-02, SMP).
     */
    protected function isSpmbUnit(?Unit $unit): bool
    {
        if (!$unit) {
            return false;
        }

        $code = strtoupper($unit->code ?? '');
        $name = strtolower($unit->name ?? '');

        return in_array($code, ['SD', 'SD-02', 'SMP'])
            || str_contains($name, 'sd')
            || str_contains($name, 'smp');
    }

    /**
     * Resolve target SPMB unit from request, active unit session (only if valid SPMB unit), or fallback to SD/SMP.
     */
    protected function resolveSpmbUnit(Request $request): Unit
    {
        if ($request->filled('unit_id')) {
            $requested = Unit::find($request->unit_id);
            if ($requested) {
                return $requested;
            }
        }

        if (session()->has('active_unit_id')) {
            $activeUnit = Unit::find(session('active_unit_id'));
            if ($this->isSpmbUnit($activeUnit)) {
                return $activeUnit;
            }
        }

        return Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first()
            ?? Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first()
            ?? Unit::firstOrFail();
    }

    /**
     * Display list of applicants with search, filters, and statistics.
     */
    public function index(Request $request, SpmbService $spmbService)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = $this->resolveSpmbUnit($request);

        // Enforce unit scope if user is not yayasan admin
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $unitCode = strtoupper($targetUnit->code ?? '');
        $unitNameLower = strtolower($targetUnit->name ?? '');
        $isSdKota = $unitCode === 'SD-02' || (str_contains($unitNameLower, 'sd') && str_contains($unitNameLower, 'kota'));
        $isSdKraksaan = !$isSdKota && ($unitCode === 'SD' || str_contains($unitNameLower, 'sd'));

        $indenMasterList = [];
        $indenMasterStats = null;

        if ($isSdKraksaan) {
            $unitSetting = SpmbSetting::where('unit_id', $targetUnit->id)->first();
            $groupInviteSuffix = !empty($unitSetting?->whatsapp_group_link)
                ? "\n\nSilakan juga bergabung ke Grup WhatsApp Resmi Orang Tua Calon Murid SD Namira Kraksaan melalui tautan berikut:\n" . $unitSetting->whatsapp_group_link
                : '';

            $allSdApplicants = SpmbApplicant::where('unit_id', $targetUnit->id)
                ->get(['id', 'registration_number', 'full_name', 'parent_phone', 'father_phone', 'mother_phone', 'status', 'is_inden']);

            // Auto-sync is_inden flag on existing SD applicants if their phone or name matches the 72 Inden master list
            if (Schema::hasColumn('spmb_applicants', 'is_inden')) {
                foreach ($allSdApplicants as $sdApp) {
                    if (!$sdApp->is_inden) {
                        $matched = $spmbService->matchSdIndenApplicant($sdApp->parent_phone, $sdApp->full_name)
                            ?? $spmbService->matchSdIndenApplicant($sdApp->father_phone, null)
                            ?? $spmbService->matchSdIndenApplicant($sdApp->mother_phone, null);
                        if ($matched) {
                            $sdApp->update(['is_inden' => true]);
                            $sdApp->is_inden = true;
                        }
                    }
                }
            }

            $registeredCount = 0;
            foreach ($spmbService->getSdIndenMasterList() as $item) {
                $normPhone = $spmbService->normalizePhone($item['phone']);
                $normName = strtolower(trim(preg_replace('/\s+/', ' ', $item['full_name'])));

                $matchedApplicant = $allSdApplicants->first(function ($app) use ($spmbService, $normPhone, $normName) {
                    if ($normPhone !== '') {
                        if (
                            $spmbService->normalizePhone($app->parent_phone) === $normPhone ||
                            $spmbService->normalizePhone($app->father_phone) === $normPhone ||
                            $spmbService->normalizePhone($app->mother_phone) === $normPhone
                        ) {
                            return true;
                        }
                    }
                    $appName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));
                    return $normName !== '' && $appName === $normName;
                });

                if ($matchedApplicant) {
                    $registeredCount++;
                }

                $waPhone = str_starts_with($normPhone, '0') ? '62' . substr($normPhone, 1) : $normPhone;
                $waText = rawurlencode("Assalamu'alaikum Bapak/Ibu Orang Tua dari ananda {$item['full_name']}, menginformasikan bahwa pendaftaran online SPMB SD Namira Kraksaan Tahun Ajaran 2027/2028 telah dibuka. Silakan melengkapi formulir pendaftaran melalui tautan: " . url('/spmb/daftar/sd') . $groupInviteSuffix);

                $indenMasterList[] = [
                    'no' => $item['no'],
                    'full_name' => $item['full_name'],
                    'phone' => $normPhone,
                    'previous_school' => $item['previous_school'],
                    'source_label' => $item['source_label'],
                    'is_registered' => $matchedApplicant !== null,
                    'applicant_id' => $matchedApplicant?->id,
                    'registration_number' => $matchedApplicant?->registration_number,
                    'status' => $matchedApplicant?->status,
                    'status_label' => $matchedApplicant?->status_label,
                    'wa_followup_link' => "https://wa.me/{$waPhone}?text={$waText}",
                ];
            }

            $totalInden = count($indenMasterList);
            $indenMasterStats = [
                'total' => $totalInden,
                'registered' => $registeredCount,
                'unregistered' => $totalInden - $registeredCount,
            ];
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
            if ($request->category === 'inden') {
                $query->where('is_inden', true);
            } elseif ($request->category === 'umum_reguler') {
                $query->where(function ($q) {
                    $q->where('is_inden', false)->orWhereNull('is_inden');
                });
            } elseif (in_array($request->category, ['internal', 'internal_tk', 'internal_sd'])) {
                $query->whereIn('category', ['internal', 'internal_tk', 'internal_sd']);
            } elseif (in_array($request->category, ['eksternal_umum', 'external_umum', 'external'])) {
                $query->whereIn('category', ['eksternal_umum', 'external_umum', 'external']);
            } else {
                $query->where('category', $request->category);
            }
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Statistics
        $stats = [
            'total' => SpmbApplicant::where('unit_id', $targetUnit->id)->count(),
            'internal' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('category', ['internal_tk', 'internal_sd', 'internal'])->count(),
            'internal_tk' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('category', ['internal_tk', 'internal_sd', 'internal'])->count(),
            'external_umum' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('category', ['eksternal_umum', 'external_umum', 'external'])->count(),
            'submitted' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('status', 'submitted')->count(),
            'verified' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('status', ['verified', 'scheduled'])->count(),
            'accepted' => SpmbApplicant::where('unit_id', $targetUnit->id)->whereIn('status', ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])->count(),
            'enrolled' => SpmbApplicant::where('unit_id', $targetUnit->id)->where('status', 'enrolled')->count(),
        ];

        $applicants = $query->latest()->paginate(15)->withQueryString();

        // Ensure SD-02 exists so it always appears in availableUnits
        Unit::firstOrCreate(
            ['code' => 'SD-02'],
            [
                'name' => 'SD NAMIRA KOTA PROBOLINGGO',
                'category' => 'formal',
                'level' => 'SD',
            ]
        );

        $availableUnits = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) 
            ? Unit::select('id', 'name', 'code')->get() 
            : [];

        return Inertia::render('SPMB/Admin/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status', 'unit_id']),
            'unit' => $targetUnit,
            'setting' => SpmbSetting::where('unit_id', $targetUnit->id)->first(),
            'availableUnits' => $availableUnits,
            'indenMasterList' => $indenMasterList,
            'indenMasterStats' => $indenMasterStats,
            'privateRegistrationUrl' => $isSdKota ? url('/spmb/daftar/sd-kota') : null,
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
        $classrooms = \App\Modules\Academic\Models\Classroom::where('unit_id', $applicant->unit_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return Inertia::render('SPMB/Admin/Show', [
            'applicant' => $applicant,
            'setting' => $setting,
            'classrooms' => $classrooms,
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
            'academic_score' => 'nullable|numeric|min:0|max:100',
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

        if ($request->hasAny(['academic_score', 'bahasa_indonesia_score', 'matematika_score', 'wawancara_notes', 'mengaji_notes'])) {
            $singleScore = $request->academic_score ?? $request->bahasa_indonesia_score;
            $data['academic_score_details'] = [
                'academic_score' => $singleScore,
                'wawancara' => $request->wawancara_notes,
                'mengaji' => $request->mengaji_notes,
                'evaluator_id' => Auth::id(),
                'evaluated_at' => now()->toIso8601String(),
            ];
        }

        if ($request->hasFile('evaluation_document')) {
            $unitSlug = app(SpmbService::class)->resolveUnitSlug($applicant->unit);
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
        $this->authorizeSpmb($applicant->unit_id);

        $request->validate([
            'virtual_account_number' => 'required|string|max:50',
            'total_admission_fee' => 'required|numeric|min:0',
            'min_down_payment' => 'nullable|numeric|min:0',
            'down_payment_deadline' => 'nullable|date',
            'full_payment_deadline' => 'nullable|date',
        ]);

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();
        $dpPercentage = (int) ($setting?->min_down_payment_percentage ?? 60);
        $isFullPaymentOnly = $dpPercentage >= 100;

        $minDownPayment = $isFullPaymentOnly
            ? (float) $request->total_admission_fee
            : (float) ($request->min_down_payment ?? ($request->total_admission_fee * ($dpPercentage / 100)));

        $applicant->update([
            'virtual_account_number' => $request->virtual_account_number,
            'total_admission_fee' => $request->total_admission_fee,
            'min_down_payment' => $minDownPayment,
            'down_payment_deadline' => $isFullPaymentOnly ? null : $request->down_payment_deadline,
            'full_payment_deadline' => $request->full_payment_deadline,
        ]);

        $isSmp = strtoupper($applicant->unit?->code ?? '') === 'SMP'
            || str_contains(strtolower($applicant->unit?->name ?? ''), 'smp');
        $vaLabel = $isSmp ? 'Nomor Virtual Account (VA)' : 'Nomor Virtual Account Bank Jatim';

        return back()->with('success', "{$vaLabel} untuk {$applicant->full_name} berhasil disimpan.");
    }

    /**
     * Verify re-registration payment (Termin 60% or 100%).
     */
    public function verifyAdmissionPayment(Request $request, SpmbApplicant $applicant)
    {
        $this->authorizeSpmb($applicant->unit_id);

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

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();
        $dpPercent = (int) ($setting?->min_down_payment_percentage ?? 60);
        $msg = $request->admission_payment_status === 'paid'
            ? 'Pelunasan 100% Berhasil'
            : "Pembayaran Termin 1 ({$dpPercent}%) Berhasil";

        return back()->with('success', "Status daftar ulang {$applicant->full_name} berhasil diperbarui: {$msg}.");
    }

    /**
     * Enroll applicant to active student in Academic module.
     */
    public function enrollStudent(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb($applicant->unit_id);

        if (!in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid'])) {
            return back()->withErrors(['error' => 'Hanya calon siswa yang telah diterima atau telah daftar ulang yang dapat dipindahkan ke Data Siswa Aktif.']);
        }

        $classroomId = $request->filled('classroom_id') ? (int)$request->classroom_id : null;
        $student = $spmbService->enrollToAcademicStudent($applicant, Auth::user(), $classroomId);
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

        $targetUnit = $this->resolveSpmbUnit($request);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $isSmpUnit = strtoupper($targetUnit->code ?? '') === 'SMP'
            || str_contains(strtolower($targetUnit->name ?? ''), 'smp');

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $targetUnit->id],
            [
                'name' => 'Pendaftaran Murid Baru ' . $targetUnit->name,
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                'admission_fee_total' => 8500000,
                'min_down_payment_percentage' => $isSmpUnit ? 100 : 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        $settingUpdates = [];
        if ($setting->academic_year === '2026/2027') {
            $settingUpdates['academic_year'] = '2027/2028';
        }
        $isSdTarget = ($targetUnit->code === 'SD') || str_contains(strtolower($targetUnit->name ?? ''), 'sd');
        if ($isSdTarget && $setting->bank_account_number === '0291008899') {
            $settingUpdates['bank_account_number'] = null;
        }
        if (!empty($settingUpdates)) {
            $setting->update($settingUpdates);
        }

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

        // List assigned panitia specifically for this target unit
        Role::firstOrCreate(['name' => 'panitia_spmb', 'guard_name' => 'web', 'team_id' => null]);
        
        $assignedPanitia = User::whereExists(function ($mq) use ($targetUnit) {
            $mq->select(DB::raw(1))
               ->from('model_has_roles')
               ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
               ->whereColumn('model_has_roles.model_id', 'users.id')
               ->where('roles.name', 'panitia_spmb')
               ->where(function ($tq) use ($targetUnit) {
                   $tq->where('model_has_roles.team_id', $targetUnit->id)
                      ->orWhereNull('model_has_roles.team_id');
               });
        })->get(['id', 'name', 'email']);

        Unit::firstOrCreate(
            ['code' => 'SD-02'],
            [
                'name' => 'SD NAMIRA KOTA PROBOLINGGO',
                'category' => 'formal',
                'level' => 'SD',
            ]
        );

        $availableUnits = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) 
            ? Unit::whereIn('code', ['SD', 'SD-02', 'SMP'])->orWhere('name', 'like', '%SD%')->orWhere('name', 'like', '%SMP%')->select('id', 'name', 'code')->get() 
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

        $targetUnit = $this->resolveSpmbUnit($request);

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
            'whatsapp_group_link' => 'nullable|url|max:500',
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
            'contact_whatsapp', 'notes',
            'selection_date', 'announcement_date'
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['announcement_released'] = $request->boolean('announcement_released');

        if (Schema::hasColumn('spmb_settings', 'whatsapp_group_link')) {
            $data['whatsapp_group_link'] = $request->input('whatsapp_group_link');
        }

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

        $targetUnit = $this->resolveSpmbUnit($request);

        $targetUser = User::findOrFail($request->user_id);

        $role = Role::firstOrCreate(['name' => 'panitia_spmb', 'guard_name' => 'web', 'team_id' => null]);

        setPermissionsTeamId($targetUnit->id);
        if (!$targetUser->hasRole('panitia_spmb')) {
            $targetUser->assignRole('panitia_spmb');
        }
        setPermissionsTeamId(session('active_unit_id'));

        return back()->with('success', "Ustadz/Ustadzah {$targetUser->name} berhasil ditugaskan sebagai Panitia SPMB {$targetUnit->name}.");
    }

    /**
     * Remove panitia_spmb role from a user.
     */
    public function removePanitia(Request $request, User $user)
    {
        $authUser = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = $this->resolveSpmbUnit($request);
        if ($targetUnit) {
            setPermissionsTeamId($targetUnit->id);
        }
        
        $user->removeRole('panitia_spmb');
        setPermissionsTeamId(session('active_unit_id'));

        $unitName = $targetUnit ? " {$targetUnit->name}" : '';
        return back()->with('success', "Penugasan Panitia SPMB{$unitName} untuk {$user->name} berhasil dicabut.");
    }

    /**
     * Delete an applicant and associated files (Super Admin, Admin Yayasan, Admin Unit).
     */
    public function destroy(SpmbApplicant $applicant)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Administrator yang berwenang menghapus data calon siswa.');
        }

        $unitId = $applicant->unit_id;
        $fullName = $applicant->full_name;
        $regNumber = $applicant->registration_number;
        $unitSlug = app(SpmbService::class)->resolveUnitSlug($applicant->unit);
        $parentUser = $applicant->user;

        // 1. Hapus file-file berkas fisik dari storage
        $fileFields = [
            'photo_path',
            'family_card_path',
            'birth_cert_path',
            'parent_id_card_path',
            'registration_payment_proof',
            're_registration_payment_proof',
            'settlement_payment_proof',
            'evaluation_document_path',
        ];

        foreach ($fileFields as $field) {
            if ($applicant->$field && Storage::disk('public')->exists($applicant->$field)) {
                Storage::disk('public')->delete($applicant->$field);
            }
        }

        // Hapus folder registrasi jika ada
        $folder = "spmb/{$unitSlug}/{$regNumber}";
        if (Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->deleteDirectory($folder);
        }

        // 2. Hapus data applicant
        $applicant->delete();

        // 3. Bersihkan akun parent jika akun khusus SPMB dan tidak memiliki pendaftar lain
        if ($parentUser && str_ends_with($parentUser->email, '@spmb.namiraschool.com')) {
            $otherApplicants = SpmbApplicant::where('user_id', $parentUser->id)->count();
            if ($otherApplicants === 0 && !$parentUser->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'guru', 'wali_kelas', 'panitia_spmb'])) {
                $parentUser->delete();
            }
        }

        return redirect()->route('spmb.admin.index', ['unit_id' => $unitId])->with('success', "Data calon siswa {$fullName} ({$regNumber}) berhasil dihapus permanen.");
    }

    /**
     * Update applicant biodata by Admin / Panitia SPMB.
     */
    public function updateApplicant(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'nik' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer',
            'total_siblings' => 'nullable|integer',
            'category' => 'nullable|string|in:internal_sd,internal_tk,internal,eksternal_umum,external_umum,external',
            'is_inden' => 'nullable|boolean',
            'previous_school' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string|max:1000',

            'father_name' => 'nullable|string|max:255',
            'father_nik' => 'nullable|string|max:20',
            'father_phone' => 'nullable|string|max:25',
            'father_job' => 'nullable|string|max:100',
            'father_education' => 'nullable|string|max:50',

            'mother_name' => 'nullable|string|max:255',
            'mother_nik' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:25',
            'mother_job' => 'nullable|string|max:100',
            'mother_education' => 'nullable|string|max:50',

            'parent_phone' => 'required|string|min:9|max:25',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $request->full_name)));

        $duplicateApplicant = SpmbApplicant::where('unit_id', $applicant->unit_id)
            ->where('parent_phone', $cleanPhone)
            ->where('id', '!=', $applicant->id)
            ->get()
            ->first(function ($app) use ($normalizedInputName) {
                $existingName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));
                return $existingName === $normalizedInputName;
            });

        if ($duplicateApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$duplicateApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar pada pendaftar lain ({$duplicateApplicant->registration_number}).",
            ])->withInput();
        }

        $unitSlug = app(SpmbService::class)->resolveUnitSlug($applicant->unit);
        $normalizedCategory = in_array($request->category, ['external_umum', 'external'], true)
            ? 'eksternal_umum'
            : ($request->category ?: $applicant->category);

        $updateData = [
            'full_name' => $request->full_name,
            'nickname' => $request->nickname,
            'gender' => $request->gender,
            'nik' => $request->nik,
            'nisn' => $request->nisn,
            'birth_place' => $request->birth_place,
            'birth_date' => $request->birth_date,
            'religion' => $request->religion ?: ($applicant->religion ?: 'Islam'),
            'child_order' => $request->child_order,
            'total_siblings' => $request->total_siblings,
            'category' => $normalizedCategory,
            'previous_school' => $request->previous_school,
            'special_notes' => $request->special_notes,
            'father_name' => $request->father_name,
            'father_nik' => $request->has('father_nik') ? $request->father_nik : $applicant->father_nik,
            'father_phone' => $request->has('father_phone') ? $request->father_phone : $applicant->father_phone,
            'father_job' => $request->has('father_job') ? $request->father_job : $applicant->father_job,
            'father_education' => $request->has('father_education') ? $request->father_education : $applicant->father_education,
            'mother_name' => $request->mother_name,
            'mother_nik' => $request->has('mother_nik') ? $request->mother_nik : $applicant->mother_nik,
            'mother_phone' => $request->has('mother_phone') ? $request->mother_phone : $applicant->mother_phone,
            'mother_job' => $request->has('mother_job') ? $request->mother_job : $applicant->mother_job,
            'mother_education' => $request->has('mother_education') ? $request->mother_education : $applicant->mother_education,
            'parent_phone' => $cleanPhone,
            'address' => $request->address,
            'rt' => $request->has('rt') ? $request->rt : $applicant->rt,
            'rw' => $request->has('rw') ? $request->rw : $applicant->rw,
            'village' => $request->has('village') ? $request->village : $applicant->village,
            'district' => $request->has('district') ? $request->district : $applicant->district,
            'city' => $request->has('city') ? $request->city : $applicant->city,
            'postal_code' => $request->has('postal_code') ? $request->postal_code : $applicant->postal_code,
        ];

        if ($request->has('is_inden') && Schema::hasColumn('spmb_applicants', 'is_inden')) {
            $updateData['is_inden'] = $request->boolean('is_inden');
        }

        if ($request->hasFile('photo')) {
            if ($applicant->photo_path && Storage::disk('public')->exists($applicant->photo_path)) {
                Storage::disk('public')->delete($applicant->photo_path);
            }
            $updateData['photo_path'] = $spmbService->storeApplicantDocument(
                $request->file('photo'),
                'PASFOTO',
                $applicant->registration_number,
                $unitSlug
            );
        }

        $applicant->update($updateData);

        // Sinkronisasi ke tabel users milik orang tua
        if ($applicant->user) {
            $parentUser = $applicant->user;
            $phoneTaken = User::where('phone', $cleanPhone)->where('id', '!=', $parentUser->id)->exists();
            $userUpdates = [
                'name' => $request->father_name ?: ($request->mother_name ?: $parentUser->name),
            ];
            if (!$phoneTaken) {
                $userUpdates['phone'] = $cleanPhone;
                if (str_ends_with($parentUser->email, '@spmb.namiraschool.com')) {
                    $newEmail = "{$cleanPhone}@spmb.namiraschool.com";
                    if (!User::where('email', $newEmail)->where('id', '!=', $parentUser->id)->exists()) {
                        $userUpdates['email'] = $newEmail;
                    }
                }
            }
            $parentUser->update($userUpdates);
        }

        return back()->with('success', "Biodata calon siswa {$applicant->full_name} berhasil diperbarui.");
    }

    /**
     * Reset parent account password by Admin / Panitia SPMB.
     */
    public function resetParentPassword(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb($applicant->unit_id);

        $request->validate([
            'new_password' => 'required|string|min:6|max:50',
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
        ]);

        $parentUser = $applicant->user;
        if (!$parentUser) {
            $parentName = $applicant->father_name ?: ($applicant->mother_name ?: $applicant->full_name);
            $parentUser = $spmbService->resolveParentUser($applicant->parent_phone, $request->new_password, $parentName);
            $applicant->update(['user_id' => $parentUser->id]);
        } else {
            $parentUser->update([
                'password' => Hash::make($request->new_password),
            ]);
        }

        return back()->with('success', "Kata sandi akun orang tua untuk {$applicant->full_name} (No. WA: {$applicant->parent_phone}) berhasil direset.");
    }

    /**
     * Export applicants and selection scores to CSV (Excel-compatible UTF-8 BOM).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $this->authorizeSpmb();

        $targetUnit = $this->resolveSpmbUnit($request);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $this->authorizeSpmb($targetUnit->id);
        }

        $query = SpmbApplicant::with(['unit'])
            ->where('unit_id', $targetUnit->id);

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

        if ($request->filled('category') && $request->category !== 'all') {
            if (in_array($request->category, ['internal', 'internal_tk', 'internal_sd'])) {
                $query->whereIn('category', ['internal', 'internal_tk', 'internal_sd']);
            } else {
                $query->where('category', $request->category);
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $applicants = $query->orderBy('id', 'asc')->get();
        $unitCode = strtoupper($targetUnit->code ?: 'UNIT');
        $filename = "Data_Pendaftar_SPMB_{$unitCode}_" . date('Y-m-d_His') . ".csv";

        return response()->streamDownload(function () use ($applicants) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'No. Registrasi',
                'Unit Sekolah',
                'Kategori / Jalur',
                'Status Pendaftaran',
                'Nama Lengkap Siswa',
                'Nama Panggilan',
                'L/P',
                'NIK Siswa',
                'NISN',
                'Tempat Lahir',
                'Tanggal Lahir',
                'Asal Sekolah',
                'Nama Ayah',
                'Pekerjaan Ayah',
                'Nama Ibu',
                'Pekerjaan Ibu',
                'No. WA Utama (Login)',
                'Alamat Lengkap',
                'Verifikasi Biaya Formulir',
                'Tgl Seleksi / Observasi',
                'Nilai Tes Akademik',
                'Catatan Observasi Wawancara',
                'Catatan Observasi Mengaji',
                'Catatan Evaluasi Umum',
                'Keputusan Seleksi',
                'No. Virtual Account',
                'Total Biaya Masuk',
                'Nominal Sudah Dibayar',
                'Status Daftar Ulang',
            ]);

            foreach ($applicants as $idx => $app) {
                $scores = $app->academic_score_details ?? [];
                $academicScore = $scores['academic_score'] ?? ($scores['bahasa_indonesia'] ?? '-');
                $wawancara = $scores['wawancara'] ?? '-';
                $mengaji = $scores['mengaji'] ?? '-';

                fputcsv($handle, [
                    $idx + 1,
                    $app->registration_number,
                    $app->unit?->name ?? '-',
                    $app->category_label,
                    $app->status_label,
                    $app->full_name,
                    $app->nickname ?? '-',
                    $app->gender,
                    $app->nik ? "'{$app->nik}" : '-',
                    $app->nisn ? "'{$app->nisn}" : '-',
                    $app->birth_place ?? '-',
                    $app->birth_date ? $app->birth_date->format('Y-m-d') : '-',
                    $app->previous_school ?? '-',
                    $app->father_name ?? '-',
                    $app->father_job ?? '-',
                    $app->mother_name ?? '-',
                    $app->mother_job ?? '-',
                    "'{$app->parent_phone}",
                    $app->address ?? '-',
                    $app->registration_payment_verified_at ? 'Terverifikasi' : 'Menunggu Cek',
                    $app->observation_date ? $app->observation_date->format('Y-m-d') : '-',
                    $academicScore,
                    $wawancara,
                    $mengaji,
                    $app->evaluation_notes ?? '-',
                    $app->evaluation_result ? strtoupper($app->evaluation_result) : '-',
                    $app->virtual_account_number ? "'{$app->virtual_account_number}" : '-',
                    (float) ($app->total_admission_fee ?? 0),
                    (float) ($app->paid_admission_amount ?? 0),
                    $app->admission_payment_status ?? 'unpaid',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
