<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\LearningObjective;
use App\Modules\Academic\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningObjectiveController extends Controller
{
    public function index(Request $request)
    {
        $unitId = session('active_unit_id');
        $subjectId = $request->input('subject_id');
        $user = $request->user();

        $query = \App\Modules\Academic\Models\Chapter::with(['subject', 'learningObjectives'])
            ->where('unit_id', $unitId)
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('learningObjectives', function($sq) use ($search) {
                      $sq->where('code', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%");
                  });
            });

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $chapters = $query->orderBy('subject_id')->orderBy('grade_level')->orderBy('semester')->orderBy('title')->paginate(10)->withQueryString();
        
        // Smart Filtering for Teachers
        $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        $isTeacher = $user->hasRole('teacher') || $teacher;

        \Illuminate\Support\Facades\Log::info('LO Index Debug', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames(),
            'has_teacher_profile' => $teacher ? true : false,
            'is_treated_as_teacher' => $isTeacher,
        ]);

        if ($isTeacher) {
            
            \Illuminate\Support\Facades\Log::info('Teacher Found?', ['teacher_id' => $teacher?->id]);

            if ($teacher) {
                // Get subjects from schedule
                $subjectIds = \App\Modules\Academic\Models\ClassSchedule::where('teacher_id', $teacher->id)
                    ->pluck('subject_id')
                    ->unique();
                
                \Illuminate\Support\Facades\Log::info('Subject IDs from Schedule', ['ids' => $subjectIds]);

                $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
            } else {
                $subjects = collect([]); // No teacher profile found
            }
        } else {
            // Admin sees all
            $subjects = Subject::where('unit_id', $unitId)->orderBy('name')->get();
        }

        $activeUnit = \App\Modules\Yayasan\Models\Unit::find($unitId);

        return Inertia::render('Academic/Objectives/Index', [
            'chapters' => $chapters,
            'subjects' => $subjects,
            'filters' => $request->only(['search', 'subject_id']),
            'unit_level' => $activeUnit ? $activeUnit->level : null,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum', 'teacher'])) {
            abort(403, 'Anda tidak memiliki akses untuk menambah data Bab/Tujuan Pembelajaran.');
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'semester' => 'required|in:1,2',
            'grade_level' => 'required|integer|min:1|max:12',
            'objectives' => 'required|array|min:1',
            'objectives.*.code' => 'required|string|max:20',
            'objectives.*.description' => 'required|string',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($subject->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Mata pelajaran tidak sesuai dengan unit Anda.');
            }
        } else {
            $unitId = $subject->unit_id;
        }

        // Security Check: Teachers can only add TPs for their subjects
        $user = auth()->user();
        $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        if (($user->hasRole('teacher') || $teacher) && $teacher) {
             $allowedSubjectIds = \App\Modules\Academic\Models\ClassSchedule::where('teacher_id', $teacher->id)
                ->pluck('subject_id')
                ->unique()
                ->toArray();
            
            if (!in_array($request->subject_id, $allowedSubjectIds)) {
                abort(403, 'Anda tidak memiliki akses untuk menambahkan TP pada mata pelajaran ini.');
            }
        }

        $chapter = \App\Modules\Academic\Models\Chapter::create([
            'unit_id' => $unitId,
            'subject_id' => $request->subject_id,
            'title' => $request->title,
            'semester' => $request->semester,
            'grade_level' => $request->grade_level,
        ]);

        foreach ($request->objectives as $obj) {
            $chapter->learningObjectives()->create([
                'unit_id' => $unitId,
                'code' => $obj['code'],
                'description' => $obj['description'],
            ]);
        }

        return redirect()->back()->with('success', 'Bab dan Tujuan Pembelajaran berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum', 'teacher'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah data Bab/Tujuan Pembelajaran.');
        }

        $chapter = \App\Modules\Academic\Models\Chapter::findOrFail($id);
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($chapter->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'semester' => 'required|in:1,2',
            'grade_level' => 'required|integer|min:1|max:12',
            'objectives' => 'array', 
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($subject->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Mata pelajaran tidak sesuai dengan unit Anda.');
            }
        }

        // Security Check: Teachers can only update TPs for their subjects
        $user = auth()->user();
        $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        if (($user->hasRole('teacher') || $teacher) && $teacher) {
             $allowedSubjectIds = \App\Modules\Academic\Models\ClassSchedule::where('teacher_id', $teacher->id)
                ->pluck('subject_id')
                ->unique()
                ->toArray();
            
            // Check both new subject_id AND existing chapter subject_id
            if (!in_array($request->subject_id, $allowedSubjectIds) || !in_array($chapter->subject_id, $allowedSubjectIds)) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah TP pada mata pelajaran ini.');
            }
        }

        $chapter->update([
            'subject_id' => $request->subject_id,
            'title' => $request->title,
            'semester' => $request->semester,
            'grade_level' => $request->grade_level,
        ]);

        // Smart Sync Logic
        if ($request->has('objectives')) {
             $existingIds = $chapter->learningObjectives()->pluck('id')->toArray();
             $incomingIds = [];

             foreach ($request->objectives as $obj) {
                 if (isset($obj['id']) && in_array($obj['id'], $existingIds)) {
                     // Update
                     LearningObjective::where('id', $obj['id'])->update([
                         'code' => $obj['code'],
                         'description' => $obj['description'],
                     ]);
                     $incomingIds[] = $obj['id'];
                 } else {
                     // Create
                     $newObj = $chapter->learningObjectives()->create([
                        'unit_id' => $unitId,
                        'code' => $obj['code'],
                        'description' => $obj['description'],
                     ]);
                     $incomingIds[] = $newObj->id;
                 }
             }

             $toDelete = array_diff($existingIds, $incomingIds);
             LearningObjective::destroy($toDelete);
        }

        return redirect()->back()->with('success', 'Data Bab dan TP berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data Bab/Tujuan Pembelajaran.');
        }

        $chapter = \App\Modules\Academic\Models\Chapter::findOrFail($id);
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($chapter->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $chapter->delete(); // Cascades to TPs via DB constrained
        return redirect()->back()->with('success', 'Bab dan seluruh TP di dalamnya berhasil dihapus.');
    }
}
