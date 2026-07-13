<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'unit_id',
        'title',
        'slug',
        'description',
        'image_path',
        'location',
        'start_date',
        'end_date',
        'status',
        'author_id',
        'views',
        'registration_link',
        'contact_person',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $appends = [
        'computed_status',
    ];

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return 'completed';
        }

        if ($this->start_date && !$this->end_date && $now->gt($this->start_date->copy()->endOfDay())) {
            return 'completed';
        }

        return 'ongoing';
    }

    public function incrementViews(): void
    {
        $this->timestamps = false;
        $this->increment('views');
        $this->timestamps = true;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . time();
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
