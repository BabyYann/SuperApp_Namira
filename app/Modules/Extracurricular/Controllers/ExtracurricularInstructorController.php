<?php

namespace App\Modules\Extracurricular\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Extracurricular\Models\Extracurricular;
use App\Modules\Extracurricular\Models\ExtracurricularInstructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ExtracurricularInstructorController extends Controller
{
    /**
     * Assign existing coach or create a new coach account.
     */
    public function store(Request $request, $extracurricularId)
    {
        $user = Auth::user();
        $activity = Extracurricular::findOrFail($extracurricularId);

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan'])) {
            abort(403, 'Hanya koordinator kesiswaan dan admin yang dapat menugaskan pelatih.');
        }

        $mode = $request->input('mode', 'existing'); // 'existing' | 'create_new'

        DB::beginTransaction();
        try {
            if ($mode === 'create_new') {
                $validated = $request->validate([
                    'name' => 'required|string|max:150',
                    'email' => 'required|string|email|max:150|unique:users,email',
                    'phone' => 'nullable|string|max:30',
                    'password' => 'required|string|min:6',
                    'role_title' => 'required|string|max:100',
                    'institution' => 'nullable|string|max:150',
                    'notes' => 'nullable|string|max:255',
                ]);

                // Create new User account
                $newUser = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => Hash::make($validated['password']),
                ]);

                // Assign pelatih_ekskul role
                $newUser->assignRole('pelatih_ekskul');

                // Assign to extracurricular
                ExtracurricularInstructor::create([
                    'extracurricular_id' => $activity->id,
                    'user_id' => $newUser->id,
                    'role_title' => $validated['role_title'],
                    'is_primary' => !$activity->instructors()->where('is_primary', true)->exists(),
                    'phone' => $validated['phone'] ?? null,
                    'institution' => $validated['institution'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                DB::commit();
                return back()->with('success', "Akun pelatih {$newUser->name} berhasil dibuat dan ditugaskan.");
            } else {
                $validated = $request->validate([
                    'user_id' => 'required|exists:users,id',
                    'role_title' => 'required|string|max:100',
                    'institution' => 'nullable|string|max:150',
                    'notes' => 'nullable|string|max:255',
                ]);

                $exists = ExtracurricularInstructor::where([
                    'extracurricular_id' => $activity->id,
                    'user_id' => $validated['user_id'],
                ])->exists();

                if ($exists) {
                    return back()->with('error', 'Pengguna sudah ditugaskan sebagai pelatih pada ekstrakurikuler ini.');
                }

                $coachUser = User::findOrFail($validated['user_id']);
                if (!$coachUser->hasRole('pelatih_ekskul')) {
                    $coachUser->assignRole('pelatih_ekskul');
                }

                ExtracurricularInstructor::create([
                    'extracurricular_id' => $activity->id,
                    'user_id' => $coachUser->id,
                    'role_title' => $validated['role_title'],
                    'is_primary' => !$activity->instructors()->where('is_primary', true)->exists(),
                    'phone' => $coachUser->phone,
                    'institution' => $validated['institution'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                DB::commit();
                return back()->with('success', "Pelatih {$coachUser->name} berhasil ditugaskan.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menugaskan pelatih: ' . $e->getMessage());
        }
    }

    /**
     * Unassign coach from extracurricular.
     */
    public function destroy($extracurricularId, $instructorId)
    {
        $instructor = ExtracurricularInstructor::where('extracurricular_id', $extracurricularId)
            ->findOrFail($instructorId);

        $name = $instructor->user?->name ?? 'Pelatih';
        $instructor->delete();

        return back()->with('success', "Pelatih {$name} berhasil dinonaktifkan dari kegiatan ini.");
    }
}
