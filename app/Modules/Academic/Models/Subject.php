<?php

namespace App\Modules\Academic\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'name',
        'code',
        'group',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
