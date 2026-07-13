<?php

namespace App\Modules\Sarpar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'sarpar_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'category_id');
    }
}
