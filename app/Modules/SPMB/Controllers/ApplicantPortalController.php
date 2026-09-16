<?php

namespace App\Modules\SPMB\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\SPMB\Services\SpmbService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('spmb.register.sd')->with('info', 'Anda belum memiliki data pendaftaran. Silakan isi formulir pendaftaran.');
        }

        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        return Inertia::render('SPMB/Applicant/Dashboard', [
            'applicant' => $applicant,
            'setting' => $setting,
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

        if (!in_array($applicant->status, ['accepted', 'partial_paid', 'fully_paid', 'enrolled'])) {
            abort(400, 'Surat Keterangan Penerimaan hanya tersedia bagi calon siswa yang telah dinyatakan Lulus/Diterima.');
        }

        $applicant->load('unit');
        $setting = SpmbSetting::where('unit_id', $applicant->unit_id)->first();

        $pdf = Pdf::loadView('pdf.spmb.surat-kelulusan', compact('applicant', 'setting'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Surat_Penerimaan_SPMB_{$applicant->registration_number}.pdf");
    }

    /**
     * Upload proof of payment for re-registration (Daftar Ulang Termin 1 / 2).
     */
    public function uploadReRegistrationProof(Request $request, SpmbApplicant $applicant, SpmbService $spmbService)
    {
        $user = Auth::user();
        if ($applicant->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'payment_proof.required' => 'Bukti pembayaran transfer wajib dipilih.',
        ]);

        $proofPath = $spmbService->storeApplicantDocument(
            $request->file('payment_proof'),
            'BUKTI_BAYAR_DAFTAR_ULANG',
            $applicant->registration_number,
            'sd-namira'
        );

        $applicant->update([
            're_registration_payment_proof' => $proofPath,
        ]);

        return back()->with('success', 'Bukti pembayaran daftar ulang berhasil diunggah dan sedang diproses oleh Panitia Keuangan.');
    }
}
