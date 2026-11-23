<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PublicSpmbController extends Controller
{
    /**
     * Check whether the currently logged-in user is an authorized admin/panitia allowed to preview locked units.
     */
    protected function isAdminPreviewUser(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return Auth::user()->hasAnyRole([
            'super_admin_yayasan',
            'admin_yayasan',
            'admin_unit',
            'panitia_spmb',
            'kepala_sekolah',
        ]);
    }

    /**
     * Determine whether a unit (or overall portal) is locked for regular public visitors (ignoring admin bypass).
     */
    protected function isLockedForPublicVisitor(?Unit $unit = null): bool
    {
        if ($unit) {
            $setting = SpmbSetting::where('unit_id', $unit->id)->first();
            if (! $setting) {
                return app(SpmbService::class)->isTkOrKbUnit($unit);
            }
            if (! $setting->is_active) {
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

        return ! SpmbSetting::where('is_active', true)->exists();
    }

    /**
     * Determine if SPMB public portal or specific unit is locked for the current request.
     */
    protected function isLocked(?Unit $unit = null): bool
    {
        if ($this->isAdminPreviewUser()) {
            if (request()->has('locked_view')) {
                return true;
            }

            return false;
        }

        return $this->isLockedForPublicVisitor($unit);
    }

    /**
     * Resolve SD Namira Kraksaan unit (code = 'SD').
     */
    protected function resolveSdKraksaanUnit(): Unit
    {
        $unit = Unit::where('code', 'SD')->first();
        if (! $unit) {
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

        if (! $unit) {
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
     * Resolve SMP Namira unit.
     */
    protected function resolveSmpUnit(): ?Unit
    {
        return Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first();
    }

    /**
     * Resolve all TK & KB Namira units ordered cleanly (TK Kraksaan, TK Dringu, KB Kraksaan, KB Dringu).
     */
    protected function resolveTkKbUnits(): Collection
    {
        $units = Unit::where(function ($q) {
            $q->whereIn('code', ['TK-KRA', 'TK-DRI', 'KB-KRA', 'KB-DRI', 'TK', 'KB'])
                ->orWhere('category', 'TK');
        })->get();

        $order = [
            'TK-KRA' => 1,
            'TK-DRI' => 2,
            'KB-KRA' => 3,
            'KB-DRI' => 4,
            'TK' => 5,
            'KB' => 6,
        ];

        return $units->sortBy(fn (Unit $u) => $order[strtoupper($u->code ?? '')] ?? (100 + $u->id))->values();
    }

    /**
     * Ensure an SpmbSetting exists for a given unit with sane defaults.
     * TK/KB units default to is_active = false so they remain locked until explicitly unlocked by Admin.
     */
    protected function ensureUnitSetting(Unit $unit): SpmbSetting
    {
        $spmbService = app(SpmbService::class);
        $code = strtoupper($unit->code ?? '');
        $defaultFutureYears = ['2028/2029', '2029/2030', '2030/2031'];

        if ($spmbService->isSmpUnit($unit)) {
            $defaults = [
                'name' => 'Pendaftaran Jalur Inden SMP Namira',
                'academic_year' => '2027/2028',
                'allow_future_inden' => true,
                'inden_academic_years' => $defaultFutureYears,
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => '0291008899',
                'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                'admission_fee_total' => 8500000,
                'min_down_payment_percentage' => 100,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ];
        } elseif ($spmbService->isSdKotaUnit($unit)) {
            $defaults = [
                'name' => 'Pendaftaran Jalur Inden SD Namira Kota Probolinggo',
                'academic_year' => '2027/2028',
                'allow_future_inden' => true,
                'inden_academic_years' => $defaultFutureYears,
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'SD NAMIRA KOTA PROBOLINGGO',
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ];
        } elseif ($spmbService->isTkOrKbUnit($unit)) {
            $defaults = [
                'name' => 'Pendaftaran Murid Baru ' . $unit->name,
                'academic_year' => '2027/2028',
                'allow_future_inden' => true,
                'inden_academic_years' => $defaultFutureYears,
                'quota' => 40,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => strtoupper($unit->name),
                'admission_fee_total' => 4500000,
                'min_down_payment_percentage' => 60,
                'is_active' => false, // Locked for public until Admin unlocks it in Settings
                'contact_whatsapp' => '082332922521',
            ];
        } else {
            $defaults = [
                'name' => 'Pendaftaran Murid Baru SD Namira Kraksaan',
                'academic_year' => '2027/2028',
                'allow_future_inden' => true,
                'inden_academic_years' => $defaultFutureYears,
                'quota' => 60,
                'registration_fee' => 250000,
                'bank_name' => 'Bank Jatim',
                'bank_account_number' => null,
                'bank_account_holder' => 'SD NAMIRA PENDAFTARAN',
                'min_down_payment_percentage' => 60,
                'is_active' => true,
                'contact_whatsapp' => '082332922521',
            ];
        }

        $setting = SpmbSetting::firstOrCreate(['unit_id' => $unit->id], $defaults);

        $updates = [];
        if ($setting->academic_year === '2026/2027') {
            $updates['academic_year'] = '2027/2028';
        }
        if ($code === 'SD' && $setting->bank_account_number === '0291008899') {
            $updates['bank_account_number'] = null;
        }
        if (! empty($updates)) {
            $setting->update($updates);
        }

        return $setting;
    }

    /**
     * Resolve pre-selected academic year from query string (?ta=2028/2029 or ?ta=2028-2029).
     */
    protected function resolveRequestedAcademicYear(Request $request, SpmbSetting $setting): string
    {
        $raw = $request->query('ta') ?: $request->query('tahun_ajaran');
        $normalized = SpmbSetting::normalizeAcademicYear(is_string($raw) ? $raw : null);
        $allowed = $setting->getAllowedAcademicYears();

        if ($normalized && in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return $allowed[0] ?? ($setting->academic_year ?: '2027/2028');
    }

    /**
     * Normalize incoming academic_year input on POST before validation so both 2028/2029 and 2028-2029 work cleanly.
     */
    protected function prepareAcademicYearInput(Request $request): void
    {
        if ($request->filled('academic_year')) {
            $normalized = SpmbSetting::normalizeAcademicYear((string) $request->input('academic_year'));
            if ($normalized !== null) {
                $request->merge(['academic_year' => $normalized]);
            }
        }
    }

    /**
     * Check whether the same child (normalized full_name + parent_phone) is already registered in the same unit & academic year.
     * Allows siblings (different full_name), cross-unit registrations (e.g. TK now + SD Inden later), and re-registration after cancellation.
     */
    protected function findDuplicateApplicant(int $unitId, string $academicYear, string $cleanPhone, string $fullName): ?SpmbApplicant
    {
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', $fullName)));

        return SpmbApplicant::where('unit_id', $unitId)
            ->where('parent_phone', $cleanPhone)
            ->where(function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear)
                    ->orWhereNull('academic_year');
            })
            ->where('status', '!=', 'cancelled')
            ->get()
            ->first(function ($app) use ($normalizedInputName) {
                $existingName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $app->full_name)));

                return $existingName === $normalizedInputName;
            });
    }

    /**
     * Clean deep-link redirector for future-year Inden URLs:
     * /spmb/daftar/sd/inden/2028-2029 -> /spmb/daftar/sd?ta=2028/2029
     */
    public function redirectIndenLink(string $unitSlug, ?string $academicYearSlug = null)
    {
        $routeName = match ($unitSlug) {
            'sd' => 'spmb.register.sd',
            'sd-kota' => 'spmb.register.sd-kota',
            'smp' => 'spmb.register.smp',
            'tk' => 'spmb.register.tk',
            default => 'spmb.index',
        };

        $params = [];
        if ($academicYearSlug) {
            $normalized = SpmbSetting::normalizeAcademicYear($academicYearSlug);
            if ($normalized) {
                $params['ta'] = $normalized;
            }
        }

        return redirect()->route($routeName, $params);
    }

    /**
     * Display public landing / portal for choosing unit & target academic year.
     */
    public function index()
    {
        $sdUnit = Unit::where('code', 'SD')->first()
            ?? Unit::where('name', 'like', '%SD%')->where('code', '!=', 'SD-02')->first();
        $sdSetting = $sdUnit ? $this->ensureUnitSetting($sdUnit) : null;

        $sdKotaUnit = $this->resolveSdKotaUnit();
        $sdKotaSetting = $this->ensureUnitSetting($sdKotaUnit);

        $smpUnit = $this->resolveSmpUnit();
        $smpSetting = $smpUnit ? $this->ensureUnitSetting($smpUnit) : null;

        $tkUnitsCollection = $this->resolveTkKbUnits();
        $tkUnitsPayload = [];
        $tkUnlocked = false;
        $tkYearOptions = [];

        foreach ($tkUnitsCollection as $tkUnit) {
            $tkSetting = $this->ensureUnitSetting($tkUnit);
            $isOpenForPublic = ! $this->isLockedForPublicVisitor($tkUnit);
            if ($isOpenForPublic) {
                $tkUnlocked = true;
            }
            $opts = $tkSetting->getSelectableAcademicYears('Tahap Berjalan');
            if (empty($tkYearOptions)) {
                $tkYearOptions = $opts;
            }
            $tkUnitsPayload[] = [
                'id' => $tkUnit->id,
                'code' => $tkUnit->code,
                'name' => $tkUnit->name,
                'is_active' => $isOpenForPublic,
                'academic_year' => $tkSetting->academic_year,
                'academic_year_options' => $opts,
            ];
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
            'sdYearOptions' => $sdSetting ? $sdSetting->getSelectableAcademicYears('Gelombang Aktif', 'Jalur Umum & Konfirmasi Inden TA berjalan') : [],
            'sdKotaUnit' => $sdKotaUnit,
            'sdKotaSetting' => $sdKotaSetting,
            'sdKotaYearOptions' => $sdKotaSetting->getSelectableAcademicYears('Jalur Inden Aktif', 'Pendaftaran Jalur Inden SD Namira Kota Probolinggo'),
            'smpUnit' => $smpUnit,
            'smpSetting' => $smpSetting,
            'smpYearOptions' => $smpSetting ? $smpSetting->getSelectableAcademicYears('Jalur Inden Aktif', 'Pendaftaran Calon Santri SMP Namira TA berjalan') : [],
            'tkUnits' => $tkUnitsPayload,
            'tkUnlocked' => $tkUnlocked,
            'tkYearOptions' => $tkYearOptions,
            'canPreviewLockedUnits' => $this->isAdminPreviewUser(),
            'isLockedForPublic' => $this->isLockedForPublicVisitor(),
        ]);
    }

    /**
     * Display the registration form for SD Namira Kraksaan.
     */
    public function registerSD(Request $request)
    {
        $unit = $this->resolveSdKraksaanUnit();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SD Namira Kraksaan saat ini belum dibuka atau sedang ditutup.');
        }

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();
        $academicYearOptions = $setting->getSelectableAcademicYears('Gelombang Aktif', 'Gelombang Umum & Konfirmasi Inden Tahun Ajaran berjalan');
        $selectedAcademicYear = $this->resolveRequestedAcademicYear($request, $setting);

        return Inertia::render('SPMB/Public/RegisterSD', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'academicYearOptions' => $academicYearOptions,
            'selectedAcademicYear' => $selectedAcademicYear,
            'isLockedForPublic' => $this->isLockedForPublicVisitor($unit),
        ]);
    }

    /**
     * Store new applicant registration for SD Namira Kraksaan.
     */
    public function storeSD(Request $request, SpmbService $spmbService)
    {
        $unit = $this->resolveSdKraksaanUnit();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran online SD Namira Kraksaan saat ini sedang ditutup sementara.');
        }

        $this->prepareAcademicYearInput($request);
        $allowedYears = $setting->getAllowedAcademicYears();

        // 1. Validate input
        $request->validate([
            'academic_year' => ['nullable', 'string', Rule::in($allowedYears)],

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
            'academic_year.in' => 'Tahun ajaran pendaftaran yang dipilih tidak tersedia untuk SD Namira Kraksaan.',
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

        if (! $request->filled('father_name') && ! $request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $targetAcademicYear = $request->input('academic_year') ?: ($setting->academic_year ?: '2027/2028');
        $isFutureInden = $setting->isFutureIndenYear($targetAcademicYear);
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);

        // 2. Check duplicate registration for the same child in the same unit & target academic year
        $existingApplicant = $this->findDuplicateApplicant($unit->id, $targetAcademicYear, $cleanPhone, (string) $request->full_name);

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SD Namira Kraksaan TA {$targetAcademicYear} (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk masuk ke portal Anda.",
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $setting, $targetAcademicYear, $isFutureInden, $cleanPhone, $spmbService) {
            // A. Generate Unique Registration Number
            $regNumber = $spmbService->generateRegistrationNumber($unit, $targetAcademicYear, $setting->academic_year);

            // B. Store documents into neat structured folder
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

            // Auto-match against the 72 Master Data Inden & Angket ONLY for primary active TA
            $indenMatch = null;
            if (! $isFutureInden) {
                $indenMatch = $spmbService->matchSdIndenApplicant($cleanPhone, $request->full_name)
                    ?? $spmbService->matchSdIndenApplicant($request->father_phone, null)
                    ?? $spmbService->matchSdIndenApplicant($request->mother_phone, null);
            }

            $previousSchool = $request->previous_school ?: ($indenMatch['previous_school'] ?? null);

            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : $request->category;
            if (! $category || $category === 'eksternal_umum') {
                if ($previousSchool && str_contains(strtolower($previousSchool), 'namira')) {
                    $category = 'internal_tk';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            // D. Create Applicant record
            $applicant = SpmbApplicant::create([
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'academic_year' => $targetAcademicYear,
                'user_id' => $user->id,
                'category' => $category,
                'is_inden' => $isFutureInden ? true : ($indenMatch !== null),
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
                'city' => $request->city,
                'postal_code' => $request->postal_code,

                'photo_path' => $photoPath,
                'family_card_path' => $kkPath,
                'birth_cert_path' => $aktaPath,
                'parent_id_card_path' => $ktpPath,
                'registration_payment_proof' => $paymentProofPath,
            ]);

            // E. Auto login the parent user
            Auth::login($user, true);

            $jalurNote = $isFutureInden ? "Jalur Inden TA {$targetAcademicYear}" : "TA {$targetAcademicYear}";

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran SD Namira Kraksaan ({$jalurNote}) berhasil! Nomor Registrasi Ananda: {$regNumber}. Silakan lengkapi berkas pendukung (Pas Foto, KK, Akta, KTP) di bawah ini sambil menunggu verifikasi pembayaran oleh panitia.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Display the registration form for SD Namira Kota Probolinggo (Unit SD-02).
     */
    public function registerSDKota(Request $request)
    {
        $unit = $this->resolveSdKotaUnit();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SD Namira Kota Probolinggo saat ini sedang ditutup.');
        }

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();
        $academicYearOptions = $setting->getSelectableAcademicYears('Jalur Inden Aktif', 'Pendaftaran Inden SD Namira Kota Probolinggo TA berjalan');
        $selectedAcademicYear = $this->resolveRequestedAcademicYear($request, $setting);

        return Inertia::render('SPMB/Public/RegisterSDKota', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'academicYearOptions' => $academicYearOptions,
            'selectedAcademicYear' => $selectedAcademicYear,
            'isLockedForPublic' => $this->isLockedForPublicVisitor($unit),
        ]);
    }

    /**
     * Store new applicant registration for SD Namira Kota Probolinggo (Unit SD-02).
     */
    public function storeSDKota(Request $request, SpmbService $spmbService)
    {
        $unit = $this->resolveSdKotaUnit();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran SD Namira Kota Probolinggo saat ini sedang ditutup.');
        }

        $this->prepareAcademicYearInput($request);
        $allowedYears = $setting->getAllowedAcademicYears();

        $request->validate([
            'academic_year' => ['nullable', 'string', Rule::in($allowedYears)],

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

            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'academic_year.in' => 'Tahun ajaran pendaftaran yang dipilih tidak tersedia untuk SD Namira Kota Probolinggo.',
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

        if (! $request->filled('father_name') && ! $request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $targetAcademicYear = $request->input('academic_year') ?: ($setting->academic_year ?: '2027/2028');
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);

        $existingApplicant = $this->findDuplicateApplicant($unit->id, $targetAcademicYear, $cleanPhone, (string) $request->full_name);

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SD Namira Kota Probolinggo TA {$targetAcademicYear} (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk masuk ke portal Anda.",
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $setting, $targetAcademicYear, $cleanPhone, $spmbService) {
            $regNumber = $spmbService->generateRegistrationNumber($unit, $targetAcademicYear, $setting->academic_year);

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
            if (! $category || $category === 'eksternal_umum') {
                if ($previousSchool && str_contains(strtolower($previousSchool), 'namira')) {
                    $category = 'internal_tk';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            $applicant = SpmbApplicant::create([
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'academic_year' => $targetAcademicYear,
                'user_id' => $user->id,
                'category' => $category,
                'is_inden' => true,
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
            ]);

            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran Inden SD Namira Kota Probolinggo (TA {$targetAcademicYear}) berhasil! Nomor Registrasi Ananda: {$regNumber}. Silakan lengkapi berkas pendukung (Pas Foto, KK, Akta, KTP) di bawah ini.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Display the registration form for SMP Namira.
     */
    public function registerSMP(Request $request)
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->firstOrFail();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran SMP Namira saat ini belum dibuka atau sedang ditutup.');
        }

        $totalApplicants = SpmbApplicant::where('unit_id', $unit->id)->count();
        $academicYearOptions = $setting->getSelectableAcademicYears('Jalur Inden Aktif', 'Pendaftaran Jalur Inden SMP Namira TA berjalan');
        $selectedAcademicYear = $this->resolveRequestedAcademicYear($request, $setting);

        return Inertia::render('SPMB/Public/RegisterSMP', [
            'unit' => $unit,
            'setting' => $setting,
            'totalApplicants' => $totalApplicants,
            'academicYearOptions' => $academicYearOptions,
            'selectedAcademicYear' => $selectedAcademicYear,
            'isLockedForPublic' => $this->isLockedForPublicVisitor($unit),
        ]);
    }

    /**
     * Store new applicant registration for SMP Namira.
     */
    public function storeSMP(Request $request, SpmbService $spmbService)
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->firstOrFail();
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', 'Layanan pendaftaran SMP saat ini belum dibuka.');
        }

        $this->prepareAcademicYearInput($request);
        $allowedYears = $setting->getAllowedAcademicYears();

        // 1. Validate input
        $request->validate([
            'academic_year' => ['nullable', 'string', Rule::in($allowedYears)],

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

            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'academic_year.in' => 'Tahun ajaran pendaftaran yang dipilih tidak tersedia untuk SMP Namira.',
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

        if (! $request->filled('father_name') && ! $request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $targetAcademicYear = $request->input('academic_year') ?: ($setting->academic_year ?: '2027/2028');
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);

        $existingApplicant = $this->findDuplicateApplicant($unit->id, $targetAcademicYear, $cleanPhone, (string) $request->full_name);

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di SMP Namira TA {$targetAcademicYear} (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk melihat dashboard Anda.",
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $setting, $targetAcademicYear, $cleanPhone, $spmbService) {
            $regNumber = $spmbService->generateRegistrationNumber($unit, $targetAcademicYear, $setting->academic_year);

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

            $parentName = $request->father_name ?: ($request->mother_name ?: $request->full_name);
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName, $unit->id);

            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : $request->category;
            if (! $category || $category === 'eksternal_umum') {
                if ($request->filled('previous_school') && str_contains(strtolower($request->previous_school), 'namira')) {
                    $category = 'internal_sd';
                } else {
                    $category = 'eksternal_umum';
                }
            }

            $applicant = SpmbApplicant::create([
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'academic_year' => $targetAcademicYear,
                'user_id' => $user->id,
                'category' => $category,
                'is_inden' => true,
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
                'previous_school' => $request->previous_school,
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
                'city' => $request->city,
                'postal_code' => $request->postal_code,

                'photo_path' => $photoPath,
                'family_card_path' => $kkPath,
                'birth_cert_path' => $aktaPath,
                'parent_id_card_path' => $ktpPath,
                'registration_payment_proof' => $paymentProofPath,
            ]);

            Auth::login($user, true);

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran SMP Namira (TA {$targetAcademicYear}) berhasil! Nomor Registrasi Ananda: {$regNumber}. Berkas bukti pembayaran Anda sedang menunggu verifikasi panitia.")
                ->with('registration_number', $regNumber);
        });
    }

    /**
     * Display the registration form for TK & KB Namira (locked for public until unlocked by Admin in Settings).
     */
    public function registerTK(Request $request)
    {
        $tkUnits = $this->resolveTkKbUnits();
        if ($tkUnits->isEmpty()) {
            return redirect()->route('spmb.index')->with('info', 'Unit TK & KB Namira belum tersedia di sistem.');
        }

        $isAdmin = $this->isAdminPreviewUser() && ! $request->has('locked_view');
        $entries = [];

        foreach ($tkUnits as $tkUnit) {
            $setting = $this->ensureUnitSetting($tkUnit);
            $isOpenForPublic = ! $this->isLockedForPublicVisitor($tkUnit);
            if (! $isAdmin && ! $isOpenForPublic) {
                continue;
            }
            $entries[] = [
                'unit' => $tkUnit,
                'setting' => $setting,
                'is_open_for_public' => $isOpenForPublic,
                'academic_year_options' => $setting->getSelectableAcademicYears('Tahap Berjalan', "Pendaftaran {$tkUnit->name} Tahun Ajaran berjalan"),
            ];
        }

        if (empty($entries)) {
            return redirect()->route('spmb.index')->with('info', 'Pendaftaran online TK & KB Namira saat ini belum dibuka secara publik. Silakan hubungi panitia melalui WhatsApp untuk konsultasi.');
        }

        $requestedCode = strtoupper(trim((string) ($request->query('unit') ?: '')));
        $requestedId = $request->integer('unit_id');

        $activeEntry = collect($entries)->first(function ($entry) use ($requestedCode, $requestedId) {
            if ($requestedId > 0 && (int) $entry['unit']->id === $requestedId) {
                return true;
            }
            if ($requestedCode !== '' && strtoupper($entry['unit']->code ?? '') === $requestedCode) {
                return true;
            }

            return false;
        }) ?? $entries[0];

        $selectedAcademicYear = $this->resolveRequestedAcademicYear($request, $activeEntry['setting']);
        $totalApplicants = SpmbApplicant::where('unit_id', $activeEntry['unit']->id)->count();

        return Inertia::render('SPMB/Public/RegisterTK', [
            'unit' => $activeEntry['unit'],
            'setting' => $activeEntry['setting'],
            'tkUnits' => array_map(fn ($e) => [
                'id' => $e['unit']->id,
                'code' => $e['unit']->code,
                'name' => $e['unit']->name,
                'is_open_for_public' => $e['is_open_for_public'],
                'setting' => $e['setting'],
                'academic_year_options' => $e['academic_year_options'],
            ], $entries),
            'academicYearOptions' => $activeEntry['academic_year_options'],
            'selectedAcademicYear' => $selectedAcademicYear,
            'totalApplicants' => $totalApplicants,
            'isLockedForPublic' => ! $activeEntry['is_open_for_public'],
        ]);
    }

    /**
     * Store new applicant registration for TK & KB Namira.
     */
    public function storeTK(Request $request, SpmbService $spmbService)
    {
        $tkUnits = $this->resolveTkKbUnits();
        $tkUnitIds = $tkUnits->pluck('id')->all();

        $request->validate([
            'unit_id' => ['required', 'integer', Rule::in($tkUnitIds)],
        ], [
            'unit_id.required' => 'Pilih unit TK / KB Namira tujuan terlebih dahulu.',
            'unit_id.in' => 'Unit TK / KB yang dipilih tidak valid.',
        ]);

        $unit = $tkUnits->firstWhere('id', (int) $request->input('unit_id'));
        $setting = $this->ensureUnitSetting($unit);

        if ($this->isLocked($unit)) {
            return redirect()->route('spmb.index')->with('error', "Pendaftaran online {$unit->name} saat ini belum dibuka untuk umum.");
        }

        $this->prepareAcademicYearInput($request);
        $allowedYears = $setting->getAllowedAcademicYears();

        $request->validate([
            'academic_year' => ['nullable', 'string', Rule::in($allowedYears)],

            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|in:L,P',
            'nik' => 'nullable|string|max:20',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer',
            'total_siblings' => 'nullable|integer',
            'category' => 'nullable|string|in:internal_kb,internal,eksternal_umum,external_umum,external',
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
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:500',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',

            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',

            'photo' => 'nullable|file|image|max:10240',
            'family_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'birth_cert' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'parent_id_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'academic_year.in' => "Tahun ajaran pendaftaran yang dipilih tidak tersedia untuk {$unit->name}.",
            'full_name.required' => 'Nama lengkap calon murid wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi sebagai akun login.',
            'password.required' => 'Kata sandi akun wajib diisi minimal 6 karakter.',
            'address.required' => 'Alamat tempat tinggal wajib diisi.',
            'registration_payment_proof.required' => 'Bukti transfer/pembayaran pendaftaran wajib diunggah.',
        ]);

        if (! $request->filled('father_name') && ! $request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $targetAcademicYear = $request->input('academic_year') ?: ($setting->academic_year ?: '2027/2028');
        $isFutureInden = $setting->isFutureIndenYear($targetAcademicYear);
        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);

        $existingApplicant = $this->findDuplicateApplicant($unit->id, $targetAcademicYear, $cleanPhone, (string) $request->full_name);

        if ($existingApplicant) {
            return back()->withErrors([
                'parent_phone' => "Data Ananda {$existingApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah terdaftar di {$unit->name} TA {$targetAcademicYear} (No. Registrasi: {$existingApplicant->registration_number}). Silakan gunakan menu Login untuk masuk ke portal Anda.",
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $unit, $setting, $targetAcademicYear, $isFutureInden, $cleanPhone, $spmbService) {
            $regNumber = $spmbService->generateRegistrationNumber($unit, $targetAcademicYear, $setting->academic_year);
            $unitSlug = $spmbService->resolveUnitSlug($unit);

            $paymentProofPath = $spmbService->storeApplicantDocument(
                $request->file('registration_payment_proof'),
                'BUKTI_BAYAR_REGISTRASI',
                $regNumber,
                $unitSlug
            );

            $photoPath = $request->hasFile('photo') ? $spmbService->storeApplicantDocument($request->file('photo'), 'PASFOTO', $regNumber, $unitSlug) : null;
            $kkPath = $request->hasFile('family_card') ? $spmbService->storeApplicantDocument($request->file('family_card'), 'KK', $regNumber, $unitSlug) : null;
            $aktaPath = $request->hasFile('birth_cert') ? $spmbService->storeApplicantDocument($request->file('birth_cert'), 'AKTA', $regNumber, $unitSlug) : null;
            $ktpPath = $request->hasFile('parent_id_card') ? $spmbService->storeApplicantDocument($request->file('parent_id_card'), 'KTP_ORTU', $regNumber, $unitSlug) : null;

            $parentName = $request->father_name ?: ($request->mother_name ?: $request->full_name);
            $user = $spmbService->resolveParentUser($cleanPhone, $request->password, $parentName, $unit->id);

            $category = in_array($request->category, ['external_umum', 'external'], true)
                ? 'eksternal_umum'
                : ($request->category ?: 'eksternal_umum');

            $applicant = SpmbApplicant::create([
                'registration_number' => $regNumber,
                'unit_id' => $unit->id,
                'academic_year' => $targetAcademicYear,
                'user_id' => $user->id,
                'category' => $category,
                'is_inden' => $isFutureInden ? true : $request->boolean('is_inden', false),
                'status' => 'submitted',

                'full_name' => $request->full_name,
                'nickname' => $request->nickname,
                'gender' => $request->gender,
                'nik' => $request->nik,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion ?: 'Islam',
                'child_order' => $request->child_order,
                'total_siblings' => $request->total_siblings,
                'previous_school' => $request->previous_school,
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
                'city' => $request->city ?: 'Probolinggo',
                'postal_code' => $request->postal_code,

                'photo_path' => $photoPath,
                'family_card_path' => $kkPath,
                'birth_cert_path' => $aktaPath,
                'parent_id_card_path' => $ktpPath,
                'registration_payment_proof' => $paymentProofPath,
            ]);

            Auth::login($user, true);

            $jalurNote = $isFutureInden ? "Jalur Inden TA {$targetAcademicYear}" : "TA {$targetAcademicYear}";

            return redirect()->route('spmb.applicant.dashboard', ['applicant_id' => $applicant->id])
                ->with('success', "Pendaftaran {$unit->name} ({$jalurNote}) berhasil! Nomor Registrasi Ananda: {$regNumber}. Silakan lengkapi berkas pendukung di Portal Calon Siswa.")
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

        if ($providedToken === '' || ! hash_equals($expectedHash, hash('sha256', $providedToken))) {
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
            } elseif ($u === 'tk' || $u === 'kb') {
                $tkIds = $this->resolveTkKbUnits()->pluck('id')->all();
                if (! empty($tkIds)) {
                    $query->whereIn('unit_id', $tkIds);
                }
            }
        }

        if ($request->filled('academic_year') && $request->query('academic_year') !== 'all') {
            $normYear = SpmbSetting::normalizeAcademicYear((string) $request->query('academic_year'));
            $query->where('academic_year', $normYear ?: (string) $request->query('academic_year'));
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
                'academic_year' => $a->academic_year,
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
                'has_registration_proof' => ! empty($a->registration_payment_proof),
                'registration_payment_verified_at' => $a->registration_payment_verified_at?->toIso8601String(),
                'has_photo' => ! empty($a->photo_path),
                'has_family_card' => ! empty($a->family_card_path),
                'has_birth_cert' => ! empty($a->birth_cert_path),
                'has_parent_id_card' => ! empty($a->parent_id_card_path),
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

        $unitSummary = SpmbApplicant::select('unit_id', 'academic_year', DB::raw('count(*) as total'))
            ->groupBy('unit_id', 'academic_year')
            ->with('unit:id,name,code')
            ->get()
            ->map(fn ($row) => [
                'unit_id' => $row->unit_id,
                'unit_name' => $row->unit?->name,
                'unit_code' => $row->unit?->code,
                'academic_year' => $row->academic_year,
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
