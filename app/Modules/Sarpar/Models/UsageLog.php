<?php

namespace App\Modules\Sarpar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    use HasFactory;

    protected $table = 'sarpar_usage_logs';

    protected $fillable = [
        'inventory_id',
        'used_by',
        'quantity_used',
        'used_date',
        'purpose',
        'notes',
    ];

    protected $casts = [
        'used_date' => 'date',
    ];

    // Relations
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
