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
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AdminSpmbController extends Controller
{
    /**
     * Check authorization for SPMB admin access.
     */
    protected function authorizeSpmb()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola SPMB.');
        }
    }

    /**
     * Display list of applicants with search, filters, and statistics.
     */
    public function index(Request $request)
    {
        $this->authorizeSpmb();

        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();

        $query = SpmbApplicant::with(['unit', 'user'])
            ->where('unit_id', $sdUnit->id);

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

        // Category Filter (Internal TK Namira vs Umum)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Statistics
        $stats = [
            'total' => SpmbApplicant::where('unit_id', $sdUnit->id)->count(),
            'internal_tk' => SpmbApplicant::where('unit_id', $sdUnit->id)->where('category', 'internal_tk')->count(),
            'external_umum' => SpmbApplicant::where('unit_id', $sdUnit->id)->where('category', 'eksternal_umum')->count(),
            'submitted' => SpmbApplicant::where('unit_id', $sdUnit->id)->where('status', 'submitted')->count(),
            'verified' => SpmbApplicant::where('unit_id', $sdUnit->id)->whereIn('status', ['verified', 'scheduled'])->count(),
            'accepted' => SpmbApplicant::where('unit_id', $sdUnit->id)->whereIn('status', ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])->count(),
            'enrolled' => SpmbApplicant::where('unit_id', $sdUnit->id)->where('status', 'enrolled')->count(),
        ];

        $applicants = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('SPMB/Admin/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status']),
            'unit' => $sdUnit,
        ]);
    }

    /**
     * Show applicant detail for verification, scheduling, and evaluation.
     */
    public function show(SpmbApplicant $applicant)
    {
        $this->authorizeSpmb();

        $applicant->load([
            'unit',
            'user',
            'student',
            'registrationPaymentVerifier',
            'scheduler',
            'evaluator',
            'admissionPaymentVerifier',
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
        $this->authorizeSpmb();

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
        $this->authorizeSpmb();

        $request->validate([
            'observation_date' => 'required|date',
            'observation_time' => 'required|string|max:50',
            'observation_location' => 'required|string|max:100',
            'psychotest_date' => 'required|date',
            'psychotest_time' => 'required|string|max:50',
            'psychotest_location' => 'required|string|max:100',
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

        return back()->with('success', "Jadwal Observasi dan Psikotes untuk {$applicant->full_name} berhasil diterbitkan.");
    }

    /**
     * Save evaluation results and upload observation report document.
     */
    public function saveEvaluation(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb();

        $request->validate([
            'evaluation_result' => 'required|in:accepted,reserve,rejected',
            'evaluation_notes' => 'nullable|string|max:1000',
            'evaluation_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data = [
            'evaluation_result' => $request->evaluation_result,
            'evaluation_notes' => $request->evaluation_notes,
            'evaluated_at' => now(),
            'evaluated_by' => Auth::id(),
            'status' => $request->evaluation_result,
        ];

        if ($request->hasFile('evaluation_document')) {
            $docPath = $spmbService->storeApplicantDocument(
                $request->file('evaluation_document'),
                'HASIL_EVALUASI',
                $applicant->registration_number,
                'sd-namira'
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

        $applicant->update([
            'paid_admission_amount' => $request->paid_admission_amount,
            'admission_payment_status' => $request->admission_payment_status,
            'admission_payment_verified_at' => now(),
            'admission_payment_verified_by' => Auth::id(),
            'status' => $status,
        ]);

        $msg = $request->admission_payment_status === 'paid' ? 'Pelunasan 100% Berhasil' : 'Pembayaran Termin 1 (60%) Berhasil';

        return back()->with('success', "Status daftar ulang {$applicant->full_name} berhasil diperbarui: {$msg}.");
    }

    /**
     * Enroll applicant to active student in Academic module.
     */
    public function enrollStudent(SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $this->authorizeSpmb();

        if (!in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid'])) {
            return back()->withErrors(['error' => 'Hanya calon siswa yang telah diterima atau telah daftar ulang yang dapat dipindahkan ke Data Siswa Aktif.']);
        }

        $student = $spmbService->enrollToAcademicStudent($applicant, Auth::user());

        return back()->with('success', "Calon siswa {$applicant->full_name} berhasil resmi dipindahkan menjadi Siswa Aktif SD Namira dengan NIS: {$student->nis}.");
    }

    /**
     * SPMB Settings and Panitia Management.
     */
    public function settings(Request $request)
    {
        $this->authorizeSpmb();

        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $sdUnit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SD Namira',
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

        // List teachers in SD Namira
        $teachers = Teacher::with('user')
            ->where('unit_id', $sdUnit->id)
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

        return Inertia::render('SPMB/Admin/Settings', [
            'setting' => $setting,
            'unit' => $sdUnit,
            'teachers' => $teachers,
            'assignedPanitia' => $assignedPanitia,
        ]);
    }

    /**
     * Update SPMB Settings.
     */
    public function updateSettings(Request $request, SpmbService $spmbService)
    {
        $this->authorizeSpmb();

        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();
        $setting = SpmbSetting::where('unit_id', $sdUnit->id)->firstOrFail();

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
            'qris_image' => 'nullable|file|image|max:3072',
        ]);

        $data = $request->only([
            'name', 'academic_year', 'quota', 'registration_fee',
            'admission_fee_total', 'min_down_payment_percentage',
            'bank_name', 'bank_account_number', 'bank_account_holder',
            'contact_whatsapp', 'notes', 'is_active'
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
        $this->authorizeSpmb();

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();
        $user = User::findOrFail($request->user_id);

        $role = Role::firstOrCreate(['name' => 'panitia_spmb', 'guard_name' => 'web']);

        if (!$user->hasRole('panitia_spmb')) {
            // Assign with team_id if teams are enabled
            setPermissionsTeamId($sdUnit->id);
            $user->assignRole('panitia_spmb');
        }

        return back()->with('success', "Ustadz/Ustadzah {$user->name} berhasil ditugaskan sebagai Panitia SPMB SD Namira.");
    }

    /**
     * Remove panitia_spmb role from a user.
     */
    public function removePanitia(Request $request, User $user)
    {
        $this->authorizeSpmb();

        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();
        setPermissionsTeamId($sdUnit->id);
        
        $user->removeRole('panitia_spmb');

        return back()->with('success', "Penugasan Panitia SPMB untuk {$user->name} berhasil dicabut.");
    }
}
