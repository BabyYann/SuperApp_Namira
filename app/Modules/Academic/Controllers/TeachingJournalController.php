<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Chapter;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\LearningObjective;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingJournal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TeachingJournalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = $user->teacher_profile; // Assuming relationship exists

        // If not a teacher, maybe show empty or redirect (for admin testing, we might need a fallback)
        if (!$teacher) {
            // For development/admin testing, maybe fetch all schedules or handle gracefully
            // return redirect()->route('dashboard')->with('error', 'Anda bukan Guru.');
        }

        $date = $request->input('date', date('Y-m-d'));
        $dayName = $this->getDayName($date); // Helper to get 'Senin', 'Selasa', etc.

        // Fetch Schedule for Today
        $schedules = [];
        if ($teacher) {
            $schedules = ClassSchedule::with(['classroom', 'subject', 'journals' => function($q) use ($date) {
                $q->whereDate('date', $date);
            }])
            ->where('teacher_id', $teacher->id)
            ->where('day', $dayName)
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'classroom' => $schedule->classroom->name,
                    'subject' => $schedule->subject->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'is_filled' => $schedule->journals->isNotEmpty(),
                    'journal_id' => $schedule->journals->first()?->id,
                ];
            });
        }

        return Inertia::render('Academic/Journal/Index', [
            'schedules' => $schedules,
            'date' => $date,
        ]);
    }

    private function getDayName($date) {
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        return $days[date('l', strtotime($date))];
    }

    public function create(Request $request)
    {
        $scheduleId = $request->input('schedule_id');
        $date = $request->input('date', date('Y-m-d'));
        
        $schedule = null;
        $classroom = null;
        $subject = null;
        $students = [];
        $chapters = [];

        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();

        // If accessed via Schedule
        if ($scheduleId) {
            $schedule = ClassSchedule::with(['classroom', 'subject'])->findOrFail($scheduleId);
            
            // Ownership check
            if ($user->hasRole('teacher') || $teacher) {
                if (!$teacher || $schedule->teacher_id !== $teacher->id) {
                    abort(403, 'Anda tidak memiliki hak untuk mengisi jurnal guru lain.');
                }
            }

            // Unit isolation
            $unitId = session('active_unit_id');
            if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
                if ($schedule->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Unit tidak sesuai.');
                }
            }

            $classroom = $schedule->classroom;
            $subject = $schedule->subject;
        } else {
            // Ad-hoc mode (Manual select) - To be implemented if needed
            // For now, we assume entry via Schedule
        }

        if ($classroom && $subject) {
            // Fetch Students
            $students = Student::where('classroom_id', $classroom->id)
                ->orderBy('full_name')
                ->get(['id', 'full_name as name', 'nis']);

            // Fetch Existing TPs grouped by Chapter
            $chapters = Chapter::with(['learningObjectives' => function($q) {
                $q->select('id', 'chapter_id', 'code', 'description');
            }])
            ->where('subject_id', $subject->id)
            ->where('semester', session('active_semester', '1')) // Assuming active semester
            ->get();
        }

        return Inertia::render('Academic/Journal/Create', [
            'schedule' => $schedule,
            'date' => $date,
            'classroom' => $classroom,
            'subject' => $subject,
            'students' => $students,
            'existingChapters' => $chapters,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,sick,permission,alpha,late',
        ]);

        // Prevent Duplicate Journal
        $exists = TeachingJournal::where('class_schedule_id', $request->class_schedule_id)
            ->where('date', $request->date)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['class_schedule_id' => 'Jurnal untuk jadwal ini sudah dibuat. Silakan edit jurnal yang sudah ada.']);
        }

        // Security: Ensure Teacher Owns this Schedule
        $schedule = ClassSchedule::findOrFail($request->class_schedule_id);
        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();

        if ($user->hasRole('teacher') || $teacher) {
            if (!$teacher || $schedule->teacher_id !== $teacher->id) {
                abort(403, 'Anda tidak memiliki hak untuk mengisi jurnal guru lain.');
            }
        }

        $unitId = session('active_unit_id');
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($schedule->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $schedule->unit_id;
        }

        DB::transaction(function () use ($request, $teacher, $unitId) {
            // 1. Handle New TPs (JIT Creation)
            $newTpIds = [];
            if ($request->has('new_tps')) {
                foreach ($request->input('new_tps') as $newTp) {
                    // Find or Create Chapter
                    $chapter = Chapter::firstOrCreate(
                        [
                            'subject_id' => $request->subject_id,
                            'title' => $newTp['chapter_title'],
                            'unit_id' => $unitId,
                        ],
                        ['semester' => '1'] // Default if creating new
                    );

                    // Create TP
                    $tp = $chapter->learningObjectives()->create([
                        'unit_id' => $unitId,
                        'code' => $newTp['code'],
                        'description' => $newTp['description'],
                    ]);
                    
                    $newTpIds[] = $tp->id;
                }
            }

            // 2. Create Journal
            $journal = TeachingJournal::create([
                'unit_id' => $unitId,
                'teacher_id' => $teacher->id ?? null, // Assuming linked
                'class_schedule_id' => $request->class_schedule_id,
                'classroom_id' => $request->classroom_id,
                'subject_id' => $request->subject_id,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'custom_theme' => $request->custom_theme,
                'notes' => $request->notes,
                'photo_path' => $request->file('photo') ? $request->file('photo')->store('journal_photos', 'public') : null,
                'status' => 'submitted',
            ]);

            // 3. Attach TPs (Existing + New)
            $allTpIds = array_merge($request->input('selected_tps', []), $newTpIds);
            if (!empty($allTpIds)) {
                $journal->learningObjectives()->attach($allTpIds);
            }

            // 4. Save Attendance
            foreach ($request->attendance as $att) {
                $journal->attendance()->create([
                    'student_id' => $att['student_id'],
                    'status' => $att['status'],
                    'note' => $att['note'] ?? null,
                ]);
            }
        });

        return redirect()->route('yayasan.teaching-journal.index', ['date' => $request->date])->with('success', 'Jurnal Mengajar berhasil disimpan!');
    }

    public function show($id)
    {
        $journal = TeachingJournal::with([
            'classroom', 
            'subject', 
            'teacher', 
            'learningObjectives.chapter', 
            'attendance.student' => function($q) {
                $q->orderBy('full_name');
            }
        ])->findOrFail($id);

        $user = auth()->user();
        $unitId = session('active_unit_id');
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($journal->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        return Inertia::render('Academic/Journal/Show', [
            'journal' => $journal,
        ]);
    }

    public function edit($id)
    {
        $journal = TeachingJournal::with(['attendance', 'learningObjectives'])->findOrFail($id);
        
        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();

        // Ownership check
        if ($user->hasRole('teacher') || $teacher) {
            if (!$teacher || $journal->teacher_id !== $teacher->id) {
                abort(403, 'Anda tidak memiliki hak untuk mengubah jurnal ini.');
            }
        }

        // Unit isolation
        $unitId = session('active_unit_id');
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($journal->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        // Reuse the create view logic but populate with existing data
        $schedule = ClassSchedule::with(['classroom', 'subject'])->find($journal->class_schedule_id);
        
        // If schedule is missing (e.g. deleted), we might need a fallback, but for now assume it exists
        // or we use the journal's stored classroom/subject
        $classroom = $journal->classroom ?? $schedule->classroom;
        $subject = $journal->subject ?? $schedule->subject;

        $students = Student::where('classroom_id', $classroom->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name as name', 'nis']);

        $chapters = Chapter::with(['learningObjectives' => function($q) {
            $q->select('id', 'chapter_id', 'code', 'description');
        }])
        ->where('subject_id', $subject->id)
        ->where('semester', session('active_semester', '1'))
        ->get();

        return Inertia::render('Academic/Journal/Create', [
            'schedule' => $schedule,
            'date' => $journal->date,
            'classroom' => $classroom,
            'subject' => $subject,
            'students' => $students,
            'existingChapters' => $chapters,
            'journal' => $journal, // Pass existing journal for Edit Mode
        ]);
    }

    public function update(Request $request, $id)
    {
        $journal = TeachingJournal::findOrFail($id);

        $request->validate([
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,sick,permission,alpha,late',
        ]);

        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();

        // Security: Ensure Teacher Owns this Journal
        if ($user->hasRole('teacher') || $teacher) {
            if (!$teacher || $journal->teacher_id !== $teacher->id) {
                abort(403, 'Anda tidak memiliki hak untuk mengedit jurnal ini.');
            }
        }

        // Unit isolation
        $unitId = session('active_unit_id');
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($journal->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        } else {
            $unitId = $journal->unit_id;
        }

        DB::transaction(function () use ($request, $journal, $unitId) {
            // 1. Handle New TPs (JIT Creation)
            $newTpIds = [];
            if ($request->has('new_tps')) {
                foreach ($request->input('new_tps') as $newTp) {
                    $chapter = Chapter::firstOrCreate(
                        [
                            'subject_id' => $journal->subject_id,
                            'title' => $newTp['chapter_title'],
                            'unit_id' => $unitId,
                        ],
                        ['semester' => '1']
                    );

                    $tp = $chapter->learningObjectives()->create([
                        'unit_id' => $unitId,
                        'code' => $newTp['code'],
                        'description' => $newTp['description'],
                    ]);
                    
                    $newTpIds[] = $tp->id;
                }
            }

            // 2. Update Journal Details
            $journal->update([
                'custom_theme' => $request->custom_theme,
                'notes' => $request->notes,
                // Photo update logic if needed
            ]);

            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($journal->photo_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($journal->photo_path);
                }
                
                $journal->update([
                    'photo_path' => $request->file('photo')->store('journal_photos', 'public')
                ]);
            }

            // 3. Sync TPs
            $allTpIds = array_merge($request->input('selected_tps', []), $newTpIds);
            $journal->learningObjectives()->sync($allTpIds);

            // 4. Update Attendance
            // We delete existing and re-create, or update in place. Re-create is safer for full sync.
            $journal->attendance()->delete();
            foreach ($request->attendance as $att) {
                $journal->attendance()->create([
                    'student_id' => $att['student_id'],
                    'status' => $att['status'],
                    'note' => $att['note'] ?? null,
                ]);
            }
        });

        return redirect()->route('yayasan.teaching-journal.index', ['date' => $journal->date])->with('success', 'Jurnal Mengajar berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $journal = TeachingJournal::findOrFail($id);

        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();

        // Security: Ensure Teacher Owns this Journal or is Admin
        if ($user->hasRole('teacher') || $teacher) {
            if (!$teacher || $journal->teacher_id !== $teacher->id) {
                abort(403, 'Anda tidak memiliki hak untuk menghapus jurnal ini.');
            }
        }

        // Unit isolation
        $unitId = session('active_unit_id');
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($journal->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        // Delete associated photo if exists
        if ($journal->photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($journal->photo_path);
        }

        $date = $journal->date;
        $journal->delete();

        return redirect()->route('yayasan.teaching-journal.index', ['date' => $date])->with('success', 'Jurnal Mengajar berhasil dihapus.');
    }

    public function exportMonthly(Request $request)
    {
        $user = auth()->user();
        $teacher = $user->teacher_profile;
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $query = TeachingJournal::with(['classroom', 'subject', 'learningObjectives', 'attendance'])
            ->where('unit_id', session('active_unit_id'))
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        // If teacher, filter by their journals only
        if ($teacher && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            $query->where('teacher_id', $teacher->id);
        }

        $journals = $query->orderBy('date')->get();

        // Generate HTML for PDF
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('id')->monthName;
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rekap Jurnal Mengajar</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
                h1 { text-align: center; color: #0d9488; margin-bottom: 5px; font-size: 18px; }
                .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background: #0d9488; color: white; padding: 8px; text-align: left; border: 1px solid #0d9488; font-size: 10px; }
                td { padding: 6px 8px; border: 1px solid #ddd; vertical-align: top; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #999; }
                .stats { background: #f0fdf4; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
                .stats span { margin-right: 20px; }
            </style>
        </head>
        <body>
            <h1>📚 Rekap Jurnal Mengajar</h1>
            <p class="subtitle">Bulan ' . $monthName . ' ' . $year . ($teacher ? ' - ' . $teacher->full_name : '') . '</p>
            
            <div class="stats">
                <strong>Total:</strong> 
                <span>' . $journals->count() . ' sesi mengajar</span>
            </div>

            <table>
                <tr>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Tujuan Pembelajaran</th>
                    <th>Kehadiran</th>
                </tr>';
        
        foreach ($journals as $j) {
            $tps = $j->learningObjectives->map(fn($tp) => $tp->code)->join(', ') ?: $j->custom_theme ?: '-';
            $hadir = $j->attendance->where('status', 'present')->count();
            $total = $j->attendance->count();
            
            $html .= '<tr>
                <td>' . $j->date->format('d/m/Y') . '</td>
                <td>' . substr($j->start_time, 0, 5) . '-' . substr($j->end_time, 0, 5) . '</td>
                <td>' . ($j->classroom->name ?? '-') . '</td>
                <td>' . ($j->subject->name ?? '-') . '</td>
                <td>' . htmlspecialchars($tps) . '</td>
                <td>' . $hadir . '/' . $total . '</td>
            </tr>';
        }
        
        $html .= '</table>
            <p class="footer">Dicetak dari SuperApp Namira pada ' . date('d/m/Y H:i') . '</p>
        </body>
        </html>';

        $filename = 'jurnal_mengajar_' . $month . '_' . $year . '.html';
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
