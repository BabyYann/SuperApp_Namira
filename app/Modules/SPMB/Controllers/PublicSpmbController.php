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
use Inertia\Inertia;

class PublicSpmbController extends Controller
{
    /**
     * Determine if SPMB public portal is locked for the current request.
     */
    protected function isLocked(): bool
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

        return true;
    }

    /**
     * Display public landing / portal for choosing unit.
     */
    public function index()
    {
        $sdUnit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->first();
        $sdSetting = null;
        if ($sdUnit) {
            $sdSetting = SpmbSetting::where('unit_id', $sdUnit->id)->first();
        }

        if ($this->isLocked()) {
            return Inertia::render('SPMB/Public/Locked', [
                'contactWhatsapp' => $sdSetting->contact_whatsapp ?? '082332922521',
                'academicYear' => $sdSetting->academic_year ?? '2026/2027',
            ]);
        }

        $units = Unit::select('id', 'name')->get();

        return Inertia::render('SPMB/Public/Index', [
            'units' => $units,
            'sdUnit' => $sdUnit,
            'sdSetting' => $sdSetting,
            'isLockedForPublic' => true,
        ]);
    }

    /**
     * Display the registration form for SD Namira.
     */
    public function registerSD()
    {
        if ($this->isLocked()) {
            return redirect()->route('spmb.index');
        }

        $unit = Unit::where('id', 3)->orWhere('name', 'like', '%SD%')->firstOrFail();
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
            'photo' => 'required|file|image|max:5120', // Max 5MB
            'family_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'birth_cert' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'parent_id_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
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
}
