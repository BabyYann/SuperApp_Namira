<?php

use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    createRole('super_admin_yayasan');
    createRole('admin_yayasan');
    createRole('admin_unit');
    createRole('panitia_spmb');
    createRole('orang_tua');

    $this->sdUnit = Unit::firstOrCreate(
        ['code' => 'SD'],
        ['name' => 'SD Namira', 'level' => 'Nasional', 'category' => 'SD']
    );

    $this->sdKotaUnit = Unit::firstOrCreate(
        ['code' => 'SD-02'],
        ['name' => 'SD NAMIRA KOTA PROBOLINGGO', 'level' => 'Nasional', 'category' => 'SD']
    );

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'Nasional', 'category' => 'SMP']
    );

    $this->tkKraUnit = Unit::firstOrCreate(
        ['code' => 'TK-KRA'],
        ['name' => 'TK Namira Kraksaan', 'level' => 'Nasional', 'category' => 'TK']
    );

    $this->tkDriUnit = Unit::firstOrCreate(
        ['code' => 'TK-DRI'],
        ['name' => 'TK Namira Dringu', 'level' => 'Nasional', 'category' => 'TK']
    );

    $futureYears = ['2028/2029', '2029/2030', '2030/2031'];

    $this->sdSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->sdUnit->id],
        [
            'name' => 'SPMB SD Namira Kraksaan',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => $futureYears,
            'quota' => 80,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'is_active' => true,
            'announcement_released' => true,
        ]
    );

    $this->sdKotaSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->sdKotaUnit->id],
        [
            'name' => 'SPMB Inden SD Namira Kota',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => $futureYears,
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'is_active' => true,
            'announcement_released' => false,
        ]
    );

    $this->smpSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->smpUnit->id],
        [
            'name' => 'SPMB Inden SMP Namira',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => $futureYears,
            'quota' => 100,
            'registration_fee' => 250000,
            'admission_fee_total' => 10720000,
            'min_down_payment_percentage' => 100,
            'is_active' => true,
            'announcement_released' => false,
        ]
    );

    // TK units start LOCKED for public (is_active = false)
    $this->tkKraSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->tkKraUnit->id],
        [
            'name' => 'SPMB TK Namira Kraksaan',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => $futureYears,
            'quota' => 40,
            'registration_fee' => 250000,
            'admission_fee_total' => 4500000,
            'min_down_payment_percentage' => 60,
            'is_active' => false,
            'announcement_released' => false,
        ]
    );

    $this->tkDriSetting = SpmbSetting::updateOrCreate(
        ['unit_id' => $this->tkDriUnit->id],
        [
            'name' => 'SPMB TK Namira Dringu',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => $futureYears,
            'quota' => 40,
            'registration_fee' => 250000,
            'admission_fee_total' => 4500000,
            'min_down_payment_percentage' => 60,
            'is_active' => false,
            'announcement_released' => false,
        ]
    );
});

function makeBaseApplicantPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Zayd Al Fatih',
        'nickname' => 'Zayd',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2021-05-10',
        'category' => 'eksternal_umum',
        'previous_school' => 'TK Tunas Harapan',
        'father_name' => 'Ahmad Fauzi',
        'mother_name' => 'Fatimah Zahra',
        'parent_phone' => '081234500001',
        'password' => 'rahasia123',
        'address' => 'Jl. Raya Panglima Sudirman No. 12',
        'registration_payment_proof' => UploadedFile::fake()->image('bukti.jpg', 600, 800),
    ], $overrides);
}

it('shows multi-year Inden options for SD, SD Kota, and SMP while keeping TK locked publicly on /spmb', function () {
    $res = $this->get(route('spmb.index'));
    $res->assertOk();
    $res->assertInertia(fn (Assert $page) => $page
        ->component('SPMB/Public/Index')
        ->where('tkUnlocked', false)
        ->has('sdYearOptions', 4)
        ->has('sdKotaYearOptions', 4)
        ->has('smpYearOptions', 4)
        ->where('sdYearOptions.0.value', '2027/2028')
        ->where('sdYearOptions.0.is_future_inden', false)
        ->where('sdYearOptions.1.value', '2028/2029')
        ->where('sdYearOptions.1.is_future_inden', true)
        ->where('sdYearOptions.2.value', '2029/2030')
    );
});

