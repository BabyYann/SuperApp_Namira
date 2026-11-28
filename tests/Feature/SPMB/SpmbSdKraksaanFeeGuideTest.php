<?php

use App\Models\User;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    createRole('super_admin_yayasan');
    createRole('panitia_spmb');
    createRole('orang_tua');

    $this->sdKraksaan = Unit::firstOrCreate(
        ['code' => 'SD'],
        ['name' => 'SD Namira', 'level' => 'SD', 'category' => 'formal']
    );

    $this->sdKota = Unit::firstOrCreate(
        ['code' => 'SD-02'],
        ['name' => 'SD NAMIRA KOTA PROBOLINGGO', 'level' => 'SD', 'category' => 'formal']
    );

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'SMP', 'category' => 'formal']
    );

    SpmbSetting::updateOrCreate(
        ['unit_id' => $this->sdKraksaan->id],
        [
            'name' => 'SPMB SD Namira Kraksaan',
            'academic_year' => '2027/2028',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'selection_date' => '2026-12-05',
            'announcement_released' => false,
            'is_active' => true,
        ]
    );

    SpmbSetting::updateOrCreate(
        ['unit_id' => $this->sdKota->id],
        [
            'name' => 'SPMB SD Namira Kota',
            'academic_year' => '2027/2028',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
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
            'is_active' => true,
        ]
    );
});

it('shows exact Putra TP 2027/2028 fee guide and allows PDF download for verified SD Namira Kraksaan applicant', function () {
    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'SPMB-SD-27001',
        'unit_id' => $this->sdKraksaan->id,
        'user_id' => $parent->id,
        'academic_year' => '2027/2028',
        'category' => 'internal_tk',
        'status' => 'verified',
        'registration_payment_verified_at' => now(),
        'full_name' => 'Muhammad Al-Fatih',
        'gender' => 'L',
        'parent_phone' => '081234560101',
    ]);

    $this->actingAs($parent)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Applicant/Dashboard')
            ->where('setting.selection_date', '2026-12-05')
            ->where('sdKraksaanFeeGuide.applicant_gender', 'L')
            ->where('sdKraksaanFeeGuide.totals.putra', 7250000)
            ->where('sdKraksaanFeeGuide.totals.putri', 7365000)
            ->where('sdKraksaanFeeGuide.totals.applicant_total', 7250000)
            ->where('sdKraksaanFeeGuide.totals.already_paid_registration', 250000)
            ->where('sdKraksaanFeeGuide.totals.remaining_estimate', 7000000)
            ->where('sdKraksaanFeeGuide.monthly_spp.amount', 500000)
            ->where('sdKraksaanFeeGuide.monthly_savings.total', 30000)
            ->has('sdKraksaanFeeGuide.initial_items', 6)
        );

    $this->actingAs($parent)
        ->get(route('spmb.applicant.print-fee-guide', $applicant->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($parent)
        ->get(route('spmb.applicant.print-card', $applicant->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('calculates exact Putri TP 2027/2028 totals for verified female SD Namira Kraksaan applicant', function () {
    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    SpmbApplicant::create([
        'registration_number' => 'SPMB-SD-27002',
        'unit_id' => $this->sdKraksaan->id,
        'user_id' => $parent->id,
        'academic_year' => '2027/2028',
        'category' => 'eksternal_umum',
        'status' => 'verified',
        'registration_payment_verified_at' => now(),
        'full_name' => 'Adreena Yasmine',
        'gender' => 'P',
        'parent_phone' => '081234560102',
    ]);

    $this->actingAs($parent)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sdKraksaanFeeGuide.applicant_gender', 'P')
            ->where('sdKraksaanFeeGuide.totals.applicant_total', 7365000)
            ->where('sdKraksaanFeeGuide.totals.remaining_estimate', 7115000)
        );
});

it('hides fee guide and forbids PDF download for unverified pending SD Kraksaan applicant', function () {
    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'SPMB-SD-27003',
        'unit_id' => $this->sdKraksaan->id,
        'user_id' => $parent->id,
        'academic_year' => '2027/2028',
        'category' => 'eksternal_umum',
        'status' => 'pending',
        'registration_payment_verified_at' => null,
        'full_name' => 'Belum Verifikasi',
        'gender' => 'L',
        'parent_phone' => '081234560103',
    ]);

    $this->actingAs($parent)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sdKraksaanFeeGuide', null)
        );

    $this->actingAs($parent)
        ->get(route('spmb.applicant.print-fee-guide', $applicant->id))
        ->assertForbidden();
});

