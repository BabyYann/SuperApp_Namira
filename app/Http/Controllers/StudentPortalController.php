<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Modules\Academic\Models\Student;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\StudentCheckin;
use App\Modules\Academic\Models\StudentPickupRequest;
use Carbon\Carbon;
use App\Models\StudentTask;
use App\Models\StudentAttendance;
use App\Modules\Counseling\Models\Violation;

class StudentPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        // 1. Get Student Profile & Class
        // We assume the User is linked to a Student Profile via 'user_id'
        // Or we match by email? Best to rely on 'user_id' in students table if exists.
        // Let's check Student model first.
        $student = Student::where('user_id', $user->id)->with('classroom')->first();

        // 2. Finance: Active Bills (Unpaid)
        $activeBill = null;
        $activeBillCount = 0;
        
        if ($student) {
            $unpaidBills = StudentBill::where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'partial']) // Include partial
                ->with('financeType') // Correct relationship
                ->get();
                
            $activeBillCount = $unpaidBills->count();
            // Calculate remaining amount: Final - Paid
            $totalUnpaid = $unpaidBills->sum(function($bill) {
                return $bill->final_amount - $bill->paid_amount;
            });
            
            if ($activeBillCount > 0) {
                // Determine the "Main" bill to show (e.g. latest or oldest?)
                // Let's show the total and the title of the latest one
                $latest = $unpaidBills->last();
                $activeBill = [
                    'amount' => 'Rp ' . number_format($totalUnpaid, 0, ',', '.'),
                    'title' => $activeBillCount . ' Tagihan Belum Lunas',
                    'status' => 'unpaid',
                    'dueDate' => $latest->created_at->addDays(14)->format('d M Y') // Mock due date logic
                ];
            }
        }

        // 3. Today's Schedule
        $todaysSchedule = [];
        if ($student && $student->classroom_id) {
            // Ensure Day Name matches Indonesian (Database Format)
            Carbon::setLocale('id'); // Force ID for this request just in case
            $dayName = Carbon::now()->isoFormat('dddd'); 

            // Fallback Mapping
            $dayMap = [
                'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
            ];

            // If isoFormat returns English, translate it.
            if (isset($dayMap[$dayName])) {
                $dayName = $dayMap[$dayName];
            }
            
            // Debugging Safety: If it's still English/Mismatch, try to force it from date
            if (!in_array($dayName, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])) {
                 $englishDay = Carbon::now()->format('l');
                 $dayName = $dayMap[$englishDay] ?? $dayName;
            }
            
            $todaysSchedule = ClassSchedule::where('classroom_id', $student->classroom_id)
                ->where('day', $dayName)
                ->with(['subject', 'teacher'])
                ->orderBy('start_time')
                ->get()
                ->map(function ($q) {
                    return [
                        'time' => substr($q->start_time, 0, 5) . ' - ' . substr($q->end_time, 0, 5),
                        'subject' => $q->subject->name ?? 'Unknown',
                        'room' => $q->classroom->name, // Explicitly use classroom name as room
                        'teacher' => $q->teacher?->full_name ?? '-',
                    ];
                });
        }

        // 4. Counseling: Violation Points Summary
        $totalPoints = 0;
        $counselingStatus = ['label' => 'AMAN', 'color' => 'text-green-600', 'bg' => 'bg-green-50'];
        
        if ($student) {
            $totalPoints = Violation::where('student_id', $student->id)->sum('points');
            if ($totalPoints > 20) {
                $counselingStatus = ['label' => 'WASPADA', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'];
            }
            if ($totalPoints > 50) {
                $counselingStatus = ['label' => 'BAHAYA', 'color' => 'text-red-600', 'bg' => 'bg-red-50'];
            }
        }

        // 5. Productivity: Today's Tasks
        $taskSummary = ['total' => 0, 'completed' => 0];
        if ($student) {
            $tasks = StudentTask::where('student_id', $student->id)
                ->whereDate('date', Carbon::today())
                ->get();
            $taskSummary = [
                'total' => $tasks->count(),
                'completed' => $tasks->where('is_completed', true)->count(),
            ];
        }

        // 6. Attendance Summary
        $attendance = ['present' => 0, 'sick' => 0, 'permission' => 0, 'alpha' => 0];
        if ($student) {
            $attendanceStats = StudentAttendance::where('student_id', $student->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $attendance = [
                'present' => $attendanceStats['H'] ?? 0,
                'sick' => $attendanceStats['S'] ?? 0,
                'permission' => $attendanceStats['I'] ?? 0,
                'alpha' => $attendanceStats['A'] ?? 0,
            ];
        }

        // 7. Recent Achievements (Badge)
        $latestAchievement = null;
        if ($student) {
            $latestAchievement = \App\Modules\Counseling\Models\Achievement::where('student_id', $student->id)
                ->latest('date')
                ->first();
        }

        // 8. Check-in Gerbang Hari Ini
        $checkinToday = null;
        if ($student) {
            $checkinToday = StudentCheckin::where('student_id', $student->id)
                ->whereDate('checkin_date', Carbon::today())
                ->first();
        }

        // 9. School GPS Locations (from attendance_locations or fallback)
        $schoolLocations = [];
        if ($student && $student->unit_id) {
            $locations = \DB::table('attendance_locations')
                ->where('unit_id', $student->unit_id)
                ->where('is_active', 1)
                ->get()
                ->map(fn($loc) => [
                    'name'   => $loc->name,
                    'lat'    => (float) $loc->latitude,
                    'lng'    => (float) $loc->longitude,
                    'radius' => max((int) $loc->radius, 500), // Min 500m for pickup convenience
                ]);
            $schoolLocations = $locations->toArray();
        }

        // Fallback if no locations configured
        if (empty($schoolLocations)) {
            $schoolLocations[] = [
                'name'   => 'Sekolah',
                'lat'    => -6.8976,
                'lng'    => 109.6681,
                'radius' => 500,
            ];
        }

        // 10. Latest Pickup Request (to calculate cooldown on frontend)
        $lastPickup = null;
        if ($student) {
            $lastPickup = StudentPickupRequest::where('student_id', $student->id)
                ->latest('requested_at')
                ->first();
        }

        return Inertia::render('Student/Dashboard', [
            'student'      => $student,
            'activeBill'   => $activeBill,
            'schedule'     => $todaysSchedule,
            'todayDate'    => Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y'),
            'counseling'   => [
                'total_points' => $totalPoints,
                'status'       => $counselingStatus
            ],
            'tasks'       => $taskSummary,
            'attendance'  => $attendance,
            'latestAchievement' => $latestAchievement ? [
                'title' => $latestAchievement->title,
                'level' => $latestAchievement->level,
            ] : null,
            // Checkin gerbang hari ini
            'checkinToday' => $checkinToday ? [
                'time'   => substr($checkinToday->checkin_time, 0, 5),
                'status' => $checkinToday->status,
            ] : null,
            // List lokasi GPS sekolah untuk Tombol Jemput
            'schoolLocations' => $schoolLocations,
            'lastPickupTime'  => $lastPickup ? $lastPickup->requested_at->toIso8601String() : null,
        ]);
    }

    /**
     * POST /student/pickup-request
     * Orang tua menekan Tombol Jemput dari Dashboard
     */
    public function pickupRequest(Request $request)
    {
        $request->validate([
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $student = Student::where('user_id', Auth::id())->with(['classroom'])->first();
        if (!$student) {
            return redirect()->back()->withErrors(['message' => 'Data siswa tidak ditemukan.']);
        }

        // Cek jika baru saja kirim permintaan penjemputan dalam 5 menit terakhir
        $lastRequest = StudentPickupRequest::where('student_id', $student->id)
            ->where('requested_at', '>=', Carbon::now()->subMinutes(5))
            ->first();

        if ($lastRequest) {
            return redirect()->back()->withErrors([
                'pickup_error' => 'Mohon tunggu 5 menit sebelum mengirim permintaan penjemputan kembali.'
            ]);
        }

        // Simpan pickup request
        $pickup = StudentPickupRequest::create([
            'student_id'   => $student->id,
            'requested_by' => Auth::id(),
            'status'       => 'pending',
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'requested_at' => Carbon::now(),
        ]);

        // TODO: Broadcast event ke komputer sekolah via Reverb
        // event(new \App\Events\StudentPickupRequested($student, $pickup));

        return redirect()->back();
    }

    public function academic() {
        $student = $this->getStudent();
        if (!$student) return redirect()->route('dashboard'); // Should not happen with middleware

        // Weekly Schedule
        $schedule = ClassSchedule::where('classroom_id', $student->classroom_id)
            ->with(['subject', 'teacher'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');

        // Subjects (Mata Pelajaran)
        // Derive from schedule or classroom relationship? 
        // Better to show subjects linked to the class?
        // For now, let's extract unique subjects from schedule
        $subjects = $schedule->flatten()->pluck('subject')->filter()->unique('id')->values();

        // Attendance Summary (Real Data)
        $attendanceStats = \App\Models\StudentAttendance::where('student_id', $student->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $attendance = [
            'present' => $attendanceStats['H'] ?? 0,
            'sick' => $attendanceStats['S'] ?? 0,
            'permission' => $attendanceStats['I'] ?? 0,
            'alpha' => $attendanceStats['A'] ?? 0,
        ];

        // Fetch Teaching Journals (Materi Pembelajaran)
        $journals = \App\Modules\Academic\Models\TeachingJournal::where('classroom_id', $student->classroom_id)
            ->where('status', 'submitted') // Corrected status: draft/submitted
            ->with(['subject', 'teacher', 'learningObjectives'])
            ->latest('date')
            ->take(10)
            ->get()
            ->map(function ($journal) {
                // Ensure consistent Localized Date
                $journalDay = $journal->date->locale('id')->isoFormat('dddd');
                return [
                    'id' => $journal->id,
                    'date' => $journal->date->format('d M Y'),
                    'day' => $journalDay,
                    'subject' => $journal->subject->name ?? 'Unknown',
                    'teacher' => $journal->teacher->full_name ?? '-',
                    'topic' => $journal->custom_theme ?? $journal->notes ?? 'Tanpa Topik',
                    'objectives' => $journal->learningObjectives->pluck('desc')->take(2), // Take 2 main points
                ];
            });

        return Inertia::render('Student/Academic', [
            'student' => $student,
            'schedule' => $schedule,
            'subjects' => $subjects,
            'attendance' => $attendance,
            'journals' => $journals,
        ]);
    }

    public function counseling(Request $request) {
        $student = $this->getStudent();
        if (!$student) return redirect()->route('dashboard');

        // Fetch Violations
        $violations = \App\Modules\Counseling\Models\Violation::where('student_id', $student->id)
            ->with('category')
            ->latest('date')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'date' => $v->date->format('d M Y'),
                    'category' => $v->category->name,
                    'points' => $v->points,
                    'description' => $v->description,
                    'photo_url' => $v->photo_path ? asset('storage/' . $v->photo_path) : null,
                ];
            });

        // Fetch Achievements
        $achievements = \App\Modules\Counseling\Models\Achievement::where('student_id', $student->id)
            ->latest('date')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'date' => $a->date->format('d M Y'),
                    'title' => $a->title,
                    'level' => $a->level,
                    'description' => $a->description,
                   'proof_url' => $a->proof_file ? asset('storage/' . $a->proof_file) : null,
                ];
            });

        $totalPoints = $violations->sum('points');
        
        // Status Logic
        $status = 'AMAN';
        $statusColor = 'text-green-600 bg-green-50 border-green-200';
        
        if ($totalPoints > 20) {
            $status = 'WASPADA';
            $statusColor = 'text-yellow-600 bg-yellow-50 border-yellow-200';
        }
        if ($totalPoints > 50) {
            $status = 'BAHAYA';
            $statusColor = 'text-red-600 bg-red-50 border-red-200';
        }

        // Fetch Counseling Sessions
        $sessions = \App\Modules\Counseling\Models\CounselingSession::where('student_id', $student->id)
            ->with('counselor')
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'date' => $s->date->format('d M Y'),
                'time' => $s->time->format('H:i'),
                'method' => $s->method,
                'status' => $s->status,
                'counselor_name' => $s->counselor->name ?? 'Konselor',
                'notes_status' => $s->notes ? 'Ada Catatan' : '-', // Don't show notes
            ]);

        return Inertia::render('Student/Counseling', [
            'student' => $student,
            'violations' => $violations,
            'achievements' => $achievements,
            'sessions' => $sessions,
            'summary' => [
                'total_points' => $totalPoints,
                'status' => $status,
                'status_color' => $statusColor,
            ]
        ]);
    }

    public function finance() {
        $student = $this->getStudent();
        if (!$student) return redirect()->route('dashboard');

        // Fetch All Bills
        $bills = StudentBill::where('student_id', $student->id)
            ->with('financeType') // Corrected relation
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'title' => $bill->financeType->name, // Corrected column
                    'amount' => $bill->final_amount, // Corrected column
                    'amount_formatted' => 'Rp ' . number_format($bill->final_amount, 0, ',', '.'),
                    'paid' => $bill->paid_amount,
                    'remaining' => $bill->final_amount - $bill->paid_amount,
                    'remaining_formatted' => 'Rp ' . number_format($bill->final_amount - $bill->paid_amount, 0, ',', '.'),
                    'status' => $bill->status, 
                    'date' => $bill->created_at->format('d M Y'),
                    'due_date' => $bill->created_at->addWeeks(2)->format('d M Y'), // Mock due date
                    'payment_date' => $bill->updated_at->format('d M Y'), // Mock payment date
                ];
            });

        $unpaidTotal = $bills->whereIn('status', ['unpaid', 'partial'])->sum('remaining');
        
        $financeAccounts = []; // Hidden to prevent ambiguity (Manual Transfer disabled)

        return Inertia::render('Student/Finance', [
            'student' => $student, // Contains va_number
            'bills' => $bills,
            'finance_accounts' => $financeAccounts,
            'stats' => [
                'unpaid_total' => 'Rp ' . number_format($unpaidTotal, 0, ',', '.'),
                'paid_count' => $bills->where('status', 'paid')->count(),
                'pending_count' => $bills->whereIn('status', ['unpaid', 'partial'])->count(),
            ]
        ]);
    }
    
    public function menu() {
        return Inertia::render('Student/Menu', [
             'user' => Auth::user(),
        ]);
    }

    private function getStudent()
    {
        return Student::where('user_id', Auth::id())->with(['classroom', 'unit'])->first();
    }
}
