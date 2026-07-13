<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $fillable = [
        'title', 'image_path', 'order_weight', 'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_weight' => 'integer'
    ];

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
