<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCheckedIn implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employee_name;
    public $status;
    public $time;
    public $type;
    public $unit_ids;

    /**
     * Create a new event instance.
     *
     * @param string $employee_name
     * @param string $status
     * @param string $time
     * @param string $type
     * @param array $unit_ids
     */
    public function __construct(string $employee_name, string $status, string $time, string $type, array $unit_ids)
    {
        $this->employee_name = $employee_name;
        $this->status = $status;
        $this->time = $time;
        $this->type = $type;
        $this->unit_ids = $unit_ids;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast on a public channel named 'attendance' for simple real-time dashboard notifications
        return [
            new Channel('attendance'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'employee_name' => $this->employee_name,
            'status' => $this->status,
            'time' => $this->time,
            'type' => $this->type,
            'unit_ids' => $this->unit_ids,
        ];
    }
}