it('supports clean Inden deep-link routes and query string pre-selection across units', function () {
    $this->get('/spmb/daftar/sd/inden/2028-2029')
        ->assertRedirect(route('spmb.register.sd', ['ta' => '2028/2029']));

    $this->get('/spmb/daftar/sd-kota/inden/2029-2030')
        ->assertRedirect(route('spmb.register.sd-kota', ['ta' => '2029/2030']));

    $this->get('/spmb/daftar/smp/inden/2028-2029')
        ->assertRedirect(route('spmb.register.smp', ['ta' => '2028/2029']));

    $this->get(route('spmb.register.sd', ['ta' => '2028/2029']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Public/RegisterSD')
            ->where('selectedAcademicYear', '2028/2029')
            ->has('academicYearOptions', 4)
        );
});

it('registers future-year Inden applicants cleanly for SD Kraksaan, SD Kota, and SMP with year-encoded registration numbers', function () {
    // 1. SD Kraksaan Inden 2028/2029
    $resSd28 = $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Aisyah Inden 2028',
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500101',
    ]));
    $resSd28->assertSessionHasNoErrors();
    $sd28 = SpmbApplicant::where('full_name', 'Aisyah Inden 2028')->firstOrFail();
    expect($sd28->unit_id)->toBe($this->sdUnit->id)
        ->and($sd28->academic_year)->toBe('2028/2029')
        ->and($sd28->is_inden)->toBeTrue()
        ->and($sd28->registration_number)->toBe('IND-SDN-28001');

    // 2. SD Kraksaan Inden 2029/2030 (same parent WA, younger sibling)
    $resSd29 = $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Umar Inden 2029',
        'academic_year' => '2029/2030',
        'parent_phone' => '081234500101',
    ]));
    $resSd29->assertSessionHasNoErrors();
    $sd29 = SpmbApplicant::where('full_name', 'Umar Inden 2029')->firstOrFail();
    expect($sd29->academic_year)->toBe('2029/2030')
        ->and($sd29->is_inden)->toBeTrue()
        ->and($sd29->registration_number)->toBe('IND-SDN-29001');

    // 3. SD Kraksaan Current TA 2027/2028 (non-master-list child remains Jalur Umum is_inden=false)
    $resSd27 = $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Hamzah Reguler 2027',
        'academic_year' => '2027/2028',
        'parent_phone' => '081234500102',
    ]));
    $resSd27->assertSessionHasNoErrors();
    $sd27 = SpmbApplicant::where('full_name', 'Hamzah Reguler 2027')->firstOrFail();
    expect($sd27->academic_year)->toBe('2027/2028')
        ->and($sd27->is_inden)->toBeFalse()
        ->and($sd27->registration_number)->toStartWith('IND-SDN-' . date('y'));

    // 4. SD Kota (SD-02) Inden 2028/2029
    $resSdKota = $this->post(route('spmb.store.sd-kota'), makeBaseApplicantPayload([
        'full_name' => 'Bilal SD Kota 2028',
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500103',
    ]));
    $resSdKota->assertSessionHasNoErrors();
    $sdKota = SpmbApplicant::where('full_name', 'Bilal SD Kota 2028')->firstOrFail();
    expect($sdKota->unit_id)->toBe($this->sdKotaUnit->id)
        ->and($sdKota->academic_year)->toBe('2028/2029')
        ->and($sdKota->is_inden)->toBeTrue()
        ->and($sdKota->registration_number)->toBe('IND-SDK-28001');

    // 5. SMP Inden 2029/2030
    $resSmp = $this->post(route('spmb.store.smp'), makeBaseApplicantPayload([
        'full_name' => 'Rayhan SMP 2029',
        'academic_year' => '2029/2030',
        'previous_school' => 'SD Namira Kota Probolinggo',
        'category' => 'internal_sd',
        'parent_phone' => '081234500104',
    ]));
    $resSmp->assertSessionHasNoErrors();
    $smp = SpmbApplicant::where('full_name', 'Rayhan SMP 2029')->firstOrFail();
    expect($smp->unit_id)->toBe($this->smpUnit->id)
        ->and($smp->academic_year)->toBe('2029/2030')
        ->and($smp->is_inden)->toBeTrue()
        ->and($smp->registration_number)->toBe('IND-SMPN-29001');
});

