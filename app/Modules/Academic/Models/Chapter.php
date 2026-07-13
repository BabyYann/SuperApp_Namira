<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'subject_id',
        'title',
        'semester',
        'grade_level',
    ];

    public function unit()
    {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function subject()
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Subject::class);
    }

    public function learningObjectives()
    {
        return $this->hasMany(LearningObjective::class);
    }
}
