<?php

namespace App\Modules\Sarpar\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use App\Modules\Academic\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'sarpar_inventories';

    protected $fillable = [
        'unit_id',
        'category_id',
        'room_id',
        'classroom_id',
        'funding_source',
        'item_type',
        'code',
        'name',
        'brand',
        'model',
        'year_acquired',
        'quantity',
        'min_stock',
        'unit_price',
        'condition',
        'status',
        'photo',
        'notes',
    ];

    protected $casts = [
        'year_acquired' => 'integer',
        'unit_price' => 'integer',
        'min_stock' => 'integer',
    ];

    protected $appends = ['location_name', 'is_low_stock'];

    // Relations
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class, 'inventory_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'inventory_id');
    }

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class, 'inventory_id');
    }

    // Helpers
    public function getLocationNameAttribute()
    {
        if ($this->classroom) {
            return 'Kelas ' . $this->classroom->name;
        }
        if ($this->room) {
            return $this->room->name;
        }
        return 'Belum ditentukan';
    }

    public function getIsLowStockAttribute()
    {
        if ($this->item_type !== 'consumable' || !$this->min_stock) {
            return false;
        }
        return $this->quantity <= $this->min_stock;
    }

    public function getConditionLabelAttribute()
    {
        return match($this->condition) {
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
            default => ucfirst($this->condition),
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'tersedia' => 'Tersedia',
            'dipinjam' => 'Dipinjam',
            'diperbaiki' => 'Dalam Perbaikan',
            'dihapus' => 'Dihapus',
            default => ucfirst($this->status),
        };
    }

    public function getFundingSourceLabelAttribute()
    {
        return match($this->funding_source) {
            'BOS' => 'Dana BOS',
            'YYS' => 'Dana Yayasan',
            default => $this->funding_source,
        };
    }

    public function getItemTypeLabelAttribute()
    {
        return match($this->item_type) {
            'asset' => 'Aset Tetap',
            'consumable' => 'Habis Pakai',
            default => ucfirst($this->item_type),
        };
    }
}

