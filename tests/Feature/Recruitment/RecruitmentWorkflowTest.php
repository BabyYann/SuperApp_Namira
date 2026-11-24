<?php

use App\Models\JobApplicant;
use App\Models\JobVacancy;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    foreach ([
        'super_admin_yayasan',
        'admin_yayasan',
        'pembina_yayasan',
        'pengawas_yayasan',
        'humas_yayasan',
        'staff_yayasan',
        'humas_unit',
        'admin_unit',
        'kepala_sekolah',
        'teacher',
        'staff_unit',
    ] as $roleName) {
        createRole($roleName);
    }

    $this->sdUnit = Unit::firstOrCreate(
        ['code' => 'SD'],
        ['name' => 'SD Namira Kraksaan', 'level' => 'Nasional', 'category' => 'SD']
    );

    $this->smpUnit = Unit::firstOrCreate(
        ['code' => 'SMP'],
        ['name' => 'SMP Namira', 'level' => 'Nasional', 'category' => 'SMP']
    );
});

function actingAsScopedRole(string $role, ?int $unitId = null): \App\Models\User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($unitId);
    $user = createUserWithRole($role, $unitId);

    return $user;
}

it('allows humas_unit and kepala_sekolah to publish poster-based vacancies for their own unit and blocks cross-unit mutations', function () {
    $humasSd = actingAsScopedRole('humas_unit', $this->sdUnit->id);

    // 1. Humas SD can view vacancies page and publish a poster-only vacancy (no long description required)
    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->get(route('yayasan.job-vacancies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Yayasan/Recruitment/Vacancies')
            ->where('isUnitScoped', true)
            ->where('canWrite', true)
            ->where('activeUnitId', $this->sdUnit->id)
        );

    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->post(route('yayasan.job-vacancies.store'), [
            'title' => 'Guru PJOK SD Namira Kraksaan',
            'unit_id' => $this->smpUnit->id, // Attempting cross-unit spoof MUST be forced to active SD unit!
            'poster' => UploadedFile::fake()->image('poster-sd.jpg', 900, 1400),
            'category' => 'teacher',
            'type' => 'full_time',
            'quota' => 2,
            'deadline' => '2026-11-28',
            'contact_whatsapp' => '082233232257',
            'placement_address' => 'SD Namira Kraksaan, Jl. Raya Kraksaan No. 100',
            'status' => 'open',
        ])
        ->assertSessionHasNoErrors();

    $sdVacancy = JobVacancy::where('title', 'Guru PJOK SD Namira Kraksaan')->firstOrFail();
    expect($sdVacancy->unit_id)->toBe($this->sdUnit->id)
        ->and($sdVacancy->poster_path)->not->toBeNull()
        ->and($sdVacancy->poster_url)->toContain('/storage/recruitment/posters/')
        ->and($sdVacancy->contact_whatsapp)->toBe('082233232257')
        ->and($sdVacancy->category_label)->toBe('Tenaga Pendidik (Guru)')
        ->and($sdVacancy->type_label)->toBe('Penuh Waktu (Full Time)');

    Storage::disk('public')->assertExists($sdVacancy->poster_path);

    // 2. Toggle status works cleanly
    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->patch(route('yayasan.job-vacancies.toggle-status', $sdVacancy->id))
        ->assertSessionHasNoErrors();

    expect($sdVacancy->fresh()->status)->toBe('closed');

    // Reopen via toggle
    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->patch(route('yayasan.job-vacancies.toggle-status', $sdVacancy->id))
        ->assertSessionHasNoErrors();

    expect($sdVacancy->fresh()->status)->toBe('open');

    // 3. Kepala Sekolah SD can update the SD vacancy (including replacing poster)
    $kepsekSd = actingAsScopedRole('kepala_sekolah', $this->sdUnit->id);
    $oldPoster = $sdVacancy->poster_path;

    $this->actingAs($kepsekSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->post(route('yayasan.job-vacancies.update', $sdVacancy->id), [
            '_method' => 'put',
            'title' => 'Guru PJOK SD Namira Kraksaan (Revisi)',
            'poster' => UploadedFile::fake()->image('poster-sd-v2.jpg', 900, 1400),
            'category' => 'teacher',
            'type' => 'full_time',
            'quota' => 3,
            'deadline' => '2026-11-28',
            'contact_whatsapp' => '082233232257',
            'placement_address' => 'SD Namira Kraksaan, Jl. Raya Kraksaan No. 100',
            'status' => 'open',
        ])
        ->assertSessionHasNoErrors();

    $sdVacancy->refresh();
    expect($sdVacancy->title)->toBe('Guru PJOK SD Namira Kraksaan (Revisi)')
        ->and($sdVacancy->poster_path)->not->toBe($oldPoster);
    Storage::disk('public')->assertMissing($oldPoster);
    Storage::disk('public')->assertExists($sdVacancy->poster_path);

    // 4. Cross-unit protection: Humas SD CANNOT edit, toggle, or delete SMP vacancy
    $smpVacancy = JobVacancy::create([
        'unit_id' => $this->smpUnit->id,
        'title' => 'Guru Tahfidz SMP Namira',
        'category' => 'teacher',
        'type' => 'full_time',
        'quota' => 1,
        'description' => 'Mengajar tahfidz',
        'requirements' => 'Hafal 15 Juz',
        'status' => 'open',
    ]);

    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->patch(route('yayasan.job-vacancies.toggle-status', $smpVacancy->id))
        ->assertForbidden();

    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->delete(route('yayasan.job-vacancies.destroy', $smpVacancy->id))
        ->assertForbidden();
});

it('allows humas_yayasan global write and keeps pembina_yayasan read-only', function () {
    $humasYayasan = actingAsScopedRole('humas_yayasan', null);

    $this->actingAs($humasYayasan)
        ->get(route('yayasan.job-vacancies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isUnitScoped', false)
            ->where('canWrite', true)
        );

    $this->actingAs($humasYayasan)
        ->post(route('yayasan.job-vacancies.store'), [
            'title' => 'Desainer Grafis Yayasan',
            'unit_id' => null,
            'category' => 'staff',
            'type' => 'full_time',
            'quota' => 1,
            'description' => 'Membuat konten visual Yayasan Namira',
            'requirements' => 'Menguasai CorelDraw/Photoshop/Figma',
            'status' => 'open',
        ])
        ->assertSessionHasNoErrors();

    expect(JobVacancy::where('title', 'Desainer Grafis Yayasan')->exists())->toBeTrue();

    // Pembina is read-only
    auth()->logout();
    session()->flush();
    $pembina = actingAsScopedRole('pembina_yayasan', null);

    $this->actingAs($pembina)
        ->get(route('yayasan.job-vacancies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canWrite', false));

    $this->actingAs($pembina)
        ->post(route('yayasan.job-vacancies.store'), [
            'title' => 'Blocked By ReadOnly',
            'category' => 'staff',
            'type' => 'full_time',
            'quota' => 1,
            'description' => 'x',
            'requirements' => 'y',
            'status' => 'open',
        ])
        ->assertForbidden();
});

it('renders public career portal with poster & appended labels, accepts full applicant documents, and exposes valid /storage URLs in admin review', function () {
    $posterPath = UploadedFile::fake()->image('open-rec.jpg', 900, 1400)->store('recruitment/posters', 'public');

    $vacancy = JobVacancy::create([
        'unit_id' => $this->sdUnit->id,
        'title' => 'Guru PJOK SD Namira',
        'poster_path' => $posterPath,
        'category' => 'teacher',
        'type' => 'full_time',
        'quota' => 1,
        'deadline' => '2026-11-28',
        'contact_whatsapp' => '082233232257',
        'placement_address' => 'SD Namira Kraksaan, Jl. Raya Kraksaan No. 100',
        'description' => 'Mengajar PJOK SD',
        'requirements' => 'S1 PJOK',
        'status' => 'open',
    ]);

    // 1. Public catalog renders appended category_label, type_label, poster_url, and institutional stats/media
    $this->get(route('careers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/CareerIndex')
            ->where('vacancies.data.0.category_label', 'Tenaga Pendidik (Guru)')
            ->where('vacancies.data.0.type_label', 'Penuh Waktu (Full Time)')
            ->where('vacancies.data.0.poster_url', Storage::url($posterPath))
            ->has('stats.units_count')
            ->has('stats.educators_count')
            ->has('stats.students_count')
            ->where('stats.open_vacancies_count', 1)
            ->has('campusMedia')
            ->has('testimonials')
        );

    // 2. Public detail renders poster + unit contact
    $this->get(route('careers.show', $vacancy->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/CareerDetail')
            ->where('vacancy.poster_url', Storage::url($posterPath))
            ->where('vacancy.contact_whatsapp', '082233232257')
        );

    // 3. Applicant submits full set of documents (CV, Surat Lamaran, Ijazah, Surat Sehat, KK, KTP, Foto 3x4)
    $this->post(route('careers.apply', $vacancy->slug), [
        'name' => 'Budi Santoso, S.Pd',
        'email' => 'budi.pjok@example.com',
        'phone' => '081234567890',
        'gender' => 'L',
        'birth_place' => 'Probolinggo',
        'birth_date' => '1997-04-12',
        'address' => 'Jl. Panglima Sudirman Kraksaan',
        'last_education' => 'S1',
        'major' => 'Pendidikan Jasmani Olahraga dan Kesehatan',
        'institution' => 'Universitas Negeri Malang',
        'gpa' => '3.78',
        'cv' => UploadedFile::fake()->create('cv-budi.pdf', 120, 'application/pdf'),
        'cover_letter' => UploadedFile::fake()->create('lamaran-budi.pdf', 100, 'application/pdf'),
        'certificate' => UploadedFile::fake()->create('ijazah-budi.pdf', 100, 'application/pdf'),
        'health_cert' => UploadedFile::fake()->image('surat-sehat.jpg', 600, 800),
        'family_card' => UploadedFile::fake()->image('kk-budi.jpg', 800, 600),
        'ktp' => UploadedFile::fake()->image('ktp-budi.jpg', 800, 500),
        'photo' => UploadedFile::fake()->image('foto-3x4.jpg', 300, 400),
        'notes' => 'Berpengalaman melatih futsal & atletik SD.',
    ])->assertSessionHasNoErrors();

    $applicant = JobApplicant::where('email', 'budi.pjok@example.com')->firstOrFail();
    expect($applicant->status_label)->toBe('Menunggu Seleksi')
        ->and($applicant->cv_url)->toStartWith('/storage/recruitment/cv/')
        ->and($applicant->health_cert_url)->toStartWith('/storage/recruitment/health_certs/')
        ->and($applicant->family_card_url)->toStartWith('/storage/recruitment/family_cards/')
        ->and($applicant->photo_url)->toStartWith('/storage/recruitment/photos/');

    // 4. Humas SD can view & update selection status + notes for SD applicant, but CANNOT convert/delete
    $humasSd = actingAsScopedRole('humas_unit', $this->sdUnit->id);
    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->get(route('yayasan.applicants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Yayasan/Recruitment/Applicants')
            ->where('canReviewApplicants', true)
            ->where('canManageApplicants', false)
            ->where('applicants.data.0.status_label', 'Menunggu Seleksi')
            ->where('applicants.data.0.cv_url', $applicant->cv_url)
            ->where('applicants.data.0.health_cert_url', $applicant->health_cert_url)
        );

    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->put(route('yayasan.applicants.update-status', $applicant->id), [
            'selection_status' => 'interview',
            'selection_notes' => 'Jadwal microteaching hari Jumat pukul 09.00 WIB',
        ])
        ->assertSessionHasNoErrors();

    expect($applicant->fresh()->selection_status)->toBe('interview')
        ->and($applicant->fresh()->status_label)->toBe('Undangan Wawancara')
        ->and($applicant->fresh()->selection_notes)->toBe('Jadwal microteaching hari Jumat pukul 09.00 WIB');

    $this->actingAs($humasSd)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->delete(route('yayasan.applicants.destroy', $applicant->id))
        ->assertForbidden();
});
