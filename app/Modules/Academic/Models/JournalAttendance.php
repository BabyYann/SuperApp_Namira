<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalAttendance extends Model
{
    use HasFactory;

    protected $table = 'journal_attendance';

    protected $fillable = [
        'teaching_journal_id',
        'student_id',
        'status',
        'note',
    ];

    public function journal()
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
