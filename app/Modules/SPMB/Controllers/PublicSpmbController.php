<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PublicSpmbController extends Controller
{
    /**
     * Determine if SPMB public portal is locked for the current request.
     */
    protected function isLocked(?Unit $unit = null): bool
    {
        // If an authorized admin/panitia wants to preview the live portal while logged in, allow them
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'panitia_spmb', 'kepala_sekolah'])) {
                if (request()->has('locked_view')) {
                    return true;
                }
                return false;
            }
        }

        if ($unit) {
            $setting = SpmbSetting::where('unit_id', $unit->id)->first();
            if ($setting && $setting->is_active) {
                if ($setting->start_date && now()->startOfDay()->lt($setting->start_date)) {
                    return true;
                }
                if ($setting->end_date && now()->endOfDay()->gt($setting->end_date->endOfDay())) {
                    return true;
                }
                return false;
            }
            return true;
        }

        $anyActive = SpmbSetting::where('is_active', true)->exists();
        return !$anyActive;
    }

    /**
     * Display public landing / portal for choosing unit.
     */
    public function index()
    {
        $sdUnit = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
        $sdSetting = null;
        if ($sdUnit) {
            $sdSetting = SpmbSetting::where('unit_id', $sdUnit->id)->first();
        }

        $smpUnit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first();
        $smpSetting = null;
        if ($smpUnit) {
            $smpSetting = SpmbSetting::where('unit_id', $smpUnit->id)->first();
        }

        if ($this->isLocked()) {
            return Inertia::render('SPMB/Public/Locked', [
                'contactWhatsapp' => $sdSetting->contact_whatsapp ?? ($smpSetting->contact_whatsapp ?? '082332922521'),
                'academicYear' => $sdSetting->academic_year ?? ($smpSetting->academic_year ?? '2026/2027'),
            ]);
        }

        $units = Unit::select('id', 'name', 'code')->get();

        return Inertia::render('SPMB/Public/Index', [
            'units' => $units,
            'sdUnit' => $sdUnit,
            'sdSetting' => $sdSetting,
            'smpUnit' => $smpUnit,
            'smpSetting' => $smpSetting,
            'isLockedForPublic' => false,
        ]);
    }

    /**
     * Display the registration form for SD Namira.
     */
    public function registerSD()
    {
        $unit = Unit::where('id', 3)->orWhere('code', 'SD')->orWhere('name', 'like', '%SD%')->firstOrFail();

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index');
        }
        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SD Namira',
                'academic_year' => '2026/2027',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => '0291008899',
                'bank_account_holder' => 'YAYASAN NAMIRA PROBOLINGGO',
                'min_down_payment_percentage' => 60,
                'is_active' => false,
                'contact_whatsapp' => '082332922521',
            ]
        );

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();

        return Inertia::render('SPMB/Public/RegisterSD', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'isLockedForPublic' => true,
        ]);
    }

    /**
     * Store new applicant registration.
     */
    public function storeSD(Request $request, SpmbService $spmbService)
    {
        if ($this->isLocked()) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran online saat ini sedang ditutup sementara untuk penyesuaian sistem.');
        }

        $unit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();

        // 1. Validate input
        $request->validate([
            // Data Calon Siswa
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'nik' => 'nullable|string|size:16',
            'nisn' => 'nullable|string|max:20',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer',
            'total_siblings' => 'nullable|integer',
            'category' => 'required|in:internal_tk,eksternal_umum',
            'previous_school' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string|max:1000',

            // Data Orang Tua
            'father_name' => 'required|string|max:255',
            'father_nik' => 'nullable|string|size:16',
            'father_phone' => 'nullable|string|max:25',
            'father_job' => 'nullable|string|max:100',
            'father_education' => 'nullable|string|max:50',

            'mother_name' => 'required|string|max:255',
            'mother_nik' => 'nullable|string|size:16',
            'mother_phone' => 'nullable|string|max:25',
            'mother_job' => 'nullable|string|max:100',
            'mother_education' => 'nullable|string|max:50',

            'parent_phone' => 'required|string|min:9|max:25',
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            // Upload Berkas
            'photo' => 'required|file|image|max:10240', // Max 10MB
            'family_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'birth_cert' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'parent_id_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'photo.required' => 'Pas foto anak berlatar merah wajib diunggah.',
            'family_card.required' => 'Kartu Keluarga (KK) wajib diunggah.',
            'birth_cert.required' => 'Akta Kelahiran wajib diunggah.',
            'parent_id_card.required' => 'KTP Orang Tua wajib diunggah.',
            'registration_payment_proof.required' => 'Bukti transfer/scan QRIS biaya pendaftaran wajib diunggah.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi sebagai akun login.',
            'password.required' => 'Kata sandi akun wajib diisi minimal 6 karakter.',
        ]);

        // Standardize clean phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$request->parent_phone);
        if (str_starts_with($cleanPhone, '62')) {
            $cleanPhone = '0' . substr($cleanPhone, 2);
        }

        // 2. Check duplicate phone number in SPMB
        $existingApplicant = SpmbApplicant::where('unit_id', $unit->id)
            ->where('parent_phone', $cleanPhone)
            ->first();

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Nomor WhatsApp {$cleanPhone} sudah terdaftar dengan Nomor Registrasi: {$existingApplicant->registration_number}. Silakan gunakan menu Login untuk melihat dashboard Anda."
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $cleanPhone, $spmbService) {
            // A. Generate Unique Registration Number
            $regNumber = $spmbService->generateRegistrationNumber($unit);

            // B. Store documents into neat structured folder
            // storage/app/public/spmb/sd-namira/{reg_number}/...
            $photoPath = $spmbService->storeApplicantDocument($request->file('photo'), 'PASFOTO', $regNumber, 'sd-namira');
            $kkPath = $spmbService->storeApplicantDocument($request->file('family_card'), 'KK', $regNumber, 'sd-namira');
            $aktaPath = $spmbService->storeApplicantDocument($request->file('birth_cert'), 'AKTA', $regNumber, 'sd-namira');
            $ktpPath = $spmbService->storeApplicantDocument($request->file('parent_id_card'), 'KTP_ORTU', $regNumber, 'sd-namira');
            $paymentProofPath = $spmbService->storeApplicantDocument($request->file('registration_payment_proof'), 'BUKTI_BAYAR_REGISTRASI', $regNumber, 'sd-namira');

            // C. Create / Resolve User account
            $parentName = $request->father_name ?: $request->mother_name;
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName);

            // D. Create Applicant record
            $applicant = SpmbApplicant::create([
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'category' => $request->category,
                'status' => 'submitted',

                // Calon Siswa
                'full_name' => $request->full_name,
                'nickname' => $request->nickname,
                'gender' => $request->gender,
                'nik' => $request->nik,
                'nisn' => $request->nisn,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion ?: 'Islam',
                'child_order' => $request->child_order,
                'total_siblings' => $request->total_siblings,
                'previous_school' => $request->previous_school,
                'special_notes' => $request->special_notes,

                // Orang Tua
                'father_name' => $request->father_name,
                'father_nik' => $request->father_nik,
                'father_phone' => $request->father_phone,
                'father_job' => $request->father_job,
                'father_education' => $request->father_education,

                'mother_name' => $request->mother_name,
                'mother_nik' => $request->mother_nik,
                'mother_phone' => $request->mother_phone,
                'mother_job' => $request->mother_job,
                'mother_education' => $request->mother_education,

                'parent_phone' => $cleanPhone,
                'address' => $request->address,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'village' => $request->village,
                'district' => $request->district,
                'city' => $request->city,
                'postal_code' => $request->postal_code,

                // Berkas
                'photo_path' => $photoPath,
                'family_card_path' => $kkPath,
                'birth_cert_path' => $aktaPath,
                'parent_id_card_path' => $ktpPath,
                'registration_payment_proof' => $paymentProofPath,
            ]);

            // E. Auto login the parent user
            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard')->with('success', "Pendaftaran berhasil! Nomor Registrasi Ananda: {$regNumber}. Berkas Anda sedang menunggu verifikasi panitia.");
        });
    }

    /**
     * Display the registration form for SMP Namira.
     */
    public function registerSMP()
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->firstOrFail();

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SMP Namira saat ini belum dibuka atau sedang ditutup.');
        }

        $setting = SpmbSetting::where('unit_id', $unit->id)->first();

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();

        return Inertia::render('SPMB/Public/RegisterSMP', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'isLockedForPublic' => false,
        ]);
    }

    /**
     * Store new applicant registration for SMP Namira.
     */
    public function storeSMP(Request $request, SpmbService $spmbService)
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->firstOrFail();

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran SMP saat ini belum dibuka.');
        }

        // 1. Validate input
        $request->validate([
            // Data Calon Siswa
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'nik' => 'nullable|string|size:16',
            'nisn' => 'nullable|string|max:20',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer',
            'total_siblings' => 'nullable|integer',
            'previous_school' => 'required|string|max:255',
            'special_notes' => 'nullable|string|max:1000',

            // Data Orang Tua
            'father_name' => 'nullable|string|max:255',
            'father_nik' => 'nullable|string|size:16',
            'father_phone' => 'nullable|string|max:25',
            'father_job' => 'nullable|string|max:100',
            'father_education' => 'nullable|string|max:50',

            'mother_name' => 'nullable|string|max:255',
            'mother_nik' => 'nullable|string|size:16',
            'mother_phone' => 'nullable|string|max:25',
            'mother_job' => 'nullable|string|max:100',
            'mother_education' => 'nullable|string|max:50',

            'parent_phone' => 'required|string|min:9|max:25',
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            // Upload Bukti Bayar: Wajib pada fase awal
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',

            // Berkas Pendukung: Bersifat opsional / tidak wajib pada fase awal
            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'full_name.required' => 'Nama lengkap calon santri wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'previous_school.required' => 'Asal Sekolah Dasar (SD/MI) wajib diisi.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi sebagai identitas akun.',
            'password.required' => 'Kata sandi akun minimal 6 karakter.',
            'address.required' => 'Alamat tempat tinggal wajib diisi.',
            'registration_payment_proof.required' => 'Bukti transfer/pembayaran pendaftaran wajib diunggah.',
            'registration_payment_proof.mimes' => 'Format bukti pembayaran harus berupa file JPG, PNG, atau PDF.',
            'registration_payment_proof.max' => 'Ukuran file bukti pembayaran maksimal 10MB.',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        // Standardize clean phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$request->parent_phone);
        if (str_starts_with($cleanPhone, '62')) {
            $cleanPhone = '0' . substr($cleanPhone, 2);
        }

        // Check duplicate phone number in SMP unit
        $existingApplicant = SpmbApplicant::where('unit_id', $unit->id)
            ->where('parent_phone', $cleanPhone)
            ->first();

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Nomor WhatsApp {$cleanPhone} sudah terdaftar di SMP Namira dengan Nomor Registrasi: {$existingApplicant->registration_number}. Silakan gunakan menu Login untuk melihat dashboard Anda."
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $cleanPhone, $spmbService) {
            // A. Generate Unique Registration Number
            $regNumber = $spmbService->generateRegistrationNumber($unit);

            // B. Store documents into neat structured folder: spmb/smp-namira/{reg_number}/...
            $paymentProofPath = $spmbService->storeApplicantDocument(
                $request->file('registration_payment_proof'),
                'BUKTI_BAYAR_REGISTRASI',
                $regNumber,
                'smp-namira'
            );

            $photoPath = $request->hasFile('photo') ? $spmbService->storeApplicantDocument($request->file('photo'), 'PASFOTO', $regNumber, 'smp-namira') : null;
            $kkPath = $request->hasFile('family_card') ? $spmbService->storeApplicantDocument($request->file('family_card'), 'KK', $regNumber, 'smp-namira') : null;
            $aktaPath = $request->hasFile('birth_cert') ? $spmbService->storeApplicantDocument($request->file('birth_cert'), 'AKTA', $regNumber, 'smp-namira') : null;
            $ktpPath = $request->hasFile('parent_id_card') ? $spmbService->storeApplicantDocument($request->file('parent_id_card'), 'KTP_ORTU', $regNumber, 'smp-namira') : null;

            // C. Create / Resolve User account
            $parentName = $request->father_name ?: ($request->mother_name ?: $request->full_name);
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName);

            // D. Create Applicant record
            $applicantData = [
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'category' => $request->category ?: 'eksternal_umum',
                'status' => 'submitted',

                // Calon Siswa
                'full_name' => $request->full_name,
                'nickname' => $request->nickname,
                'gender' => $request->gender,
                'nik' => $request->nik,
                'nisn' => $request->nisn,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion ?: 'Islam',
                'child_order' => $request->child_order,
                'total_siblings' => $request->total_siblings,
                'previous_school' => $request->previous_school,
                'special_notes' => $request->special_notes,

                // Orang Tua
                'father_name' => $request->father_name,
                'father_nik' => $request->father_nik,
                'father_phone' => $request->father_phone,
                'father_job' => $request->father_job,
                'father_education' => $request->father_education,

                'mother_name' => $request->mother_name,
                'mother_nik' => $request->mother_nik,
                'mother_phone' => $request->mother_phone,
                'mother_job' => $request->mother_job,
                'mother_education' => $request->mother_education,

                'parent_phone' => $cleanPhone,
                'address' => $request->address,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'village' => $request->village,
                'district' => $request->district,
                'city' => $request->city,
                'postal_code' => $request->postal_code,

                // Berkas
                'photo_path' => $photoPath,
                'family_card_path' => $kkPath,
                'birth_cert_path' => $aktaPath,
                'parent_id_card_path' => $ktpPath,
                'registration_payment_proof' => $paymentProofPath,
            ];

            if (Schema::hasColumn('spmb_applicants', 'is_inden')) {
                $applicantData['is_inden'] = true;
            }

            $applicant = SpmbApplicant::create($applicantData);

            // E. Auto login the parent user
            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard')
                ->with('success', "Pendaftaran SMP Namira berhasil! Nomor Registrasi Ananda: {$regNumber}. Berkas bukti pembayaran Anda sedang menunggu verifikasi panitia.")
                ->with('registration_number', $regNumber);
        });
    }
}
