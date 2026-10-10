<?php

namespace App\Modules\Extracurricular\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularSession extends Model
{
    use HasFactory;

    protected $table = 'extracurricular_sessions';

    protected $fillable = [
        'extracurricular_id',
        'academic_year_id',
        'instructor_id',
        'date',
        'start_time',
        'end_time',
        'topic',
        'notes',
        'photo_paths',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'photo_paths' => 'array',
    ];

    protected $appends = [
        'photo_urls',
        'attendees_summary',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class, 'session_id');
    }

    public function getPhotoUrlsAttribute()
    {
        if (empty($this->photo_paths)) {
            return [];
        }

        $paths = is_array($this->photo_paths) ? $this->photo_paths : (json_decode($this->photo_paths, true) ?: []);

        return array_map(function ($p) {
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                return $p;
            }
            return asset('storage/' . $p);
        }, $paths);
    }

    public function getAttendeesSummaryAttribute()
    {
        $hadir = $this->attendances->where('status', 'hadir')->count();
        $izin = $this->attendances->where('status', 'izin')->count();
        $sakit = $this->attendances->where('status', 'sakit')->count();
        $alpha = $this->attendances->where('status', 'alpha')->count();
        $total = $this->attendances->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpha' => $alpha,
            'percentage' => $total > 0 ? round(($hadir / $total) * 100) : 0,
        ];
    }
}
