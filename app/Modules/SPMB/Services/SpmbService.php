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
}
