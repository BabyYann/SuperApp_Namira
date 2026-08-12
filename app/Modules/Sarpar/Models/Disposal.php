<?php

namespace App\Modules\Sarpar\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Yayasan\Models\Unit;
use App\Models\User;

class Disposal extends Model
{
    protected $table = 'sarpar_disposals';

    protected $fillable = [
        'inventory_id',
        'unit_id',
        'disposal_type',
        'reason',
        'requested_by',
        'approved_by',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
