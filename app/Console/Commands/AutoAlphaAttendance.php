<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\EmployeeAttendance;
use App\Modules\Yayasan\Models\Holiday;
use Carbon\Carbon;

class AutoAlphaAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-alpha';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generating alpha status for absent employees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // 1. Skip Sunday (General Rule)
        if ($today->isSunday()) {
            $this->info("Today is Sunday. No alpha generation needed.");
            return;
        }

        // 2. Fetch Active Users (Teachers & Staff)
        // Ensure they have valid profiles
        $users = User::whereHas('teacher_profile', function($q) {
            $q->where('is_active', true);
        })->orWhereHas('staff', function($q) {
            $q->where('is_active', true);
        })->get();

        $count = 0;

        foreach ($users as $user) {
            // Get User's Unit ID
            $unitId = null;
            if ($user->teacher_profile) $unitId = $user->teacher_profile->unit_id;
            elseif ($user->staff) $unitId = $user->staff->unit_id;

            // 3. Check Holiday (Global OR Unit Specific)
            $isHoliday = Holiday::whereDate('date', $today)
                ->where(function($q) use ($unitId) {
                    $q->whereNull('unit_id') // Global
                      ->orWhere('unit_id', $unitId); // Unit Specific
                })->exists();

            if ($isHoliday) {
                continue; // Skip this user
            }

            // 4. Check Existing Attendance
            $exists = EmployeeAttendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->exists();

            if (!$exists) {
                // 5. Create Alpha
                EmployeeAttendance::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'status' => 'alpha', // Make sure enum supports this or string
                    'note' => 'System Auto-Alpha',
                    'approval_status' => 'not_required', // Valid
                    // late_minutes default null/0
                ]);
                $count++;
            }
        }

        $this->info("Alpha generation complete. {$count} records created.");
    }
}
