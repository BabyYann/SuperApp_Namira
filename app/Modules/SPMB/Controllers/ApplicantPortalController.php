<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicantPortalController extends Controller
{
    /**
     * Determine whether official announcement / evaluation results are released to the parent portal.
     */
    protected function isAnnouncementReleasedForApplicant(SpmbApplicant $applicant, ?SpmbSetting $setting): bool
    {
        if ($setting && $setting->announcement_released) {
            return true;
        }

        if ($setting && $setting->announcement_date && now()->startOfDay()->gte($setting->announcement_date)) {
            return true;
        }

        return in_array($applicant->status, ['partial_paid', 'fully_paid', 'enrolled'], true);
    }

    /**
     * Check whether the authenticated user owns this applicant record (by user_id or normalized parent_phone),
     * or optionally holds a staff/panitia role.
     */
    private function isApplicantOwner(SpmbApplicant $applicant, User $user): bool
    {
        if ((int) $applicant->user_id === (int) $user->id) {
            return true;
        }

        if (!empty($user->phone) && !empty($applicant->parent_phone)) {
            $spmbService = app(SpmbService::class);
            return $spmbService->normalizePhone($applicant->parent_phone) === $spmbService->normalizePhone($user->phone);
        }

        return false;
    }

    /**
     * Display the applicant dashboard for the logged-in parent.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        $allApplicants = SpmbApplicant::with(['unit'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (!empty($user->phone)) {
                    $normPhone = app(SpmbService::class)->normalizePhone($user->phone);
                    $q->orWhereIn('parent_phone', array_unique(array_filter([$user->phone, $normPhone])));
                }
            })
            ->latest()
            ->get();

        if ($allApplicants->isEmpty()) {
            return redirect()->route('spmb.index')->with('info', 'Anda belum memiliki data pendaftaran. Silakan pilih unit sekolah untuk melakukan pendaftaran.');
        }

        $requestedId = $request->integer('applicant_id');
        $applicant = $requestedId
            ? ($allApplicants->firstWhere('id', $requestedId) ?? $allApplicants->first())
            : $allApplicants->first();

        $settingsByUnit = SpmbSetting::whereIn('unit_id', $allApplicants->pluck('unit_id')->unique())
            ->get()
            ->keyBy('unit_id');

        $siblings = $allApplicants->map(function (SpmbApplicant $item) use ($settingsByUnit) {
            $itemSetting = $settingsByUnit->get($item->unit_id);
            $itemReleased = $this->isAnnouncementReleasedForApplicant($item, $itemSetting);
            $maskedLabel = (!$itemReleased && in_array($item->status, ['accepted', 'reserve', 'rejected'], true))
                ? 'Menunggu Pengumuman'
                : $item->status_label;

            return [
                'id' => $item->id,
                'registration_number' => $item->registration_number,
                'full_name' => $item->full_name,
                'unit_name' => $item->unit?->code === 'SD' ? 'SD Namira Kraksaan' : ($item->unit?->name ?? 'Namira'),
                'status_label' => $maskedLabel,
            ];
        })->values();

        $setting = $settingsByUnit->get($applicant->unit_id);

        // Gating Pengumuman Resmi: terbuka hanya jika ceklis rilis aktif, tanggal pengumuman sudah tiba, atau tahap daftar ulang.
        $isAnnouncementReleased = $this->isAnnouncementReleasedForApplicant($applicant, $setting);

        // Amankan nilai dan catatan evaluasi internal agar tidak bocor ke browser orang tua
        $hiddenFields = ['academic_score_details', 'evaluation_notes'];
        if (!$isAnnouncementReleased) {
            $hiddenFields = array_merge($hiddenFields, [
                'evaluation_result',
                'evaluation_document_path',
                'evaluation_document_url',
                'virtual_account_number',
                'total_admission_fee',
                'min_down_payment',
            ]);
        }
        $applicant->makeHidden($hiddenFields);

        return Inertia::render('SPMB/Applicant/Dashboard', [
            'applicant' => $applicant,
            'siblings' => $siblings,
            'setting' => $setting,
            'isAnnouncementReleased' => $isAnnouncementReleased,
        ]);
    }

    /**
     * Download / Print Applicant Card (Kartu Tanda Peserta SPMB).
     */
    public function printCard(SpmbApplicant $applicant)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user) && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses ditolak.');
        }

        $applicant->load('unit');
        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        $pdf = Pdf::loadView('pdf.spmb.kartu-peserta', compact('applicant', 'setting'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Kartu_Peserta_SPMB_{$applicant->registration_number}.pdf");
    }

    /**
     * Download / Print Letter of Acceptance (Surat Keputusan Penerimaan).
     */
    public function printAcceptedLetter(SpmbApplicant $applicant)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user) && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses ditolak.');
        }

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        // Gating akses surat kelulusan sebelum rilis resmi
        $isAnnouncementReleased = $this->isAnnouncementReleasedForApplicant($applicant, $setting);

        if (!$isAnnouncementReleased && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses ditolak: Surat Keterangan Penerimaan belum dapat diunduh sebelum pengumuman resmi dirilis.');
        }

        if (!in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])) {
            abort(400, 'Surat Keterangan Penerimaan hanya tersedia bagi calon siswa yang telah dinyatakan Lulus/Diterima.');
        }

        $applicant->load('unit');

        $pdf = Pdf::loadView('pdf.spmb.surat-kelulusan', compact('applicant', 'setting'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Surat_Penerimaan_SPMB_{$applicant->registration_number}.pdf");
    }

    /**
     * Upload proof of payment for re-registration (Daftar Ulang Termin 1 / 2 Pelunasan).
     */
    public function uploadReRegistrationProof(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user)) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'payment_type' => 'nullable|in:termin_1,termin_2,pelunasan',
        ], [
            'payment_proof.required' => 'Bukti pembayaran transfer wajib dipilih.',
        ]);

        $unitSlug = $spmbService->resolveUnitSlug($applicant->unit);
        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();
        $dpPercentage = (int) ($setting?->min_down_payment_percentage ?? 60);
        $isDirectFullPayment = $dpPercentage >= 100;

        // Deteksi apakah ini pembayaran Termin 1 atau Pelunasan (Termin 2 / Lunas 100%)
        $isSettlement = $isDirectFullPayment
            || ($request->payment_type === 'termin_2' || $request->payment_type === 'pelunasan')
            || ($applicant->status === 'partial_paid' || $applicant->admission_payment_status === 'partial')
            || (!empty($applicant->re_registration_payment_proof) && empty($applicant->settlement_payment_proof));

        $docTag = $isSettlement ? 'BUKTI_BAYAR_PELUNASAN' : 'BUKTI_BAYAR_DAFTAR_ULANG_TERMIN_1';

        $proofPath = $spmbService->storeApplicantDocument(
            $request->file('payment_proof'),
            $docTag,
            $applicant->registration_number,
            $unitSlug
        );

        $updateData = [];
        $history = $applicant->payment_history ?? [];

        if ($isSettlement && Schema::hasColumn('spmb_applicants', 'settlement_payment_proof')) {
            $updateData['settlement_payment_proof'] = $proofPath;
            $history[] = [
                'type' => 'termin_2',
                'title' => $isDirectFullPayment ? 'Pembayaran Daftar Ulang (Lunas 100%)' : 'Pembayaran Pelunasan (Termin 2 - 100%)',
                'proof_path' => $proofPath,
                'uploaded_at' => now()->toIso8601String(),
                'status' => 'pending',
            ];
            $msg = $isDirectFullPayment
                ? 'Bukti pembayaran daftar ulang (100%) berhasil diunggah dan sedang diproses oleh Panitia Keuangan.'
                : 'Bukti pembayaran pelunasan daftar ulang berhasil diunggah dan sedang diproses oleh Panitia Keuangan.';
        } else {
            $updateData['re_registration_payment_proof'] = $proofPath;
            $history[] = [
                'type' => 'termin_1',
                'title' => "Pembayaran Daftar Ulang (Termin 1 / DP {$dpPercentage}%)",
                'proof_path' => $proofPath,
                'uploaded_at' => now()->toIso8601String(),
                'status' => 'pending',
            ];
            $msg = 'Bukti pembayaran daftar ulang Termin 1 berhasil diunggah dan sedang diproses oleh Panitia Keuangan.';
        }

        if (Schema::hasColumn('spmb_applicants', 'payment_history')) {
            $updateData['payment_history'] = $history;
        }

        $applicant->update($updateData);

        return back()->with('success', $msg);
    }

    /**
     * Update applicant biodata by parent (allowed before enrolled status).
     */
    public function updateProfile(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user)) {
            abort(403, 'Akses ditolak.');
        }

        if ($applicant->status === 'enrolled') {
            return back()->with('error', 'Biodata tidak dapat diubah karena siswa telah resmi ditetapkan menjadi Siswa Aktif.');
        }

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

            'photo' => 'nullable|file|image|max:10240',
        ], [
            'full_name.required' => 'Nama lengkap calon siswa wajib diisi.',
            'birth_place.required' => 'Tempat lahir wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'previous_school.required' => 'Asal sekolah wajib diisi.',
            'parent_phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'address.required' => 'Alamat tempat tinggal wajib diisi.',
        ]);

        if (!$request->filled('father_name') && !$request->filled('mother_name')) {
            return back()->withErrors([
                'father_name' => 'Setidaknya salah satu nama orang tua (Ayah atau Ibu) wajib diisi.',
            ])->withInput();
        }

        $cleanPhone = $spmbService->normalizePhone($request->parent_phone);
        $normalizedInputName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $request->full_name)));

        // Check duplicate phone + same child name in the same unit (allows twins/siblings)
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
                'parent_phone' => "Data Ananda {$duplicateApplicant->full_name} dengan Nomor WhatsApp {$cleanPhone} sudah digunakan pada pendaftaran lain ({$duplicateApplicant->registration_number}).",
            ])->withInput();
        }

        $unitSlug = $spmbService->resolveUnitSlug($applicant->unit);
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
        ];

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

        // Sinkronkan akun user orang tua jika nomor WA berubah
        if ($user) {
            $phoneTakenByOtherUser = User::where('phone', $cleanPhone)
                ->where('id', '!=', $user->id)
                ->exists();

            $userUpdates = [
                'name' => $request->father_name ?: ($request->mother_name ?: $user->name),
            ];

            if (!$phoneTakenByOtherUser) {
                $userUpdates['phone'] = $cleanPhone;
                if (str_ends_with($user->email, '@spmb.namiraschool.com')) {
                    $newEmail = "{$cleanPhone}@spmb.namiraschool.com";
                    if (!User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
                        $userUpdates['email'] = $newEmail;
                    }
                }
            }

            $user->update($userUpdates);
        }

        return back()->with('success', 'Biodata calon siswa berhasil diperbarui.');
    }

    /**
     * Replace initial registration payment proof (allowed before verified by panitia).
     */
    public function updateRegistrationProof(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user)) {
            abort(403, 'Akses ditolak.');
        }

        if ($applicant->registration_payment_verified_at) {
            return back()->with('error', 'Bukti pembayaran pendaftaran sudah diverifikasi panitia dan tidak dapat diganti.');
        }

        $request->validate([
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'registration_payment_proof.required' => 'Silakan pilih file bukti transfer pendaftaran yang baru.',
            'registration_payment_proof.mimes' => 'Format file harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $unitSlug = $spmbService->resolveUnitSlug($applicant->unit);

        if ($applicant->registration_payment_proof && Storage::disk('public')->exists($applicant->registration_payment_proof)) {
            Storage::disk('public')->delete($applicant->registration_payment_proof);
        }

        $proofPath = $spmbService->storeApplicantDocument(
            $request->file('registration_payment_proof'),
            'BUKTI_BAYAR_REGISTRASI',
            $applicant->registration_number,
            $unitSlug
        );

        $applicant->update([
            'registration_payment_proof' => $proofPath,
        ]);

        return back()->with('success', 'Bukti transfer pendaftaran awal berhasil diperbarui.');
    }

    /**
     * Upload or replace supporting documents (Pas Foto, KK, Akta Kelahiran, KTP Orang Tua) from Portal.
     */
    public function uploadSupportingDocument(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $user = Auth::user();
        if (!$this->isApplicantOwner($applicant, $user)) {
            abort(403, 'Akses ditolak.');
        }

        if ($applicant->status === 'enrolled') {
            return back()->with('error', 'Berkas tidak dapat diubah karena siswa telah resmi ditetapkan menjadi Siswa Aktif.');
        }

        $request->validate([
            'doc_type' => 'required|in:photo,family_card,birth_cert,parent_id_card',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'file.required' => 'Silakan pilih file berkas yang akan diunggah.',
            'file.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $unitSlug = $spmbService->resolveUnitSlug($applicant->unit);

        $mapping = [
            'photo' => ['column' => 'photo_path', 'tag' => 'PASFOTO', 'label' => 'Pas Foto Anak'],
            'family_card' => ['column' => 'family_card_path', 'tag' => 'KK', 'label' => 'Kartu Keluarga (KK)'],
            'birth_cert' => ['column' => 'birth_cert_path', 'tag' => 'AKTA', 'label' => 'Akta Kelahiran'],
            'parent_id_card' => ['column' => 'parent_id_card_path', 'tag' => 'KTP_ORTU', 'label' => 'KTP Orang Tua'],
        ];

        $docInfo = $mapping[$request->doc_type];
        $column = $docInfo['column'];

        if ($applicant->{$column} && Storage::disk('public')->exists($applicant->{$column})) {
            Storage::disk('public')->delete($applicant->{$column});
        }

        $storedPath = $spmbService->storeApplicantDocument(
            $request->file('file'),
            $docInfo['tag'],
            $applicant->registration_number,
            $unitSlug
        );

        $applicant->update([
            $column => $storedPath,
        ]);

        return back()->with('success', "Berkas {$docInfo['label']} berhasil diunggah dan tersimpan.");
    }
}
