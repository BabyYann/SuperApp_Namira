<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Helper to get units via roles
    public function getUnitsAttribute()
    {
        // Get team_ids from assigned roles
        $teamIds = \DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->unique();
            
        return \App\Modules\Yayasan\Models\Unit::whereIn('id', $teamIds)->get();
    }

    public function teacher_profile()
    {
        return $this->hasOne(\App\Modules\Academic\Models\Teacher::class);
    }

    public function staff()
    {
        return $this->hasOne(\App\Modules\Employee\Models\Staff::class);
    }
    
    // Dynamic Profile Photo URL
    public function getProfilePhotoUrlAttribute()
    {
        // 0. Check User's own profile photo (for admin/staff)
        if (!empty($this->profile_photo)) {
            return asset('storage/' . $this->profile_photo);
        }

        // 1. Check Student Profile
        $student = $this->hasOne(\App\Modules\Academic\Models\Student::class)->first();
        if ($student && !empty($student->photo)) {
            return asset('storage/' . $student->photo);
        }

        // 2. Check Teacher Profile
        $teacher = $this->teacher_profile()->first();
        if ($teacher && !empty($teacher->photo)) {
             return asset('storage/' . $teacher->photo);
        }

        // 3. Fallback to UI Avatars
        $name = trim($this->name);
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    // Ensure it's appended to JSON
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Send the password reset notification.
     * Override to use custom Indonesian notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function deviceTokens()
    {
        return $this->hasMany(UserDeviceToken::class);
    }
}
