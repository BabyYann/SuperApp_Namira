<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;

class LmsMaterialFile extends Model
{
    protected $table = 'lms_material_files';

    protected $fillable = [
        'lms_material_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function material()
    {
        return $this->belongsTo(LmsMaterial::class, 'lms_material_id');
    }
}
