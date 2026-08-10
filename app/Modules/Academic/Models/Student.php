<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\StudentFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unit_id',
        'nis',
        'nisn',
        'full_name',
        'gender',
        'parent_name',
        'parent_phone',
        'guardian_name',
        'guardian_phone',
        'address',
        'pob',
        'dob',
        'photo',
        'classroom_id',
        'academic_year_id',
        'va_number',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    protected static function newFactory()
    {
        return StudentFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function bills()
    {
        return $this->hasMany(\App\Modules\Finance\Models\StudentBill::class);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Modules\Finance\Models\Transaction::class);
    }

    public function daycareProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\Daycare\Models\DaycareChildProfile::class, 'student_id');
    }

    public function authorizedPickups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Daycare\Models\DaycareAuthorizedPickup::class, 'student_id');
    }

    public function daycareAttendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Daycare\Models\DaycareAttendance::class, 'student_id');
    }

    public function daycareDailyLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Daycare\Models\DaycareDailyLog::class, 'student_id');
    }

    public function growthRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Daycare\Models\DaycareGrowthRecord::class, 'student_id');
    }

    public function developmentalJournals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Daycare\Models\DaycareDevelopmentalJournal::class, 'student_id');
    }
}
