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
        'dob' => 'date:Y-m-d',
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

    public function quranEnrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Quran\Models\QuranEnrollment::class, 'student_id');
    }

    public function quranLevelHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Quran\Models\QuranLevelHistory::class, 'student_id');
    }

    public function quranAssessments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Quran\Models\QuranAssessment::class, 'student_id');
    }

    public function classPromotions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassPromotion::class, 'student_id');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\StudentAttendance::class, 'student_id');
    }

    public function checkins(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentCheckin::class, 'student_id');
    }

    public function extracurricularMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Extracurricular\Models\ExtracurricularMember::class, 'student_id');
    }

    public function violations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Counseling\Models\Violation::class, 'student_id');
    }

    public function achievements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Counseling\Models\Achievement::class, 'student_id');
    }

    public function counselingSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Counseling\Models\CounselingSession::class, 'student_id');
    }
}
