<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareAuthorizedPickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'relationship',
        'phone',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }
}
