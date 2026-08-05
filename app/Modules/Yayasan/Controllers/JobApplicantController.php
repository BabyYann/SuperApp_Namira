<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobApplicant;
use App\Models\JobVacancy;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Employee\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class JobApplicantController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan'])) {
            abort(403, 'Akses Ditolak: Hanya Yayasan yang dapat melihat daftar pelamar karir.');
        }

        $query = JobApplicant::with(['vacancy.unit', 'user']);

        if ($request->filled('vacancy_id') && $request->vacancy_id !== 'all') {
            $query->where('job_vacancy_id', $request->vacancy_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('selection_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('applicant_code', 'like', "%{$search}%");
            });
        }

        $applicants = $query->latest()->paginate(20)->withQueryString();
        $vacancies = JobVacancy::select('id', 'title', 'status')->get();

        $stats = [
            'total' => JobApplicant::count(),
            'pending' => JobApplicant::where('selection_status', 'pending')->count(),
            'shortlisted' => JobApplicant::where('selection_status', 'shortlisted')->count(),
            'interview' => JobApplicant::where('selection_status', 'interview')->count(),
            'accepted' => JobApplicant::where('selection_status', 'accepted')->count(),
            'rejected' => JobApplicant::where('selection_status', 'rejected')->count(),
        ];

        return Inertia::render('Yayasan/Recruitment/Applicants', [
            'applicants' => $applicants,
            'vacancies' => $vacancies,
            'stats' => $stats,
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'selection_status' => 'required|in:pending,shortlisted,interview,accepted,rejected',
            'selection_notes' => 'nullable|string|max:1000',
        ]);

        $applicant->update([
            'selection_status' => $validated['selection_status'],
            'selection_notes' => $validated['selection_notes'] ?? null,
        ]);

        return redirect()->back()->with('success', "Status seleksi pelamar {$applicant->name} berhasil diperbarui menjadi " . $applicant->status_label . "!");
    }

    public function convertToEmployee(Request $request, JobApplicant $applicant)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        if ($applicant->selection_status !== 'accepted') {
            return redirect()->back()->with('error', 'Hanya pelamar dengan status Diterima yang dapat direkrut menjadi pegawai.');
        }

        if ($applicant->converted_to_user_id) {
            return redirect()->back()->with('warning', 'Pelamar ini sudah direkrut dan memiliki akun pegawai.');
        }

        $vacancy = $applicant->vacancy;
        $unitId = $vacancy ? $vacancy->unit_id : session('active_unit_id');

        // Check if email already exists
        $user = User::where('email', $applicant->email)->first();
        if (!$user) {
            $randomPassword = 'Namira' . rand(1000, 9999) . '!';
            $user = User::create([
                'name' => $applicant->name,
                'email' => $applicant->email,
                'password' => Hash::make($randomPassword),
            ]);

            // Assign role & unit
            $roleName = ($vacancy && $vacancy->category === 'teacher') ? 'teacher' : 'staff_unit';
            if ($unitId) {
                setPermissionsTeamId($unitId);
                $user->assignRole($roleName);
            } else {
                $user->assignRole('staff_yayasan');
            }

            // Create profile
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

        return redirect()->back()->with('success', "Berhasil mererekrut {$applicant->name}! Akun User & Profil Pegawai berhasil dibuat (Email: {$user->email}).");
    }

    public function destroy(JobApplicant $applicant)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            abort(403, 'Akses Ditolak.');
        }

        $applicant->delete();

        return redirect()->back()->with('success', 'Data pelamar berhasil dihapus.');
    }
}
