<?php

namespace App\Modules\Quran\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranSetting extends Model
{
    protected $table = 'quran_settings';

    protected $fillable = [
        'unit_id',
        'school_name',
        'school_address',
        'city_name',
        'kepala_sekolah',
        'kepala_tpq',
        'kepala_tpq_title',
        'ttd_kepala_tpq',
        'ttd_kepala_sekolah',
        'ttd_config',
        'lock_input_nilai',
        'lock_cetak_rapor',
        'allow_teacher_promote',
        'allow_teacher_self_plotting',
        'report_date',
    ];

    protected $casts = [
        'ttd_config' => 'array',
        'lock_input_nilai' => 'boolean',
        'lock_cetak_rapor' => 'boolean',
        'allow_teacher_promote' => 'boolean',
        'allow_teacher_self_plotting' => 'boolean',
        'report_date' => 'date',
    ];

    protected $appends = [
        'ttd_kepala_tpq_url',
        'ttd_kepala_sekolah_url',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getTtdKepalaTpqUrlAttribute(): ?string
    {
        if (empty($this->ttd_kepala_tpq)) {
            return null;
        }
        if (str_starts_with($this->ttd_kepala_tpq, 'data:') || str_starts_with($this->ttd_kepala_tpq, 'http')) {
            return $this->ttd_kepala_tpq;
        }

        return asset('storage/' . ltrim($this->ttd_kepala_tpq, '/'));
    }

    public function getTtdKepalaSekolahUrlAttribute(): ?string
    {
        if (empty($this->ttd_kepala_sekolah)) {
            return null;
        }
        if (str_starts_with($this->ttd_kepala_sekolah, 'data:') || str_starts_with($this->ttd_kepala_sekolah, 'http')) {
            return $this->ttd_kepala_sekolah;
        }

        return asset('storage/' . ltrim($this->ttd_kepala_sekolah, '/'));
    }
}
