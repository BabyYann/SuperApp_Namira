<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'chapter_id',
        'code',
        'description',
    ];

    public function unit()
    {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function subject()
    {
        return $this->hasOneThrough(
            Subject::class,
            Chapter::class,
            'id', // Foreign key on chapters table...
            'id', // Foreign key on subjects table...
            'chapter_id', // Local key on learning_objectives table...
            'subject_id' // Local key on chapters table...
        );
    }

    public function journals()
    {
        return $this->belongsToMany(TeachingJournal::class, 'journal_learning_objectives');
    }
}