it('strictly excludes SD Namira Kota and SMP applicants from SD Kraksaan fee guide', function () {
    $parentKota = User::factory()->create();
    $parentKota->assignRole('orang_tua');

    $appKota = SpmbApplicant::create([
        'registration_number' => 'SPMB-SD02-27001',
        'unit_id' => $this->sdKota->id,
        'user_id' => $parentKota->id,
        'academic_year' => '2027/2028',
        'category' => 'eksternal_umum',
        'status' => 'verified',
        'registration_payment_verified_at' => now(),
        'full_name' => 'Siswa SD Kota',
        'gender' => 'L',
        'parent_phone' => '081234560104',
    ]);

    $this->actingAs($parentKota)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sdKraksaanFeeGuide', null));

    $this->actingAs($parentKota)
        ->get(route('spmb.applicant.print-fee-guide', $appKota->id))
        ->assertForbidden();

    $parentSmp = User::factory()->create();
    $parentSmp->assignRole('orang_tua');

    $appSmp = SpmbApplicant::create([
        'registration_number' => 'SPMB-SMP-27001',
        'unit_id' => $this->smpUnit->id,
        'user_id' => $parentSmp->id,
        'academic_year' => '2027/2028',
        'category' => 'internal_sd',
        'status' => 'verified',
        'registration_payment_verified_at' => now(),
        'full_name' => 'Siswa SMP',
        'gender' => 'P',
        'parent_phone' => '081234560105',
    ]);

    $this->actingAs($parentSmp)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sdKraksaanFeeGuide', null));

    $this->actingAs($parentSmp)
        ->get(route('spmb.applicant.print-fee-guide', $appSmp->id))
        ->assertForbidden();
});

it('hides TP 2027/2028 Kraksaan fee guide for future-year Inden applicants', function () {
    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'IND-SD-28001',
        'unit_id' => $this->sdKraksaan->id,
        'user_id' => $parent->id,
        'academic_year' => '2028/2029',
        'is_inden' => true,
        'category' => 'eksternal_umum',
        'status' => 'verified',
        'registration_payment_verified_at' => now(),
        'full_name' => 'Inden Tahun Depan',
        'gender' => 'L',
        'parent_phone' => '081234560106',
    ]);

    $this->actingAs($parent)
        ->get(route('spmb.applicant.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sdKraksaanFeeGuide', null));

    $this->actingAs($parent)
        ->get(route('spmb.applicant.print-fee-guide', $applicant->id))
        ->assertForbidden();
});

it('shows fee guide preview in admin Show view and auto-fills Dec 5 schedule on payment verification', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin_yayasan');

    $applicant = SpmbApplicant::create([
        'registration_number' => 'SPMB-SD-27099',
        'unit_id' => $this->sdKraksaan->id,
        'academic_year' => '2027/2028',
        'category' => 'internal_tk',
        'status' => 'submitted',
        'full_name' => 'Calon Murid Kraksaan',
        'gender' => 'P',
        'parent_phone' => '081234560199',
    ]);

    // Before verification: admin sees isSdKraksaan=true, sdKraksaanFeeGuide=null
    $this->actingAs($admin)
        ->get(route('spmb.admin.show', $applicant->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Admin/Show')
            ->where('isSdKraksaan', true)
            ->where('sdKraksaanFeeGuide', null)
        );

    // Verify payment -> auto-fills observation_date & psychotest_date to 2026-12-05!
    $this->actingAs($admin)
        ->post(route('spmb.admin.verify-payment', $applicant->id))
        ->assertRedirect();

    $applicant->refresh();
    expect($applicant->observation_date?->format('Y-m-d'))->toBe('2026-12-05')
        ->and($applicant->psychotest_date?->format('Y-m-d'))->toBe('2026-12-05');

    // After verification: admin sees populated Putri fee guide & can download PDF
    $this->actingAs($admin)
        ->get(route('spmb.admin.show', $applicant->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isSdKraksaan', true)
            ->where('sdKraksaanFeeGuide.totals.applicant_total', 7365000)
            ->where('sdKraksaanFeeGuide.totals.remaining_estimate', 7115000)
        );

    $this->actingAs($admin)
        ->get(route('spmb.applicant.print-fee-guide', $applicant->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
