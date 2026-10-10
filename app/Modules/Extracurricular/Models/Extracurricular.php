<?php

namespace App\Modules\Extracurricular\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use App\Modules\Sarpar\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extracurricular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'extracurriculars';

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'name',
        'category',
        'day_of_week',
        'start_time',
        'end_time',
        'room_id',
        'location_name',
        'max_quota',
        'is_mandatory',
        'gender_restriction',
        'target_levels',
        'description',
        'cover_image',
        'status',
        'created_by',
    ];

    protected $casts = [
        'target_levels' => 'array',
        'is_mandatory' => 'boolean',
        'max_quota' => 'integer',
    ];

    protected $appends = [
        'cover_image_url',
        'category_label',
        'formatted_schedule',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function instructors()
    {
        return $this->hasMany(ExtracurricularInstructor::class, 'extracurricular_id');
    }

    public function members()
    {
        return $this->hasMany(ExtracurricularMember::class, 'extracurricular_id');
    }

    public function activeMembers()
    {
        return $this->hasMany(ExtracurricularMember::class, 'extracurricular_id')->where('status', 'active');
    }

    public function sessions()
    {
        return $this->hasMany(ExtracurricularSession::class, 'extracurricular_id')->orderBy('date', 'desc');
    }

    public function grades()
    {
        return $this->hasMany(ExtracurricularGrade::class, 'extracurricular_id');
    }

    public function getCoverImageUrlAttribute()
    {
        if (!$this->cover_image) {
            return null;
        }
        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }
        return asset('storage/' . $this->cover_image);
    }

    public function getCategoryLabelAttribute()
    {
        return match ($this->category) {
            'olahraga' => 'Olahraga & Atletik',
            'seni_budaya' => 'Seni & Budaya',
            'keagamaan' => 'Keagamaan & Karakter',
            'sains_teknologi' => 'Sains & Teknologi',
            'kepanduan' => 'Kepanduan & Bela Diri',
            default => 'Peminatan Lainnya',
        };
    }

    public function getFormattedScheduleAttribute()
    {
        $day = $this->day_of_week ?: 'Fleksibel';
        $time = ($this->start_time && $this->end_time) ? "{$this->start_time} - {$this->end_time}" : '';
        return $time ? "{$day}, {$time} WIB" : $day;
    }
}
