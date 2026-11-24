<?php

namespace App\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'title',
        'poster_path',
        'slug',
        'category',
        'type',
        'quota',
        'description',
        'requirements',
        'deadline',
        'contact_whatsapp',
        'placement_address',
        'status',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'quota' => 'integer',
        'views_count' => 'integer',
    ];

    protected $appends = [
        'category_label',
        'type_label',
        'poster_url',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($vacancy) {
            if (empty($vacancy->slug)) {
                $baseSlug = Str::slug($vacancy->title) ?: 'lowongan';
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $vacancy->slug = $slug;
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

    public function getPosterUrlAttribute(): ?string
    {
        if (empty($this->poster_path)) {
            return null;
        }

        if (Str::startsWith($this->poster_path, ['http://', 'https://', '/'])) {
            return $this->poster_path;
        }

        return Storage::url($this->poster_path);
    }

    public function getCategoryLabelAttribute(): string
    {
        $map = [
            'teacher' => 'Tenaga Pendidik (Guru)',
            'staff' => 'Tenaga Kependidikan (Staf)',
            'operational' => 'Operasional & Sarpras',
            'other' => 'Lainnya',
        ];

        return $map[$this->category] ?? (string) $this->category;
    }

    public function getTypeLabelAttribute(): string
    {
        $map = [
            'full_time' => 'Penuh Waktu (Full Time)',
            'part_time' => 'Paruh Waktu (Part Time)',
            'contract' => 'Kontrak',
        ];

        return $map[$this->type] ?? (string) $this->type;
    }
}
