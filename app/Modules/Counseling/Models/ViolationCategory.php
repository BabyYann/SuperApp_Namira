<?php

namespace App\Modules\Counseling\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViolationCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'name',
        'type', // ringan, sedang, berat
        'default_points',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
