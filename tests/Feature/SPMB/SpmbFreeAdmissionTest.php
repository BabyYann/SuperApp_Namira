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

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'SMP', 'category' => 'formal']
    );

    $this->smpSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->smpUnit->id],
        [
            'name' => 'SPMB SMP',
            'academic_year' => '2026/2027',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 10720000,
            'min_down_payment_percentage' => 100,
            'is_active' => true,
        ]
    );

    $this->smpPanitia = User::factory()->create();
    $this->smpPanitia->assignRole('panitia_spmb');

    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $this->applicant = SpmbApplicant::create([
        'registration_number' => 'IND-SMPN-26301',
        'unit_id' => $this->smpUnit->id,
        'user_id' => $parent->id,
        'category' => 'eksternal_umum',
        'status' => 'accepted',
        'evaluation_result' => 'accepted',
        'full_name' => 'Rafif Putra',
        'gender' => 'L',
        'parent_phone' => '081299990001',
        'total_admission_fee' => 10720000,
        'min_down_payment' => 10720000,
    ]);
});

it('allows setting single applicant fee to 0 without VA and automatically marks fully_paid', function () {
    $response = $this->actingAs($this->smpPanitia)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.set-va', $this->applicant->id), [
            'virtual_account_number' => '',
            'total_admission_fee' => 0,
            'min_down_payment' => 0,
            'full_payment_deadline' => '',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->applicant->refresh();

    expect($this->applicant->virtual_account_number)->toBeNull()
        ->and((float) $this->applicant->total_admission_fee)->toBe(0.0)
        ->and((float) $this->applicant->min_down_payment)->toBe(0.0)
        ->and((float) $this->applicant->paid_admission_amount)->toBe(0.0)
        ->and($this->applicant->admission_payment_status)->toBe('paid')
        ->and($this->applicant->status)->toBe('fully_paid')
        ->and($this->applicant->admission_payment_verified_at)->not->toBeNull();
});

it('still requires virtual account number when total admission fee is greater than 0', function () {
    $response = $this->actingAs($this->smpPanitia)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.set-va', $this->applicant->id), [
            'virtual_account_number' => '',
            'total_admission_fee' => 10720000,
        ]);

    $response->assertSessionHasErrors('virtual_account_number');
});

it('preserves explicit 0 fee when re-saving evaluation and reverts auto fully_paid if fee is raised back above 0', function () {
    $this->actingAs($this->smpPanitia)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.set-va', $this->applicant->id), [
            'virtual_account_number' => '',
            'total_admission_fee' => 0,
        ])
        ->assertSessionHasNoErrors();

    // Re-saving evaluation must NOT overwrite explicit 0 back to default fee
    $this->actingAs($this->smpPanitia)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.save-evaluation', $this->applicant->id), [
            'evaluation_result' => 'accepted',
            'evaluation_notes' => 'Beasiswa tanggungan full',
        ])
        ->assertSessionHasNoErrors();

    $this->applicant->refresh();
    expect((float) $this->applicant->total_admission_fee)->toBe(0.0)
        ->and($this->applicant->status)->toBe('fully_paid');

    // Raising fee back > 0 without any uploaded payment proof reverts status from fully_paid to accepted
    $this->actingAs($this->smpPanitia)
        ->withSession(['active_unit_id' => $this->smpUnit->id])
        ->post(route('spmb.admin.set-va', $this->applicant->id), [
            'virtual_account_number' => '9881029100889955',
            'total_admission_fee' => 10720000,
            'full_payment_deadline' => '2026-12-30',
        ])
        ->assertSessionHasNoErrors();

    $this->applicant->refresh();
    expect($this->applicant->status)->toBe('accepted')
        ->and($this->applicant->admission_payment_status)->toBe('unpaid')
        ->and($this->applicant->virtual_account_number)->toBe('9881029100889955');
});
