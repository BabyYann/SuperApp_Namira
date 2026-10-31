<?php

namespace App\Modules\SPMB\Services;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpmbService
{
    /**
     * Generate unique registration number.
     * Format: IND-{UNIT_CODE}-{YY}{001} (e.g. IND-SDN-26001)
     */
    public function generateRegistrationNumber(Unit $unit): string
    {
        $yearCode = date('y');
        $code = strtoupper($unit->code ?? '');
        $name = strtolower($unit->name ?? '');

        if ($code === 'SMP' || str_contains($name, 'smp')) {
            $unitCode = 'SMPN';
        } elseif ($code === 'SD' || str_contains($name, 'sd')) {
            $unitCode = 'SDN';
        } elseif ($code === 'TK' || str_contains($name, 'tk')) {
            $unitCode = 'TKN';
        } elseif ($code === 'KB' || str_contains($name, 'kb')) {
            $unitCode = 'KBN';
        } else {
            $unitCode = match ($unit->id) {
                1 => 'PGN',
                2 => 'TKN',
                3 => 'SDN',
                4 => 'SMPN',
                6 => 'SDN',
                7 => 'SMPN',
                default => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $unit->name), 0, 3)),
            };
        }

        $prefix = "IND-{$unitCode}-{$yearCode}";

        $lastNumber = SpmbApplicant::where('registration_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('registration_number');

        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, -3);
            $newSequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '001';
        }

        return "{$prefix}{$newSequence}";
    }

    /**
     * Store and organize an uploaded document neatly into its dedicated applicant directory.
     * Folder: spmb/{unit_slug}/{registration_number}/{DOC_TYPE}_{registration_number}_{time}.ext
     */
    public function storeApplicantDocument(UploadedFile $file, string $docType, string $registrationNumber, string $unitSlug = 'sd-namira'): string
    {
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $cleanDocType = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $docType));
        $cleanRegNum = preg_replace('/[^A-Za-z0-9_-]/', '', $registrationNumber);
        
        $filename = "{$cleanDocType}_{$cleanRegNum}_{$timestamp}.{$extension}";
        $folder = "spmb/{$unitSlug}/{$cleanRegNum}";

        return $file->storeAs($folder, $filename, 'public');
    }

    /**
     * Register or resolve parent User account.
     */
    public function resolveParentUser(string $phone, string $password, string $parentName, ?int $unitId = null): User
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Check if user already exists with this phone or email
        $user = User::where('phone', $cleanPhone)
            ->orWhere('email', "{$cleanPhone}@spmb.namiraschool.com")
            ->first();

        if (!$user) {
            // Create new user for parent
            $user = User::create([
                'name' => $parentName,
                'email' => "{$cleanPhone}@spmb.namiraschool.com",
                'phone' => $cleanPhone,
                'password' => Hash::make($password),
            ]);
        }

        // Ensure 'orang_tua' role is assigned and scoped to the unit
        $parentRole = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'orang_tua',
            'guard_name' => 'web',
        ]);

        $hasParentRole = DB::table('model_has_roles')
            ->where('role_id', $parentRole->id)
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->when($unitId, fn ($q) => $q->where('team_id', $unitId))
            ->exists();

        if (!$hasParentRole) {
            // If there is a role row with null team_id for orang_tua, update it instead of duplicating
            $nullTeamRow = DB::table('model_has_roles')
                ->where('role_id', $parentRole->id)
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->whereNull('team_id')
                ->exists();

            if ($nullTeamRow && $unitId) {
                DB::table('model_has_roles')
                    ->where('role_id', $parentRole->id)
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->whereNull('team_id')
                    ->update(['team_id' => $unitId]);
            } elseif (!$nullTeamRow) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $parentRole->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'team_id' => $unitId,
                ]);
            }
        }

        return $user;
    }

    /**
     * Convert accepted and paid applicant into an active Academic Student.
     */
    public function enrollToAcademicStudent(SpmbApplicant $applicant, User $enrolledBy, ?int $classroomId = null): Student
    {
        return DB::transaction(function () use ($applicant, $enrolledBy, $classroomId) {
            // Find or generate student
            $existingStudent = Student::where('nik', $applicant->nik)
                ->where('unit_id', $applicant->unit_id)
                ->first();

            if (!$existingStudent) {
                // Generate a temporary / preliminary NIS
                $yearPrefix = date('y');
                $lastNis = Student::where('unit_id', $applicant->unit_id)
                    ->where('nis', 'like', "{$yearPrefix}%")
                    ->orderBy('nis', 'desc')
                    ->value('nis');

                $nextSeq = $lastNis ? ((int)substr($lastNis, -4)) + 1 : 1;
                $nis = $yearPrefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

                $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();

                $studentData = [
                    'unit_id' => $applicant->unit_id,
                    'user_id' => $applicant->user_id,
                    'academic_year_id' => $activeYear?->id,
                    'classroom_id' => $classroomId,
                    'nis' => $nis,
                    'nisn' => $applicant->nisn,
                    'nik' => $applicant->nik,
                    'full_name' => $applicant->full_name,
                    'gender' => $applicant->gender,
                    'birth_place' => $applicant->birth_place,
                    'birth_date' => $applicant->birth_date,
                    'religion' => $applicant->religion ?: 'Islam',
                    'address' => $applicant->address,
                    'father_name' => $applicant->father_name,
                    'mother_name' => $applicant->mother_name,
                    'parent_name' => $applicant->father_name ?: $applicant->mother_name,
                    'parent_phone' => $applicant->parent_phone,
                    'va_number' => $applicant->virtual_account_number,
                    'status' => 'active',
                ];

                $student = Student::create($studentData);
            } else {
                $student = $existingStudent;
                if ($classroomId && !$student->classroom_id) {
                    $student->update(['classroom_id' => $classroomId]);
                }
            }

            // Ensure the linked user account also gets 'siswa' role scoped to the unit
            if ($applicant->user_id) {
                $studentRole = \Spatie\Permission\Models\Role::firstOrCreate([
                    'name' => 'siswa',
                    'guard_name' => 'web',
                ]);

                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $studentRole->id,
                    'model_type' => User::class,
                    'model_id' => $applicant->user_id,
                ], [
                    'team_id' => $applicant->unit_id,
                ]);
            }

            // Sync admission payment to Finance Transactions if paid_admission_amount > 0
            if ($applicant->paid_admission_amount > 0 && class_exists(\App\Modules\Finance\Models\Transaction::class)) {
                $alreadyRecorded = \App\Modules\Finance\Models\Transaction::where('student_id', $student->id)
                    ->where('source', 'spmb_admission')
                    ->exists();

                if (!$alreadyRecorded) {
                    \App\Modules\Finance\Models\Transaction::create([
                        'student_id' => $student->id,
                        'user_id' => $enrolledBy->id,
                        'transaction_code' => 'TRX/SPMB/' . date('Y/m/') . str_pad($applicant->id, 4, '0', STR_PAD_LEFT),
                        'amount' => $applicant->paid_admission_amount,
                        'payment_method' => 'transfer',
                        'source' => 'spmb_admission',
                        'notes' => 'Daftar Ulang SPMB (' . $applicant->registration_number . ')',
                        'transaction_date' => $applicant->admission_payment_verified_at ?? now(),
                        'allocated_amount' => $applicant->paid_admission_amount,
                    ]);
                }
            }

            // Update applicant record
            $applicant->update([
                'status' => 'enrolled',
                'enrolled_student_id' => $student->id,
                'enrolled_at' => now(),
                'enrolled_by' => $enrolledBy->id,
            ]);

            return $student;
        });
    }

    /**
     * Normalize Indonesian phone number to 08... format.
     */
    public function normalizePhone(?string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($clean, '62')) {
            $clean = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '0' . $clean;
        }
        return $clean;
    }

    /**
     * Master Data Angket TK Namira 2026 & Inden SD Namira (72 Entri).
     */
    public function getSdIndenMasterList(): array
    {
        return [
            ['no' => 1, 'full_name' => 'Clemira Fachrana Zahra', 'phone' => '085234577767', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 2, 'full_name' => 'NAUFA AZZAHRA AZKAYRA ANDARU', 'phone' => '085707461592', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 3, 'full_name' => 'Reza Rahadian Basuki', 'phone' => '082330521607', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 4, 'full_name' => 'Raisa qanita Fransisca', 'phone' => '082146075750', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 5, 'full_name' => 'Keinara hafsha adzkiya farhana', 'phone' => '082334861305', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 6, 'full_name' => 'Fatih Rizky Ramadhan', 'phone' => '082237518548', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 7, 'full_name' => 'Muhammad Fatih Hasan', 'phone' => '085233041839', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 8, 'full_name' => 'Adeeva Octavia Putri Haspita', 'phone' => '081334309545', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 9, 'full_name' => 'YUSUF SAKTI WIRATAMA WIDYANTORO', 'phone' => '085231831469', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 10, 'full_name' => 'Zaskia Anjani putri Burhanuddin', 'phone' => '085334084015', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 11, 'full_name' => 'Fatiha aina salsabila', 'phone' => '082132439367', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 12, 'full_name' => 'Dhefin Adelyo Athariq', 'phone' => '081233100700', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 13, 'full_name' => 'Muhamad Dinar', 'phone' => '082314950555', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 14, 'full_name' => 'Khalisa Dafiyah Yamina', 'phone' => '082145451352', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 15, 'full_name' => 'MUHAMMAD TAUFIQ HIDAYATULLAH', 'phone' => '085230724693', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 16, 'full_name' => 'Azkiya Kayla Mardhiyah', 'phone' => '081935433075', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 17, 'full_name' => 'Amira Barroh At-Taqiyyah', 'phone' => '081335208980', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 18, 'full_name' => 'Kiara Nur Aprilia Nathania', 'phone' => '085257787838', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 19, 'full_name' => 'Raghata Angkasa Putra Wardana', 'phone' => '08113771017', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 20, 'full_name' => 'muhammad alvino Saputra', 'phone' => '082132761702', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 21, 'full_name' => "Ahmad Fatih Rif'an Aziz", 'phone' => '082301815399', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 22, 'full_name' => 'Shezi Agacia Halina Huda', 'phone' => '081230414997', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 23, 'full_name' => 'ABIDAH ADRISTI ALESHA SHAQUEENA HASAN', 'phone' => '082336697833', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 24, 'full_name' => 'Jinan Zelmira Andanila', 'phone' => '085955203909', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 25, 'full_name' => 'Arisha Daritri Almahyra', 'phone' => '082299178992', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 26, 'full_name' => 'Zavier Rashdan Habibi', 'phone' => '081212677038', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 27, 'full_name' => 'Asiqna Nur Fatimah', 'phone' => '085731519685', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 28, 'full_name' => 'ARKANA AIDAN ABDUL AZIZ', 'phone' => '082331111304', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 29, 'full_name' => 'Alesha Nafisah putri', 'phone' => '082131363661', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 30, 'full_name' => 'Atika Fithriya Tsabita Ahmad', 'phone' => '082133008641', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 31, 'full_name' => 'muhammad arshaka', 'phone' => '082302559423', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 32, 'full_name' => 'Hanin Himayatul ilah', 'phone' => '085234343336', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 33, 'full_name' => 'Labib nuha khayri faeyza al Ghani', 'phone' => '085704301547', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 34, 'full_name' => 'Hisyam Balya Nazeeh', 'phone' => '082143551955', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 35, 'full_name' => 'Salim Alwi Aljufri', 'phone' => '081232055055', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 36, 'full_name' => 'Muhammad Zafran Al Hasby', 'phone' => '081359564307', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 37, 'full_name' => 'Charissa Putri El Anwar', 'phone' => '085258833953', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 38, 'full_name' => 'ARIENDRA NAUFAL RABBANI', 'phone' => '085855114999', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 39, 'full_name' => 'Melviano Keenan Mubarak', 'phone' => '082131011181', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 40, 'full_name' => 'Queena Hagia shofia', 'phone' => '08111767080', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 41, 'full_name' => 'AHMAD THORIQ AL BAITY', 'phone' => '082214974721', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 42, 'full_name' => 'Nafisah Al Ilmi', 'phone' => '082302301003', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 43, 'full_name' => 'MUHAMMAD RHAMZA ABDUL MALIKI', 'phone' => '085231647868', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 44, 'full_name' => 'MAHREEN AIZA NUGROHO', 'phone' => '081292200530', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 45, 'full_name' => 'MUHAMMAD SYARIF HIDAYATULLAH', 'phone' => '083800665479', 'previous_school' => 'TK Namira', 'source_label' => 'Angket TK Namira'],
            ['no' => 46, 'full_name' => 'Salwa rasyifa Aurora', 'phone' => '081348638439', 'previous_school' => 'TK tunas mulia Paiton', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 47, 'full_name' => 'Aisyah Zakiya putri satriya', 'phone' => '082136585323', 'previous_school' => 'TK khodijah semarang', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 48, 'full_name' => 'Yasmine Yumna Andrian', 'phone' => '082234953535', 'previous_school' => 'TK PKK kertosuko krucil', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 49, 'full_name' => 'Aghnia qori Aina', 'phone' => '082301815249', 'previous_school' => 'TK permata', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 50, 'full_name' => 'Muhammad Hamka Al Fatih', 'phone' => '082245923315', 'previous_school' => 'TK Argopuro 2', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 51, 'full_name' => "Zulaikha Mar'ah Faradis", 'phone' => '085243288101', 'previous_school' => 'TK Kusuma', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 52, 'full_name' => 'Arsyista Mecca', 'phone' => '082266666324', 'previous_school' => 'TK aba pendil', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 53, 'full_name' => 'Khalisa dafiyah Yamina', 'phone' => '082230991456', 'previous_school' => 'TK Namira', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 54, 'full_name' => 'Kenzio farzan arrazka', 'phone' => '085258958081', 'previous_school' => 'TK IT permata', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 55, 'full_name' => 'Haydar Arkana Aziz', 'phone' => '082229195160', 'previous_school' => 'TK PG Pajarakan', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 56, 'full_name' => 'Evano bramastha Rajendra Birowo', 'phone' => '082143603135', 'previous_school' => 'TK PG Pajarakan', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 57, 'full_name' => 'Muhammad Haidar al-Fatih', 'phone' => '081335587009', 'previous_school' => 'Cordova Islamic school Surabaya', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 58, 'full_name' => 'Muhammad arshaka alghozali', 'phone' => '082302559423', 'previous_school' => 'TK Namira', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 59, 'full_name' => 'Kashieva Aurora mahbuba', 'phone' => '082229796636', 'previous_school' => 'TK ABA harapan kraksaan', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 60, 'full_name' => 'Alvaro arzan kafeel Ahmad', 'phone' => '082261035550', 'previous_school' => 'TK Islam Siti Khadijah', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 61, 'full_name' => 'Elramdhan parvez Maulana', 'phone' => '082269631889', 'previous_school' => 'TK Islam Siti Khadijah', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 62, 'full_name' => 'Akbar Caesar nurdaffa', 'phone' => '082330514776', 'previous_school' => 'TK Nira Indria sebaung gending', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 63, 'full_name' => 'Arsalaan Ammar Shiddiq L Satrio', 'phone' => '081216089391', 'previous_school' => 'TKN II pembina', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 64, 'full_name' => 'Rafif Ibrahim Al Ghifari', 'phone' => '085233248352', 'previous_school' => 'TK kemuning suko', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 65, 'full_name' => 'El Jalaluddin rumi Hidayat', 'phone' => '082229390659', 'previous_school' => 'TK Nira Indria', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 66, 'full_name' => 'Muhammad ziyad kamali', 'phone' => '085236123591', 'previous_school' => 'RA Mambaul Irfan asembakor', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 67, 'full_name' => 'Arunika numa sandhikatullah', 'phone' => '082323263264', 'previous_school' => 'TK tunas mulia', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 68, 'full_name' => 'Ahmad farel Maulana Hasan', 'phone' => '085869518878', 'previous_school' => 'TK negeri pembina Dringu', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 69, 'full_name' => 'Danendra Shidqi hafidzan alkhalifi', 'phone' => '085204247140', 'previous_school' => 'TK permata', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 70, 'full_name' => 'Adreena Yasmine kahisha', 'phone' => '085233000034', 'previous_school' => 'TK PG Pajarakan', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 71, 'full_name' => 'Muhammad Jembar ranggalawe', 'phone' => '082301751765', 'previous_school' => 'TK alfarida Kraksaan', 'source_label' => 'Inden Tapel 2027-2028'],
            ['no' => 72, 'full_name' => 'Juniar geo vishaka Prasetya', 'phone' => '082310300747', 'previous_school' => 'TK IT permata', 'source_label' => 'Inden Tapel 2027-2028'],
        ];
    }

    /**
     * Match an applicant's phone or full name against the 72 SD Inden master list.
     */
    public function matchSdIndenApplicant(?string $phone, ?string $fullName = null): ?array
    {
        $cleanPhone = $this->normalizePhone($phone);
        $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', (string) $fullName)));

        foreach ($this->getSdIndenMasterList() as $item) {
            if ($cleanPhone !== '' && $this->normalizePhone($item['phone']) === $cleanPhone) {
                return $item;
            }
        }

        if ($cleanName !== '') {
            foreach ($this->getSdIndenMasterList() as $item) {
                $masterName = strtolower(trim(preg_replace('/\s+/', ' ', $item['full_name'])));
                if ($masterName === $cleanName) {
                    return $item;
                }
            }
        }

        return null;
    }
}
