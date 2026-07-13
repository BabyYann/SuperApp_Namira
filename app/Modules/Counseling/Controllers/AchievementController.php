<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\Achievement;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $query = Achievement::query()
            ->with(['student.classroom', 'creator'])
            ->where('unit_id', session('active_unit_id'));

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('student', function($sq) use ($request) {
                    $sq->where('full_name', 'like', '%'.$request->search.'%');
                })
                ->orWhere('title', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->level) {
            $query->where('level', $request->level);
        }

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $achievements = $query->latest('date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($a) => [
                'id' => $a->id,
                'date' => $a->date ? $a->date->format('d/m/Y') : '-',
                'student_name' => $a->student->full_name ?? 'Unknown',
                'classroom' => $a->student->classroom->name ?? '-',
                'title' => $a->title,
                'level' => $a->level,
                'description' => $a->description,
                'proof_file' => $a->proof_file ? asset('storage/' . $a->proof_file) : null,
                'creator_name' => $a->creator->name ?? 'System/Unknown',
            ]);

        $user = auth()->user();
        $canDelete = $user->hasAnyRole(['super_admin_yayasan', 'admin_unit', 'admin_yayasan', 'bk']);

        return Inertia::render('Counseling/Achievement/Index', [
            'achievements' => $achievements,
            'filters' => $request->only(['search', 'level', 'start_date', 'end_date']),
            'canDelete' => $canDelete
        ]);
    }

    public function create()
    {
        // Two-step dropdown logic: Classrooms first
        $classrooms = Classroom::where('unit_id', session('active_unit_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $students = Student::where('unit_id', session('active_unit_id'))
            ->orderBy('full_name') // Using full_name as standard
            ->get(['id', 'full_name', 'classroom_id'])
            ->map(fn($s) => [
                'id' => $s->id,
                'label' => $s->full_name,
                'classroom_id' => $s->classroom_id
            ]);

        return Inertia::render('Counseling/Achievement/Create', [
            'classrooms' => $classrooms,
            'students' => $students
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:255',
            'level' => 'required|string', // e.g. Sekolah, Kabupaten, Nasional
            'description' => 'nullable|string',
            'proof_file' => 'nullable|file|image|max:2048',
        ]);

        // Unit isolation: verify student belongs to active unit
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $student = Student::findOrFail($request->student_id);
            if ($student->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Siswa tersebut berada di unit lain.');
            }
        }

        $data = $request->except('proof_file');
        $data['unit_id'] = session('active_unit_id');
        $data['created_by'] = auth()->id();

        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('achievements', 'public');
        }

        $achievement = Achievement::create($data);

        // Automated WhatsApp Notification to parents
        try {
            $student = Student::with(['unit', 'classroom'])->find($request->student_id);
            if ($student && !empty($student->parent_phone)) {
                $dateFormatted = \Carbon\Carbon::parse($request->date)->translatedFormat('d F Y');
                $unitName = $student->unit->name ?? 'Namira School';
                
                $message = "🏆 *Pemberitahuan Prestasi Siswa*\n\n"
                    . "Yth. Orang Tua/Wali dari *{$student->full_name}* (Kelas: {$student->classroom->name}).\n\n"
                    . "Kabar gembira! Kami menginformasikan bahwa putra/putri Anda tercatat meraih prestasi berikut:\n"
                    . "• *Prestasi*: {$request->title}\n"
                    . "• *Tingkat*: {$request->level}\n"
                    . "• *Tanggal*: {$dateFormatted}\n"
                    . (!empty($request->description) ? "• *Keterangan*: {$request->description}\n" : "") . "\n"
                    . "Selamat atas pencapaian yang diraih ananda. Semoga terus memotivasi untuk berkarya dan berprestasi! 🌟\n\n"
                    . "Terima kasih.\n-- *{$unitName}*";

                \App\Helpers\WhatsAppHelper::send($student->parent_phone, $message);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send automated WA achievement notification: " . $e->getMessage());
        }

        return redirect()->route('counseling.achievements.index')
            ->with('success', 'Prestasi berhasil dicatat.');
    }

    public function destroy(Achievement $achievement)
    {
        if ($achievement->unit_id != session('active_unit_id')) abort(403);
        
        // Permission Check
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_unit', 'admin_yayasan', 'bk'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data ini.');
        }

        $achievement->delete();
        return redirect()->back()->with('success', 'Data dihapus.');
    }
}
