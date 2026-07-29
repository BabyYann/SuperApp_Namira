<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SarprasMaintenanceReported implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $item_name;
    public $reporter_name;
    public $issue_description;
    public $unit_id;
    public $reported_at;

    public function __construct(string $item_name, string $reporter_name, string $issue_description, int $unit_id, string $reported_at)
    {
        $this->item_name         = $item_name;
        $this->reporter_name     = $reporter_name;
        $this->issue_description = $issue_description;
        $this->unit_id          = $unit_id;
        $this->reported_at       = $reported_at;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('sarpras-maintenance'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'item_name'         => $this->item_name,
            'reporter_name'     => $this->reporter_name,
            'issue_description' => $this->issue_description,
            'unit_id'          => $this->unit_id,
            'reported_at'       => $this->reported_at,
        ];
    }
}
