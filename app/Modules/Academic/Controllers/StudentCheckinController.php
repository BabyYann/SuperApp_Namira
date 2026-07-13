<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\StudentCheckin;
use App\Modules\Yayasan\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentCheckinController extends Controller
{
    /**
     * Halaman QR Scanner untuk Guru Piket
     */
    public function index()
    {
        $today = Carbon::today();

        // Rekap check-in hari ini
        $todayCheckins = StudentCheckin::whereDate('checkin_date', $today)
            ->with(['student.classroom'])
            ->orderBy('checkin_time', 'asc')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'student_name'  => $c->student->full_name ?? '-',
                'nis'           => $c->student->nis ?? '-',
                'classroom'     => $c->student->classroom->name ?? '-',
                'checkin_time'  => $c->checkin_time,
                'status'        => $c->status,
            ]);

        $deadline = SystemSetting::getSetting('checkin_deadline', '07:30');

        return Inertia::render('Academic/QRScanner/Index', [
            'todayCheckins' => $todayCheckins,
            'todayDate'     => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
            'deadline'      => $deadline,
            'hadir_count'   => $todayCheckins->where('status', 'hadir')->count(),
            'terlambat_count' => $todayCheckins->where('status', 'terlambat')->count(),
        ]);
    }

    /**
     * Endpoint AJAX: Proses scan QR / Barcode
     * POST /academic/checkin/scan
     * Body: { nis: "5251564" }
     */
    public function scan(Request $request)
    {
        $request->validate(['nis' => 'required|string|max:50']);

        $nis = trim($request->nis);

        // 1. Cari siswa berdasarkan NIS
        $student = Student::where('nis', $nis)->with(['classroom', 'unit'])->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => "NIS «{$nis}» tidak ditemukan dalam database.",
                'code'    => 'STUDENT_NOT_FOUND',
            ], 404);
        }

        $today = Carbon::today();

        // 2. Cek apakah sudah check-in hari ini
        $existing = StudentCheckin::where('student_id', $student->id)
            ->whereDate('checkin_date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success'  => false,
                'message'  => "{$student->full_name} sudah tercatat hadir pukul {$existing->checkin_time}.",
                'code'     => 'ALREADY_CHECKED_IN',
                'student'  => $this->formatStudentResponse($student, $existing),
            ], 409);
        }

        // 3. Tentukan status: hadir atau terlambat
        $now          = Carbon::now();
        $deadlineTime = $today->copy()->setTimeFromTimeString(
            SystemSetting::getSetting('checkin_deadline', '07:30')
        );
        $status = $now->greaterThan($deadlineTime) ? 'terlambat' : 'hadir';

        // 4. Simpan check-in
        $checkin = StudentCheckin::create([
            'student_id'       => $student->id,
            'unit_id'          => $student->unit_id,
            'scanned_by'       => Auth::id(),
            'checkin_date'     => $today->toDateString(),
            'checkin_time'     => $now->format('H:i:s'),
            'status'           => $status,
        ]);

        // 5. Kirim WA notifikasi ke orang tua (non-blocking via queue)
        $this->dispatchWhatsAppNotification($student, $checkin, $status, $now);

        return response()->json([
            'success' => true,
            'message' => "✅ {$student->full_name} berhasil tercatat hadir.",
            'status'  => $status,
            'student' => $this->formatStudentResponse($student, $checkin),
        ]);
    }

    /**
     * Rekap kehadiran untuk laporan Admin / Yayasan
     */
    public function recap(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $checkins = StudentCheckin::whereDate('checkin_date', $date)
            ->with(['student.classroom', 'scannedByUser'])
            ->orderBy('checkin_time')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'nis'          => $c->student->nis ?? '-',
                'student_name' => $c->student->full_name ?? '-',
                'classroom'    => $c->student->classroom->name ?? '-',
                'checkin_time' => $c->checkin_time,
                'status'       => $c->status,
                'scanned_by'   => $c->scannedByUser->name ?? 'Sistem',
            ]);

        return response()->json([
            'date'     => $date->locale('id')->isoFormat('dddd, D MMMM Y'),
            'checkins' => $checkins,
            'summary'  => [
                'hadir'     => $checkins->where('status', 'hadir')->count(),
                'terlambat' => $checkins->where('status', 'terlambat')->count(),
                'total'     => $checkins->count(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function formatStudentResponse(Student $student, StudentCheckin $checkin): array
    {
        return [
            'id'            => $student->id,
            'full_name'     => $student->full_name,
            'nis'           => $student->nis,
            'classroom'     => $student->classroom->name ?? '-',
            'photo_url'     => $student->photo_path
                ? asset('storage/' . $student->photo_path)
                : null,
            'checkin_time'  => $checkin->checkin_time,
            'status'        => $checkin->status,
            'parent_phone'  => $student->parent_phone,
        ];
    }

    private function dispatchWhatsAppNotification(Student $student, StudentCheckin $checkin, string $status, Carbon $time): void
    {
        // Hanya kirim jika ada nomor orang tua
        if (!$student->parent_phone) return;

        $timeFormatted = $time->format('H:i');
        $dateFormatted = $time->locale('id')->isoFormat('dddd, D MMMM Y');
        $statusText    = $status === 'terlambat' ? '⚠️ *TERLAMBAT*' : '✅ *HADIR TEPAT WAKTU*';
        $classroomName = $student->classroom->name ?? '-';

        $unitName = $student->unit->name ?? 'Namira School';

        if ($status === 'terlambat') {
            $message = "📋 *Notifikasi Kehadiran Siswa*\n\n"
                . "Yth. Orang Tua/Wali dari *{$student->full_name}*\n\n"
                . "Ananda tercatat {$statusText} di sekolah.\n\n"
                . "📅 Tanggal  : {$dateFormatted}\n"
                . "🕐 Jam Masuk: {$timeFormatted} WIB\n"
                . "📚 Kelas    : {$classroomName}\n"
                . "🏫 NIS      : {$student->nis}\n\n"
                . "Mohon untuk memastikan keberangkatan lebih awal agar tidak terlambat lagi.\n\n"
                . "_Pesan ini dikirim otomatis oleh sistem {$unitName}._";
        } else {
            $message = "📋 *Notifikasi Kehadiran Siswa*\n\n"
                . "Yth. Orang Tua/Wali dari *{$student->full_name}*\n\n"
                . "Ananda tercatat {$statusText} di sekolah.\n\n"
                . "📅 Tanggal  : {$dateFormatted}\n"
                . "🕐 Jam Masuk: {$timeFormatted} WIB\n"
                . "📚 Kelas    : {$classroomName}\n"
                . "🏫 NIS      : {$student->nis}\n\n"
                . "Terima kasih. Semoga ananda belajar dengan baik hari ini! 🌟\n\n"
                . "_Pesan ini dikirim otomatis oleh sistem {$unitName}._";
        }

        // Dispatch ke WhatsApp Queue yang sudah ada (send() otomatis masuk queue)
        \App\Helpers\WhatsAppHelper::send($student->parent_phone, $message);
    }
}
