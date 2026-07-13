<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassPromotion;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Modules\Academic\Requests\PromotionRequest;
use App\Modules\Academic\Services\Promotion\PromotionValidationEngine;
use App\Modules\Academic\Exceptions\PromotionValidationException;
use App\Modules\Academic\Services\Promotion\PromotionPreviewService;
use App\Modules\Academic\Services\Promotion\PromotionExecutionService;

class ClassPromotionController extends Controller
{
    /**
     * Show promotion wizard - now simplified since classrooms are permanent
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses menu promosi kelas.');
        }

        $unitId = session('active_unit_id');
        
        // Get all academic years with student counts
        $academicYears = AcademicYear::orderBy('id', 'desc')->get()->map(function ($year) use ($unitId) {
            // Count students in this year for this unit
            $year->student_count = Student::where('unit_id', $unitId)
                ->where('academic_year_id', $year->id)
                ->count();
            return $year;
        });
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        
        // Get all classrooms for this unit (permanent - no year filtering)
        $classrooms = Classroom::where('unit_id', $unitId)
            ->with('homeroomTeacher')
            ->withCount('students')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return Inertia::render('Academic/Promotion/Index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'classrooms' => $classrooms,
            'statusOptions' => ClassPromotion::$statusLabels,
        ]);
    }

    /**
     * Preview students for promotion
     */
    public function preview(Request $request, PromotionPreviewService $previewService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melakukan pratinjau promosi kelas.');
        }

        $unitId = session('active_unit_id');
        
        $request->validate([
            'from_classroom_id' => 'required|exists:classrooms,id',
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_classroom_id' => 'nullable|exists:classrooms,id',
            'to_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $fromClassroom = Classroom::findOrFail($request->from_classroom_id);
        $fromAcademicYear = AcademicYear::findOrFail($request->from_academic_year_id);
        
        // Ensure classroom belongs to user's active unit
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $fromClassroom->unit_id !== $unitId) {
            abort(403, 'Kelas tidak ditemukan di unit Anda.');
        }
        
        $toClassroom = $request->to_classroom_id ? Classroom::find($request->to_classroom_id) : null;
        if ($toClassroom && !auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $toClassroom->unit_id !== $unitId) {
            abort(403, 'Kelas tujuan tidak ditemukan di unit Anda.');
        }
        
        $toAcademicYear = AcademicYear::findOrFail($request->to_academic_year_id);

        // Run validation and stats via preview service (N+1 query optimized)
        $previewData = $previewService->getPreviewData($fromClassroom->id, $fromAcademicYear->id);

        // Check if any of these students already promoted to target year
        $studentIds = collect($previewData['students'])->pluck('student_id')->toArray();
        $alreadyPromoted = ClassPromotion::whereIn('student_id', $studentIds)
            ->where('from_classroom_id', $fromClassroom->id)
            ->where('to_academic_year_id', $toAcademicYear->id)
            ->pluck('student_id')
            ->toArray();

        return response()->json([
            'students' => $previewData['students'],
            'summary' => $previewData['summary'],
            'fromClassroom' => $fromClassroom,
            'fromAcademicYear' => $fromAcademicYear,
            'toClassroom' => $toClassroom,
            'toAcademicYear' => $toAcademicYear,
            'alreadyPromoted' => $alreadyPromoted,
        ]);
    }

    /**
     * Export promotion preview to CSV/Excel
     */
    public function exportPreview(Request $request, PromotionPreviewService $previewService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengekspor pratinjau promosi kelas.');
        }

        $unitId = session('active_unit_id');
        
