<?php

namespace App\Modules\Quran\Services;

use App\Models\User;
use App\Modules\Quran\Models\QuranGroup;
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

    public const GLOBAL_MANAGER_ROLES = [
        'super_admin_yayasan',
        'admin_yayasan',
    ];

    public const UNIT_MANAGER_ROLES = [
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
        $req = request();
        $attrKey = 'resolved_user_roles_' . $user->id;
        if ($req && $req->attributes->has($attrKey)) {
            return $req->attributes->get($attrKey);
        }

        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', User::class)
            ->pluck('roles.name')
            ->unique()
            ->values()
            ->toArray();

        if ($req) {
            $req->attributes->set($attrKey, $roles);
        }

        return $roles;
    }

    public function getUserRoleNamesForUnit(User $user, int $unitId): array
    {
        $req = request();
        $attrKey = 'resolved_user_unit_roles_' . $user->id . '_' . $unitId;
        if ($req && $req->attributes->has($attrKey)) {
            return $req->attributes->get($attrKey);
        }

        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', User::class)
            ->where(function ($q) use ($unitId) {
                $q->where('model_has_roles.team_id', $unitId)
                    ->orWhereNull('model_has_roles.team_id');
            })
            ->pluck('roles.name')
            ->unique()
            ->values()
            ->toArray();

        if ($req) {
            $req->attributes->set($attrKey, $roles);
        }

        return $roles;
    }

    public function isGlobalAdmin(User $user): bool
    {
        return count(array_intersect($this->getUserRoleNames($user), self::GLOBAL_ROLES)) > 0;
    }

    /**
     * Check whether the user can manage the Quran module.
     * When $unitId is provided, unit-scoped manager roles (e.g. kepala_sekolah, admin_unit)
     * must belong to that target unit (or be global/unscoped) so a Pavlov unit admin/kepsek
     * assigned as a cross-unit Quran teacher in SD does not inherit SD manager permissions.
     */
    public function canManage(User $user, ?int $unitId = null): bool
    {
        $allRoles = $this->getUserRoleNames($user);
        if (count(array_intersect($allRoles, self::GLOBAL_MANAGER_ROLES)) > 0) {
            return true;
        }

        if ($unitId === null) {
            return count(array_intersect($allRoles, self::MANAGER_ROLES)) > 0;
        }

        $unitRoles = $this->getUserRoleNamesForUnit($user, $unitId);

        return count(array_intersect($unitRoles, self::UNIT_MANAGER_ROLES)) > 0;
    }

    public function isQuranTeacher(User $user): bool
    {
        return in_array('guru_alquran', $this->getUserRoleNames($user), true);
    }

    public function isQuranCoordinator(User $user): bool
    {
        return in_array('koordinator_alquran', $this->getUserRoleNames($user), true);
    }

    private function getAllUnits(): Collection
    {
        $req = request();
        if ($req && $req->attributes->has('resolved_all_units')) {
            return $req->attributes->get('resolved_all_units');
        }

        $units = Unit::orderBy('id')->get();
        if ($req) {
            $req->attributes->set('resolved_all_units', $units);
        }

        return $units;
    }

    /**
     * Return Pavlov unit(s) whose teachers can be assigned as cross-unit inclusive Quran mentors.
     */
    public function getPavlovUnits(): Collection
    {
        return $this->getAllUnits()->filter(function (Unit $unit) {
            $code = strtoupper((string) $unit->code);
            $name = strtolower((string) $unit->name);

            return $code === 'PVO' || str_contains($name, 'pavlov');
        })->values();
    }

    /**
     * Return all units eligible for the Program Al-Qur'an (exclude Daycare, Pavlov, and Kantor Yayasan).
     */
    public function getEligibleUnits(): Collection
    {
        $req = request();
        if ($req && $req->attributes->has('resolved_quran_eligible_units')) {
            return $req->attributes->get('resolved_quran_eligible_units');
        }

        $eligible = $this->getAllUnits()->filter(function (Unit $unit) {
            if ($unit->isDaycare()) {
                return false;
            }
            $code = strtoupper((string) $unit->code);
            if ($code === 'YAYASAN') {
                return false;
            }

            return $unit->hasFeature('quran');
        })->values();

        if ($req) {
            $req->attributes->set('resolved_quran_eligible_units', $eligible);
        }

        return $eligible;
    }

    /**
     * Return eligible formal units where the user is actively assigned as a Quran group teacher or assistant.
     * Allows Pavlov teachers assigned to an inclusive group in SD (etc.) to access that unit's Quran module.
     */
    public function getAssignedQuranUnits(User $user): Collection
    {
        $req = request();
        $attrKey = 'resolved_quran_assigned_units_' . $user->id;
        if ($req && $req->attributes->has($attrKey)) {
            return $req->attributes->get($attrKey);
        }

        $eligibleUnits = $this->getEligibleUnits();
        if ($eligibleUnits->isEmpty()) {
            $empty = collect();
            if ($req) {
                $req->attributes->set($attrKey, $empty);
            }

            return $empty;
        }

        $assignedUnitIds = QuranGroup::where('is_active', true)
            ->whereIn('unit_id', $eligibleUnits->pluck('id'))
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('assistant_teacher_id', $user->id);
            })
            ->distinct()
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $assigned = empty($assignedUnitIds)
            ? collect()
            : $eligibleUnits->whereIn('id', $assignedUnitIds)->values();

        if ($req) {
            $req->attributes->set($attrKey, $assigned);
        }

        return $assigned;
    }

    /**
     * Units the user can switch between in QuranNav (Global admins see all eligible units;
     * cross-unit assigned teachers with >1 assigned unit can switch between their assigned units).
     */
    public function getSwitchableUnits(User $user, Unit $currentUnit): Collection
    {
        if ($this->isGlobalAdmin($user)) {
            return $this->getEligibleUnits();
        }

        $assigned = $this->getAssignedQuranUnits($user);
        if ($assigned->count() > 1) {
            return $assigned;
        }

        return collect();
    }

    /**
     * Resolve target unit for Quran module. Aborts 403 if non-eligible unit (Daycare/Pavlov)
     * unless the user has an active cross-unit Quran group assignment in an eligible unit.
     */
    public function resolveUnit(Request $request, User $user): Unit
    {
        $isGlobal = $this->isGlobalAdmin($user);
        $allUnits = $this->getAllUnits();
        $eligibleUnits = $this->getEligibleUnits();
        $assignedUnits = $isGlobal ? collect() : $this->getAssignedQuranUnits($user);

        $requestedUnitId = $request->input('unit_id');
        if ($requestedUnitId) {
            if ($isGlobal) {
                $unit = $allUnits->firstWhere('id', (int) $requestedUnitId) ?? Unit::find($requestedUnitId);
                if ($unit && !$unit->isDaycare() && strtoupper((string) $unit->code) !== 'YAYASAN') {
                    return $unit;
                }
            } elseif ($assignedUnits->isNotEmpty()) {
                $assignedMatch = $assignedUnits->firstWhere('id', (int) $requestedUnitId);
                if ($assignedMatch) {
                    return $assignedMatch;
                }
            }
        }

        $activeUnitId = session('active_unit_id') ?? $user->teacher_profile?->unit_id;
        $activeUnit = $activeUnitId ? ($allUnits->firstWhere('id', (int) $activeUnitId) ?? Unit::find($activeUnitId)) : null;

        if ($activeUnit) {
            if ($activeUnit->isDaycare()) {
                if ($isGlobal && $eligibleUnits->isNotEmpty()) {
                    return $eligibleUnits->first();
                }
                if ($assignedUnits->isNotEmpty()) {
                    return $assignedUnits->first();
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

        if ($assignedUnits->isNotEmpty()) {
            return $assignedUnits->first();
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
     * Identify which of the given user IDs are cross-unit teachers from Pavlov
     * (not registered as teachers in the current formal unit).
     *
     * @param  array<int, int|null>  $userIds
     * @return array<int, array{unit_id: int, unit_name: string, unit_code: string}>
     */
    public function getCrossUnitTeacherOrigins(array $userIds, int $currentUnitId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($ids)) {
            return [];
        }

        $pavlovUnits = $this->getPavlovUnits()->keyBy('id');
        if ($pavlovUnits->isEmpty()) {
            return [];
        }

        $pavlovUnitIds = $pavlovUnits->keys()->all();

        // Exclude anyone who is also a registered teacher in the current unit
        $localTeacherIds = DB::table('teachers')
            ->where('unit_id', $currentUnitId)
            ->whereIn('user_id', $ids)
            ->pluck('user_id')
            ->flip()
            ->all();

        $origins = [];

        $pavlovTeachers = DB::table('teachers')
            ->whereIn('unit_id', $pavlovUnitIds)
            ->whereIn('user_id', $ids)
            ->get(['user_id', 'unit_id']);

        foreach ($pavlovTeachers as $row) {
            $uid = (int) $row->user_id;
            if (isset($localTeacherIds[$uid])) {
                continue;
            }
            $u = $pavlovUnits->get((int) $row->unit_id);
            if ($u) {
                $origins[$uid] = [
                    'unit_id' => $u->id,
                    'unit_name' => $u->name,
                    'unit_code' => (string) $u->code,
                ];
            }
        }

        // Also check Pavlov-scoped roles for Pavlov users without a teachers row
        $remainingIds = array_values(array_diff($ids, array_keys($origins), array_keys($localTeacherIds)));
        if (!empty($remainingIds)) {
            $pavlovRoleRows = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $remainingIds)
                ->whereIn('team_id', $pavlovUnitIds)
                ->get(['model_id', 'team_id']);

            foreach ($pavlovRoleRows as $row) {
                $uid = (int) $row->model_id;
                $u = $pavlovUnits->get((int) $row->team_id);
                if ($u && !isset($origins[$uid])) {
                    $origins[$uid] = [
                        'unit_id' => $u->id,
                        'unit_name' => $u->name,
                        'unit_code' => (string) $u->code,
                    ];
                }
            }
        }

        return $origins;
    }

    /**
     * List eligible teachers/pengampu for Quran groups in the unit.
     * Prioritizes local guru_alquran & koordinator_alquran, followed by regular unit teachers,
     * and includes cross-unit Guru Utusan Pavlov for inclusive/ABK Quran groups.
     */
    public function getEligibleTeachers(int $unitId): Collection
    {
        $allowedRoles = [
            'guru_alquran',
            'koordinator_alquran',
            'teacher',
            'wali_kelas',
            'admin_unit',
            'kepala_sekolah',
            'koordinator_kurikulum',
        ];

        $roleRows = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', $allowedRoles)
            ->where(function ($q) use ($unitId) {
                $q->where('model_has_roles.team_id', $unitId)
                    ->orWhereNull('model_has_roles.team_id');
            })
            ->select('model_has_roles.model_id', 'roles.name as role_name')
            ->get();

        $rolesByUser = [];
        foreach ($roleRows as $row) {
            $rolesByUser[(int) $row->model_id][] = $row->role_name;
        }

        $teacherUserIds = DB::table('teachers')
            ->where('unit_id', $unitId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $localCandidateIds = array_values(array_unique(array_merge(array_keys($rolesByUser), $teacherUserIds)));
        $localIdSet = array_flip($localCandidateIds);

        // Cross-unit Guru Utusan Pavlov
        $pavlovOriginByUser = [];
        $pavlovUnits = $this->getPavlovUnits()->reject(fn (Unit $u) => $u->id === $unitId)->keyBy('id');
        if ($pavlovUnits->isNotEmpty()) {
            $pavlovUnitIds = $pavlovUnits->keys()->all();

            $pavlovTeachers = DB::table('teachers')
                ->whereIn('unit_id', $pavlovUnitIds)
                ->whereNotNull('user_id')
                ->get(['user_id', 'unit_id']);

            foreach ($pavlovTeachers as $row) {
                $uid = (int) $row->user_id;
                if (isset($localIdSet[$uid])) {
                    continue;
                }
                $pUnit = $pavlovUnits->get((int) $row->unit_id);
                if ($pUnit) {
                    $pavlovOriginByUser[$uid] = $pUnit;
                }
            }

            $pavlovRoleRows = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->whereIn('roles.name', ['teacher', 'wali_kelas', 'guru_alquran', 'koordinator_alquran', 'kepala_sekolah', 'admin_unit'])
                ->whereIn('model_has_roles.team_id', $pavlovUnitIds)
                ->select('model_has_roles.model_id', 'model_has_roles.team_id', 'roles.name as role_name')
                ->get();

            foreach ($pavlovRoleRows as $row) {
                $uid = (int) $row->model_id;
                if (isset($localIdSet[$uid])) {
                    continue;
                }
                $pUnit = $pavlovUnits->get((int) $row->team_id);
                if ($pUnit && !isset($pavlovOriginByUser[$uid])) {
                    $pavlovOriginByUser[$uid] = $pUnit;
                }
                $rolesByUser[$uid][] = $row->role_name;
            }
        }

        $allCandidateIds = array_values(array_unique(array_merge($localCandidateIds, array_keys($pavlovOriginByUser))));

        if (empty($allCandidateIds)) {
            return collect();
        }

        $users = User::whereIn('id', $allCandidateIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return $users->map(function (User $u) use ($rolesByUser, $pavlovOriginByUser) {
            $roles = array_values(array_unique($rolesByUser[$u->id] ?? []));
            $pavlovUnit = $pavlovOriginByUser[$u->id] ?? null;
            $isCrossUnit = $pavlovUnit !== null;
            $isQuran = !$isCrossUnit && (in_array('guru_alquran', $roles, true) || in_array('koordinator_alquran', $roles, true));

            if ($isCrossUnit) {
                $roleLabel = 'Guru Utusan ' . $pavlovUnit->name;
            } else {
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
                $roleLabel = implode(' • ', $labelParts);
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'roles' => $roles,
                'is_quran_staff' => $isQuran,
                'is_cross_unit' => $isCrossUnit,
                'origin_unit_name' => $pavlovUnit?->name,
                'origin_unit_code' => $pavlovUnit?->code,
                'role_label' => $roleLabel,
            ];
        })->sortBy([
            ['is_cross_unit', 'asc'],
            ['is_quran_staff', 'desc'],
            ['name', 'asc'],
        ])->values();
    }
}
