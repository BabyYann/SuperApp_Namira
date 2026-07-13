<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;

class LmsSubmissionFile extends Model
{
    protected $table = 'lms_submission_files';

    protected $fillable = [
        'lms_submission_id',
        'file_path',
        'file_name',
    ];

    public function submission()
    {
        return $this->belongsTo(LmsSubmission::class, 'lms_submission_id');
    }
}
