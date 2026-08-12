<?php

namespace App\Modules\Sarpar\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Yayasan\Models\Unit;
use App\Models\User;

class Transfer extends Model
{
    protected $table = 'sarpar_transfers';

    protected $fillable = [
        'inventory_id',
        'from_unit_id',
        'to_unit_id',
        'from_room_id',
        'to_room_id',
        'reason',
        'transferred_by',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    public function fromRoom()
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
