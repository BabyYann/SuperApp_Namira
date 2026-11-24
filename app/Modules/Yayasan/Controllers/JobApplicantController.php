<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobApplicant;
use App\Models\JobVacancy;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Employee\Models\Staff;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class JobApplicantController extends Controller
{
    private function resolveAccess(): array
    {
        $user = auth()->user();
        $isGlobalManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']);
        $isGlobalReview = $isGlobalManage || $user->hasAnyRole(['humas_yayasan', 'staff_yayasan']);
        $isGlobalView = $isGlobalReview || $user->hasAnyRole(['pembina_yayasan', 'pengawas_yayasan']);
        $unitRoles = ['humas_unit', 'admin_unit', 'kepala_sekolah'];
        $isUnitScoped = ! $isGlobalView && $user->hasAnyRole($unitRoles);

        $activeUnitId = session('active_unit_id');
        if (! $activeUnitId && $isUnitScoped) {
            $activeUnitId = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_id', $user->id)
                ->whereIn('r.name', $unitRoles)
                ->whereNotNull('mhr.team_id')
                ->value('mhr.team_id');
        }

        return [
            'canView' => $isGlobalView || $isUnitScoped,
            'canReview' => $isGlobalReview || $isUnitScoped,
            'canConvertAndDelete' => $isGlobalManage,
            'isUnitScoped' => $isUnitScoped,
            'activeUnitId' => $activeUnitId ? (int) $activeUnitId : null,
        ];
    }

    public function index(Request $request)
    {
        $access = $this->resolveAccess();
        if (! $access['canView']) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat daftar pelamar karir.');
        }

        $baseQuery = JobApplicant::query();
        if ($access['isUnitScoped']) {
            if (! $access['activeUnitId']) {
                abort(403, 'Akses Ditolak: Unit aktif tidak ditemukan.');
            }
            $baseQuery->whereHas('vacancy', fn ($q) => $q->where('unit_id', $access['activeUnitId']));
        }

        $query = (clone $baseQuery)->with(['vacancy.unit', 'user']);

        if ($request->filled('vacancy_id') && $request->vacancy_id !== 'all') {
            $query->where('job_vacancy_id', (int) $request->vacancy_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('selection_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('applicant_code', 'like', "%{$search}%")
                    ->orWhere('major', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        $applicants = $query->latest()->paginate(20)->withQueryString();
        $vacancies = $access['isUnitScoped']
            ? JobVacancy::where('unit_id', $access['activeUnitId'])->select('id', 'title', 'status')->latest()->get()
            : JobVacancy::select('id', 'title', 'status')->latest()->get();

        $statusCounts = (clone $baseQuery)
            ->select('selection_status', DB::raw('COUNT(*) as total'))
            ->groupBy('selection_status')
            ->pluck('total', 'selection_status');

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'shortlisted' => (int) ($statusCounts['shortlisted'] ?? 0),
            'interview' => (int) ($statusCounts['interview'] ?? 0),
            'accepted' => (int) ($statusCounts['accepted'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
        ];

        $activeUnit = $access['activeUnitId'] ? Unit::find($access['activeUnitId']) : null;

        return Inertia::render('Yayasan/Recruitment/Applicants', [
            'applicants' => $applicants,
            'vacancies' => $vacancies,
            'stats' => $stats,
            'canReviewApplicants' => $access['canReview'],
            'canManageApplicants' => $access['canConvertAndDelete'],
            'isUnitScoped' => $access['isUnitScoped'],
            'activeUnitName' => $activeUnit?->name,
            'filters' => $request->only(['vacancy_id', 'status', 'search']),
            'statuses' => [
                'pending' => 'Menunggu Seleksi',
                'shortlisted' => 'Lolos Administrasi',
                'interview' => 'Undangan Wawancara',
                'accepted' => 'Diterima',
                'rejected' => 'Ditolak',
            ],
        ]);
    }

    public function updateStatus(Request $request, JobApplicant $applicant)
    {
        $access = $this->resolveAccess();
        if (! $access['canReview']) {
            abort(403, 'Akses Ditolak.');
        }

        $applicant->loadMissing('vacancy');
        if ($access['isUnitScoped'] && (int) ($applicant->vacancy?->unit_id ?? 0) !== (int) $access['activeUnitId']) {
            abort(403, 'Akses Ditolak: Anda hanya dapat menilai pelamar pada lowongan unit Anda.');
        }

        $validated = $request->validate([
            'selection_status' => 'required|in:pending,shortlisted,interview,accepted,rejected',
            'selection_notes' => 'nullable|string|max:1000',
        ]);

        $applicant->update([
            'selection_status' => $validated['selection_status'],
            'selection_notes' => $validated['selection_notes'] ?? null,
        ]);

        return redirect()->back()->with('success', "Status seleksi pelamar {$applicant->name} berhasil diperbarui menjadi {$applicant->status_label}!");
    }

    public function convertToEmployee(Request $request, JobApplicant $applicant)
    {
        $access = $this->resolveAccess();
        if (! $access['canConvertAndDelete']) {
            abort(403, 'Akses Ditolak: Hanya Admin Yayasan yang berwenang merekrut pelamar menjadi akun pegawai.');
        }

        if ($applicant->selection_status !== 'accepted') {
            return redirect()->back()->with('error', 'Hanya pelamar dengan status Diterima yang dapat direkrut menjadi pegawai.');
        }

        if ($applicant->converted_to_user_id) {
            return redirect()->back()->with('warning', 'Pelamar ini sudah direkrut dan memiliki akun pegawai.');
        }

        $vacancy = $applicant->vacancy;
        $unitId = $vacancy ? $vacancy->unit_id : session('active_unit_id');
        $previousTeamId = getPermissionsTeamId();

        try {
            $user = User::where('email', $applicant->email)->first();
            if (! $user) {
                $randomPassword = 'Namira' . rand(1000, 9999) . '!';
                $user = User::create([
                    'name' => $applicant->name,
                    'email' => $applicant->email,
                    'password' => Hash::make($randomPassword),
                ]);

                $roleName = ($vacancy && $vacancy->category === 'teacher') ? 'teacher' : 'staff_unit';
                if ($unitId) {
                    setPermissionsTeamId($unitId);
                    $user->assignRole($roleName);
                } else {
                    setPermissionsTeamId(null);
                    $user->assignRole('staff_yayasan');
                }

                if ($vacancy && $vacancy->category === 'teacher') {
                    Teacher::create([
                        'user_id' => $user->id,
                        'unit_id' => $unitId,
                        'niy' => 'NIY-' . date('Ym') . rand(100, 999),
                        'phone' => $applicant->phone,
                        'address' => $applicant->address,
                        'gender' => $applicant->gender,
                        'is_active' => true,
                    ]);
                } else {
                    Staff::create([
                        'user_id' => $user->id,
                        'unit_id' => $unitId,
                        'nik' => 'NIK-' . date('Ym') . rand(100, 999),
                        'phone' => $applicant->phone,
                        'address' => $applicant->address,
                        'gender' => $applicant->gender,
                        'is_active' => true,
                    ]);
                }
            }

            $applicant->update([
                'converted_to_user_id' => $user->id,
            ]);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        return redirect()->back()->with('success', "Berhasil merekrut {$applicant->name}! Akun User & Profil Pegawai berhasil dibuat (Email: {$user->email}).");
    }

    public function destroy(JobApplicant $applicant)
    {
        $access = $this->resolveAccess();
        if (! $access['canConvertAndDelete']) {
            abort(403, 'Akses Ditolak.');
        }

        foreach (['cv_path', 'cover_letter_path', 'certificate_path', 'health_cert_path', 'ktp_path', 'family_card_path', 'photo_path'] as $col) {
            if (! empty($applicant->{$col}) && Storage::disk('public')->exists($applicant->{$col})) {
                Storage::disk('public')->delete($applicant->{$col});
            }
        }

        $applicant->delete();

        return redirect()->back()->with('success', 'Data pelamar berhasil dihapus.');
    }
}
