<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LmsAnnouncement extends Model
{
    protected $table = 'lms_announcements';

    protected $fillable = [
        'lms_classroom_id',
        'content',
        'author_id',
    ];

    public function classroom()
    {
        return $this->belongsTo(LmsClassroom::class, 'lms_classroom_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
