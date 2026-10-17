<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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

        // Gating Pengumuman Resmi: periksa flag rilis manual atau tanggal jadwal pengumuman
        $isAnnouncementReleased = false;
        if ($setting) {
            if ($setting->announcement_released) {
                $isAnnouncementReleased = true;
            } elseif ($setting->announcement_date && now()->startOfDay()->gte($setting->announcement_date)) {
                $isAnnouncementReleased = true;
            }
        } else {
            // Default jika belum ada konfigurasi tanggal
            $isAnnouncementReleased = in_array($applicant->status, ['accepted', 'reserve', 'rejected', 'partial_paid', 'fully_paid', 'enrolled']);
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
        if ($setting) {
            if ($setting->announcement_released || ($setting->announcement_date && now()->startOfDay()->gte($setting->announcement_date))) {
                $isAnnouncementReleased = true;
            }
        } else {
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
}
