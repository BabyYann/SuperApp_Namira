<?php

namespace App\Modules\Extracurricular\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Extracurricular\Models\Extracurricular;
use App\Modules\Extracurricular\Models\ExtracurricularMember;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExtracurricularMemberController extends Controller
{
    /**
     * Add single or bulk students to extracurricular.
     */
    public function store(Request $request, $extracurricularId)
    {
        $user = Auth::user();
        $activity = Extracurricular::findOrFail($extracurricularId);

        $canManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan']);
        $isInstructor = $activity->instructors()->where('user_id', $user->id)->exists();

        if (!$canManage && !$isInstructor) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola anggota ekstrakurikuler ini.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first() 
            ?: AcademicYear::latest('id')->first();

        $mode = $request->input('mode', 'single'); // 'single' | 'classroom_bulk' | 'multiple_ids'

        DB::beginTransaction();
        try {
            $currentCount = $activity->activeMembers()->count();
            $maxQuota = $activity->max_quota;

            if ($mode === 'classroom_bulk') {
                $validated = $request->validate([
                    'classroom_id' => 'required|exists:classrooms,id',
                ]);

                $students = Student::where('classroom_id', $validated['classroom_id'])->get();
                $addedCount = 0;

                foreach ($students as $student) {
                    if ($currentCount + $addedCount >= $maxQuota) {
                        break;
                    }

                    $exists = ExtracurricularMember::where([
                        'extracurricular_id' => $activity->id,
                        'student_id' => $student->id,
                        'academic_year_id' => $activeYear?->id ?? 1,
                        'semester' => $activeYear?->semester ?? 'ganjil',
                    ])->exists();

                    if (!$exists) {
                        ExtracurricularMember::create([
                            'extracurricular_id' => $activity->id,
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYear?->id ?? 1,
                            'semester' => $activeYear?->semester ?? 'ganjil',
                            'joined_date' => now()->toDateString(),
                            'status' => 'active',
                        ]);
                        $addedCount++;
                    }
                }

                DB::commit();
                return back()->with('success', "Berhasil menambahkan {$addedCount} siswa dari kelas ke dalam ekstrakurikuler.");
            } else {
                $validated = $request->validate([
                    'student_id' => 'required|exists:students,id',
                    'notes' => 'nullable|string|max:255',
                ]);

                if ($currentCount >= $maxQuota) {
                    return back()->with('error', "Kuota maksimal ({$maxQuota} siswa) untuk ekstrakurikuler ini sudah penuh.");
                }

                $exists = ExtracurricularMember::where([
                    'extracurricular_id' => $activity->id,
                    'student_id' => $validated['student_id'],
                    'academic_year_id' => $activeYear?->id ?? 1,
                    'semester' => $activeYear?->semester ?? 'ganjil',
                ])->first();

                if ($exists) {
                    if ($exists->status !== 'active') {
                        $exists->update(['status' => 'active']);
                        DB::commit();
                        return back()->with('success', 'Status siswa berhasil diaktifkan kembali.');
                    }
                    return back()->with('error', 'Siswa sudah terdaftar dalam ekstrakurikuler ini.');
                }

                ExtracurricularMember::create([
                    'extracurricular_id' => $activity->id,
                    'student_id' => $validated['student_id'],
                    'academic_year_id' => $activeYear?->id ?? 1,
                    'semester' => $activeYear?->semester ?? 'ganjil',
                    'joined_date' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => $validated['notes'] ?? null,
                ]);

                DB::commit();
                return back()->with('success', 'Siswa berhasil ditambahkan sebagai anggota.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan anggota: ' . $e->getMessage());
        }
    }

    /**
     * Update member status (active / inactive / dropped).
     */
    public function updateStatus(Request $request, $extracurricularId, $memberId)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,dropped',
            'notes' => 'nullable|string|max:255',
        ]);

        $member = ExtracurricularMember::where('extracurricular_id', $extracurricularId)
            ->findOrFail($memberId);

        $member->update($validated);

        return back()->with('success', 'Status anggota berhasil diperbarui.');
    }

    /**
     * Remove member from extracurricular.
     */
    public function destroy($extracurricularId, $memberId)
    {
        $member = ExtracurricularMember::where('extracurricular_id', $extracurricularId)
            ->findOrFail($memberId);

        $studentName = $member->student?->full_name ?? 'Siswa';
        $member->delete();

        return back()->with('success', "{$studentName} berhasil dihapus dari daftar anggota.");
    }
}