it('rejects disallowed academic_year values and prevents duplicate child registration in the same unit & year', function () {
    // Disallowed future year
    $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Invalid Year Child',
        'academic_year' => '2035/2036',
        'parent_phone' => '081234500201',
    ]))->assertSessionHasErrors('academic_year');

    // When allow_future_inden is turned off, 2028/2029 must be rejected
    $this->sdSetting->update(['allow_future_inden' => false]);
    $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Blocked Future Child',
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500202',
    ]))->assertSessionHasErrors('academic_year');

    // Re-enable and verify duplicate guard scopes by (unit, academic_year, phone, full_name)
    $this->sdSetting->update(['allow_future_inden' => true]);
    $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Salma Duplikat',
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500203',
    ]))->assertSessionHasNoErrors();

    // Same child, same unit, same year -> blocked
    $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Salma Duplikat',
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500203',
    ]))->assertSessionHasErrors('parent_phone');
});

it('keeps TK locked for public until unlocked by admin in Settings, while allowing admin preview and post-unlock registration', function () {
    // 1. Public visitor is blocked from /spmb/daftar/tk while locked
    $this->get(route('spmb.register.tk'))
        ->assertRedirect(route('spmb.index'));

    $this->post(route('spmb.store.tk'), makeBaseApplicantPayload([
        'unit_id' => $this->tkKraUnit->id,
        'academic_year' => '2028/2029',
        'parent_phone' => '081234500301',
    ]))->assertRedirect(route('spmb.index'));

    expect(SpmbApplicant::where('unit_id', $this->tkKraUnit->id)->count())->toBe(0);

    // 2. Super Admin CAN preview /spmb/daftar/tk with isLockedForPublic = true
    $superAdmin = createUserWithRole('super_admin_yayasan');
    $this->actingAs($superAdmin)
        ->get(route('spmb.register.tk'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Public/RegisterTK')
            ->where('isLockedForPublic', true)
            ->has('tkUnits', 2)
        );

    // 3. Admin unlocks TK Kraksaan via Settings with custom future Inden years
    $this->actingAs($superAdmin)
        ->post(route('spmb.admin.update-settings'), [
            'unit_id' => $this->tkKraUnit->id,
            'name' => 'Pendaftaran Murid Baru TK Namira Kraksaan',
            'academic_year' => '2027/2028',
            'allow_future_inden' => true,
            'inden_academic_years' => ['2028/2029', '2029/2030'],
            'quota' => 45,
            'registration_fee' => 250000,
            'admission_fee_total' => 4500000,
            'min_down_payment_percentage' => 60,
            'bank_name' => 'Bank Jatim',
            'is_active' => true,
            'announcement_released' => false,
        ])
        ->assertSessionHasNoErrors();

    auth()->logout();

    // 4. Now /spmb shows tkUnlocked = true and public visitor can register Inden 2028/2029 for TK Kraksaan
    $this->get(route('spmb.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tkUnlocked', true));

    $this->get(route('spmb.register.tk'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Public/RegisterTK')
            ->where('isLockedForPublic', false)
            ->has('tkUnits', 1) // Only unlocked TK-KRA shown to public, TK-DRI stays hidden!
        );

    $this->post(route('spmb.store.tk'), makeBaseApplicantPayload([
        'unit_id' => $this->tkKraUnit->id,
        'full_name' => 'Hana TK Inden 2028',
        'academic_year' => '2028/2029',
        'category' => 'eksternal_umum',
        'parent_phone' => '081234500302',
    ]))->assertSessionHasNoErrors();

    $tkApp = SpmbApplicant::where('full_name', 'Hana TK Inden 2028')->firstOrFail();
    expect($tkApp->unit_id)->toBe($this->tkKraUnit->id)
        ->and($tkApp->academic_year)->toBe('2028/2029')
        ->and($tkApp->is_inden)->toBeTrue()
        ->and($tkApp->registration_number)->toBe('IND-TKK-28001');
});

it('guards future-year Inden portal announcements, prints accurate PDF academic year, and filters admin index & CSV export by academic_year', function () {
    // SD setting has announcement_released = true for 2027/2028
    $this->post(route('spmb.store.sd'), makeBaseApplicantPayload([
        'full_name' => 'Safa Inden 2029',
        'academic_year' => '2029/2030',
        'parent_phone' => '081234500401',
    ]))->assertSessionHasNoErrors();

    $futureApp = SpmbApplicant::where('full_name', 'Safa Inden 2029')->firstOrFail();

    // Dashboard for 2029/2030 Inden must have isFutureInden = true & isAnnouncementReleased = false
    $this->get(route('spmb.applicant.dashboard', ['applicant_id' => $futureApp->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Applicant/Dashboard')
            ->where('isFutureInden', true)
            ->where('isAnnouncementReleased', false)
            ->where('applicant.academic_year', '2029/2030')
        );

    // Participant card blade renders applicant's TA 2029/2030 (not setting's 2027/2028)
    $cardHtml = view('pdf.spmb.kartu-peserta', [
        'applicant' => $futureApp->load('unit'),
        'setting' => $this->sdSetting,
    ])->render();
    expect($cardHtml)->toContain('TA 2029/2030')
        ->and($cardHtml)->toContain('Menunggu Jadwal Seleksi TA 2029/2030');

    // Create a 2027/2028 applicant to verify Admin filtering & CSV export
    SpmbApplicant::create([
        'unit_id' => $this->sdUnit->id,
        'academic_year' => '2027/2028',
        'registration_number' => 'IND-SDN-26901',
        'full_name' => 'Siswa Reguler 2027',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2020-01-01',
        'parent_phone' => '081234500402',
        'address' => 'Kraksaan',
        'status' => 'submitted',
    ]);

    auth()->logout();
    session()->flush();
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);

    $superAdmin = createUserWithRole('super_admin_yayasan');

    // Admin Index filtered to 2029/2030 returns ONLY Safa Inden 2029
    $this->actingAs($superAdmin)
        ->get(route('spmb.admin.index', ['unit_id' => $this->sdUnit->id, 'academic_year' => '2029/2030']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SPMB/Admin/Index')
            ->where('stats.total', 1)
            ->has('applicants.data', 1)
            ->where('applicants.data.0.full_name', 'Safa Inden 2029')
        );

    // CSV Export filtered to 2029/2030 includes Tahun Ajaran column and ONLY 2029/2030 rows
    $csvRes = $this->actingAs($superAdmin)
        ->get(route('spmb.admin.export', ['unit_id' => $this->sdUnit->id, 'academic_year' => '2029/2030']));
    $csvRes->assertOk();
    $csv = $csvRes->streamedContent();
    expect($csv)->toContain('Tahun Ajaran')
        ->and($csv)->toContain('2029/2030')
        ->and($csv)->toContain('Safa Inden 2029')
        ->and($csv)->not->toContain('Siswa Reguler 2027');

    // Admin can update applicant academic_year via update-biodata
    $this->actingAs($superAdmin)
        ->post(route('spmb.admin.update-biodata', $futureApp->id), [
            'full_name' => $futureApp->full_name,
            'gender' => 'L',
            'birth_place' => 'Probolinggo',
            'birth_date' => '2021-05-10',
            'academic_year' => '2030/2031',
            'is_inden' => true,
            'category' => 'eksternal_umum',
            'father_name' => 'Ahmad Fauzi',
            'parent_phone' => $futureApp->parent_phone,
            'address' => 'Jl. Raya Panglima Sudirman No. 12',
        ])
        ->assertSessionHasNoErrors();

    expect($futureApp->fresh()->academic_year)->toBe('2030/2031');
});
