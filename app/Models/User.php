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
        'phone',
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

    protected ?\Illuminate\Support\Collection $cachedUnits = null;
    protected ?string $cachedProfilePhotoUrl = null;

    // Helper to get units via roles & profile tables (teachers, staff, students)
    public function getUnitsAttribute()
    {
        if ($this->cachedUnits !== null) {
            return $this->cachedUnits;
        }

        // Single UNION query across roles, teachers, staff, and students (replaces 4 separate round-trips)
        $roleUnits = \DB::table('model_has_roles')
            ->select('team_id as unit_id')
            ->where('model_id', $this->id)
            ->whereNotNull('team_id');

        $teacherUnits = \DB::table('teachers')
            ->select('unit_id')
            ->where('user_id', $this->id)
            ->whereNotNull('unit_id');

        $staffUnits = \DB::table('staff')
            ->select('unit_id')
            ->where('user_id', $this->id)
            ->whereNotNull('unit_id');

        $studentUnits = \DB::table('students')
            ->select('unit_id')
            ->where('user_id', $this->id)
            ->whereNotNull('unit_id');

        $teamIds = $roleUnits
            ->union($teacherUnits)
            ->union($staffUnits)
            ->union($studentUnits)
            ->pluck('unit_id')
            ->toArray();

        $hasSpmbTable = \Illuminate\Support\Facades\Cache::remember(
            'schema_has_table_spmb_applicants',
            86400,
            fn () => \Illuminate\Support\Facades\Schema::hasTable('spmb_applicants')
        );

        if ($hasSpmbTable) {
            $spmbUnitIds = \DB::table('spmb_applicants')
                ->where(function ($q) {
                    $q->where('user_id', $this->id);
                    if (!empty($this->phone)) {
                        $q->orWhere('parent_phone', $this->phone);
                    }
                })
                ->whereNotNull('unit_id')
                ->pluck('unit_id')
                ->toArray();
            $teamIds = array_merge($teamIds, $spmbUnitIds);
        }

        $uniqueTeamIds = array_values(array_unique(array_filter($teamIds)));
        if (empty($uniqueTeamIds)) {
            return $this->cachedUnits = collect();
        }

        $req = request();
        if ($req && $req->attributes->has('resolved_all_units')) {
            $idLookup = array_flip(array_map('intval', $uniqueTeamIds));
            return $this->cachedUnits = $req->attributes->get('resolved_all_units')
                ->filter(fn ($u) => isset($idLookup[(int) $u->id]))
                ->values();
        }

        return $this->cachedUnits = \App\Modules\Yayasan\Models\Unit::whereIn('id', $uniqueTeamIds)->get();
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
        if ($this->cachedProfilePhotoUrl !== null) {
            return $this->cachedProfilePhotoUrl;
        }

        // 0. Check User's own profile photo (for admin/staff)
        if (!empty($this->profile_photo)) {
            $path = $this->profile_photo;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $this->cachedProfilePhotoUrl = $path;
            }
            if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
                return $this->cachedProfilePhotoUrl = asset(ltrim($path, '/'));
            }
            return $this->cachedProfilePhotoUrl = asset('storage/' . $path);
        }

        // 1. Check Student Profile
        $student = $this->relationLoaded('student') ? $this->student : $this->hasOne(\App\Modules\Academic\Models\Student::class)->select('id', 'user_id', 'photo')->first();
        if ($student && !empty($student->photo)) {
            $path = $student->photo;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $this->cachedProfilePhotoUrl = $path;
            }
            if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
                return $this->cachedProfilePhotoUrl = asset(ltrim($path, '/'));
            }
            return $this->cachedProfilePhotoUrl = asset('storage/' . $path);
        }

        // 2. Check Teacher Profile
        $teacher = $this->relationLoaded('teacher_profile') ? $this->teacher_profile : $this->teacher_profile()->select('id', 'user_id', 'photo')->first();
        if ($teacher && !empty($teacher->photo)) {
            $path = $teacher->photo;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $this->cachedProfilePhotoUrl = $path;
            }
            if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
                return $this->cachedProfilePhotoUrl = asset(ltrim($path, '/'));
            }
            return $this->cachedProfilePhotoUrl = asset('storage/' . $path);
        }

        // 3. Check Staff Profile
        $staff = $this->relationLoaded('staff') ? $this->staff : $this->staff()->select('id', 'user_id', 'photo')->first();
        if ($staff && !empty($staff->photo)) {
            $path = $staff->photo;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $this->cachedProfilePhotoUrl = $path;
            }
            if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
                return $this->cachedProfilePhotoUrl = asset(ltrim($path, '/'));
            }
            return $this->cachedProfilePhotoUrl = asset('storage/' . $path);
        }

        // 4. Fallback to UI Avatars
        $name = trim($this->name);
        return $this->cachedProfilePhotoUrl = 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
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
