<?php

use App\Models\User;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    $this->sdSetting = SpmbSetting::firstOrCreate(
        ['unit_id' => $this->sdUnit->id],
        [
            'name' => 'SPMB SD',
            'academic_year' => '2026/2027',
            'quota' => 60,
            'registration_fee' => 250000,
            'admission_fee_total' => 8500000,
            'min_down_payment_percentage' => 60,
            'is_active' => true,
        ]
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

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin_yayasan');
});

function createSpmbApplicantForVa(Unit $unit, array $overrides = []): SpmbApplicant
{
    static $seq = 1;
    $seq++;

    $parent = User::factory()->create();
    $parent->assignRole('orang_tua');

    return SpmbApplicant::create(array_merge([
        'registration_number' => strtoupper($unit->code).'-2026-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'unit_id' => $unit->id,
        'user_id' => $parent->id,
        'category' => 'eksternal_umum',
        'status' => 'accepted',
        'full_name' => 'Calon Siswa '.$seq,
        'gender' => 'L',
        'parent_phone' => '081234567'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
    ], $overrides));
}

function makeTempXlsx(array $headers, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($headers as $colIdx => $label) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
        $sheet->setCellValue("{$col}1", $label);
    }

    foreach ($rows as $rowOffset => $row) {
        $r = $rowOffset + 2;
        foreach ($row as $colIdx => $value) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValueExplicit("{$col}{$r}", (string) $value, DataType::TYPE_STRING);
        }
    }

    $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'va_test_'.uniqid('', true).'.xlsx';
    (new Xlsx($spreadsheet))->save($tmpPath);

    return new UploadedFile($tmpPath, 'va_import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('downloads pre-filled xlsx VA template respecting unit payment policy and filters', function () {
    $acceptedWithoutVa = createSpmbApplicantForVa($this->smpUnit, [
        'registration_number' => 'IND-SMPN-26101',
        'full_name' => 'Siswa Lulus Tanpa VA',
        'status' => 'accepted',
        'virtual_account_number' => null,
    ]);
    $acceptedWithVa = createSpmbApplicantForVa($this->smpUnit, [
        'registration_number' => 'IND-SMPN-26102',
        'full_name' => 'Siswa Sudah Ada VA',
        'status' => 'accepted',
        'virtual_account_number' => '9881029100889902',
    ]);
    $submittedApplicant = createSpmbApplicantForVa($this->smpUnit, [
        'registration_number' => 'IND-SMPN-26103',
        'full_name' => 'Siswa Belum Lulus',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->superAdmin)->get(route('spmb.admin.va-template', [
        'unit_id' => $this->smpUnit->id,
        'only_accepted' => 1,
        'include_existing' => 0,
    ]));

    $response->assertOk();
    $binary = $response->streamedContent();
    $tmpRead = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tpl_'.uniqid('', true).'.xlsx';
    file_put_contents($tmpRead, $binary);

    $loaded = IOFactory::load($tmpRead);
    $sheet = $loaded->getActiveSheet();
    $data = array_values(array_filter(
        $sheet->toArray(),
        fn ($row) => trim((string) ($row[0] ?? '')) !== ''
    ));

    // SMP is 100% full payment -> 7 columns (no DP 60% column)
    expect($data[0])->toHaveCount(7);
    expect($data[0][4])->toBe('No. Virtual Account');
    expect($data[0][6])->toContain('Lunas 100%');
    // Only acceptedWithoutVa included
    expect($data)->toHaveCount(2);
    expect($data[1][0])->toBe($acceptedWithoutVa->registration_number);
    expect((float) str_replace(',', '', (string) $data[1][5]))->toBe(10720000.0);

    @unlink($tmpRead);
});

