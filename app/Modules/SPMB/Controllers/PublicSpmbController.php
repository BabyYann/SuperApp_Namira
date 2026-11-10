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
            if ($setting) {
                if (!$setting->is_active) {
                    return true;
                }
                if ($setting->start_date && now()->startOfDay()->lt($setting->start_date)) {
                    return true;
                }
                if ($setting->end_date && now()->endOfDay()->gt($setting->end_date->endOfDay())) {
                    return true;
                }
                return false;
            }
            return false;
        }

        $anyActive = SpmbSetting::where('is_active', true)->exists();
        return !$anyActive;
    }

    /**
     * Resolve SD Namira Kraksaan unit (code = 'SD').
     */
    protected function resolveSdKraksaanUnit(): Unit
    {
        $unit = Unit::where('code', 'SD')->first();
        if (!$unit) {
            $unit = Unit::where('name', 'like', '%SD%')
                ->where('code', '!=', 'SD-02')
                ->where('name', 'not like', '%KOTA%')
                ->firstOrFail();
        }
        return $unit;
    }

    /**
     * Resolve or create SD Namira Kota Probolinggo unit (code = 'SD-02').
     */
    protected function resolveSdKotaUnit(): Unit
    {
        $unit = Unit::where('code', 'SD-02')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%SD%')
                  ->where('name', 'like', '%KOTA%');
            })
            ->first();

        if (!$unit) {
            $unit = Unit::create([
                'name' => 'SD NAMIRA KOTA PROBOLINGGO',
                'code' => 'SD-02',
                'category' => 'formal',
                'level' => 'SD',
            ]);
        }

        return $unit;
    }

    /**
     * Display public landing / portal for choosing unit.
     */
    public function index()
    {
        $sdUnit = Unit::where('code', 'SD')->first()
            ?? Unit::where('name', 'like', '%SD%')->where('code', '!=', 'SD-02')->first();
        $sdSetting = null;
        if ($sdUnit) {
            $sdSetting = SpmbSetting::firstOrCreate(
                ['unit_id' => $sdUnit->id],
                [
                    'name' => 'Pendaftaran Murid Baru SD Namira Kraksaan',
                    'academic_year' => '2027/2028',
                    'quota' => 60,
                    'registration_fee' => 250000,
                    'bank_name' => 'Bank Jatim',
                    'bank_account_number' => null,
                    'bank_account_holder' => 'SD NAMIRA PENDAFTARAN',
                    'min_down_payment_percentage' => 60,
                    'is_active' => true,
                    'contact_whatsapp' => '082332922521',
                ]
            );
            $sdUpdates = [];
            if ($sdSetting->academic_year === '2026/2027') {
                $sdUpdates['academic_year'] = '2027/2028';
            }
            if ($sdSetting->bank_account_number === '0291008899') {
                $sdUpdates['bank_account_number'] = null;
            }
            if (!empty($sdUpdates)) {
                $sdSetting->update($sdUpdates);
            }
        }

        // Ensure SD Namira Kota Probolinggo (SD-02) unit & setting also exist
        $sdKotaUnit = $this->resolveSdKotaUnit();
        $sdKotaSetting = SpmbSetting::firstOrCreate(
            ['unit_id' => $sdKotaUnit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SD Namira Kota Probolinggo',
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'SD NAMIRA KOTA PROBOLINGGO',
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        if ($sdKotaSetting->academic_year === '2026/2027') {
            $sdKotaSetting->update(['academic_year' => '2027/2028']);
        }

        $smpUnit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first();
        $smpSetting = null;
        if ($smpUnit) {
            $smpSetting = SpmbSetting::firstOrCreate(
                ['unit_id' => $smpUnit->id],
                [
                    'name' => 'Pendaftaran Jalur Inden SMP Namira',
                    'academic_year' => '2027/2028',
                    'quota' => 60,
                    'registration_fee' => 250000,
                    'bank_name' => 'Bank Jatim',
                    'bank_account_number' => '0291008899',
                    'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                    'admission_fee_total' => 8500000,
                    'min_down_payment_percentage' => 60,
                    'is_active' => true,
                    'contact_whatsapp' => '082332922521',
                ]
            );
            if ($smpSetting->academic_year === '2026/2027') {
                $smpSetting->update(['academic_year' => '2027/2028']);
            }
        }

        if ($this->isLocked()) {
            return Inertia::render('SPMB/Public/Locked', [
                'contactWhatsapp' => $sdSetting->contact_whatsapp ?? ($smpSetting->contact_whatsapp ?? '082332922521'),
                'academicYear' => $sdSetting->academic_year ?? ($smpSetting->academic_year ?? '2027/2028'),
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
     * Display the registration form for SD Namira Kraksaan.
     */
    public function registerSD()
    {
        $unit = $this->resolveSdKraksaanUnit();

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'name' => 'Pendaftaran Murid Baru SD Namira Kraksaan',
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'SD NAMIRA PENDAFTARAN',
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        $sdUpdates = [];
        if ($setting->academic_year === '2026/2027') {
            $sdUpdates['academic_year'] = '2027/2028';
        }
        if ($setting->bank_account_number === '0291008899') {
            $sdUpdates['bank_account_number'] = null;
        }
        if (!empty($sdUpdates)) {
            $setting->update($sdUpdates);
        }

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SD Namira Kraksaan saat ini belum dibuka atau sedang ditutup.');
        }

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();

        return Inertia::render('SPMB/Public/RegisterSD', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'isLockedForPublic' => false,
        ]);
    }

    /**
     * Store new applicant registration for SD Namira Kraksaan.
     */
    public function storeSD(Request $request, SpmbService $spmbService)
    {
        $unit = $this->resolveSdKraksaanUnit();

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran online SD Namira Kraksaan saat ini sedang ditutup sementara.');
        }

        // 1. Validate input
        $request->validate([
            // Data Calon Siswa
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
            'category' => 'nullable|string|in:internal_tk,internal,eksternal_umum,external_umum,external',
            'is_inden' => 'nullable|boolean',
            'previous_school' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string|max:1000',

            // Data Orang Tua
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
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            // Upload Bukti Bayar: Wajib pada fase awal
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            // Berkas Pendukung: Fleksibel / dapat dilengkapi setelah masuk portal
            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'full_name.required' => 'Nama lengkap calon murid wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi sebagai akun login.',
            'password.required' => 'Kata sandi akun wajib diisi minimal 6 karakter.',
            'address.required' => 'Alamat tempat tinggal wajib diisi.',
            'registration_payment_proof.required' => 'Bukti transfer/scan QRIS biaya pendaftaran wajib diunggah.',
            'registration_payment_proof.mimes' => 'Format bukti pembayaran harus berupa file JPG, PNG, WEBP, atau PDF.',
            'registration_payment_proof.max' => 'Ukuran file bukti pembayaran maksimal 10MB.',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        // Standardize clean phone number (handles 08..., 628..., +62 8..., and 8...)
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $request->full_name)));

        // 2. Check duplicate registration for the same child in SPMB SD (allows twins/siblings with different names)
        $existingApplicant = SpmbApplicant::where('unit_id', $unit->id)
            ->where('parent_phone', $cleanPhone)
            ->get()
            ->first(function ($app) use ($normalizedInputName) {
                $existingName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));
                return $existingName === $normalizedInputName;
            });

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SD Namira (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk masuk ke portal Anda."
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $cleanPhone, $spmbService) {
            // A. Generate Unique Registration Number
            $regNumber = $spmbService->generateRegistrationNumber($unit);

            // B. Store documents into neat structured folder: storage/app/public/spmb/sd-namira/{reg_number}/...
            $paymentProofPath = $spmbService->storeApplicantDocument(
                $request->file('registration_payment_proof'),
                'BUKTI_BAYAR_REGISTRASI',
                $regNumber,
                'sd-namira'
            );

            $photoPath = $request->hasFile('photo') ? $spmbService->storeApplicantDocument($request->file('photo'), 'PASFOTO', $regNumber, 'sd-namira') : null;
            $kkPath = $request->hasFile('family_card') ? $spmbService->storeApplicantDocument($request->file('family_card'), 'KK', $regNumber, 'sd-namira') : null;
            $aktaPath = $request->hasFile('birth_cert') ? $spmbService->storeApplicantDocument($request->file('birth_cert'), 'AKTA', $regNumber, 'sd-namira') : null;
            $ktpPath = $request->hasFile('parent_id_card') ? $spmbService->storeApplicantDocument($request->file('parent_id_card'), 'KTP_ORTU', $regNumber, 'sd-namira') : null;

            // C. Create / Resolve User account
            $parentName = $request->father_name ?: ($request->mother_name ?: $request->full_name);
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName, $unit->id);

            // Auto-match against the 72 Master Data Inden & Angket
            $indenMatch = $spmbService->matchSdIndenApplicant($cleanPhone, $request->full_name)
                ?? $spmbService->matchSdIndenApplicant($request->father_phone, null)
                ?? $spmbService->matchSdIndenApplicant($request->mother_phone, null);

            $previousSchool = $request->previous_school ?: ($indenMatch['previous_school'] ?? null);

            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : $request->category;
            if (!$category || $category === 'eksternal_umum') {
                if ($previousSchool && str_contains(strtolower($previousSchool), 'namira')) {
                    $category = 'internal_tk';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            // D. Create Applicant record
            $applicantData = [
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'category' => $category,
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
                'previous_school' => $previousSchool,
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
                $applicantData['is_inden'] = $indenMatch !== null;
            }

            $applicant = SpmbApplicant::create($applicantData);

            // E. Auto login the parent user
            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran SD Namira Kraksaan berhasil! Nomor Registrasi Ananda: {$regNumber}. Silakan lengkapi berkas pendukung (Pas Foto, KK, Akta, KTP) di bawah ini sambil menunggu verifikasi pembayaran oleh panitia.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Display the private registration form for SD Namira Kota Probolinggo (Unit SD-02 - Inden).
     */
    public function registerSDKota()
    {
        $unit = $this->resolveSdKotaUnit();

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SD Namira Kota Probolinggo',
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'SD NAMIRA KOTA PROBOLINGGO',
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        if ($setting->academic_year === '2026/2027') {
            $setting->update(['academic_year' => '2027/2028']);
        }

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SD Namira Kota Probolinggo saat ini sedang ditutup.');
        }

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();

        return Inertia::render('SPMB/Public/RegisterSDKota', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'isLockedForPublic' => false,
        ]);
    }

    /**
     * Store new applicant registration for SD Namira Kota Probolinggo (Unit SD-02 - Inden).
     */
    public function storeSDKota(Request $request, SpmbService $spmbService)
    {
        $unit = $this->resolveSdKotaUnit();

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran SD Namira Kota Probolinggo saat ini sedang ditutup.');
        }

        $request->validate([
            // Data Calon Siswa
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
            'category' => 'nullable|string|in:internal_tk,internal,eksternal_umum,external_umum,external',
            'previous_school' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string|max:1000',

            // Data Orang Tua
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
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            // Upload Bukti Pembayaran Inden
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            // Berkas Pendukung: Fleksibel menyusul di portal
            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'full_name.required' => 'Nama lengkap calon murid wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi sebagai akun login.',
            'password.required' => 'Kata sandi akun wajib diisi minimal 6 karakter.',
            'address.required' => 'Alamat tempat tinggal wajib diisi.',
            'registration_payment_proof.required' => 'Bukti pembayaran pendaftaran inden wajib diunggah.',
            'registration_payment_proof.mimes' => 'Format bukti pembayaran harus berupa file JPG, PNG, WEBP, atau PDF.',
            'registration_payment_proof.max' => 'Ukuran file bukti pembayaran maksimal 10MB.',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $request->full_name)));

        $existingApplicant = SpmbApplicant::where('unit_id', $unit->id)
            ->where('parent_phone', $cleanPhone)
            ->get()
            ->first(function ($app) use ($normalizedInputName) {
                $existingName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));
                return $existingName === $normalizedInputName;
            });

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SD Namira Kota Probolinggo (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk masuk ke portal Anda."
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $cleanPhone, $spmbService) {
            $regNumber = $spmbService->generateRegistrationNumber($unit);

            $paymentProofPath = $spmbService->storeApplicantDocument(
                $request->file('registration_payment_proof'),
                'BUKTI_BAYAR_REGISTRASI',
                $regNumber,
                'sd-kota'
            );

            $photoPath = $request->hasFile('photo') ? $spmbService->storeApplicantDocument($request->file('photo'), 'PASFOTO', $regNumber, 'sd-kota') : null;
            $kkPath = $request->hasFile('family_card') ? $spmbService->storeApplicantDocument($request->file('family_card'), 'KK', $regNumber, 'sd-kota') : null;
            $aktaPath = $request->hasFile('birth_cert') ? $spmbService->storeApplicantDocument($request->file('birth_cert'), 'AKTA', $regNumber, 'sd-kota') : null;
            $ktpPath = $request->hasFile('parent_id_card') ? $spmbService->storeApplicantDocument($request->file('parent_id_card'), 'KTP_ORTU', $regNumber, 'sd-kota') : null;

            $parentName = $request->father_name ?: ($request->mother_name ?: $request->full_name);
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName, $unit->id);

            $previousSchool = $request->previous_school;
            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : $request->category;
            if (!$category || $category === 'eksternal_umum') {
                if ($previousSchool && str_contains(strtolower($previousSchool), 'namira')) {
                    $category = 'internal_tk';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            $applicantData = [
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'category' => $category,
                'status' => 'submitted',

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
                'previous_school' => $previousSchool,
                'special_notes' => $request->special_notes,

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
                'city' => $request->city ?: 'Kota Probolinggo',
                'postal_code' => $request->postal_code,

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

            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran Inden SD Namira Kota Probolinggo berhasil! Nomor Registrasi Ananda: {$regNumber}. Silakan lengkapi berkas pendukung (Pas Foto, KK, Akta, KTP) di bawah ini.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Display the registration form for SMP Namira.
     */
    public function registerSMP()
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->firstOrFail();

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SMP Namira',
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => '0291008899',
                'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                'admission_fee_total' => 8500000,
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        if ($setting->academic_year === '2026/2027') {
            $setting->update(['academic_year' => '2027/2028']);
        }

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SMP Namira saat ini belum dibuka atau sedang ditutup.');
        }

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

        $setting = SpmbSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'name' => 'Pendaftaran Jalur Inden SMP Namira',
                'academic_year' => '2027/2028',
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => '0291008899',
                'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                'admission_fee_total' => 8500000,
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ]
        );
        if ($setting->academic_year === '2026/2027') {
            $setting->update(['academic_year' => '2027/2028']);
        }

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
            'category' => 'nullable|string|in:internal_sd,internal_tk,internal,eksternal_umum,external_umum,external',
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
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            // Berkas Pendukung: Bersifat opsional / tidak wajib pada fase awal
            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
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
            'registration_payment_proof.mimes' => 'Format bukti pembayaran harus berupa file JPG, PNG, WEBP, atau PDF.',
            'registration_payment_proof.max' => 'Ukuran file bukti pembayaran maksimal 10MB.',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        // Standardize clean phone number
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $request->full_name)));

        // Check duplicate registration for the same child in SMP unit
        $existingApplicant = SpmbApplicant::where('unit_id', $unit->id)
            ->where('parent_phone', $cleanPhone)
            ->get()
            ->first(function ($app) use ($normalizedInputName) {
                $existingName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));
                return $existingName === $normalizedInputName;
            });

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SMP Namira (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk melihat dashboard Anda."
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
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName, $unit->id);

            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : $request->category;
            if (!$category || $category === 'eksternal_umum') {
                if ($request->filled('previous_school') && str_contains(strtolower($request->previous_school), 'namira')) {
                    $category = 'internal_sd';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            // D. Create Applicant record
            $applicantData = [
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'category' => $category,
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

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran SMP Namira berhasil! Nomor Registrasi Ananda: {$regNumber}. Berkas bukti pembayaran Anda sedang menunggu verifikasi panitia.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Read-only encrypted-hash-verified live monitor endpoint for SPMB applicants.
     * Returns 404 if token is missing or invalid; never exposes credentials or raw keys in code.
     */
    public function liveMonitor(Request $request, SpmbService $spmbService)
    {
        if (env('SPMB_MONITOR_ENABLED', true) === false) {
            abort(404);
        }

        $providedToken = trim((string) ($request->header('X-Namira-Sync-Key') ?: $request->query('sync_key', '')));
        $expectedHash = (string) env(
            'SPMB_MONITOR_KEY_HASH',
            'e8a698021e32029d342f46ebfe08917bab2fcb56dd3182e1fba2278b03a99584'
        );

        if ($providedToken === '' || !hash_equals($expectedHash, hash('sha256', $providedToken))) {
            abort(404);
        }

        $query = SpmbApplicant::with('unit:id,name,code')->orderBy('id', 'desc');

        if ($request->filled('unit_id')) {
            $query->where('unit_id', (int) $request->query('unit_id'));
        } elseif ($request->filled('unit')) {
            $u = strtolower(trim((string) $request->query('unit')));
            if ($u === 'sd') {
                $unit = $this->resolveSdKraksaanUnit();
                $query->where('unit_id', $unit->id);
            } elseif ($u === 'sd-kota' || $u === 'sdk') {
                $unit = $this->resolveSdKotaUnit();
                $query->where('unit_id', $unit->id);
            } elseif ($u === 'smp') {
                $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first();
                if ($unit) {
                    $query->where('unit_id', $unit->id);
                }
            }
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('registration_number', 'like', "%{$s}%")
                  ->orWhere('full_name', 'like', "%{$s}%")
                  ->orWhere('nickname', 'like', "%{$s}%")
                  ->orWhere('parent_phone', 'like', "%{$s}%")
                  ->orWhere('father_name', 'like', "%{$s}%")
                  ->orWhere('mother_name', 'like', "%{$s}%")
                  ->orWhere('previous_school', 'like', "%{$s}%")
                  ->orWhere('nik', 'like', "%{$s}%");
            });
        }

        $limit = min(max((int) $request->query('limit', 100), 1), 300);
        $totalMatching = (clone $query)->count();

        $applicants = $query->limit($limit)->get()->map(function (SpmbApplicant $a) {
            return [
                'id' => $a->id,
                'registration_number' => $a->registration_number,
                'unit_id' => $a->unit_id,
                'unit_name' => $a->unit?->name,
                'unit_code' => $a->unit?->code,
                'full_name' => $a->full_name,
                'nickname' => $a->nickname,
                'gender' => $a->gender,
                'birth_place' => $a->birth_place,
                'birth_date' => $a->birth_date ? $a->birth_date->format('Y-m-d') : null,
                'nik' => $a->nik,
                'nisn' => $a->nisn,
                'category' => $a->category,
                'category_label' => $a->category_label,
                'is_inden' => (bool) $a->is_inden,
                'previous_school' => $a->previous_school,
                'father_name' => $a->father_name,
                'mother_name' => $a->mother_name,
                'parent_phone' => $a->parent_phone,
                'address' => $a->address,
                'city' => $a->city,
                'status' => $a->status,
                'status_label' => $a->status_label,
                'has_registration_proof' => !empty($a->registration_payment_proof),
                'registration_payment_verified_at' => $a->registration_payment_verified_at?->toIso8601String(),
                'has_photo' => !empty($a->photo_path),
                'has_family_card' => !empty($a->family_card_path),
                'has_birth_cert' => !empty($a->birth_cert_path),
                'has_parent_id_card' => !empty($a->parent_id_card_path),
                'observation_date' => $a->observation_date ? $a->observation_date->format('Y-m-d') : null,
                'observation_time' => $a->observation_time,
                'psychotest_date' => $a->psychotest_date ? $a->psychotest_date->format('Y-m-d') : null,
                'psychotest_time' => $a->psychotest_time,
                'evaluation_result' => $a->evaluation_result,
                'admission_payment_status' => $a->admission_payment_status,
                'paid_admission_amount' => $a->paid_admission_amount,
                'created_at' => $a->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'updated_at' => $a->updated_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ];
        });

        $unitSummary = SpmbApplicant::select('unit_id', DB::raw('count(*) as total'))
            ->groupBy('unit_id')
            ->with('unit:id,name,code')
            ->get()
            ->map(fn ($row) => [
                'unit_id' => $row->unit_id,
                'unit_name' => $row->unit?->name,
                'unit_code' => $row->unit?->code,
                'total' => (int) $row->total,
            ]);

        return response()->json([
            'ok' => true,
            'server_time_wib' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'unit_summary' => $unitSummary,
            'total_matching' => $totalMatching,
            'applicants' => $applicants,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
