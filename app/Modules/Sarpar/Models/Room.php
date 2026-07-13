<?php

namespace App\Modules\Sarpar\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'sarpar_rooms';

    protected $fillable = [
        'unit_id',
        'name',
        'building',
        'floor',
        'capacity',
        'description',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'room_id');
    }
}