it('previews VA xlsx accurately with new, overwrite, skip, scientific notation error, and unknown reg error', function () {
    $appNew = createSpmbApplicantForVa($this->sdUnit, [
        'registration_number' => 'IND-SDN-26001',
        'full_name' => 'Budi Santoso',
        'virtual_account_number' => null,
    ]);
    $appOverwrite = createSpmbApplicantForVa($this->sdUnit, [
        'registration_number' => 'IND-SDN-26002',
        'full_name' => 'Siti Aminah',
        'virtual_account_number' => '9880000000000001',
    ]);
    $appBlank = createSpmbApplicantForVa($this->sdUnit, [
        'registration_number' => 'IND-SDN-26003',
        'full_name' => 'Andi Wijaya',
        'virtual_account_number' => null,
    ]);

    $file = makeTempXlsx(
        [
            'No. Registrasi',
            'Nama Calon Siswa',
            'No. Virtual Account',
            'Total Biaya Masuk (Rp)',
            'Batas Bayar DP 60% (YYYY-MM-DD)',
            'Batas Bayar Lunas 100% (YYYY-MM-DD)',
        ],
        [
            [$appNew->registration_number, $appNew->full_name, '9881029100889901', '8500000', '', '2026-12-30'],
            [$appOverwrite->registration_number, $appOverwrite->full_name, '9881029100889902', '', '', ''],
            [$appBlank->registration_number, $appBlank->full_name, '', '', '', ''],
            ['IND-SDN-26999', 'Tidak Terdaftar', '9881029100889903', '', '', ''],
            [$appNew->registration_number, $appNew->full_name, '9.88103E+15', '', '', ''],
        ]
    );

    $response = $this->actingAs($this->superAdmin)->postJson(route('spmb.admin.va-import.preview'), [
        'unit_id' => $this->sdUnit->id,
        'file' => $file,
        'global_dp_deadline' => '2026-12-10',
        'global_full_deadline' => '2026-12-20',
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.new_count', 1)
        ->assertJsonPath('summary.overwrite_count', 1)
        ->assertJsonPath('summary.skipped_count', 1)
        ->assertJsonPath('summary.error_count', 2)
        ->assertJsonPath('rows.0.action', 'new')
        ->assertJsonPath('rows.0.down_payment_deadline', '2026-12-10')
        ->assertJsonPath('rows.0.full_payment_deadline', '2026-12-30') // row date overrides global
        ->assertJsonPath('rows.1.action', 'overwrite')
        ->assertJsonPath('rows.1.full_payment_deadline', '2026-12-20') // uses global date
        ->assertJsonPath('rows.2.action', 'skip')
        ->assertJsonPath('rows.3.action', 'error')
        ->assertJsonPath('rows.4.action', 'error');

    @unlink($file->getPathname());
});

it('commits bulk VA import atomically and respects 100% policy on SMP vs 60% DP on SD', function () {
    $smpApp = createSpmbApplicantForVa($this->smpUnit, [
        'registration_number' => 'IND-SMPN-26201',
        'virtual_account_number' => null,
    ]);
    $smpExisting = createSpmbApplicantForVa($this->smpUnit, [
        'registration_number' => 'IND-SMPN-26202',
        'virtual_account_number' => '9881111111111111',
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('spmb.admin.va-import.commit'), [
        'unit_id' => $this->smpUnit->id,
        'skip_existing' => true,
        'rows' => [
            [
                'registration_number' => $smpApp->registration_number,
                'new_va' => '9882222222222222',
                'total_admission_fee' => 10720000,
                'down_payment_deadline' => '2026-12-01', // should be ignored/null for SMP 100%
                'full_payment_deadline' => '2026-12-25',
            ],
            [
                'registration_number' => $smpExisting->registration_number,
                'new_va' => '9883333333333333',
                'total_admission_fee' => 10720000,
                'down_payment_deadline' => null,
                'full_payment_deadline' => '2026-12-25',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('result.updated_new', 1)
        ->assertJsonPath('result.updated_overwrite', 0)
        ->assertJsonPath('result.skipped', 1);

    $smpApp->refresh();
    $smpExisting->refresh();

    expect($smpApp->virtual_account_number)->toBe('9882222222222222');
    expect((float) $smpApp->total_admission_fee)->toBe(10720000.0);
    expect((float) $smpApp->min_down_payment)->toBe(10720000.0);
    expect($smpApp->down_payment_deadline)->toBeNull();
    expect($smpApp->full_payment_deadline?->format('Y-m-d'))->toBe('2026-12-25');

    // skip_existing kept existing VA untouched
    expect($smpExisting->virtual_account_number)->toBe('9881111111111111');
});

it('forbids unauthorized users and cross-unit panitia from importing VA', function () {
    $parentUser = User::factory()->create();
    $parentUser->assignRole('orang_tua');

    $this->actingAs($parentUser)
        ->get(route('spmb.admin.va-template', ['unit_id' => $this->sdUnit->id]))
        ->assertForbidden();

    $sdPanitia = User::factory()->create();
    $sdPanitia->assignRole('panitia_spmb');

    $this->actingAs($sdPanitia)
        ->withSession(['active_unit_id' => $this->sdUnit->id])
        ->postJson(route('spmb.admin.va-import.commit'), [
            'unit_id' => $this->smpUnit->id,
            'rows' => [
                [
                    'registration_number' => 'IND-SMPN-99999',
                    'new_va' => '9880000000000009',
                    'total_admission_fee' => 10720000,
                ],
            ],
        ])
        ->assertForbidden();
});
