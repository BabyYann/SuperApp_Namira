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
        $unitCode = match ($unit->id) {
            1 => 'PGN',
            2 => 'TKN',
            3 => 'SDN',
            4 => 'SMPN',
            default => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $unit->name), 0, 3)),
        };

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
    public function resolveParentUser(string $phone, string $password, string $parentName): User
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Check if user already exists with this phone or email
        $existingUser = User::where('phone', $cleanPhone)
            ->orWhere('email', "{$cleanPhone}@spmb.namiraschool.com")
            ->first();

        if ($existingUser) {
            return $existingUser;
        }

        // Create new user for parent
        $user = User::create([
            'name' => $parentName,
            'email' => "{$cleanPhone}@spmb.namiraschool.com",
            'phone' => $cleanPhone,
            'password' => Hash::make($password),
        ]);

        return $user;
    }

    /**
     * Convert accepted and paid applicant into an active Academic Student.
     */
    public function enrollToAcademicStudent(SpmbApplicant $applicant, User $enrolledBy): Student
    {
        return DB::transaction(function () use ($applicant, $enrolledBy) {
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

                $student = Student::create([
                    'unit_id' => $applicant->unit_id,
                    'user_id' => $applicant->user_id,
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
                    'parent_phone' => $applicant->parent_phone,
                    'status' => 'active',
                ]);
            } else {
                $student = $existingStudent;
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
