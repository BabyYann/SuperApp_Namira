<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentScannedAtGate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $student_name;
    public $nis;
    public $classroom;
    public $checkin_time;
    public $status;
    public $unit_id;

    public function __construct(string $student_name, string $nis, string $classroom, string $checkin_time, string $status, int $unit_id)
    {
        $this->student_name = $student_name;
        $this->nis          = $nis;
        $this->classroom    = $classroom;
        $this->checkin_time = $checkin_time;
        $this->status       = $status;
        $this->unit_id      = $unit_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('student-gate-scan'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'student_name' => $this->student_name,
            'nis'          => $this->nis,
            'classroom'    => $this->classroom,
            'checkin_time' => $this->checkin_time,
            'status'       => $this->status,
            'unit_id'      => $this->unit_id,
        ];
    }
}
