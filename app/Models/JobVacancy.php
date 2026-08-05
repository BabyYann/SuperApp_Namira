<?php

namespace App\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'title',
        'slug',
        'category',
        'type',
        'quota',
        'description',
        'requirements',
        'deadline',
        'status',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'quota' => 'integer',
        'views_count' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($vacancy) {
            if (empty($vacancy->slug)) {
                $slug = Str::slug($vacancy->title);
                $count = static::where('slug', 'like', "{$slug}%")->count();
                $vacancy->slug = $count ? "{$slug}-{$count}" : $slug;
            }
        });
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applicants()
    {
        return $this->hasMany(JobApplicant::class, 'job_vacancy_id');
    }

    public function getCategoryLabelAttribute()
    {
        $map = [
            'teacher' => 'Tenaga Pendidik (Guru)',
            'staff' => 'Tenaga Kependidikan (Staf)',
            'operational' => 'Operasional & Sarpar',
            'other' => 'Lainnya',
        ];
        return $map[$this->category] ?? $this->category;
    }

    public function getTypeLabelAttribute()
    {
        $map = [
            'full_time' => 'Penuh Waktu (Full Time)',
            'part_time' => 'Paruh Waktu (Part Time)',
            'contract' => 'Kontrak',
        ];
        return $map[$this->type] ?? $this->type;
    }
}
