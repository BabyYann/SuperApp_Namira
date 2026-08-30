<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Academic\Models\ClassSchedule;
use App\Services\NotificationDispatcher;
use App\Modules\Yayasan\Models\Holiday;
use Carbon\Carbon;

class SendTeachingJournalReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically send push notification reminders to teachers who have not filled their teaching journal after class time ends.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');

        // 1. Skip Sunday
        if ($today->isSunday()) {
            $this->info('Today is Sunday. Skipping journal reminders.');
            return;
        }

        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $dayName = $days[$today->format('l')] ?? 'Senin';

        // 2. Fetch all schedules that have ended by current time
        $schedules = ClassSchedule::with(['classroom', 'subject', 'teacher.user', 'unit'])
            ->where('day', $dayName)
            ->where('end_time', '<=', $currentTime)
            ->whereDoesntHave('journals', function ($q) use ($today) {
                $q->whereDate('date', $today);
            })
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            $unitId = $schedule->unit_id;

            // Check if today is a Holiday for this unit
            $isHoliday = Holiday::whereDate('date', $today)
                ->where(function ($q) use ($unitId) {
                    $q->whereNull('unit_id')->orWhere('unit_id', $unitId);
                })->exists();

            if ($isHoliday) {
                continue;
            }

            $teacherUser = $schedule->teacher?->user;
            if (!$teacherUser) {
                continue;
            }

            $timeSlot = substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5);
            $subjectName = $schedule->subject?->name ?? 'Mata Pelajaran';
            $classroomName = $schedule->classroom?->name ?? 'Kelas';
            $dateFormatted = $today->locale('id')->isoFormat('dddd, D MMMM Y');

            NotificationDispatcher::sendToUser(
                $teacherUser,
                '⏰ Pengingat: Isi Jurnal Mengajar',
                "Halo {$teacherUser->name}, jam mengajar {$subjectName} di {$classroomName} ({$timeSlot} WIB) telah selesai. Mohon segera melengkapi jurnal mengajar & presensi kelas hari ini.",
                'academic',
                [
                    'url' => route('yayasan.teaching-journal.create', [
                        'schedule_id' => $schedule->id,
                        'date' => $today->toDateString(),
                    ]),
                    'schedule_id' => $schedule->id,
                    'date' => $today->toDateString(),
                ]
            );

            $count++;
        }

        $this->info("Journal reminders dispatch complete. {$count} reminders sent.");
    }
}
