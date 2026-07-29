<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentTransactionRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $student_name;
    public $amount;
    public $payment_type;
    public $unit_id;
    public $time;

    public function __construct(string $student_name, float $amount, string $payment_type, int $unit_id, string $time)
    {
        $this->student_name = $student_name;
        $this->amount       = $amount;
        $this->payment_type = $payment_type;
        $this->unit_id      = $unit_id;
        $this->time         = $time;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('finance-transactions'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'student_name' => $this->student_name,
            'amount'       => $this->amount,
            'payment_type' => $this->payment_type,
            'unit_id'      => $this->unit_id,
            'time'         => $this->time,
        ];
    }
}
