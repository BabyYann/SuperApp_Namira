<?php

namespace App\Modules\Yayasan\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'description',
        'unit_id',
        'is_recurring',
        'event_type',
        'color',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // Default colors per event type
    public function getDisplayColorAttribute(): string
    {
        if ($this->color) {
            return $this->color;
        }

        return match($this->event_type) {
            'libur' => '#ef4444',  // Red
            'ujian' => '#f59e0b',  // Amber
            'event' => '#3b82f6',  // Blue
            'rapat' => '#8b5cf6',  // Purple
            default => '#6b7280', // Gray
        };
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
