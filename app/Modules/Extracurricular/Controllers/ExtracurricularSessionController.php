<?php

namespace App\Modules\Extracurricular\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Extracurricular\Models\Extracurricular;
use App\Modules\Extracurricular\Models\ExtracurricularAttendance;
use App\Modules\Extracurricular\Models\ExtracurricularSession;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\WhatsAppQueue;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExtracurricularSessionController extends Controller
{
    /**
     * Store new activity session with documentation photos and student attendance.
     */
    public function store(Request $request, $extracurricularId)
    {
        $user = Auth::user();
        $activity = Extracurricular::with(['activeMembers.student'])->findOrFail($extracurricularId);

        $canManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan']);
        $isInstructor = $activity->instructors()->where('user_id', $user->id)->exists();

        if (!$canManage && !$isInstructor) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat sesi kegiatan ini.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'topic' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'photos' => 'nullable|array', // array of base64 strings or URLs
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpha',
            'attendances.*.notes' => 'nullable|string|max:255',
            'send_wa' => 'nullable|boolean',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        // Handle photos (Canvas compressed base64 strings or uploaded files)
        $savedPhotoPaths = [];
        if (!empty($validated['photos'])) {
            foreach ($validated['photos'] as $idx => $photoData) {
                if (is_string($photoData) && str_starts_with($photoData, 'data:image/')) {
                    $path = $this->handleBase64Image($photoData, 'extracurricular/sessions');
                    if ($path) {
                        $savedPhotoPaths[] = $path;
                    }
                }
            }
        }

        // Also check if multipart files uploaded
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $savedPhotoPaths[] = $file->store('extracurricular/sessions', 'public');
            }
        }

        DB::beginTransaction();
        try {
            $session = ExtracurricularSession::create([
                'extracurricular_id' => $activity->id,
                'academic_year_id' => $activeYear?->id,
                'instructor_id' => $user->id,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
                'photo_paths' => !empty($savedPhotoPaths) ? $savedPhotoPaths : null,
                'status' => 'completed',
                'created_by' => $user->id,
            ]);

            // Save attendances
            $studentsById = Student::whereIn('id', collect($validated['attendances'])->pluck('student_id'))
                ->get()
                ->keyBy('id');

            foreach ($validated['attendances'] as $att) {
                ExtracurricularAttendance::create([
                    'session_id' => $session->id,
                    'student_id' => $att['student_id'],
                    'status' => $att['status'],
                    'notes' => $att['notes'] ?? null,
                ]);

                // Optional WhatsApp Notification to parents
                if ($request->boolean('send_wa')) {
                    $student = $studentsById->get($att['student_id']);
                    $phone = $student?->parent_phone ?: $student?->guardian_phone;

                    if ($phone) {
                        $statusIndo = match ($att['status']) {
                            'hadir' => 'HADIR',
                            'izin' => 'IZIN',
                            'sakit' => 'SAKIT',
                            'alpha' => 'TIDAK HADIR (ALPHA)',
                            default => strtoupper($att['status']),
                        };

                        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }

                        $waText = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
                            . "Pemberitahuan Kegiatan Ekstrakurikuler Namira:\n"
                            . "• Ananda: *{$student->full_name}*\n"
                            . "• Kegiatan: *{$activity->name}*\n"
                            . "• Materi/Topik: {$session->topic}\n"
                            . "• Tanggal: " . date('d-m-Y', strtotime($session->date)) . " ({$session->start_time} - {$session->end_time} WIB)\n"
                            . "• Status Kehadiran: *{$statusIndo}*\n\n"
                            . "Terima kasih atas dukungannya dalam membimbing ananda.\n"
                            . "Wassalamu'alaikum Warahmatullahi Wabarakatuh.\n"
                            . "_Sistem Informasi SuperApp Namira_";

                        WhatsAppQueue::create([
                            'phone' => $cleanPhone,
                            'message' => $waText,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            // In-app notification to coordinators
            NotificationDispatcher::sendToRoles(
                ['koordinator_kesiswaan', 'kepala_sekolah'],
                $activity->unit_id,
                "Jurnal Ekskul: {$activity->name}",
                "Pelatih telah menyelesaikan sesi latihan '{$session->topic}' dengan {$session->attendances()->where('status', 'hadir')->count()} siswa hadir.",
                'academic',
                ['url' => route('extracurricular.show', $activity->id)]
            );

            DB::commit();
            return back()->with('success', 'Sesi kegiatan dan presensi berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan sesi kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Update activity session and student attendances.
     */
    public function update(Request $request, $extracurricularId, $sessionId)
    {
        $user = Auth::user();
        $activity = Extracurricular::findOrFail($extracurricularId);
        $session = ExtracurricularSession::where('extracurricular_id', $extracurricularId)->findOrFail($sessionId);

        $canManage = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'koordinator_kesiswaan']);
        $isInstructor = $activity->instructors()->where('user_id', $user->id)->exists();

        if (!$canManage && !$isInstructor) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah sesi kegiatan ini.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'topic' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'photos' => 'nullable|array',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpha',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $savedPhotoPaths = $session->photo_paths ?: [];

        if (!empty($validated['photos'])) {
            foreach ($validated['photos'] as $photoData) {
                if (is_string($photoData) && str_starts_with($photoData, 'data:image/')) {
                    $path = $this->handleBase64Image($photoData, 'extracurricular/sessions');
                    if ($path) {
                        $savedPhotoPaths[] = $path;
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $session->update([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
                'photo_paths' => $savedPhotoPaths,
            ]);

            foreach ($validated['attendances'] as $att) {
                ExtracurricularAttendance::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'student_id' => $att['student_id'],
                    ],
                    [
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                    ]
                );
            }

            DB::commit();
            return back()->with('success', 'Sesi kegiatan dan presensi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui sesi kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Delete activity session and attached photos.
     */
    public function destroy($extracurricularId, $sessionId)
    {
        $session = ExtracurricularSession::where('extracurricular_id', $extracurricularId)->findOrFail($sessionId);

        if (!empty($session->photo_paths) && is_array($session->photo_paths)) {
            foreach ($session->photo_paths as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }

        $session->delete();

        return back()->with('success', 'Sesi kegiatan latihan berhasil dihapus.');
    }

    /**
     * Helper to decode Base64 image and save to disk.
     */
    private function handleBase64Image(string $base64Data, string $folder): ?string
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
            $fileName = $folder . '/' . uniqid('session_', true) . '.' . $type;
            Storage::disk('public')->put($fileName, $data);
            return $fileName;
        }
        return null;
    }
}
