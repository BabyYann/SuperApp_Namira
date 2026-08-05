<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobApplicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_vacancy_id',
        'applicant_code',
        'name',
        'email',
        'phone',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'last_education',
        'major',
        'institution',
        'gpa',
        'cv_path',
        'cover_letter_path',
        'certificate_path',
        'ktp_path',
        'photo_path',
        'notes',
        'selection_status',
        'selection_notes',
        'converted_to_user_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($applicant) {
            if (empty($applicant->applicant_code)) {
                $dateCode = date('Ymd');
                $random = strtoupper(Str::random(4));
                $applicant->applicant_code = "LAM-{$dateCode}-{$random}";
            }
        });
    }

    public function vacancy()
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'converted_to_user_id');
    }

    public function getStatusLabelAttribute()
    {
        $map = [
            'pending' => 'Menunggu Seleksi',
            'shortlisted' => 'Lolos Administrasi',
            'interview' => 'Undangan Wawancara',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];
        return $map[$this->selection_status] ?? $this->selection_status;
    }
}
