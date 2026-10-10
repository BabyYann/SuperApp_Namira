<?php

namespace App\Modules\Extracurricular\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularInstructor extends Model
{
    use HasFactory;

    protected $table = 'extracurricular_instructors';

    protected $fillable = [
        'extracurricular_id',
        'user_id',
        'role_title',
        'is_primary',
        'phone',
        'institution',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
