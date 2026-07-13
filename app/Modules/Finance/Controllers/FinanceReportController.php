<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Helpers\WhatsAppHelper;

class FinanceReportController extends Controller
{
    public function arrears(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');

        if ($request->classroom_id) {
            $classroom = Classroom::findOrFail($request->classroom_id);
            if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
                if ($classroom->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Unit tidak sesuai.');
                }
            }
        }

        $classrooms = Classroom::where('unit_id', $unitId)->get();
        
        $query = Student::where('unit_id', $unitId)
            ->whereHas('bills', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial']);
            })
            ->with(['classroom', 'unit', 'bills' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial']);
            }]);

        if ($request->classroom_id) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->search) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        // Calculate Summary Stats (Pre-pagination)
        $summaryQuery = clone $query;
        $studentCount = $summaryQuery->count();
        
        $totalArrearsSum = $summaryQuery->get()->sum(function ($student) {
             return $student->bills->whereIn('status', ['unpaid', 'partial'])->sum(function($b) {
                  return $b->final_amount - $b->paid_amount;
             });
        });

        $students = $query->paginate(20)
            ->withQueryString()
            ->through(function ($student) {
            $totalArrears = $student->bills->sum(function ($bill) {
                return $bill->final_amount - $bill->paid_amount;
            });

            // Generate WA Message
            $billDetails = $student->bills->map(function($b) {
                return "- " . ($b->financeType->name ?? $b->description) . " (" . $b->billing_date->translatedFormat('M Y') . ")";
            })->take(5)->implode("%0a");
            
            if ($student->bills->count() > 5) {
                $billDetails .= "%0a...dan " . ($student->bills->count() - 5) . " lainnya";
            }

            $unitName = $student->unit->name ?? 'Namira School';
            $message = "Assalamu'alaikum, Yth. Orang Tua/Wali Ananda *{$student->full_name}* ({$student->classroom->name}).%0a%0aBerikut informasi tunggakan administrasi per " . now()->translatedFormat('d M Y') . ":%0a{$billDetails}%0a%0a*Total Tunggakan: Rp " . number_format($totalArrears, 0, ',', '.') . "*%0a%0aMohon segera melakukan pembayaran melalui VA Bank Jatim: *{$student->va_number}*.%0a%0aTerima kasih.%0a%0a-- *{$unitName}*";
            
            $waLink = WhatsAppHelper::generateLink($student->parent_phone, urldecode($message));
            
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'classroom' => $student->classroom->name ?? '-',
                'nis' => $student->nis,
                'parent_phone' => $student->parent_phone,
                'total_arrears' => $totalArrears,
                'bill_count' => $student->bills->count(),
                'bills' => $student->bills->map(function($b) {
                     return [
                         'name' => $b->financeType->name ?? $b->description,
                         'month' => $b->billing_date->format('M Y'),
                         'amount' => $b->final_amount - $b->paid_amount
                     ];
                })->take(3), // Show top 3
                'wa_link' => $waLink
            ];
        });

        return Inertia::render('Finance/Reports/Arrears', [
            'students' => $students,
            'classrooms' => $classrooms,
            'filters' => $request->only(['classroom_id', 'search']),
            'total_arrears_sum' => $totalArrearsSum,
            'student_count' => $studentCount
        ]);
    }

    public function printArrearsLetter(Student $student)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($student->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        $student->load(['classroom', 'bills' => function ($q) {
            $q->whereIn('status', ['unpaid', 'partial'])->orderBy('due_date', 'asc');
        }]);

        $totalArrears = $student->bills->sum(function ($bill) {
            return $bill->final_amount - $bill->paid_amount;
        });

        $data = [
            'student' => $student,
            'bills' => $student->bills,
            'total_arrears' => $totalArrears,
            'date' => now()->translatedFormat('d F Y'),
        ];

        // Ensure we have a view for this
        $pdf = Pdf::loadView('pdf.finance.arrears_letter', $data);
        return $pdf->stream('Surat_Tagihan_' . $student->nis . '.pdf');
    }

    public function printRecap(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang.');
        }

        $unitId = session('active_unit_id');

        if ($request->classroom_id) {
            $classroom = Classroom::findOrFail($request->classroom_id);
            if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
                if ($classroom->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Unit tidak sesuai.');
                }
            }
        }

        $query = Student::where('unit_id', $unitId)
            ->whereHas('bills', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial']);
            })
            ->with(['classroom', 'bills' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial']);
            }]);

        if ($request->classroom_id) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->search) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        $students = $query->get()->map(function ($student) {
            $totalArrears = $student->bills->sum(function ($bill) {
                return $bill->final_amount - $bill->paid_amount;
            });
            return (object) [
                'name' => $student->full_name,
                'nis' => $student->nis,
                'classroom' => $student->classroom->name ?? '-',
                'total_arrears' => $totalArrears
            ];
        });

        $grandTotal = $students->sum('total_arrears');

        $data = [
            'students' => $students,
            'grand_total' => $grandTotal,
            'filter_class' => $request->classroom_id ? Classroom::find($request->classroom_id)->name : 'Semua Kelas',
            'date' => now()->translatedFormat('d F Y'),
        ];

        $pdf = Pdf::loadView('pdf.finance.arrears_recap', $data);
        return $pdf->stream('Rekap_Tunggakan_' . date('Ymd') . '.pdf');
    }
}
