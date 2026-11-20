<?php

namespace App\Modules\Quran\Services;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuranAccessService
{
    public const MANAGER_ROLES = [
        'super_admin_yayasan',
        'admin_yayasan',
        'admin_unit',
        'kepala_sekolah',
        'koordinator_alquran',
        'koordinator_kurikulum',
    ];

    public const GLOBAL_ROLES = [
        'super_admin_yayasan',
        'admin_yayasan',
        'pengawas_yayasan',
        'pembina_yayasan',
    ];

    public function getUserRoleNames(User $user): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', User::class)
            ->pluck('roles.name')
            ->unique()
            ->values()
            ->toArray();
    }

    public function isGlobalAdmin(User $user): bool
    {
        return count(array_intersect($this->getUserRoleNames($user), self::GLOBAL_ROLES)) > 0;
    }

    public function canManage(User $user): bool
    {
        return count(array_intersect($this->getUserRoleNames($user), self::MANAGER_ROLES)) > 0;
    }

    public function isQuranTeacher(User $user): bool
    {
        return in_array('guru_alquran', $this->getUserRoleNames($user), true);
    }

    public function isQuranCoordinator(User $user): bool
    {
        return in_array('koordinator_alquran', $this->getUserRoleNames($user), true);
    }

    /**
     * Return all units eligible for the Program Al-Qur'an (exclude Daycare, Pavlov, and Kantor Yayasan).
     */
    public function getEligibleUnits(): Collection
    {
        return Unit::orderBy('id')->get()->filter(function (Unit $unit) {
            if ($unit->isDaycare()) {
                return false;
            }
            $code = strtoupper((string) $unit->code);
            if ($code === 'YAYASAN') {
                return false;
            }

            return $unit->hasFeature('quran');
        })->values();
    }

    /**
     * Resolve target unit for Quran module. Aborts 403 if non-eligible unit (Daycare/Pavlov).
     */
    public function resolveUnit(Request $request, User $user): Unit
    {
        $isGlobal = $this->isGlobalAdmin($user);
        $eligibleUnits = $this->getEligibleUnits();

        $requestedUnitId = $request->input('unit_id');
        if ($requestedUnitId && $isGlobal) {
            $unit = Unit::find($requestedUnitId);
            if ($unit && !$unit->isDaycare() && strtoupper((string) $unit->code) !== 'YAYASAN') {
                return $unit;
            }
        }

        $activeUnitId = session('active_unit_id') ?? $user->teacher_profile?->unit_id;
        $activeUnit = $activeUnitId ? Unit::find($activeUnitId) : null;

        if ($activeUnit) {
            if ($activeUnit->isDaycare()) {
                if ($isGlobal && $eligibleUnits->isNotEmpty()) {
                    return $eligibleUnits->first();
                }
                abort(403, 'Program Al-Qur\'an tidak tersedia untuk unit Daycare / Pavlov.');
            }

            if (strtoupper((string) $activeUnit->code) === 'YAYASAN') {
                if ($eligibleUnits->isNotEmpty()) {
                    // Prefer SD unit if available as default for Yayasan admins
                    return $eligibleUnits->first(fn (Unit $u) => strtoupper((string) $u->code) === 'SD')
                        ?? $eligibleUnits->first();
                }
            }

            return $activeUnit;
        }

        if ($eligibleUnits->isNotEmpty()) {
            return $eligibleUnits->first();
        }

        abort(404, 'Tidak ditemukan unit sekolah yang mendukung Program Al-Qur\'an.');
    }

    public function resolveAcademicYear(?int $requestedYearId = null): AcademicYear
    {
        if ($requestedYearId) {
            $found = AcademicYear::find($requestedYearId);
            if ($found) {
                return $found;
            }
        }

        return AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::latest('id')->firstOrFail();
    }

    /**
     * List eligible teachers/pengampu for Quran groups in the unit.
     * Prioritizes guru_alquran & koordinator_alquran, while also allowing regular teachers/unit admins.
     */
    public function getEligibleTeachers(int $unitId): Collection
    {
        $roleRows = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', [
                'guru_alquran',
                'koordinator_alquran',
                'teacher',
                'wali_kelas',
                'admin_unit',
                'kepala_sekolah',
                'koordinator_kurikulum',
            ])
            ->where(function ($q) use ($unitId) {
                $q->where('model_has_roles.team_id', $unitId)
                    ->orWhereNull('model_has_roles.team_id');
            })
            ->select('model_has_roles.model_id', 'roles.name as role_name')
            ->get();

        $rolesByUser = [];
        foreach ($roleRows as $row) {
            $rolesByUser[$row->model_id][] = $row->role_name;
        }

        $teacherUserIds = DB::table('teachers')
            ->where('unit_id', $unitId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();

        $candidateIds = array_values(array_unique(array_merge(array_keys($rolesByUser), $teacherUserIds)));

        if (empty($candidateIds)) {
            return collect();
        }

        $users = User::whereIn('id', $candidateIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return $users->map(function (User $u) use ($rolesByUser) {
            $roles = array_values(array_unique($rolesByUser[$u->id] ?? []));
            $isQuran = in_array('guru_alquran', $roles, true) || in_array('koordinator_alquran', $roles, true);

            $labelParts = [];
            if (in_array('koordinator_alquran', $roles, true)) {
                $labelParts[] = 'Koordinator Al-Qur\'an';
            }
            if (in_array('guru_alquran', $roles, true)) {
                $labelParts[] = 'Guru Al-Qur\'an';
            }
            if (in_array('teacher', $roles, true) || in_array('wali_kelas', $roles, true)) {
                $labelParts[] = 'Guru';
            }
            if (empty($labelParts)) {
                $labelParts[] = 'Pengampu';
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'roles' => $roles,
                'is_quran_staff' => $isQuran,
                'role_label' => implode(' • ', $labelParts),
            ];
        })->sortBy([
            ['is_quran_staff', 'desc'],
            ['name', 'asc'],
        ])->values();
    }
}
