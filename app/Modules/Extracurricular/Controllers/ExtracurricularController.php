<?php

namespace App\Modules\Extracurricular\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Extracurricular\Models\Extracurricular;
use App\Modules\Extracurricular\Models\ExtracurricularInstructor;
use App\Modules\Extracurricular\Models\ExtracurricularMember;
use App\Modules\Extracurricular\Models\ExtracurricularSession;
use App\Modules\Sarpar\Models\Room;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class ExtracurricularController extends Controller
{
    /**
     * Display extracurricular activities list / catalog.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $unitId = session('active_unit_id') ?? $user->teacher_profile?->unit_id;

        // Role-based permissions
        $isGlobalAdmin = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pengawas_yayasan']);
        $canManage = $user->hasAnyRole([
            'super_admin_yayasan', 
            'admin_yayasan', 
            'admin_unit', 
            'kepala_sekolah', 
            'koordinator_kesiswaan'
        ]);
        $isCoach = $user->hasRole('pelatih_ekskul');

        // Active Academic Year
        $activeYear = AcademicYear::where('is_active', true)->first() 
            ?: AcademicYear::latest('id')->first();

        $query = Extracurricular::query()
            ->with([
                'unit:id,name,code',
                'room:id,name,location',
                'instructors.user:id,name,phone,profile_photo',
                'academicYear:id,name,semester',
            ])
            ->withCount(['activeMembers', 'sessions']);

        // Scoping per unit
        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by day
        if ($request->filled('day') && $request->day !== 'all') {
            $query->where('day_of_week', $request->day);
        }

        // Filter by search keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderBy('name')->get();

        // Check if user is an instructor for any of the activities
        $assignedActivityIds = ExtracurricularInstructor::where('user_id', $user->id)
            ->pluck('extracurricular_id')
            ->toArray();

        $activities->transform(function ($item) use ($assignedActivityIds, $canManage) {
            $item->is_my_activity = in_array($item->id, $assignedActivityIds);
            $item->can_edit = $canManage || $item->is_my_activity;
            return $item;
        });

        // Statistics
        $totalActivities = $activities->count();
        $totalMembers = ExtracurricularMember::whereIn('extracurricular_id', $activities->pluck('id'))
            ->where('status', 'active')
            ->distinct('student_id')
            ->count('student_id');

        $startOfMonth = Carbon::now()->startOfMonth();
        $totalSessionsMonth = ExtracurricularSession::whereIn('extracurricular_id', $activities->pluck('id'))
            ->where('date', '>=', $startOfMonth)
            ->count();

        $totalInstructors = ExtracurricularInstructor::whereIn('extracurricular_id', $activities->pluck('id'))
            ->distinct('user_id')
            ->count('user_id');

        // Rooms for dropdown (Sarpar)
        $rooms = Room::query();
        if ($unitId) {
            $rooms->where('unit_id', $unitId);
        }
        $rooms = $rooms->orderBy('name')->get(['id', 'name', 'location']);

        // Users available for instructors
        $availableInstructors = User::whereHas('roles', function ($r) {
            $r->whereIn('name', ['pelatih_ekskul', 'teacher', 'staff_unit']);
        })->orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Extracurricular/Index', [
            'activities' => $activities,
            'stats' => [
                'total_activities' => $totalActivities,
                'total_members' => $totalMembers,
                'total_sessions_month' => $totalSessionsMonth,
                'total_instructors' => $totalInstructors,
            ],
            'rooms' => $rooms,
            'availableInstructors' => $availableInstructors,
            'filters' => $request->only(['category', 'day', 'search']),
            'canManage' => $canManage,
            'isCoach' => $isCoach,
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Store new extracurricular activity.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $unitId = session('active_unit_id') ?? $user->teacher_profile?->unit_id;

        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah ekstrakurikuler.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|in:olahraga,seni_budaya,keagamaan,sains_teknologi,kepanduan,lainnya',
            'day_of_week' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'room_id' => 'nullable|exists:sarpar_rooms,id',
            'location_name' => 'nullable|string|max:200',
            'max_quota' => 'required|integer|min:1|max:500',
            'is_mandatory' => 'nullable|boolean',
            'gender_restriction' => 'required|string|in:all,male_only,female_only',
            'target_levels' => 'nullable|array',
            'description' => 'nullable|string|max:2000',
            'cover_image' => 'nullable', // file or base64 string
            'instructor_ids' => 'nullable|array',
            'instructor_ids.*' => 'exists:users,id',
        ]);

        $coverImagePath = null;
        if ($request->filled('cover_image')) {
            $coverImagePath = $this->handleImageUpload($request->input('cover_image'), 'extracurricular/covers');
        } elseif ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('extracurricular/covers', 'public');
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        DB::beginTransaction();
        try {
            $activity = Extracurricular::create([
                'unit_id' => $unitId ?? 1,
                'academic_year_id' => $activeYear?->id,
                'name' => $validated['name'],
                'category' => $validated['category'],
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'room_id' => $validated['room_id'] ?? null,
                'location_name' => $validated['location_name'] ?? null,
                'max_quota' => $validated['max_quota'],
                'is_mandatory' => $request->boolean('is_mandatory'),
                'gender_restriction' => $validated['gender_restriction'],
                'target_levels' => $validated['target_levels'] ?? null,
                'description' => $validated['description'] ?? null,
                'cover_image' => $coverImagePath,
                'status' => 'active',
                'created_by' => $user->id,
            ]);

            // Assign instructors if provided
            if (!empty($validated['instructor_ids'])) {
                foreach ($validated['instructor_ids'] as $idx => $coachUserId) {
                    ExtracurricularInstructor::create([
                        'extracurricular_id' => $activity->id,
                        'user_id' => $coachUserId,
                        'role_title' => $idx === 0 ? 'Pelatih Utama' : 'Asisten Pelatih',
                        'is_primary' => $idx === 0,
                    ]);

                    // Assign role pelatih_ekskul to the user if they don't have it
                    $coachUser = User::find($coachUserId);
                    if ($coachUser && !$coachUser->hasRole('pelatih_ekskul')) {
                        $coachUser->assignRole('pelatih_ekskul');
                    }
                }
            }

            DB::commit();
            return back()->with('success', "Ekstrakurikuler {$activity->name} berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan ekstrakurikuler: ' . $e->getMessage());
        }
    }

    /**
     * Display detailed activity view (Tabs: Info, Anggota, Jurnal Sesi & Presensi, Nilai Rapor).
     */
    public function show($id)
    {
        $user = Auth::user();
        $unitId = session('active_unit_id') ?? $user->teacher_profile?->unit_id;

        $activity = Extracurricular::with([
            'unit:id,name,code',
            'room:id,name,location',
            'academicYear:id,name,semester',
            'instructors.user:id,name,email,phone,profile_photo',
            'activeMembers.student.classroom:id,name,level',
            'activeMembers.grade',
            'sessions' => function ($q) {
                $q->with(['instructor:id,name,phone', 'attendances.student:id,full_name,nisn'])
                  ->orderBy('date', 'desc');
            },
        ])->findOrFail($id);

        $canManage = $user->hasAnyRole([
            'super_admin_yayasan', 
            'admin_yayasan', 
            'admin_unit', 
            'kepala_sekolah', 
            'koordinator_kesiswaan'
        ]);

        $isInstructor = $activity->instructors->contains('user_id', $user->id);

        // Classrooms in the same unit for bulk adding students
        $classrooms = \App\Modules\Academic\Models\Classroom::query();
        if ($unitId) {
            $classrooms->where('unit_id', $unitId);
        }
        $classrooms = $classrooms->orderBy('level')->orderBy('name')->get(['id', 'name', 'level']);

        // Rooms for location selection
        $rooms = Room::query();
        if ($unitId) {
            $rooms->where('unit_id', $unitId);
        }
        $rooms = $rooms->orderBy('name')->get(['id', 'name', 'location']);

        // Available teachers / coaches
        $availableInstructors = User::whereHas('roles', function ($r) {
            $r->whereIn('name', ['pelatih_ekskul', 'teacher', 'staff_unit']);
        })->orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Extracurricular/Show', [
            'activity' => $activity,
            'classrooms' => $classrooms,
            'rooms' => $rooms,
            'availableInstructors' => $availableInstructors,
            'canManage' => $canManage,
            'isInstructor' => $isInstructor,
        ]);
    }

    /**
     * Update extracurricular activity.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $activity = Extracurricular::findOrFail($id);

        $canManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan']);
        $isInstructor = $activity->instructors()->where('user_id', $user->id)->exists();

        if (!$canManage && !$isInstructor) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah ekstrakurikuler ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|in:olahraga,seni_budaya,keagamaan,sains_teknologi,kepanduan,lainnya',
            'day_of_week' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'room_id' => 'nullable|exists:sarpar_rooms,id',
            'location_name' => 'nullable|string|max:200',
            'max_quota' => 'required|integer|min:1|max:500',
            'is_mandatory' => 'nullable|boolean',
            'gender_restriction' => 'required|string|in:all,male_only,female_only',
            'target_levels' => 'nullable|array',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|string|in:active,inactive',
            'cover_image' => 'nullable',
        ]);

        if ($request->filled('cover_image') && str_starts_with($request->cover_image, 'data:image/')) {
            if ($activity->cover_image && Storage::disk('public')->exists($activity->cover_image)) {
                Storage::disk('public')->delete($activity->cover_image);
            }
            $validated['cover_image'] = $this->handleImageUpload($request->cover_image, 'extracurricular/covers');
        } elseif ($request->hasFile('cover_image')) {
            if ($activity->cover_image && Storage::disk('public')->exists($activity->cover_image)) {
                Storage::disk('public')->delete($activity->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('extracurricular/covers', 'public');
        } else {
            unset($validated['cover_image']);
        }

        $validated['is_mandatory'] = $request->boolean('is_mandatory');

        $activity->update($validated);

        return back()->with('success', 'Data ekstrakurikuler berhasil diperbarui.');
    }

    /**
     * Delete extracurricular activity.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan'])) {
            abort(403, 'Hanya koordinator kesiswaan dan admin yang dapat menghapus ekstrakurikuler.');
        }

        $activity = Extracurricular::findOrFail($id);
        $name = $activity->name;
        $activity->delete();

        return redirect()->route('extracurricular.index')->with('success', "Ekstrakurikuler {$name} berhasil dihapus.");
    }

    /**
     * Helper to decode Base64 image and save to disk.
     */
    private function handleImageUpload(string $base64Data, string $folder): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $type = 'jpg';
            }
            $data = base64_decode($data);
            if ($data === false) {
                return null;
            }
            $fileName = $folder . '/' . uniqid('cover_', true) . '.' . $type;
            Storage::disk('public')->put($fileName, $data);
            return $fileName;
        }
        return null;
    }
}
