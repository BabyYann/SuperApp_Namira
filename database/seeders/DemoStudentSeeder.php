<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Finance\Models\FinanceType;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Yayasan\Models\Unit;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or Create SMP Unit
        $unit = Unit::where('code', 'SMP')->first() ?? Unit::first();
        $unitId = $unit ? $unit->id : 3;

        // Set Spatie Team Scope
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($unitId);
        }

        // 2. Create or Update Student User Account
        $user = User::updateOrCreate(
            ['email' => 'siswa.demo@namiraschool.com'],
            [
                'name'     => 'Ahmad Zaki Pratama',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign 'siswa' Spatie Role with team scope
        $role = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id'   => $role->id,
                'model_type'=> User::class,
                'model_id'  => $user->id,
                'team_id'   => $unitId,
            ],
            []
        );

        // 3. Get or Create Classroom (7A)
        $classroom = Classroom::firstOrCreate(
            ['name' => '7A', 'unit_id' => $unitId],
            ['level' => '7', 'is_active' => true]
        );

        // 4. Create or Update Student Profile
        $student = Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'    => 'Ahmad Zaki Pratama',
                'gender'       => 'L',
                'nis'          => '20267001',
                'nisn'         => '0091283741',
                'unit_id'      => $unitId,
                'classroom_id' => $classroom->id,
                'status'       => 'active',
                'entry_year'   => '2026',
                'va_number'    => '0283749102',
            ]
        );

        // 5. Create Subjects & Schedule for 7A
        $subjectNames = [
            'PAI' => 'Pendidikan Agama Islam & Moral',
            'MAT' => 'Matematika Terpadu',
            'IPA' => 'Ilmu Pengetahuan Alam',
            'BIG' => 'Bahasa Inggris Global',
            'BIN' => 'Bahasa Indonesia',
            'TAF' => 'Tahfidz & Tilawah Al-Qur\'an',
            'IPS' => 'Ilmu Pengetahuan Sosial',
            'INF' => 'Informatika & Coding',
        ];

        $subjects = [];
        foreach ($subjectNames as $code => $name) {
            $subjects[$code] = Subject::firstOrCreate(
                ['code' => $code, 'unit_id' => $unitId],
                ['name' => $name, 'is_active' => true]
            );
        }

        // Add Schedules for Classroom 7A
        $schedules = [
            ['day' => 'Senin', 'subject' => 'PAI', 'start' => '07:30:00', 'end' => '09:00:00'],
            ['day' => 'Senin', 'subject' => 'MAT', 'start' => '09:15:00', 'end' => '10:45:00'],
            ['day' => 'Senin', 'subject' => 'BIN', 'start' => '11:00:00', 'end' => '12:30:00'],
            ['day' => 'Selasa', 'subject' => 'IPA', 'start' => '07:30:00', 'end' => '09:00:00'],
            ['day' => 'Selasa', 'subject' => 'BIG', 'start' => '09:15:00', 'end' => '10:45:00'],
            ['day' => 'Selasa', 'subject' => 'IPS', 'start' => '11:00:00', 'end' => '12:30:00'],
            ['day' => 'Rabu', 'subject' => 'TAF', 'start' => '07:30:00', 'end' => '09:00:00'],
            ['day' => 'Rabu', 'subject' => 'INF', 'start' => '09:15:00', 'end' => '10:45:00'],
            ['day' => 'Kamis', 'subject' => 'MAT', 'start' => '07:30:00', 'end' => '09:00:00'],
            ['day' => 'Kamis', 'subject' => 'IPA', 'start' => '09:15:00', 'end' => '10:45:00'],
            ['day' => 'Jumat', 'subject' => 'PAI', 'start' => '07:30:00', 'end' => '09:00:00'],
            ['day' => 'Jumat', 'subject' => 'BIG', 'start' => '09:15:00', 'end' => '10:30:00'],
        ];

        foreach ($schedules as $s) {
            ClassSchedule::firstOrCreate(
                [
                    'classroom_id' => $classroom->id,
                    'day'          => $s['day'],
                    'start_time'   => $s['start'],
                ],
                [
                    'unit_id'    => $unitId,
                    'end_time'   => $s['end'],
                    'subject_id' => $subjects[$s['subject']]->id,
                ]
            );
        }

        // 6. Create Finance Type & Student Bill
        $financeType = FinanceType::firstOrCreate(
            ['code' => 'SPP_SMP', 'unit_id' => $unitId],
            [
                'name' => 'SPP Bulanan SMP Namira',
                'description' => 'Sumbangan Pembinaan Pendidikan Bulanan',
                'is_active' => true,
            ]
        );

        StudentBill::updateOrCreate(
            [
                'student_id' => $student->id,
                'bill_code'  => 'INV/2026/08/0001',
            ],
            [
                'finance_type_id' => $financeType->id,
                'description'     => 'SPP Bulanan Agustus 2026 - Ahmad Zaki',
                'billing_date'    => Carbon::parse('2026-08-01'),
                'due_date'        => Carbon::parse('2026-08-15'),
                'original_amount' => 450000,
                'discount_amount' => 0,
                'final_amount'    => 450000,
                'paid_amount'     => 0,
                'status'          => 'unpaid',
            ]
        );
    }
}
