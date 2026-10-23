<?php

use App\Models\User;
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
    
    // Ensure roles exist
    createRole('panitia_spmb');
    createRole('super_admin_yayasan');
    createRole('admin_unit');
    createRole('orang_tua');

    // Create SD and SMP units
    $this->sdUnit = Unit::firstOrCreate(
        ['code' => 'SD'],
        ['name' => 'SD Namira', 'level' => 'SD', 'category' => 'formal']
    );

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'SMP', 'category' => 'formal']
    );

    // Create Settings for both units
    $this->sdSetting = SpmbSetting::firstOrCreate(
        ['unit_id' => $this->sdUnit->id],
        [
            'name' => 'Pendaftaran Jalur Inden SD Namira',
            'academic_year' => '2026/2027',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'is_active' => true,
        ]
    );

    $this->smpSetting = SpmbSetting::firstOrCreate(
        ['unit_id' => $this->smpUnit->id],
        [
            'name' => 'Pendaftaran Jalur Inden SMP Namira',
            'academic_year' => '2026/2027',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 9000000,
            'min_down_payment_percentage' => 60,
            'selection_date' => '2026-11-07',
            'announcement_date' => '2026-11-13',
            'announcement_released' => false,
            'is_active' => true,
        ]
    );
});

it('can view SPMB SMP registration form publicly', function () {
    $response = $this->get(route('spmb.register.smp'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SPMB/Public/RegisterSMP')
        ->has('unit')
        ->has('setting')
    );
});

it('allows registration for SMP with only biodata and payment proof without supporting documents', function () {
    $payload = [
        'full_name' => 'Ahmad Fadhil Prasetyo',
        'nickname' => 'Fadhil',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-05-12',
        'nik' => '3574011205140001',
        'previous_school' => 'SD Negeri Sukabumi 2',
        'category' => 'external',
        'father_name' => 'Budi Prasetyo',
        'father_job' => 'Wiraswasta',
        'mother_name' => 'Siti Aminah',
        'mother_job' => 'PNS',
        'parent_phone' => '081234567890',
        'password' => 'secret123',
        'address' => 'Jl. Panglima Sudirman No. 45, Probolinggo',
        'special_notes' => 'Hafal Juz 30',
        'child_order' => 1,
        'total_siblings' => 2,
        'registration_payment_proof' => UploadedFile::fake()->image('bukti_transfer.jpg', 600, 800),
        // family_card, birth_cert, parent_id_card, photo are deliberately OMITTED
    ];

    $response = $this->post(route('spmb.store.smp'), $payload);

    $response->assertRedirect(route('spmb.applicant.dashboard'));
    $response->assertSessionHas('registration_number');

    // Assert applicant recorded in database with unit_id SMP and is_inden = true
    $this->assertDatabaseHas('spmb_applicants', [
        'unit_id' => $this->smpUnit->id,
        'full_name' => 'Ahmad Fadhil Prasetyo',
        'parent_phone' => '081234567890',
        'is_inden' => true,
        'status' => 'submitted',
    ]);

    $applicant = SpmbApplicant::where('full_name', 'Ahmad Fadhil Prasetyo')->first();
    expect($applicant)->not->toBeNull();
    expect($applicant->registration_number)->toStartWith('IND-SMPN-');
    expect($applicant->registration_payment_proof)->not->toBeNull();
    expect($applicant->family_card_path)->toBeNull();
    expect($applicant->birth_cert_path)->toBeNull();

    // Assert parent User account created automatically
    $this->assertDatabaseHas('users', [
        'phone' => '081234567890',
    ]);
});

it('fails SMP registration if payment proof is missing', function () {
    $payload = [
        'full_name' => 'Ahmad Fadhil Prasetyo',
        'nickname' => 'Fadhil',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-05-12',
        'nik' => '3574011205140001',
        'previous_school' => 'SD Negeri Sukabumi 2',
        'father_name' => 'Budi Prasetyo',
        'mother_name' => 'Siti Aminah',
        'parent_phone' => '081234567890',
        'password' => 'secret123',
        'address' => 'Jl. Panglima Sudirman No. 45',
        // registration_payment_proof is missing
    ];

    $response = $this->post(route('spmb.store.smp'), $payload);

    $response->assertSessionHasErrors(['registration_payment_proof']);
    $this->assertDatabaseMissing('spmb_applicants', [
        'full_name' => 'Ahmad Fadhil Prasetyo',
    ]);
});

it('requires all 4 supporting documents for SD registration as regression protection', function () {
    $payload = [
        'full_name' => 'Nabila Putri Zahra',
        'gender' => 'P',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2020-03-10',
        'nik' => '3574011003200002',
        'previous_school' => 'TK Namira',
        'father_name' => 'Hendra',
        'mother_name' => 'Dewi',
        'parent_phone' => '082333444555',
        'password' => 'secret123',
        'address' => 'Jl. Mastrip No. 12',
        'registration_payment_proof' => UploadedFile::fake()->image('bukti_transfer.jpg'),
        // family_card, birth_cert, parent_id_card, photo are NOT provided
    ];

    $response = $this->post(route('spmb.store.sd'), $payload);

    // SD route MUST require all 4 supporting documents
    $response->assertSessionHasErrors(['family_card', 'birth_cert', 'parent_id_card', 'photo']);
    $this->assertDatabaseMissing('spmb_applicants', [
        'full_name' => 'Nabila Putri Zahra',
    ]);
});

it('allows panitia to record academic scores and observations for SMP applicant', function () {
    $panitia = createUserWithRole('panitia_spmb', $this->smpUnit->id);

    $applicant = SpmbApplicant::create([
        'unit_id' => $this->smpUnit->id,
        'registration_number' => 'IND-SMPN-26001',
        'full_name' => 'Muhammad Rifqi',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-01-01',
        'father_name' => 'Rahmat',
        'mother_name' => 'Rina',
        'parent_phone' => '081234000111',
        'address' => 'Jl. Merdeka No. 1',
        'status' => 'scheduled',
        'is_inden' => true,
    ]);

    $response = $this->actingAs($panitia)
        ->post(route('spmb.admin.save-evaluation', $applicant->id), [
            'evaluation_result' => 'accepted',
            'evaluation_notes' => 'Sangat direkomendasikan masuk kelas unggulan',
            'academic_score' => 91.5,
            'wawancara_notes' => 'Karakter santun, motivasi belajar tinggi',
            'mengaji_notes' => 'Lancar tilawah surat Al-Baqarah, makhraj fasih',
        ]);

    $response->assertRedirect();
    $applicant->refresh();

    expect($applicant->evaluation_result)->toBe('accepted');
    expect($applicant->status)->toBe('accepted');
    expect($applicant->academic_score_details)->not->toBeNull();
    expect((float)$applicant->academic_score_details['academic_score'])->toEqual(91.5);
    expect($applicant->academic_score_details['wawancara'])->toBe('Karakter santun, motivasi belajar tinggi');
    expect($applicant->academic_score_details['mengaji'])->toBe('Lancar tilawah surat Al-Baqarah, makhraj fasih');
});

it('gates announcement results and letter before official release date', function () {
    $parent = User::create([
        'name' => 'Budi Prasetyo',
        'email' => 'budi.prasetyo@example.com',
        'phone' => '081234567890',
        'password' => bcrypt('password'),
    ]);

    $applicant = SpmbApplicant::create([
        'user_id' => $parent->id,
        'unit_id' => $this->smpUnit->id,
        'registration_number' => 'IND-SMPN-26002',
        'full_name' => 'Ahmad Fadhil Prasetyo',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-05-12',
        'father_name' => 'Budi Prasetyo',
        'mother_name' => 'Siti Aminah',
        'parent_phone' => '081234567890',
        'address' => 'Jl. Panglima Sudirman No. 45',
        'status' => 'accepted',
        'evaluation_result' => 'accepted',
        'is_inden' => true,
        'academic_score_details' => [
            'bahasa_indonesia' => 90,
            'matematika' => 85,
        ],
    ]);

    // Setting has announcement_date in future and announcement_released = false
    $this->smpSetting->update([
        'announcement_date' => now()->addDays(5)->toDateString(),
        'announcement_released' => false,
    ]);

    // 1. Dashboard: isAnnouncementReleased must be false, internal scores hidden
    $response = $this->actingAs($parent)->get(route('spmb.applicant.dashboard'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SPMB/Applicant/Dashboard')
        ->where('isAnnouncementReleased', false)
        ->missing('applicant.academic_score_details')
    );

    // 2. Letter of acceptance (SKL): blocked with 403 Forbidden before release
    $sklResponse = $this->actingAs($parent)->get(route('spmb.applicant.print-skl', $applicant->id));
    $sklResponse->assertForbidden();

    // 3. Now release announcement
    $this->smpSetting->update(['announcement_released' => true]);

    $responseAfterRelease = $this->actingAs($parent)->get(route('spmb.applicant.dashboard'));
    $responseAfterRelease->assertInertia(fn (Assert $page) => $page
        ->where('isAnnouncementReleased', true)
    );

    $sklResponseAfterRelease = $this->actingAs($parent)->get(route('spmb.applicant.print-skl', $applicant->id));
    $sklResponseAfterRelease->assertOk();
});

it('enforces unit scoping so SD panitia cannot evaluate SMP applicant', function () {
    $sdPanitia = createUserWithRole('panitia_spmb', $this->sdUnit->id);

    $smpApplicant = SpmbApplicant::create([
        'unit_id' => $this->smpUnit->id,
        'registration_number' => 'IND-SMPN-26003',
        'full_name' => 'Siswa SMP Target',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-02-02',
        'father_name' => 'Ayah',
        'mother_name' => 'Ibu',
        'parent_phone' => '081234999888',
        'address' => 'Jl. Kenanga No. 8',
        'status' => 'scheduled',
        'is_inden' => true,
    ]);

    // SD panitia attempts to evaluate SMP applicant
    $response = $this->actingAs($sdPanitia)
        ->post(route('spmb.admin.save-evaluation', $smpApplicant->id), [
            'evaluation_result' => 'accepted',
            'evaluation_notes' => 'Illegal attempt across units',
        ]);

    $response->assertForbidden();
});

it('tracks installment Termin 1 and settlement Termin 2 proofs separately without losing history', function () {
    $parent = User::factory()->create(['phone' => '081299990001']);
    $parent->assignRole('orang_tua');

    $applicant = SpmbApplicant::create([
        'unit_id' => $this->smpUnit->id,
        'user_id' => $parent->id,
        'registration_number' => 'IND-SMPN-26004',
        'full_name' => 'Calon Siswa Angsuran',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-04-04',
        'father_name' => 'Ayah Siswa',
        'mother_name' => 'Ibu Siswa',
        'parent_phone' => '081299990001',
        'address' => 'Jl. Pahlawan No. 10',
        'status' => 'accepted',
        'total_admission_fee' => 8500000,
        'min_down_payment' => 5100000,
        'is_inden' => true,
    ]);

    // 1. Upload Termin 1 (DP 60%)
    $termin1File = UploadedFile::fake()->image('bukti_termin_1.jpg');
    $res1 = $this->actingAs($parent)->post(route('spmb.applicant.upload-proof', $applicant->id), [
        'payment_proof' => $termin1File,
        'payment_type' => 'termin_1',
    ]);
    $res1->assertSessionHasNoErrors();

    $applicant->refresh();
    expect($applicant->re_registration_payment_proof)->not->toBeNull();
    $termin1Path = $applicant->re_registration_payment_proof;

    // 2. Admin verifies Termin 1
    $panitia = createUserWithRole('panitia_spmb', $this->smpUnit->id);
    $verifyRes = $this->actingAs($panitia)->post(route('spmb.admin.verify-admission-payment', $applicant->id), [
        'paid_admission_amount' => 5100000,
        'admission_payment_status' => 'partial',
    ]);
    $verifyRes->assertSessionHasNoErrors();

    $applicant->refresh();
    expect($applicant->status)->toBe('partial_paid')
        ->and($applicant->paid_admission_amount)->toEqual(5100000);

    // 3. Upload Termin 2 (Pelunasan 100%)
    $termin2File = UploadedFile::fake()->image('bukti_pelunasan.jpg');
    $res2 = $this->actingAs($parent)->post(route('spmb.applicant.upload-proof', $applicant->id), [
        'payment_proof' => $termin2File,
        'payment_type' => 'termin_2',
    ]);
    $res2->assertSessionHasNoErrors();

    $applicant->refresh();
    // Termin 1 proof must STILL EXIST and NOT be overwritten!
    expect($applicant->re_registration_payment_proof)->toBe($termin1Path)
        ->and($applicant->settlement_payment_proof)->not->toBeNull()
        ->and($applicant->settlement_payment_proof)->not->toBe($termin1Path);

    // 4. Admin verifies Pelunasan
    $settleRes = $this->actingAs($panitia)->post(route('spmb.admin.verify-admission-payment', $applicant->id), [
        'paid_admission_amount' => 8500000,
        'admission_payment_status' => 'paid',
    ]);
    $settleRes->assertSessionHasNoErrors();

    $applicant->refresh();
    expect($applicant->status)->toBe('fully_paid')
        ->and($applicant->paid_admission_amount)->toEqual(8500000)
        ->and($applicant->settlement_payment_verified_at)->not->toBeNull();
});

it('properly distinguishes internal category as Alumni SD Namira for SMP and Alumni TK Namira for SD', function () {
    $smpApplicant = SpmbApplicant::create([
        'unit_id' => $this->smpUnit->id,
        'registration_number' => 'IND-SMPN-26099',
        'full_name' => 'Santri SMP Internal',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-01-01',
        'parent_phone' => '081234000999',
        'address' => 'Probolinggo',
        'category' => 'internal_sd',
        'previous_school' => 'SD Namira Kota Probolinggo',
    ]);

    $sdApplicant = SpmbApplicant::create([
        'unit_id' => $this->sdUnit->id,
        'registration_number' => 'IND-SDN-26099',
        'full_name' => 'Santri SD Internal',
        'gender' => 'P',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2019-01-01',
        'parent_phone' => '081234000888',
        'address' => 'Probolinggo',
        'category' => 'internal_tk',
        'previous_school' => 'TK Namira Kota Probolinggo',
    ]);

    expect($smpApplicant->category_label)->toBe('Alumni SD Namira')
        ->and($sdApplicant->category_label)->toBe('Alumni TK Namira');

    // Test Admin index statistics counting internal applicants for SMP
    $panitia = createUserWithRole('panitia_spmb', $this->smpUnit->id);
    $response = $this->actingAs($panitia)->get(route('spmb.admin.index', ['unit' => 'smp']));
    $response->assertInertia(fn ($page) => $page
        ->component('SPMB/Admin/Index')
        ->where('stats.internal_tk', 1)
        ->where('stats.internal', 1)
    );
});

it('allows super admin to permanently delete an applicant and clears stored files', function () {
    $superAdmin = createUserWithRole('super_admin_yayasan');
    
    // Create test applicant with uploaded file
    $filePath = 'spmb/smp-namira/IND-SMPN-26999/test_dokumen.jpg';
    Storage::disk('public')->put($filePath, 'sample file content');

    $applicant = SpmbApplicant::create([
        'unit_id' => $this->smpUnit->id,
        'registration_number' => 'IND-SMPN-26999',
        'full_name' => 'Calon Siswa Dihapus',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '2014-01-01',
        'parent_phone' => '081234999888',
        'address' => 'Probolinggo',
        'category' => 'eksternal_umum',
        'photo_path' => $filePath,
    ]);

    expect(Storage::disk('public')->exists($filePath))->toBeTrue();

    $response = $this->actingAs($superAdmin)->delete(route('spmb.admin.destroy', $applicant->id));

    $response->assertRedirect(route('spmb.admin.index'));
    expect(SpmbApplicant::find($applicant->id))->toBeNull()
        ->and(Storage::disk('public')->exists($filePath))->toBeFalse();
});


