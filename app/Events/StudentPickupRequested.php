<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentPickupRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $student_name;
    public $classroom_name;
    public $pickup_status;
    public $requested_at;
    public $unit_id;

    public function __construct(string $student_name, string $classroom_name, string $pickup_status, string $requested_at, int $unit_id)
    {
        $this->student_name   = $student_name;
        $this->classroom_name = $classroom_name;
        $this->pickup_status  = $pickup_status;
        $this->requested_at   = $requested_at;
        $this->unit_id        = $unit_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('student-pickups'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'student_name'   => $this->student_name,
            'classroom_name' => $this->classroom_name,
            'pickup_status'  => $this->pickup_status,
            'requested_at'   => $this->requested_at,
            'unit_id'        => $this->unit_id,
        ];
    }
}