        $request->validate([
            'from_classroom_id' => 'required|exists:classrooms,id',
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_classroom_id' => 'nullable|exists:classrooms,id',
            'to_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $fromClassroom = Classroom::findOrFail($request->from_classroom_id);
        $fromAcademicYear = AcademicYear::findOrFail($request->from_academic_year_id);
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $fromClassroom->unit_id !== $unitId) {
            abort(403, 'Kelas tidak ditemukan di unit Anda.');
        }

        $previewData = $previewService->getPreviewData($fromClassroom->id, $fromAcademicYear->id);

        $filename = 'Preview_Promosi_' . str_replace(' ', '_', $fromClassroom->name) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($previewData) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fputs($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Kelas Asal', 'Status Kelulusan/Promosi', 'Keterangan / Pelanggaran']);

            $no = 1;
            foreach ($previewData['students'] as $student) {
                $statusLabel = strtoupper($student['status']);
                $reasons = collect($student['violations'])->map(fn($v) => $v['message'])->join('; ');

                fputcsv($file, [
                    $no++,
                    $student['nis'],
                    $student['nama'],
                    $student['classroom'],
                    $statusLabel,
                    $reasons ?: 'Memenuhi Syarat (Eligible)'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process promotions - update student's classroom_id and academic_year_id
     */
    public function store(PromotionRequest $request, PromotionExecutionService $executionService)
    {
        $unitId = session('active_unit_id');

        $fromClassroom = Classroom::findOrFail($request->from_classroom_id);
        
        // Ensure classroom belongs to user's active unit
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $fromClassroom->unit_id !== $unitId) {
            abort(403, 'Kelas tidak ditemukan di unit Anda.');
        }

        try {
            $executionService->executePromotion(
                $request->from_classroom_id,
                $request->from_academic_year_id,
                $request->to_classroom_id,
                $request->to_academic_year_id,
                $request->promotions,
                Auth::id()
            );
            
            return redirect()->route('yayasan.promotion.history')
                ->with('success', count($request->promotions) . ' siswa berhasil diproses.');
                
        } catch (PromotionValidationException $e) {
            $formattedErrors = [];
            foreach ($e->getValidationErrors() as $studentId => $data) {
                foreach ($data['errors'] as $err) {
                    $formattedErrors[] = "{$data['student_name']}: {$err}";
                }
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Validasi promosi gagal: ' . implode(' | ', $formattedErrors))
                ->withErrors(['promotions' => $formattedErrors]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses promosi: ' . $e->getMessage());
        }
    }

    /**
     * Show promotion history
     */
    public function history(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat riwayat promosi kelas.');
        }

        $unitId = session('active_unit_id');
        
        $query = ClassPromotion::with([
            'student',
            'fromClassroom',
            'toClassroom',
            'fromAcademicYear',
            'toAcademicYear',
            'promotedBy',
            'rolledBackBy'
        ])
        ->whereHas('fromClassroom', fn($q) => $q->where('unit_id', $unitId));

        // Filters
        if ($request->academic_year_id) {
            $query->where('to_academic_year_id', $request->academic_year_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $promotions = $query->orderBy('promoted_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $academicYears = AcademicYear::orderBy('id', 'desc')->get();

        return Inertia::render('Academic/Promotion/History', [
            'promotions' => $promotions,
            'academicYears' => $academicYears,
            'statusOptions' => ClassPromotion::$statusLabels,
            'filters' => $request->only(['academic_year_id', 'status']),
        ]);
    }

    /**
     * Export promotion history to PDF
     */
    public function export(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengekspor riwayat promosi kelas.');
        }

        $unitId = session('active_unit_id');
        $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
        
        $query = ClassPromotion::with([
            'student',
            'fromClassroom',
            'toClassroom',
            'fromAcademicYear',
            'toAcademicYear',
            'promotedBy',
            'rolledBackBy'
        ])
        ->whereHas('fromClassroom', fn($q) => $q->where('unit_id', $unitId));

        // Filters
        if ($request->academic_year_id) {
            $query->where('to_academic_year_id', $request->academic_year_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $promotions = $query->orderBy('promoted_at', 'desc')->get();
        
        $academicYear = $request->academic_year_id 
            ? AcademicYear::find($request->academic_year_id) 
            : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.promotion-report', [
            'promotions' => $promotions,
            'unit' => $unit,
            'academicYear' => $academicYear,
            'statusLabels' => ClassPromotion::$statusLabels,
            'filters' => [
                'status' => $request->status,
            ],
            'generatedAt' => Carbon::now()->format('d F Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'Rekap_Kenaikan_Kelas_' . ($unit ? $unit->name : 'All') . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Rollback a promotion - restore student to previous state
     */
    public function rollback(ClassPromotion $promotion, PromotionExecutionService $executionService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membatalkan promosi kelas.');
        }

        $unitId = session('active_unit_id');
        
        // Ensure promotion belongs to this unit
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && (!$promotion->fromClassroom || $promotion->fromClassroom->unit_id !== $unitId)) {
            abort(403, 'Tidak memiliki akses untuk membatalkan promosi ini.');
        }

        try {
            $executionService->rollbackPromotion($promotion, Auth::id());

            return redirect()->back()->with('success', 'Promosi berhasil dibatalkan. Siswa dikembalikan ke kelas sebelumnya.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan promosi: ' . $e->getMessage());
        }
    }
}
