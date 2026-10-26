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
     * Display the applicant dashboard for the logged-in parent.
     */
    public function dashboard()
    {
        $user = Auth::user();

        $applicant = SpmbApplicant::with(['unit'])
            ->where('user_id', $user->id)
            ->orWhere('parent_phone', $user->phone)
            ->latest()
            ->first();

        if (!$applicant) {
            return redirect()->route('spmb.index')->with('info', 'Anda belum memiliki data pendaftaran. Silakan pilih unit sekolah untuk melakukan pendaftaran.');
        }

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        // Gating Pengumuman Resmi:
        // 1. Jika rilis manual diaktifkan di setting: rilis untuk semua
        // 2. Jika tanggal jadwal pengumuman sudah tiba/lewat: rilis untuk semua
        // 3. Jika siswa sudah tahap daftar ulang/resmi (partial_paid, fully_paid, enrolled): otomatis terbuka penuh
        // 4. Jika tanggal pengumuman belum diset (null) dan siswa sudah diberi keputusan: otomatis terbuka
        // 5. Jika tanggal pengumuman di masa depan dan rilis manual belum aktif: tahan hingga jadwalnya tiba
        $isAnnouncementReleased = false;
        if ($setting && $setting->announcement_released) {
            $isAnnouncementReleased = true;
        } elseif ($setting && $setting->announcement_date && now()->startOfDay()->gte($setting->announcement_date)) {
            $isAnnouncementReleased = true;
        } elseif (in_array($applicant->status, ['partial_paid', 'fully_paid', 'enrolled'])) {
            $isAnnouncementReleased = true;
        } elseif ((!$setting || !$setting->announcement_date) && in_array($applicant->status, ['accepted', 'reserve', 'rejected'])) {
            $isAnnouncementReleased = true;
        }

        // Amankan nilai dan catatan evaluasi internal agar tidak bocor ke browser orang tua
        $applicant->makeHidden(['academic_score_details', 'evaluation_notes']);

        return Inertia::render('SPMB/Applicant/Dashboard', [
            'applicant' => $applicant,
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
        if ($applicant->user_id !== $user->id && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
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
        if ($applicant->user_id !== $user->id && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'panitia_spmb'])) {
            abort(403, 'Akses ditolak.');
        }

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        // Gating akses surat kelulusan sebelum rilis resmi
        $isAnnouncementReleased = false;
        if ($setting && ($setting->announcement_released || ($setting->announcement_date && now()->startOfDay()->gte($setting->announcement_date)))) {
            $isAnnouncementReleased = true;
        } elseif (in_array($applicant->status, ['partial_paid', 'fully_paid', 'enrolled'])) {
            $isAnnouncementReleased = true;
        } elseif ((!$setting || !$setting->announcement_date) && in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])) {
            $isAnnouncementReleased = true;
        }

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
        if ($applicant->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'payment_type' => 'nullable|in:termin_1,termin_2,pelunasan',
        ], [
            'payment_proof.required' => 'Bukti pembayaran transfer wajib dipilih.',
        ]);

        $unitSlug = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp') ? 'smp-namira' : 'sd-namira';

        // Deteksi apakah ini pembayaran Termin 1 atau Pelunasan (Termin 2)
        $isSettlement = ($request->payment_type === 'termin_2' || $request->payment_type === 'pelunasan')
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
                'title' => 'Pembayaran Pelunasan (Termin 2 - 100%)',
                'proof_path' => $proofPath,
                'uploaded_at' => now()->toIso8601String(),
                'status' => 'pending',
            ];
            $msg = 'Bukti pembayaran pelunasan daftar ulang berhasil diunggah dan sedang diproses oleh Panitia Keuangan.';
        } else {
            $updateData['re_registration_payment_proof'] = $proofPath;
            $history[] = [
                'type' => 'termin_1',
                'title' => 'Pembayaran Daftar Ulang (Termin 1 / DP 60%)',
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
        if ($applicant->user_id !== $user->id && $applicant->parent_phone !== $user->phone) {
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
            'category' => 'nullable|string|in:internal_sd,internal_tk,internal,eksternal_umum,external',
            'previous_school' => 'required|string|max:255',
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

        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$request->parent_phone);
        if (str_starts_with($cleanPhone, '62')) {
            $cleanPhone = '0' . substr($cleanPhone, 2);
        }

        // Check duplicate phone in the same unit
        $duplicateApplicant = SpmbApplicant::where('unit_id', $applicant->unit_id)
            ->where('parent_phone', $cleanPhone)
            ->where('id', '!=', $applicant->id)
            ->first();

        if ($duplicateApplicant) {
            return back()->withErrors([
                'parent_phone' => "Nomor WhatsApp {$cleanPhone} sudah digunakan pada pendaftaran lain ({$duplicateApplicant->registration_number}).",
            ])->withInput();
        }

        $unitSlug = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp') ? 'smp-namira' : 'sd-namira';

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
            'category' => $request->category ?: $applicant->category,
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
        if ($applicant->user_id !== $user->id && $applicant->parent_phone !== $user->phone) {
            abort(403, 'Akses ditolak.');
        }

        if ($applicant->registration_payment_verified_at) {
            return back()->with('error', 'Bukti pembayaran pendaftaran sudah diverifikasi panitia dan tidak dapat diganti.');
        }

        $request->validate([
            'registration_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'registration_payment_proof.required' => 'Silakan pilih file bukti transfer pendaftaran yang baru.',
            'registration_payment_proof.mimes' => 'Format file harus berupa JPG, PNG, atau PDF.',
        ]);

        $unitSlug = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp') ? 'smp-namira' : 'sd-namira';

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
}
