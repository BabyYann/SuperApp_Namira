<?php

namespace App\Modules\SPMB\Models;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SpmbApplicant extends Model
{
    use HasFactory;

    protected $table = 'spmb_applicants';

    protected $fillable = [
        'registration_number',
        'unit_id',
        'user_id',
        'category',
        'status',
        'full_name',
        'nickname',
        'gender',
        'nik',
        'nisn',
        'birth_place',
        'birth_date',
        'religion',
        'child_order',
        'total_siblings',
        'previous_school',
        'special_notes',
        'father_name',
        'father_nik',
        'father_phone',
        'father_job',
        'father_education',
        'mother_name',
        'mother_nik',
        'mother_phone',
        'mother_job',
        'mother_education',
        'parent_phone',
        'address',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'postal_code',
        'photo_path',
        'family_card_path',
        'birth_cert_path',
        'parent_id_card_path',
        'registration_payment_proof',
        'registration_payment_verified_at',
        'registration_payment_verified_by',
        'observation_date',
        'observation_time',
        'observation_location',
        'psychotest_date',
        'psychotest_time',
        'psychotest_location',
        'schedule_notes',
        'scheduled_at',
        'scheduled_by',
        'evaluation_result',
        'evaluation_notes',
        'evaluation_document_path',
        'evaluated_at',
        'evaluated_by',
        'virtual_account_number',
        'total_admission_fee',
        'min_down_payment',
        'paid_admission_amount',
        'down_payment_deadline',
        'full_payment_deadline',
        're_registration_payment_proof',
        'admission_payment_status',
        'admission_payment_verified_at',
        'admission_payment_verified_by',
        'enrolled_student_id',
        'enrolled_at',
        'enrolled_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'observation_date' => 'date',
        'psychotest_date' => 'date',
        'down_payment_deadline' => 'date',
        'full_payment_deadline' => 'date',
        'registration_payment_verified_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'admission_payment_verified_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'total_admission_fee' => 'decimal:2',
        'min_down_payment' => 'decimal:2',
        'paid_admission_amount' => 'decimal:2',
    ];

    protected $appends = [
        'photo_url',
        'family_card_url',
        'birth_cert_url',
        'parent_id_card_url',
        'registration_payment_proof_url',
        'evaluation_document_url',
        're_registration_payment_proof_url',
        'status_label',
        'wa_link',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'enrolled_student_id');
    }

    public function registrationPaymentVerifier()
    {
        return $this->belongsTo(User::class, 'registration_payment_verified_by');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function admissionPaymentVerifier()
    {
        return $this->belongsTo(User::class, 'admission_payment_verified_by');
    }

    // Accessor URLs
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function getFamilyCardUrlAttribute(): ?string
    {
        return $this->family_card_path ? Storage::url($this->family_card_path) : null;
    }

    public function getBirthCertUrlAttribute(): ?string
    {
        return $this->birth_cert_path ? Storage::url($this->birth_cert_path) : null;
    }

    public function getParentIdCardUrlAttribute(): ?string
    {
        return $this->parent_id_card_path ? Storage::url($this->parent_id_card_path) : null;
    }

    public function getRegistrationPaymentProofUrlAttribute(): ?string
    {
        return $this->registration_payment_proof ? Storage::url($this->registration_payment_proof) : null;
    }

    public function getEvaluationDocumentUrlAttribute(): ?string
    {
        return $this->evaluation_document_path ? Storage::url($this->evaluation_document_path) : null;
    }

    public function getReRegistrationPaymentProofUrlAttribute(): ?string
    {
        return $this->re_registration_payment_proof ? Storage::url($this->re_registration_payment_proof) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi (Siap Jadwal)',
            'scheduled' => 'Jadwal Observasi & Psikotes Diterbitkan',
            'evaluated' => 'Selesai Seleksi / Penilaian',
            'accepted' => 'Diterima (Lolos Seleksi)',
            'reserve' => 'Cadangan',
            'rejected' => 'Belum Diterima',
            'partial_paid' => 'Daftar Ulang Termin 1 (60%)',
            'fully_paid' => 'Lunas Daftar Ulang (100%)',
            'enrolled' => 'Resmi Menjadi Siswa',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getWaLinkAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$this->parent_phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        $greeting = "Assalamu'alaikum Ayah/Bunda {$this->father_name} / {$this->mother_name},\nKami dari Panitia SPMB SD Namira menginfokan terkait pendaftaran Ananda {$this->full_name} (No. Reg: {$this->registration_number}).\n";

        return "https://wa.me/{$phone}?text=" . urlencode($greeting);
    }
}
