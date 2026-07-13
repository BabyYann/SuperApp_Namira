<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\FinanceType;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Yayasan\Models\Unit; // Correct Namespace
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentBillController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke tagihan siswa.');
        }

        try {
            $query = StudentBill::query()
                ->with(['student', 'financeType']);

            $user = auth()->user();
            if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
                $unitId = session('active_unit_id');
                $query->whereHas('student', function ($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            }

            $bills = $query->when($request->search, function ($query, $search) {
                    $query->whereHas('student', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%") // changed name to full_name based on Student model
                          ->orWhere('nis', 'like', "%{$search}%");
                    })->orWhere('bill_code', 'like', "%{$search}%");
                })
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->latest()
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Finance/Bills/Index', [
                'bills' => $bills,
                'filters' => $request->only(['search', 'status']),
            ]);
        } catch (\Throwable $e) {
             Log::error('Bills Index Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
             return back()->with('error', 'Gagal memuat data tagihan: ' . $e->getMessage());
        }
    }

    public function create()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk membuat tagihan.');
        }

        try {
            // Data for dropdowns
            $types = FinanceType::where('is_active', true)->where('unit_id', session('active_unit_id'))->get();
            
            // Scope classrooms by unit (classrooms are now permanent)
            $classrooms = Classroom::where('unit_id', session('active_unit_id'))
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            return Inertia::render('Finance/Bills/Create', [
                'types' => $types,
                'classrooms' => $classrooms,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bills Create Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk membuat tagihan.');
        }

        Log::info('Entering StudentBillController::store', $request->all());

        $validated = $request->validate([
            'finance_type_id' => 'required|exists:finance_types,id',
            'billing_period' => 'required|date', // e.g., 2024-01-01
            'due_date' => 'required|date|after_or_equal:billing_period',
            'target_type' => 'required|in:all,class,student',
            'classroom_id' => 'required_if:target_type,class|nullable|exists:classrooms,id',
            'student_id' => 'required_if:target_type,student|nullable|exists:students,id',
            'description' => 'required|string',
        ]);

        $financeType = FinanceType::findOrFail($validated['finance_type_id']);
        $unitId = session('active_unit_id');
        
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($financeType->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Jenis biaya tidak sesuai dengan unit Anda.');
            }
            if ($validated['target_type'] === 'class' && $validated['classroom_id']) {
                $classroom = Classroom::findOrFail($validated['classroom_id']);
                if ($classroom->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Kelas tidak sesuai dengan unit Anda.');
                }
            }
            if ($validated['target_type'] === 'student' && $validated['student_id']) {
                $student = Student::findOrFail($validated['student_id']);
                if ($student->unit_id !== $unitId) {
                    abort(403, 'Akses Ditolak: Siswa tidak sesuai dengan unit Anda.');
                }
            }
        } else {
            $unitId = $financeType->unit_id;
        }

        DB::beginTransaction();
        try {
            // Fetch Students based on Target
            $students = collect();
            
            if ($validated['target_type'] === 'student') {
                $students = Student::where('id', $validated['student_id'])->get();
            } elseif ($validated['target_type'] === 'class') {
                $students = Student::where('classroom_id', $validated['classroom_id'])->get();
            } else {
                // ALL Students in Active Unit
                $students = Student::where('unit_id', $unitId)->get();
            }

            if ($students->isEmpty()) {
                DB::rollBack();
                return back()->with('error', 'Tidak ada siswa yang ditemukan untuk target yang dipilih.');
            }

            $count = 0;
            foreach ($students as $student) {
                // Duplicate Check: Same Student, Same Fee Type, Same Billing Period (Month)
                $exists = StudentBill::where('student_id', $student->id)
                    ->where('finance_type_id', $financeType->id)
                    ->whereYear('billing_date', date('Y', strtotime($validated['billing_period'])))
                    ->whereMonth('billing_date', date('m', strtotime($validated['billing_period'])))
                    ->exists();

                if ($exists && $financeType->billing_cycle === 'monthly') {
                    continue; // Skip if already billed for this month
                }
                
                // Get Discount
                $discount = StudentDiscount::where('student_id', $student->id)
                    ->where('finance_type_id', $financeType->id)
                    ->where('start_date', '<=', $validated['billing_period'])
                    ->where('end_date', '>=', $validated['billing_period'])
                    ->first();

                $discountAmount = 0;
                if ($discount) {
                    if ($discount->amount) {
                        $discountAmount = $discount->amount;
                    } elseif ($discount->percentage) {
                        $discountAmount = $financeType->amount * ($discount->percentage / 100);
                    }
                }

                $finalAmount = max(0, $financeType->amount - $discountAmount);

                StudentBill::create([
                    'student_id' => $student->id,
                    'finance_type_id' => $financeType->id,
                    'bill_code' => 'INV/' . date('Ym') . '/' . Str::random(5) . '/' . $student->id,
                    'description' => $validated['description'],
                    'billing_date' => $validated['billing_period'],
                    'due_date' => $validated['due_date'],
                    'original_amount' => $financeType->amount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);
                $count++;
            }
            DB::commit();
            return redirect()->route('yayasan.finance.bills.index')->with('success', "Berhasil membuat tagihan untuk $count siswa.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bill Generate Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Gagal membuat tagihan (Fatal): ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke tagihan siswa.');
        }

        // Fetch fresh data mimicking Index logic to ensure relationships are loaded correctly
        $bill = StudentBill::with(['student', 'financeType', 'transactions'])->findOrFail($id);
        
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
            if ($bill->student->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        return Inertia::render('Finance/Bills/Show', [
            'bill' => $bill,
            'transactions' => $bill->transactions
        ]);
    }

    public function update(Request $request, StudentBill $bill)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk mengubah tagihan.');
        }

        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
            if ($bill->student->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        // Only allow editing if status is unpaid or partial (with restrictions)
        if ($bill->status === 'paid') {
            return back()->with('error', 'Tagihan yang sudah lunas tidak dapat diedit.');
        }

        $validated = $request->validate([
            'final_amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);

        if ($bill->status === 'partial' && $validated['final_amount'] < $bill->paid_amount) {
             return back()->with('error', 'Nominal baru tidak boleh lebih kecil dari jumlah yang sudah dibayar.');
        }

        $bill->update([
            'final_amount' => $validated['final_amount'],
            'description' => $validated['description'],
        ]);

        if ($bill->paid_amount >= $bill->final_amount && $bill->final_amount > 0) {
            $bill->status = 'paid';
        } elseif ($bill->paid_amount > 0) {
            $bill->status = 'partial';
        } else {
             $bill->status = 'unpaid';
        }
        $bill->save();

        return back()->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(StudentBill $bill)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk menghapus tagihan.');
        }

        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
            if ($bill->student->unit_id !== $unitId) {
                abort(403, 'Akses Ditolak: Unit tidak sesuai.');
            }
        }

        if ($bill->paid_amount > 0) {
            return back()->with('error', 'Tagihan yang sudah ada pembayaran tidak dapat dihapus. Gunakan fitur edit untuk penyesuaian.');
        }

        $bill->delete();

        return back()->with('success', 'Tagihan berhasil dihapus.');
    }
}
