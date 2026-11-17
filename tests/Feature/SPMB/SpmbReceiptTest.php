<?php

use App\Models\User;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    createRole('super_admin_yayasan');
    createRole('panitia_spmb');
    createRole('orang_tua');

    $this->sdUnit = Unit::firstOrCreate(
        ['code' => 'SD'],
        ['name' => 'SD Namira', 'level' => 'SD', 'category' => 'formal']
    );

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'SMP', 'category' => 'formal']
    );

    SpmbSetting::updateOrCreate(
        ['unit_id' => $this->sdUnit->id],
        [
            'name' => 'SPMB SD',
            'academic_year' => '2027/2028',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'announcement_released' => true,
            'is_active' => true,
        ]
    );

    SpmbSetting::updateOrCreate(
        ['unit_id' => $this->smpUnit->id],
        [
            'name' => 'SPMB SMP',
            'academic_year' => '2027/2028',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 10720000,
            'min_down_payment_percentage' => 100,
            'announcement_released' => true,
            'is_active' => true,
        ]
    );
});

it('forbids downloading official receipt before finance verification even after parent uploads proof', function () {
    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'IND-SMPN-27001',
        'unit_id' => $this->smpUnit->id,
        'user_id' => $parent->id,
        'category' => 'eksternal_umum',
        'status' => 'accepted',
        'full_name' => 'Budi Santoso',
        'gender' => 'L',
        'parent_phone' => '081234560001',
        'total_admission_fee' => 10720000,
        'settlement_payment_proof' => 'spmb/smp-namira/bukti.jpg',
    ]);

    $this->actingAs($parent)
        ->get(route('spmb.applicant.print-receipt', $applicant->id))
        ->assertForbidden();
});

it('renders and downloads official PDF receipt after payment verification with Indonesian terbilang', function () {
    $verifier = User::factory()->create(['name' => 'Ustadzah Keuangan']);
    $verifier->assignRole('panitia_spmb');

    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'IND-SMPN-27002',
        'unit_id' => $this->smpUnit->id,
        'user_id' => $parent->id,
        'category' => 'internal_sd',
        'status' => 'accepted',
        'full_name' => 'Aisyah Azzahra',
        'father_name' => 'Hendra Wijaya',
        'gender' => 'P',
        'parent_phone' => '081234560002',
        'virtual_account_number' => '9881029100889902',
        'total_admission_fee' => 10720000,
        'min_down_payment' => 10720000,
        'settlement_payment_proof' => 'spmb/smp-namira/lunas.jpg',
        'payment_history' => [
            [
                'type' => 'pelunasan',
                'title' => 'Pembayaran Daftar Ulang (Lunas 100%)',
                'proof_path' => 'spmb/smp-namira/lunas.jpg',
                'uploaded_at' => now()->toIso8601String(),
                'status' => 'pending',
            ],
        ],
    ]);

    $this->actingAs($verifier)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.verify-admission-payment', $applicant->id), [
            'paid_admission_amount' => 10720000,
            'admission_payment_status' => 'paid',
        ])
        ->assertSessionHasNoErrors();

    $applicant->refresh();
    expect($applicant->payment_history[0]['status'])->toBe('verified')
        ->and((float) $applicant->payment_history[0]['verified_amount'])->toBe(10720000.0);

    // Check rendered HTML contains exact institutional fields, terbilang, and verifier name
    $html = view('pdf.spmb.kwitansi-daftar-ulang', [
        'applicant' => $applicant->fresh(['unit', 'admissionPaymentVerifier', 'settlementPaymentVerifier']),
        'setting' => SpmbSetting::where('unit_id', $this->smpUnit->id)->first(),
        'receiptNumber' => 'KW/SPMB-SMP/2027/IND-SMPN-27002',
        'paidTerbilang' => 'Sepuluh Juta Tujuh Ratus Dua Puluh Ribu Rupiah',
    ])->render();

    expect($html)
        ->toContain('KW/SPMB-SMP/2027/IND-SMPN-27002')
        ->toContain('AISYAH AZZAHRA')
        ->toContain('Sepuluh Juta Tujuh Ratus Dua Puluh Ribu Rupiah')
        ->toContain('Ustadzah Keuangan')
        ->toContain('LUNAS 100%');

    // Both parent owner and admin can download the actual DomPDF file
    $parentDownload = $this->actingAs($parent)->get(route('spmb.applicant.print-receipt', $applicant->id));
    $parentDownload->assertOk();
    expect($parentDownload->headers->get('content-type'))->toContain('application/pdf');

    $adminDownload = $this->actingAs($verifier)->get(route('spmb.applicant.print-receipt', $applicant->id));
    $adminDownload->assertOk();
    expect($adminDownload->headers->get('content-type'))->toContain('application/pdf');
});

it('allows printing receipt for Rp 0 free admission and partial termin 1 SD', function () {
    $verifier = User::factory()->create(['name' => 'Admin SMP']);
    $verifier->assignRole('panitia_spmb');

    $freeParent = User::factory()->create();
    $freeParent->assignRole('orang_tua');

    $freeApplicant = SpmbApplicant::create([
        'registration_number' => 'IND-SMPN-27003',
        'unit_id' => $this->smpUnit->id,
        'user_id' => $freeParent->id,
        'category' => 'eksternal_umum',
        'status' => 'fully_paid',
        'full_name' => 'Rafif Putra',
        'gender' => 'L',
        'parent_phone' => '081234560003',
        'total_admission_fee' => 0,
        'min_down_payment' => 0,
        'paid_admission_amount' => 0,
        'admission_payment_status' => 'paid',
        'admission_payment_verified_at' => now(),
        'admission_payment_verified_by' => $verifier->id,
    ]);

    $this->actingAs($freeParent)
        ->get(route('spmb.applicant.print-receipt', $freeApplicant->id))
        ->assertOk();

    // Another unrelated parent cannot access Rafif's receipt
    $otherParent = User::factory()->create();
    $otherParent->assignRole('orang_tua');

    $this->actingAs($otherParent)
        ->get(route('spmb.applicant.print-receipt', $freeApplicant->id))
        ->assertForbidden();
});
