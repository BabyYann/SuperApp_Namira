<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareChildProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'nickname',
        'blood_type',
        'allergies',
        'special_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
        'routine_notes',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }
}
