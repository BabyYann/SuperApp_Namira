<?php

namespace App\Modules\Employee\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff'; // Explicitly set table name

    protected $fillable = [
        'user_id',
        'unit_id',
        'nip',
        'full_name',
        'gender',
        'phone',
        'position',
        'photo',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
