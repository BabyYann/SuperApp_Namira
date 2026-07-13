<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $unitId = session('active_unit_id');
        $user = auth()->user();
        
        // Check if user is a teacher
        $teacherProfile = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        $isTeacher = $user->hasRole('teacher') || $teacherProfile;

        // Always fetch the currently active academic year depending on DB status, 
        // as session might be stale after migration.
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
        $academicYearId = $activeYear ? $activeYear->id : session('active_academic_year_id');

        // Get Classrooms for Dropdown (now permanent - no year filtering)
        $classrooms = Classroom::where('unit_id', $unitId)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
            
        // Selected Classroom
        $selectedClassroomId = $request->input('classroom_id') ? (int) $request->input('classroom_id') : null;
        $schedule = [];
        $personalSchedule = [];

        // If Teacher, fetch their personal schedule by default
        if ($isTeacher && $teacherProfile) {
            $personalSchedule = ClassSchedule::with(['subject', 'classroom'])
                ->where('teacher_id', $teacherProfile->id)
                ->where('unit_id', $unitId) // Optional: restrict to current unit or show all? Assuming current unit for now.
                ->get()
                ->groupBy('day');
        }

        // If a specific classroom is selected, fetch that classroom's schedule
        if ($selectedClassroomId) {
            $schedule = ClassSchedule::with(['subject', 'teacher'])
                ->where('classroom_id', $selectedClassroomId)
                ->get()
                ->groupBy('day');
        }

        // Master Data for Modal
        $subjects = Subject::where('unit_id', $unitId)->orderBy('name')->get();
        $teachers = Teacher::where('unit_id', $unitId)->orderBy('full_name')->get();

        return Inertia::render('Academic/Schedules/Index', [
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'schedule' => $schedule,
            'personalSchedule' => $personalSchedule,
            'isTeacher' => (bool) $isTeacher,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'debug' => [
                'session_unit_id' => $unitId,
                'db_active_year_id' => $academicYearId,
                'classroom_count' => $classrooms->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        }

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day' => ['required', \Illuminate\Validation\Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $subject = Subject::findOrFail($request->subject_id);
        $teacher = Teacher::findOrFail($request->teacher_id);
        
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($classroom->unit_id !== $unitId || $subject->unit_id !== $unitId || $teacher->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            if ($classroom->unit_id !== $subject->unit_id || $classroom->unit_id !== $teacher->unit_id) {
                abort(403, 'Akses Ditolak: Unit classroom, subject, dan teacher harus sama.');
            }
            $unitId = $classroom->unit_id;
        }

        // Check for Teacher Conflict
        if ($this->hasConflict($request->teacher_id, $request->day, $request->start_time, $request->end_time, null, $unitId)) {
             $conflictedClass = $this->getConflictedClass($request->teacher_id, $request->day, $request->start_time, $request->end_time, null, $unitId);
             return redirect()->back()->withErrors(['teacher_id' => "Guru ini sedang mengajar di kelas {$conflictedClass->name} pada jam tersebut."]);
        }

        // Check for Classroom Conflict
        if ($this->hasClassroomConflict($request->classroom_id, $request->day, $request->start_time, $request->end_time, null, $unitId)) {
             $conflictedSchedule = $this->getConflictedClassroomSchedule($request->classroom_id, $request->day, $request->start_time, $request->end_time, null, $unitId);
             return redirect()->back()->withErrors(['classroom_id' => "Kelas ini sudah memiliki jadwal pelajaran {$conflictedSchedule->subject->name} oleh guru {$conflictedSchedule->teacher->full_name} pada jam tersebut."]);
        }

        ClassSchedule::create([
            'unit_id' => $unitId,
            'classroom_id' => $request->classroom_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function clone(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        }

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'from_day' => 'required|string',
            'to_day' => 'required|string|different:from_day',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($classroom->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $classroom->unit_id;
        }
        
        $sourceSchedules = ClassSchedule::where('classroom_id', $request->classroom_id)
            ->where('day', $request->from_day)
            ->get();

        if ($sourceSchedules->isEmpty()) {
            return redirect()->back()->withErrors(['from_day' => 'Tidak ada jadwal di hari asal.']);
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($sourceSchedules as $schedule) {
            // Check conflict for target day
            if ($this->hasConflict($schedule->teacher_id, $request->to_day, $schedule->start_time, $schedule->end_time, null, $unitId)) {
                $failCount++;
                continue;
            }

            ClassSchedule::create([
                'unit_id' => $unitId,
                'classroom_id' => $request->classroom_id,
                'subject_id' => $schedule->subject_id,
                'teacher_id' => $schedule->teacher_id,
                'day' => $request->to_day,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ]);
            $successCount++;
        }

        $message = "Berhasil clone $successCount jadwal.";
        if ($failCount > 0) {
            $message .= " ($failCount gagal karena bentrok guru).";
        }

        return redirect()->back()->with('success', $message);
    }

    public function reset(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        }

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($classroom->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $classroom->unit_id;
        }

        ClassSchedule::where('classroom_id', $request->classroom_id)
            ->where('unit_id', $unitId)
            ->delete();

        return redirect()->back()->with('success', 'Semua jadwal kelas ini berhasil dihapus.');
    }
    
    public function update(Request $request, ClassSchedule $schedule)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($schedule->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $schedule->unit_id;
        }

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day' => ['required', \Illuminate\Validation\Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $subject = Subject::findOrFail($request->subject_id);
        $teacher = Teacher::findOrFail($request->teacher_id);
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($classroom->unit_id !== $unitId || $subject->unit_id !== $unitId || $teacher->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            if ($classroom->unit_id !== $subject->unit_id || $classroom->unit_id !== $teacher->unit_id) {
                abort(403, 'Akses Ditolak: Unit classroom, subject, dan teacher harus sama.');
            }
        }

        // Check for Teacher Conflict (Excluding current schedule)
        if ($this->hasConflict($request->teacher_id, $request->day, $request->start_time, $request->end_time, $schedule->id, $unitId)) {
             $conflictedClass = $this->getConflictedClass($request->teacher_id, $request->day, $request->start_time, $request->end_time, $schedule->id, $unitId);
             return redirect()->back()->withErrors(['teacher_id' => "Guru ini sedang mengajar di kelas {$conflictedClass->name} pada jam tersebut."]);
        }

        // Check for Classroom Conflict (Excluding current schedule)
        if ($this->hasClassroomConflict($request->classroom_id, $request->day, $request->start_time, $request->end_time, $schedule->id, $unitId)) {
             $conflictedSchedule = $this->getConflictedClassroomSchedule($request->classroom_id, $request->day, $request->start_time, $request->end_time, $schedule->id, $unitId);
             return redirect()->back()->withErrors(['classroom_id' => "Kelas ini sudah memiliki jadwal pelajaran {$conflictedSchedule->subject->name} oleh guru {$conflictedSchedule->teacher->full_name} pada jam tersebut."]);
        }

        $schedule->update([
            'classroom_id' => $request->classroom_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    private function hasConflict($teacherId, $day, $startTime, $endTime, $ignoreId = null, $unitId = null)
    {
        $query = ClassSchedule::where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('unit_id', $unitId ?? session('active_unit_id'));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })
                ->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })
                ->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();
    }

    private function getConflictedClass($teacherId, $day, $startTime, $endTime, $ignoreId = null, $unitId = null)
    {
         $query = ClassSchedule::where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('unit_id', $unitId ?? session('active_unit_id'));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

         $schedule = $query->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })
                ->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })
                ->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                });
            })
            ->first();
            
        return $schedule ? Classroom::find($schedule->classroom_id) : null;
    }

    private function hasClassroomConflict($classroomId, $day, $startTime, $endTime, $ignoreId = null, $unitId = null)
    {
        $query = ClassSchedule::where('classroom_id', $classroomId)
            ->where('day', $day)
            ->where('unit_id', $unitId ?? session('active_unit_id'));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })
                ->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })
                ->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();
    }

    private function getConflictedClassroomSchedule($classroomId, $day, $startTime, $endTime, $ignoreId = null, $unitId = null)
    {
        $query = ClassSchedule::with(['subject', 'teacher'])
            ->where('classroom_id', $classroomId)
            ->where('day', $day)
            ->where('unit_id', $unitId ?? session('active_unit_id'));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })
                ->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })
                ->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                });
            })
            ->first();
    }


    public function exportPdf(Request $request) 
    {
        // Teachers are allowed to export PDF
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $classroom = Classroom::with(['homeroomTeacher'])->findOrFail($request->classroom_id);
        
        // Scope by Unit
        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($classroom->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $classroom->unit_id;
        }
        
        $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();

        $schedule = ClassSchedule::with(['subject', 'teacher'])
            ->where('classroom_id', $classroom->id)
            ->get()
            ->groupBy('day');
            
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        // Logo URL
        $logoUrl = $unit && $unit->logo ? url('storage/' . $unit->logo) : '';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Jadwal Pelajaran - ' . $classroom->name . '</title>
            <style>
                @page { size: landscape; margin: 1cm; }
                @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
                body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
                .header { display: flex; align-items: center; border-bottom: 3px solid #0d9488; padding-bottom: 15px; margin-bottom: 20px; }
                .logo { width: 70px; height: 70px; margin-right: 20px; object-fit: contain; }
                .header-text h1 { margin: 0; font-size: 16px; color: #0d9488; text-transform: uppercase; }
                .header-text h2 { margin: 5px 0 0; font-size: 14px; font-weight: bold; }
                .header-text p { margin: 3px 0 0; font-size: 10px; color: #666; }
                .meta { display: flex; gap: 40px; margin-bottom: 20px; font-size: 12px; }
                .meta-item { display: flex; gap: 8px; }
                .meta-label { font-weight: bold; color: #666; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th { background: #0d9488; color: white; padding: 10px; text-align: center; text-transform: uppercase; font-size: 12px; }
                td { border: 1px solid #ddd; padding: 8px; vertical-align: top; min-height: 100px; }
                .schedule-item { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
                .schedule-item:last-child { border-bottom: none; margin-bottom: 0; }
                .time { display: inline-block; background: #e0f2f1; color: #0d9488; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; margin-bottom: 4px; }
                .subject { font-weight: bold; display: block; margin-bottom: 2px; }
                .teacher { font-style: italic; color: #666; font-size: 10px; }
                .empty { text-align: center; color: #ccc; padding: 30px 0; }
                .footer { margin-top: 30px; display: flex; justify-content: flex-end; }
                .signature { text-align: center; width: 200px; }
                .signature-line { margin-top: 60px; border-top: 1px solid #333; }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logoUrl ? '<img src="' . $logoUrl . '" class="logo" alt="Logo">' : '') . '
                <div class="header-text">
                    <h1>YAYASAN NAMIRA SCHOOL</h1>
                    <h2>' . ($unit->name ?? 'SATUAN PENDIDIKAN NAMIRA') . '</h2>
                    <p>' . ($unit->address ?? 'Jalan Pasar 1 No. 58, Tanjung Sari, Medan Selayang') . '</p>
                </div>
            </div>

            <div class="meta">
                <div class="meta-item"><span class="meta-label">Kelas:</span> ' . $classroom->name . '</div>
                <div class="meta-item"><span class="meta-label">Wali Kelas:</span> ' . ($classroom->homeroomTeacher->full_name ?? '-') . '</div>
                <div class="meta-item"><span class="meta-label">Tahun Ajaran:</span> ' . ($activeYear->name ?? '-') . '</div>
            </div>

            <table>
                <thead><tr>';

        foreach ($days as $day) {
            $html .= '<th>' . $day . '</th>';
        }

        $html .= '</tr></thead><tbody><tr>';

        foreach ($days as $day) {
            $html .= '<td>';
            if (isset($schedule[$day]) && count($schedule[$day]) > 0) {
                foreach ($schedule[$day]->sortBy('start_time') as $item) {
                    $startTime = \Carbon\Carbon::parse($item->start_time)->format('H:i');
                    $endTime = \Carbon\Carbon::parse($item->end_time)->format('H:i');
                    $html .= '<div class="schedule-item">
                        <span class="time">' . $startTime . ' - ' . $endTime . '</span>
                        <span class="subject">' . $item->subject->name . '</span>
                        <span class="teacher">' . $item->teacher->full_name . '</span>
                    </div>';
                }
            } else {
                $html .= '<div class="empty">- Libur -</div>';
            }
            $html .= '</td>';
        }

        $html .= '</tr></tbody></table>

            <div class="footer">
                <div class="signature">
                    <p>Medan, ' . now()->translatedFormat('d F Y') . '</p>
                    <p>Kepala Sekolah</p>
                    <div class="signature-line"></div>
                    <p style="font-weight: bold;">( ..................................... )</p>
                </div>
            </div>

            <script>window.onload = function() { window.print(); }</script>
        </body>
        </html>';

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function destroy(ClassSchedule $schedule)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'koordinator_kurikulum'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($schedule->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
