<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;

class LmsMaterial extends Model
{
    protected $table = 'lms_materials';

    protected $fillable = [
        'lms_classroom_id',
        'title',
        'description',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function classroom()
    {
        return $this->belongsTo(LmsClassroom::class, 'lms_classroom_id');
    }

    public function files()
    {
        return $this->hasMany(LmsMaterialFile::class, 'lms_material_id');
    }
}
