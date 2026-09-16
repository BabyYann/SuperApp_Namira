<?php

namespace App\Modules\SPMB\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SpmbSetting extends Model
{
    use HasFactory;

    protected $table = 'spmb_settings';

    protected $fillable = [
        'unit_id',
        'name',
        'academic_year',
        'quota',
        'registration_fee',
        'qris_image_path',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'admission_fee_total',
        'min_down_payment_percentage',
        'is_active',
        'start_date',
        'end_date',
        'contact_whatsapp',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quota' => 'integer',
        'registration_fee' => 'decimal:2',
        'admission_fee_total' => 'decimal:2',
        'min_down_payment_percentage' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = ['qris_image_url'];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        if ($this->qris_image_path) {
            return Storage::url($this->qris_image_path);
        }
        return null;
    }
}
