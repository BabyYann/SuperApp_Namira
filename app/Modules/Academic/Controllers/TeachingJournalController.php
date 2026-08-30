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

use App\Models\StudentAttendance;
use App\Modules\Academic\Models\StudentCheckin;
use App\Modules\Yayasan\Models\Unit;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;

class TeachingJournalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = $user->teacher_profile ?? \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
        
        $hasAdminRole = $user->hasAnyRole([
            'super_admin_yayasan', 
            'admin_yayasan', 
            'pembina_yayasan', 
            'pengawas_yayasan', 
            'admin_unit', 
            'kepala_sekolah'
        ]);

        $isGlobalAdmin = $user->hasAnyRole([
            'super_admin_yayasan', 
            'admin_yayasan', 
            'pembina_yayasan', 
            'pengawas_yayasan'
        ]);

        // Mode switch (if teacher also has admin role, allow toggling via query param 'view=my' or 'view=monitoring')
        $viewMode = $request->input('view');
        if (!$viewMode) {
            $viewMode = $hasAdminRole ? 'monitoring' : 'my';
        }

        $date = $request->input('date', date('Y-m-d'));
        $dayName = $this->getDayName($date);

        // Fetch Units for Global Roles
        $units = [];
        if ($isGlobalAdmin) {
            $units = Unit::orderBy('id')->get(['id', 'name', 'code']);
        }

        // Active Unit Filter
        $selectedUnitId = $request->input('unit_id', session('active_unit_id'));
        if ($selectedUnitId === 'all' && !$isGlobalAdmin) {
            $selectedUnitId = session('active_unit_id');
        }

        $schedules = [];
        $stats = [
            'total' => 0,
            'filled' => 0,
            'unfilled' => 0,
            'compliance_rate' => 0,
        ];

        if ($viewMode === 'my' && $teacher) {
            // Teacher Personal View
            $schedules = ClassSchedule::with(['classroom', 'subject', 'journals' => function($q) use ($date) {
                $q->whereDate('date', $date);
            }])
            ->where('teacher_id', $teacher->id)
            ->where('day', $dayName)
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                $journal = $schedule->journals->first();
                return [
                    'id' => $schedule->id,
                    'classroom' => $schedule->classroom?->name ?? '-',
                    'subject' => $schedule->subject?->name ?? '-',
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'is_filled' => $journal !== null,
                    'journal_id' => $journal?->id,
                    'photo_path' => $journal?->photo_path,
                    'notes' => $journal?->notes,
                ];
            });

            $total = $schedules->count();
            $filled = $schedules->where('is_filled', true)->count();
            $stats = [
                'total' => $total,
                'filled' => $filled,
                'unfilled' => $total - $filled,
                'compliance_rate' => $total > 0 ? round(($filled / $total) * 100) : 0,
            ];
        } else {
            // Executive & Supervisory Monitoring View (Super Admin, Kepsek, Pengawas)
            $query = ClassSchedule::with([
                'unit:id,name,code',
                'classroom:id,name',
                'subject:id,name',
                'teacher.user:id,name',
                'journals' => function($q) use ($date) {
                    $q->whereDate('date', $date);
                }
            ])
            ->where('day', $dayName);

            // Filter Unit
            if ($selectedUnitId && $selectedUnitId !== 'all') {
                $query->where('unit_id', $selectedUnitId);
            } elseif (!$isGlobalAdmin) {
                $query->where('unit_id', session('active_unit_id'));
            }

            // Filter Classroom if provided
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', $request->classroom_id);
            }

            // Filter Teacher if provided
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            $rawSchedules = $query->orderBy('start_time')->get();

            $schedules = $rawSchedules->map(function ($schedule) {
                $journal = $schedule->journals->first();
                return [
                    'id' => $schedule->id,
                    'unit_id' => $schedule->unit_id,
                    'unit_name' => $schedule->unit?->name ?? '-',
                    'classroom_id' => $schedule->classroom_id,
                    'classroom' => $schedule->classroom?->name ?? '-',
                    'subject_id' => $schedule->subject_id,
                    'subject' => $schedule->subject?->name ?? '-',
                    'teacher_id' => $schedule->teacher_id,
                    'teacher_name' => $schedule->teacher?->user?->name ?? $schedule->teacher?->full_name ?? 'Belum Ditugaskan',
                    'teacher_user_id' => $schedule->teacher?->user_id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'is_filled' => $journal !== null,
                    'journal_id' => $journal?->id,
                    'photo_path' => $journal?->photo_path,
                    'custom_theme' => $journal?->custom_theme,
                    'notes' => $journal?->notes,
                    'filled_at' => $journal?->created_at ? Carbon::parse($journal->created_at)->format('H:i') : null,
                ];
            });

            // Filter status if requested
            $statusFilter = $request->input('status');
            if ($statusFilter === 'filled') {
                $schedules = $schedules->where('is_filled', true)->values();
            } elseif ($statusFilter === 'unfilled') {
                $schedules = $schedules->where('is_filled', false)->values();
            }

            $total = $rawSchedules->count();
            $filled = $rawSchedules->filter(fn($s) => $s->journals->isNotEmpty())->count();
            $stats = [
                'total' => $total,
                'filled' => $filled,
                'unfilled' => $total - $filled,
                'compliance_rate' => $total > 0 ? round(($filled / $total) * 100) : 0,
            ];
        }

        return Inertia::render('Academic/Journal/Index', [
            'schedules' => $schedules,
            'date' => $date,
            'stats' => $stats,
            'viewMode' => $viewMode,
            'hasAdminRole' => $hasAdminRole,
            'isGlobalAdmin' => $isGlobalAdmin,
            'isTeacher' => $teacher !== null,
            'units' => $units,
            'selectedUnitId' => $selectedUnitId,
            'filters' => [
                'status' => $request->input('status', 'all'),
                'unit_id' => $selectedUnitId,
            ],
        ]);
    }

    public function sendReminder(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:class_schedules,id',
            'date' => 'required|date',
        ]);

        $schedule = ClassSchedule::with(['classroom', 'subject', 'teacher.user'])->findOrFail($request->schedule_id);
        $teacherUser = $schedule->teacher?->user;

        if (!$teacherUser) {
            return redirect()->back()->with('error', 'Akun pengguna untuk guru jadwal ini tidak ditemukan.');
        }

        $timeSlot = substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5);
        $subjectName = $schedule->subject?->name ?? 'Mata Pelajaran';
        $classroomName = $schedule->classroom?->name ?? 'Kelas';
        $dateFormatted = Carbon::parse($request->date)->locale('id')->isoFormat('dddd, D MMMM Y');

        NotificationDispatcher::sendToUser(
            $teacherUser,
            '⏰ Pengingat: Jurnal Belum Diisi',
            "Halo {$teacherUser->name}, jam mengajar {$subjectName} di {$classroomName} ({$timeSlot} WIB) pada {$dateFormatted} belum diisi. Mohon segera melengkapi jurnal mengajar & presensi kelas.",
            'academic',
            [
                'url' => route('yayasan.teaching-journal.create', [
                    'schedule_id' => $schedule->id,
                    'date' => $request->date,
                ]),
                'schedule_id' => $schedule->id,
                'date' => $request->date,
            ]
        );

        return redirect()->back()->with('success', "Pengingat berhasil dikirimkan ke HP & akun {$teacherUser->name}.");
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
        }

        if ($classroom && $subject) {
            // Fetch Daily Attendance & Gate Checkins for auto-population
            $dailyAttendances = StudentAttendance::where('classroom_id', $classroom->id)
                ->whereDate('date', $date)
                ->get()
                ->keyBy('student_id');

            $gateCheckins = StudentCheckin::whereDate('checkin_date', $date)
                ->get()
                ->keyBy('student_id');

            // Fetch Students with Auto-Populated Statuses
            $students = Student::where('classroom_id', $classroom->id)
                ->orderBy('full_name')
                ->get(['id', 'full_name as name', 'nis'])
                ->map(function ($s) use ($dailyAttendances, $gateCheckins) {
                    $daily = $dailyAttendances->get($s->id);
                    $gate  = $gateCheckins->get($s->id);

                    $defaultStatus = 'present';
                    $defaultNote   = '';
                    $sourceBadge   = null;

                    if ($daily) {
                        if ($daily->status === 'S') {
                            $defaultStatus = 'sick';
                            $defaultNote   = $daily->note ?: 'Sakit (Wali Kelas)';
                            $sourceBadge   = 'Sakit (Wali Kelas)';
                        } elseif ($daily->status === 'I') {
                            $defaultStatus = 'permission';
                            $defaultNote   = $daily->note ?: 'Izin (Wali Kelas)';
                            $sourceBadge   = 'Izin (Wali Kelas)';
                        } elseif ($daily->status === 'A') {
                            $defaultStatus = 'alpha';
                            $defaultNote   = 'Alpha (Wali Kelas)';
                            $sourceBadge   = 'Alpha (Wali Kelas)';
                        } elseif ($daily->status === 'H') {
                            $defaultStatus = 'present';
                            $defaultNote   = $daily->note ?: 'Hadir (Wali Kelas)';
                            $sourceBadge   = 'Hadir (Wali Kelas)';
                        }
                    }

                    if ($gate && !$sourceBadge) {
                        $defaultStatus = $gate->status === 'terlambat' ? 'late' : 'present';
                        $defaultNote   = "Scan Gerbang {$gate->checkin_time} WIB";
                        $sourceBadge   = "Scan Gerbang {$gate->checkin_time}";
                    }

                    return [
                        'id'             => $s->id,
                        'name'           => $s->name,
                        'nis'            => $s->nis,
                        'default_status' => $defaultStatus,
                        'default_note'   => $defaultNote,
                        'source_badge'   => $sourceBadge,
                    ];
                });

            // Fetch Existing TPs grouped by Chapter (Filtered by Classroom Grade Level & Semester)
            $gradeLevel = (int) filter_var($classroom->level ?? $classroom->name, FILTER_SANITIZE_NUMBER_INT);

            $chaptersQuery = Chapter::with(['learningObjectives' => function($q) {
                $q->select('id', 'chapter_id', 'code', 'description');
            }])
            ->where('subject_id', $subject->id)
            ->where('semester', session('active_semester', '1'));

            if ($gradeLevel > 0) {
                $chaptersQuery->where(function($q) use ($gradeLevel) {
                    $q->where('grade_level', $gradeLevel)->orWhereNull('grade_level');
                });
            }

            $chapters = $chaptersQuery->get();
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
                    // Find or Create Chapter with Classroom Grade Level
                    $classroomModel = Classroom::find($request->classroom_id);
                    $targetGradeLevel = (int) filter_var($classroomModel?->level ?? $classroomModel?->name, FILTER_SANITIZE_NUMBER_INT) ?: 1;

                    $chapter = Chapter::firstOrCreate(
                        [
                            'subject_id' => $request->subject_id,
                            'title' => $newTp['chapter_title'],
                            'unit_id' => $unitId,
                            'grade_level' => $targetGradeLevel,
                        ],
                        ['semester' => session('active_semester', '1')]
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

            // 2. Process Photo (Supports both File upload and Base64 compressed image)
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('journal_photos', 'public');
            } elseif ($request->photo && is_string($request->photo) && str_starts_with($request->photo, 'data:image')) {
                $image = $request->photo;
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = 'journal_' . ($teacher->id ?? auth()->id()) . '_' . time() . '.jpg';
                if (!file_exists(storage_path('app/public/journal_photos'))) {
                    mkdir(storage_path('app/public/journal_photos'), 0777, true);
                }
                \Illuminate\Support\Facades\Storage::disk('public')->put('journal_photos/' . $imageName, base64_decode($image));
                $photoPath = 'journal_photos/' . $imageName;
            }

            // Create Journal
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
                'photo_path' => $photoPath,
                'status' => 'submitted',
            ]);

            // Broadcast Reverb Event
            try {
                $teacherUser = Auth::user();
                $classroomModel = \App\Modules\Academic\Models\Classroom::find($request->classroom_id);
                $subjectModel   = \App\Modules\Academic\Models\Subject::find($request->subject_id);

                event(new \App\Events\TeachingJournalSubmitted(
                    $teacherUser->name ?? 'Guru',
                    $classroomModel->name ?? '-',
                    $subjectModel->name ?? '-',
                    (int) $unitId,
                    Carbon::now()->format('H:i:s')
                ));
            } catch (\Throwable $e) {
                // Fail-safe if Reverb offline
            }

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

        $gradeLevel = (int) filter_var($classroom->level ?? $classroom->name, FILTER_SANITIZE_NUMBER_INT);

        $chaptersQuery = Chapter::with(['learningObjectives' => function($q) {
            $q->select('id', 'chapter_id', 'code', 'description');
        }])
        ->where('subject_id', $subject->id)
        ->where('semester', session('active_semester', '1'));

        if ($gradeLevel > 0) {
            $chaptersQuery->where(function($q) use ($gradeLevel) {
                $q->where('grade_level', $gradeLevel)->orWhereNull('grade_level');
            });
        }

        $chapters = $chaptersQuery->get();

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
                    $classroomModel = $journal->classroom;
                    $targetGradeLevel = (int) filter_var($classroomModel?->level ?? $classroomModel?->name, FILTER_SANITIZE_NUMBER_INT) ?: 1;

                    $chapter = Chapter::firstOrCreate(
                        [
                            'subject_id' => $journal->subject_id,
                            'title' => $newTp['chapter_title'],
                            'unit_id' => $unitId,
                            'grade_level' => $targetGradeLevel,
                        ],
                        ['semester' => session('active_semester', '1')]
                    );

                    $tp = $chapter->learningObjectives()->create([
                        'unit_id' => $unitId,
                        'code' => $newTp['code'],
                        'description' => $newTp['description'],
                    ]);
                    
                    $newTpIds[] = $tp->id;
                }
            }

            $photoPath = $journal->photo_path;
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($journal->photo_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($journal->photo_path);
                }
                $photoPath = $request->file('photo')->store('journal_photos', 'public');
            } elseif ($request->photo && is_string($request->photo) && str_starts_with($request->photo, 'data:image')) {
                // Delete old photo if exists
                if ($journal->photo_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($journal->photo_path);
                }
                $image = $request->photo;
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = 'journal_' . ($teacher->id ?? auth()->id()) . '_' . time() . '.jpg';
                if (!file_exists(storage_path('app/public/journal_photos'))) {
                    mkdir(storage_path('app/public/journal_photos'), 0777, true);
                }
                \Illuminate\Support\Facades\Storage::disk('public')->put('journal_photos/' . $imageName, base64_decode($image));
                $photoPath = 'journal_photos/' . $imageName;
            }

            // 2. Update Journal Details
            $journal->update([
                'custom_theme' => $request->custom_theme,
                'notes' => $request->notes,
                'photo_path' => $photoPath,
            ]);

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
            <p class="footer">Dicetak dari Namira App pada ' . date('d/m/Y H:i') . '</p>
        </body>
        </html>';

        $filename = 'jurnal_mengajar_' . $month . '_' . $year . '.html';
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
