<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
        'health_cert_path',
        'ktp_path',
        'family_card_path',
        'photo_path',
        'notes',
        'selection_status',
        'selection_notes',
        'converted_to_user_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    protected $appends = [
        'status_label',
        'cv_url',
        'cover_letter_url',
        'certificate_url',
        'health_cert_url',
        'ktp_url',
        'family_card_url',
        'photo_url',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($applicant) {
            if (empty($applicant->applicant_code)) {
                $dateCode = date('Ymd');
                do {
                    $random = strtoupper(Str::random(4));
                    $code = "LAM-{$dateCode}-{$random}";
                } while (static::where('applicant_code', $code)->exists());
                $applicant->applicant_code = $code;
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

    private function resolvePublicFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::url($path);
    }

    public function getCvUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->cv_path);
    }

    public function getCoverLetterUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->cover_letter_path);
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->certificate_path);
    }

    public function getHealthCertUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->health_cert_path);
    }

    public function getKtpUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->ktp_path);
    }

    public function getFamilyCardUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->family_card_path);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->resolvePublicFileUrl($this->photo_path);
    }

    public function getStatusLabelAttribute(): string
    {
        $map = [
            'pending' => 'Menunggu Seleksi',
            'shortlisted' => 'Lolos Administrasi',
            'interview' => 'Undangan Wawancara',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];

        return $map[$this->selection_status] ?? (string) $this->selection_status;
    }
}
