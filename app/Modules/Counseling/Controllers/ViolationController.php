<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\Violation;
use App\Modules\Counseling\Models\ViolationCategory;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ViolationController extends Controller
{
    public function index(Request $request)
    {
        $query = Violation::query()
            ->with(['student.classroom', 'category', 'reporter'])
            ->where('unit_id', session('active_unit_id')); // Scope by Unit

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('student', function ($sq) use ($request) {
                    $sq->where('full_name', 'like', '%' . $request->search . '%')
                      ->orWhere('nis', 'like', '%' . $request->search . '%');
                })
                ->orWhereHas('category', function ($cq) use ($request) {
                    $cq->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }
        
        // Filter by Date Range
        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $violations = $query->latest('date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($v) => [
                'id' => $v->id,
                'date' => $v->date->format('Y-m-d'),
                'student' => [
                    'name' => $v->student->full_name ?? 'Unknown',
                    'classroom' => $v->student->classroom->name ?? 'No Class',
                ],
                'category' => [
                    'name' => $v->category->name,
                    'type' => $v->category->type,
                ],
                'description' => $v->description,
                'points' => $v->points,
                'photo_proof' => $v->photo_proof ? asset('storage/' . $v->photo_proof) : null,
                'reporter_name' => $v->reporter->name ?? 'System',
            ]);

        $user = auth()->user();
        $canDelete = $user->hasAnyRole(['super_admin_yayasan', 'admin_unit', 'admin_yayasan', 'bk']);

        return Inertia::render('Counseling/Violation/Index', [
            'violations' => $violations,
            'filters' => $request->only(['search', 'start_date', 'end_date']),
            'canDelete' => $canDelete
        ]);
    }

    public function create()
    {
        $unitId = session('active_unit_id');

        // Fetch Classrooms for the Filter
        $classrooms = \App\Modules\Academic\Models\Classroom::where('unit_id', $unitId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Fetch Students Scoped by Unit
        $students = Student::with('classroom')
            ->where('unit_id', $unitId)
            ->select('id', 'full_name', 'classroom_id', 'nis')
            ->orderBy('full_name') // Order by name for easier search
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'classroom_id' => $s->classroom_id, // Critical for frontend filtering
                    'label' => $s->full_name . ' (' . $s->nis . ')',
                ];
            });

        return Inertia::render('Counseling/Violation/Create', [
            'categories' => ViolationCategory::where('unit_id', $unitId)->orderBy('name')->get(),
            'students' => $students,
            'classrooms' => $classrooms,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => 'required|exists:violation_categories,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048', // 2MB Max
        ]);

        // Get default points
        $category = ViolationCategory::find($request->violation_category_id);
        
        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('violations', 'public');
        }

        $violation = Violation::create([
            'unit_id' => session('active_unit_id'),
            'student_id' => $request->student_id,
            'violation_category_id' => $category->id,
            'date' => $request->date,
            'points' => $category->default_points, // Use default
            'description' => $request->description,
            'photo_proof' => $path,
            'reported_by' => Auth::id(),
            'approved_by' => Auth::id(), // Auto-approve for now (BK Input)
        ]);

        $student = Student::with(['unit', 'classroom', 'user'])->find($request->student_id);

        if ($student) {
            if ($student->user) {
                \App\Services\NotificationDispatcher::sendToUser(
                    $student->user,
                    '⚠️ Catatan Kedisiplinan Siswa',
                    "Tercatat pelanggaran kedisiplinan: {$category->name} ({$category->default_points} poin) pada {$request->date}.",
                    'counseling',
                    ['violation_id' => $violation->id]
                );
            }
            \App\Services\NotificationDispatcher::sendToRoles(
                ['wali_kelas', 'bk', 'admin_unit', 'kepala_sekolah'],
                $violation->unit_id,
                '⚠️ Catatan Kedisiplinan Siswa',
                "Siswa {$student->full_name} ({$student->classroom->name}) tercatat pelanggaran {$category->name} ({$category->default_points} poin).",
                'counseling',
                ['violation_id' => $violation->id]
            );
        }

        // Calculate accumulated points & check warning thresholds
        $totalPoints = Violation::where('student_id', $request->student_id)->sum('points');
        $warningLevel = null;
        if ($totalPoints >= 100) {
            $warningLevel = 'Surat Peringatan 3 (SP-3 / Pemanggilan Orang Tua)';
        } elseif ($totalPoints >= 75) {
            $warningLevel = 'Surat Peringatan 2 (SP-2)';
        } elseif ($totalPoints >= 50) {
            $warningLevel = 'Surat Peringatan 1 (SP-1)';
        } elseif ($totalPoints >= 30) {
            $warningLevel = 'Peringatan Lisan / Pembinaan Khusus BK';
        }

        if ($warningLevel && $student) {
            \App\Services\NotificationDispatcher::sendToRoles(
                ['wali_kelas', 'bk', 'admin_unit', 'kepala_sekolah'],
                $violation->unit_id,
                '🚨 Ambang Batas Pelanggaran Terlampaui',
                "Siswa {$student->full_name} ({$student->classroom->name}) telah mencapai {$totalPoints} akumulasi poin pelanggaran. Status: {$warningLevel}.",
                'counseling',
                ['student_id' => $student->id, 'total_points' => $totalPoints]
            );
        }

        // Automated WhatsApp Notification to parents
        try {
            if ($student && !empty($student->parent_phone)) {
                $dateFormatted = \Carbon\Carbon::parse($request->date)->translatedFormat('d F Y');
                $unitName = $student->unit->name ?? 'Namira School';
                
                $warningNote = $warningLevel ? "\n⚠️ *Status Kedisiplinan*: Akumulasi mencapai *{$totalPoints} poin* ({$warningLevel}).\n" : "";

                $message = "📋 *Pemberitahuan Pelanggaran Siswa*\n\n"
                    . "Yth. Orang Tua/Wali dari *{$student->full_name}* (Kelas: {$student->classroom->name}).\n\n"
                    . "Kami menginformasikan bahwa putra/putri Anda tercatat melakukan pelanggaran berikut:\n"
                    . "• *Pelanggaran*: {$category->name}\n"
                    . "• *Tanggal*: {$dateFormatted}\n"
                    . "• *Poin Pelanggaran*: {$category->default_points} poin\n"
                    . (!empty($request->description) ? "• *Keterangan*: {$request->description}\n" : "")
                    . $warningNote . "\n"
                    . "Mohon perhatian dan kerja samanya untuk membimbing ananda agar lebih disiplin.\n\n"
                    . "Terima kasih.\n-- *{$unitName}*";

                \App\Helpers\WhatsAppHelper::send($student->parent_phone, $message);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send automated WA violation notification: " . $e->getMessage());
        }

        $successMsg = $warningLevel 
            ? "Pelanggaran berhasil dicatat. Peringatan: Total poin mencapai {$totalPoints} ({$warningLevel})." 
            : 'Pelanggaran berhasil dicatat.';

        return redirect()->route('counseling.violations.index')->with('success', $successMsg);
    }

    public function destroy(Violation $violation)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'bk'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pelanggaran.');
        }

        if (
            !auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            && $violation->unit_id != session('active_unit_id')
        ) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk data pelanggaran di unit lain.');
        }

        $violation->delete();
        return redirect()->back()->with('success', 'Data pelanggaran dihapus.');
    }
}
