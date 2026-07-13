<?php

namespace App\Modules\Yayasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppQueue extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages_queue';

    protected $fillable = [
        'phone',
        'message',
        'status',
        'retry_count',
        'last_attempt_at',
        'next_attempt_at',
        'error_message',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];
}
