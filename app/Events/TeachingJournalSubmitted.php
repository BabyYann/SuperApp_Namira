<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeachingJournalSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teacher_name;
    public $classroom_name;
    public $subject_name;
    public $unit_id;
    public $time;

    public function __construct(string $teacher_name, string $classroom_name, string $subject_name, int $unit_id, string $time)
    {
        $this->teacher_name   = $teacher_name;
        $this->classroom_name = $classroom_name;
        $this->subject_name   = $subject_name;
        $this->unit_id        = $unit_id;
        $this->time           = $time;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('teaching-journals'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'teacher_name'   => $this->teacher_name,
            'classroom_name' => $this->classroom_name,
            'subject_name'   => $this->subject_name,
            'unit_id'        => $this->unit_id,
            'time'           => $this->time,
        ];
    }
}
