<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\TeacherFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unit_id',
        'nip',
        'full_name',
        'gender',
        'phone',
        'photo',
    ];

    protected static function newFactory()
    {
        return TeacherFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function classroom() // As Wali Kelas
    {
        return $this->hasOne(Classroom::class, 'homeroom_teacher_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'teacher_id');
    }
}
