<?php

namespace App\Modules\Sarpar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $table = 'sarpar_maintenance_logs';

    protected $fillable = [
        'inventory_id',
        'reported_by',
        'handled_by',
        'issue',
        'action_taken',
        'cost',
        'reported_date',
        'resolved_date',
        'status',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'resolved_date' => 'date',
        'cost' => 'integer',
    ];

    // Relations
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // Helpers
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'in_progress' => 'Sedang Ditangani',
            'resolved' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'amber',
            'in_progress' => 'blue',
            'resolved' => 'green',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
