<?php

namespace App\Modules\Extracurricular\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Extracurricular\Models\Extracurricular;
use App\Modules\Extracurricular\Models\ExtracurricularGrade;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExtracurricularGradeController extends Controller
{
    /**
     * Store or bulk update student semester grades.
     */
    public function store(Request $request, $extracurricularId)
    {
        $user = Auth::user();
        $activity = Extracurricular::findOrFail($extracurricularId);

        $canManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan']);
        $isInstructor = $activity->instructors()->where('user_id', $user->id)->exists();

        if (!$canManage && !$isInstructor) {
            abort(403, 'Anda tidak memiliki hak akses untuk menginput nilai ekstrakurikuler ini.');
        }

        $validated = $request->validate([
            'grades' => 'required|array|min:1',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.predicate' => 'required|in:A,B,C,D,Sangat Baik,Baik,Cukup,Kurang',
            'grades.*.description' => 'required|string|max:1000',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        DB::beginTransaction();
        try {
            foreach ($validated['grades'] as $gradeData) {
                ExtracurricularGrade::updateOrCreate(
                    [
                        'extracurricular_id' => $activity->id,
                        'student_id' => $gradeData['student_id'],
                        'academic_year_id' => $activeYear?->id ?? 1,
                        'semester' => $activeYear?->semester ?? 'ganjil',
                    ],
                    [
                        'predicate' => $gradeData['predicate'],
                        'description' => $gradeData['description'],
                        'given_by' => $user->id,
                    ]
                );
            }

            DB::commit();
            return back()->with('success', 'Penilaian capaian ekstrakurikuler berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }
}
