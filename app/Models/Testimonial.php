<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'unit_id', 'name', 'role_or_title', 'quote', 'photo_path', 'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function unit(): BelongsTo {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
