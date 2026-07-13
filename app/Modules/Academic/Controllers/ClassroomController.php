<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassroomController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');

        // Classrooms are now permanent - no year filtering needed
        $classrooms = Classroom::with(['homeroomTeacher'])
            ->where('unit_id', $unitId)
            ->withCount('students')
            ->orderBy('level')
            ->orderBy('name')
            ->get();
            
        $teachers = \App\Modules\Academic\Models\Teacher::where('unit_id', $unitId)->get();

        return Inertia::render('Academic/Classrooms/Index', [
            'classrooms' => $classrooms,
            'teachers' => $teachers,
        ]);
    }

    public function create()
    {
        // Handled by Modal in Index
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menambah data kelas.');
        }

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;

         $validated = $request->validate([
             'name' => 'required|string|max:50',
             'level' => 'required|string|max:10',
             'homeroom_teacher_id' => 'nullable|exists:teachers,id',
         ]);

         try {
             Classroom::create([
                 'unit_id' => $unitId,
                 'name' => $validated['name'],
                 'level' => $validated['level'],
                 'homeroom_teacher_id' => $validated['homeroom_teacher_id'],
             ]);

             return redirect()->back()->with('success', 'Kelas berhasil dibuat.');
         } catch (\Exception $e) {
             return redirect()->back()->with('error', 'Gagal membuat kelas. Silakan coba lagi.');
         }
    }

    public function edit(Classroom $classroom)
    {
         // Handled by Modal in Index
    }

    public function update(Request $request, Classroom $classroom)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $classroom->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengubah data kelas dari unit lain.');
        }

        // Security: Only Admin or Homeroom Teacher can update
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            $isTeacher = $user->hasRole('teacher') || $user->hasRole('wali_kelas');
            $teacherProfile = $user->teacher_profile;

            if (!$isTeacher || !$teacherProfile || $classroom->homeroom_teacher_id !== $teacherProfile->id) {
                abort(403, 'Hanya Wali Kelas yang dapat mengubah data kelas ini.');
            }
        }

        $validated = $request->validate([
             'name' => 'required|string|max:50',
             'level' => 'required|string|max:10',
             'homeroom_teacher_id' => 'nullable|exists:teachers,id',
         ]);

        $classroom->update($validated);

        return redirect()->back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function show(Classroom $classroom)
    {
        if ($classroom->unit_id != session('active_unit_id')) abort(403);

        $classroom->load(['homeroomTeacher', 'students' => function($q) {
            $q->orderBy('full_name');
        }]);

        // Get students in this Unit who are NOT in any class for this Academic Year
        $availableStudents = \App\Modules\Academic\Models\Student::where('unit_id', session('active_unit_id'))
            ->whereNull('classroom_id')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Academic/Classrooms/Show', [
            'classroom' => $classroom,
            'availableStudents' => $availableStudents,
            'isHomeroomTeacher' => auth()->user()->teacher_profile && auth()->user()->teacher_profile->id === $classroom->homeroom_teacher_id,
        ]);
    }

    public function addStudent(Request $request, Classroom $classroom)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $classroom->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses kelas dari unit lain.');
        }

        // Security Check
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            $isTeacher = $user->hasRole('teacher') || $user->hasRole('wali_kelas');
            $teacherProfile = $user->teacher_profile;

            if (!$isTeacher || !$teacherProfile || $classroom->homeroom_teacher_id !== $teacherProfile->id) {
                abort(403, 'Hanya Wali Kelas yang dapat menambahkan siswa.');
            }
        }

        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        \App\Modules\Academic\Models\Student::whereIn('id', $request->student_ids)
            ->where('unit_id', session('active_unit_id'))
            ->update(['classroom_id' => $classroom->id]);

        return redirect()->back()->with('success', count($request->student_ids) . ' Siswa berhasil ditambahkan ke kelas.');
    }

    public function removeStudent(Classroom $classroom, \App\Modules\Academic\Models\Student $student)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $classroom->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses kelas dari unit lain.');
        }

        // Security Check
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            $isTeacher = $user->hasRole('teacher') || $user->hasRole('wali_kelas');
            $teacherProfile = $user->teacher_profile;

            if (!$isTeacher || !$teacherProfile || $classroom->homeroom_teacher_id !== $teacherProfile->id) {
                abort(403, 'Hanya Wali Kelas yang dapat mengeluarkan siswa.');
            }
        }

        if ($student->classroom_id !== $classroom->id) {
            return redirect()->back()->with('error', 'Siswa tidak berada di kelas ini.');
        }

        $student->update(['classroom_id' => null]);

        return redirect()->back()->with('success', 'Siswa dikeluarkan dari kelas.');
    }

    public function destroy(Classroom $classroom)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat menghapus data kelas.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $classroom->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menghapus kelas dari unit lain.');
        }
        
        $classroom->students()->update(['classroom_id' => null]);
        
        $classroom->delete();
        return redirect()->back()->with('success', 'Kelas dihapus.');
    }
}
