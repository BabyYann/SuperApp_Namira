<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\BillPayment;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk melihat data transaksi.');
        }

        $query = Transaction::with(['student', 'financeAccount']);
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
            $query->whereHas('student', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            });
        }

        $transactions = $query->latest()->paginate(20);

        return Inertia::render('Finance/Transactions/Index', [
            'transactions' => $transactions
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Access Denied');
        }

        // For Manual Input (Cash/Transfer Manual)
        // ... (To be implemented later if needed)
    }

    public function import()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak');
        }

        $accounts = FinanceAccount::where('is_active', true)->get();
        return Inertia::render('Finance/Transactions/Import', [
            'accounts' => $accounts
        ]);
    }

    public function processImport(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Akses Ditolak');
        }

        Log::info('Starting Import Process', $request->all());
        
        $request->validate([
            'file' => 'required|file|max:2048', // Removed mimes strict check for debugging
            'finance_account_id' => 'required|exists:finance_accounts,id',
        ]);

        $file = $request->file('file');
        $financeAccountId = $request->finance_account_id;
        
        Log::info('File received', [
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'path' => $file->getPathname()
        ]);

        // Open file and strip BOM if present
        $content = file_get_contents($file->getPathname());
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
            file_put_contents($file->getPathname(), $content); // Save stripped content
        }

        // Detect Delimiter (Probe)
        $candidates = [',', ';', "\t", '|'];
        $delimiter = ',';
        $maxCols = 0;

        foreach ($candidates as $cand) {
            $handle = fopen($file->getPathname(), 'r');
            $testRow = fgetcsv($handle, 0, $cand);
            fclose($handle);
            if ($testRow && count($testRow) > $maxCols) {
                $maxCols = count($testRow);
                $delimiter = $cand;
            }
        }
        Log::info("Import Detected Delimiter: '$delimiter'");

        $handle = fopen($file->getPathname(), 'r');
        set_time_limit(300);
        
        $processed = 0;
        $skippedDuplicate = 0;
        $skippedNoVA = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            $rowNum = 0;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
                // Skip empty rows
                if (!$row || (count($row) === 1 && is_null($row[0]))) continue;

                $description = implode(' ', $row);
                $vaNumber = null;
                $amount = 0;
                $student = null;

                // 1. Find VA
                if (preg_match('/(\d{8,20})/', $description, $matches)) {
                    $potentialVa = $matches[1];
                    
                    $studentQuery = Student::where('va_number', $potentialVa)->with('unit');
                    if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
                        $studentQuery->where('unit_id', session('active_unit_id'));
                    }
                    
                    $student = $studentQuery->first();
                    if ($student) {
                        $vaNumber = $potentialVa;
                    }
                }

                if (!$student) {
                    $skippedNoVA++;
                    continue; 
                }

                // 2. Find Amount (Flexible)
                foreach ($row as $col) {
                     // Cleanup formatting: 150.000,00 -> 150000.00
                     // Remove 'Rp', 'IDR', thousands dot
                     $clean = str_replace(['Rp', 'IDR', ' ', '.'], '', $col); 
                     $clean = str_replace(',', '.', $clean); // commas becomes decimal
                     
                     if (is_numeric($clean) && (float)$clean > 1000) {
                         $amount = (float)$clean;
                         break;
                     }
                }

                if ($amount <= 0) {
                    $skippedNoVA++; // Count as invalid format
                    continue;
                }

                // 3. Duplicate Check
                $isDuplicate = Transaction::where('student_id', $student->id)
                    ->where('amount', $amount)
                    ->where('transaction_date', '>=', now()->subDays(1)) // Check recent only? Or exact match?
                    ->where('source', 'import_batch')
                    ->exists();

                // Relaxed duplicate check: If same amount & student today, warn but maybe allow?
                // Strict check: if (exists) skip.
                // Re-using strictly strict check for now but with explicit counter
                 $isDuplicate = Transaction::where('student_id', $student->id)
                    ->where('amount', $amount)
                    ->where('notes', 'Import Logic: ' . substr($description, 0, 50))
                    ->exists();

                if ($isDuplicate) {
                    $skippedDuplicate++;
                    continue; 
                }

                $this->applyWaterfallPayment($student, $amount, $financeAccountId, $description);
                $processed++;
            }
            DB::commit();
            fclose($handle);
            
            $msg = "Import Selesai. Berhasil: $processed.";
            if ($skippedDuplicate > 0) $msg .= " (Duplikat: $skippedDuplicate).";
            if ($skippedNoVA > 0) $msg .= " (Format Salah/Tanpa VA: $skippedNoVA).";
            
            if ($processed == 0 && ($skippedDuplicate + $skippedNoVA) == 0) {
                 return back()->with('error', "Gagal membaca file. Pastikan format CSV benar.");
            }
            
            return redirect()->route('yayasan.finance.transactions.index')
                ->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Import Fail: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    private function applyWaterfallPayment($student, $totalAmount, $financeAccountId, $originalDesc)
    {
        // 1. Get Unpaid/Partial Bills ordered by Due Date (Oldest first)
        $bills = StudentBill::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc')
            ->get();

        $remainingAmount = $totalAmount;
        $allocatedamount = 0;

        // Create Transaction Header (initially with generic info)
        $transaction = Transaction::create([
            'student_id' => $student->id,
            'user_id' => Auth::id(), // Added user_id (Admin who imported)
            'finance_account_id' => $financeAccountId,
            'transaction_code' => 'TRX-' . time() . '-' . Str::random(4),
            'amount' => $totalAmount,
            'payment_method' => 'transfer', // Correct ENUM value (was 'bank_transfer')
            'source' => 'import_batch',
            'notes' => 'Import Logic: ' . substr($originalDesc, 0, 50),
            'transaction_date' => now(),
            'allocated_amount' => 0, // Update later
            'excess_amount' => 0, // Update later
        ]);

        foreach ($bills as $bill) {
            if ($remainingAmount <= 0) break;

            $amountOwed = $bill->final_amount - $bill->paid_amount;
            
            // Determine how much to pay for this specific bill
            $paymentForBill = min($remainingAmount, $amountOwed);
            
            if ($paymentForBill > 0) {
                // Create pivot record
                BillPayment::create([
                    'transaction_id' => $transaction->id,
                    'student_bill_id' => $bill->id,
                    'amount' => $paymentForBill,
                ]);

                // Update Bill
                $bill->paid_amount += $paymentForBill;
                if ($bill->paid_amount >= $bill->final_amount) {
                    $bill->status = 'paid';
                } else {
                    $bill->status = 'partial';
                }
                $bill->save();

                // Update counters
                $remainingAmount -= $paymentForBill;
                $allocatedamount += $paymentForBill;
            }
        }

        // Handle Excess (Deposit)
        $excessAmount = max(0, $remainingAmount);
        
        // Update Transaction with final distribution
        $transaction->update([
            'allocated_amount' => $allocatedamount,
            'excess_amount' => $excessAmount,
        ]);

        // Automated WhatsApp Notification via WAHA to parents for payment confirmation
        try {
            if ($student && !empty($student->parent_phone)) {
                $amountFormatted = number_format($totalAmount, 0, ',', '.');
                $dateFormatted = now()->translatedFormat('d F Y');
                $unitName = $student->unit->name ?? 'Namira School Foundation';
                $message = "Yth. Orang Tua/Wali dari *{$student->full_name}*.\n\nKami menginformasikan bahwa pembayaran sekolah sebesar *Rp {$amountFormatted}* telah kami terima dengan sukses pada tanggal *{$dateFormatted}*.\n\nTerima kasih.\n-- *{$unitName}*";

                \App\Helpers\WhatsAppHelper::send($student->parent_phone, $message);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send automated WA payment notification: " . $e->getMessage());
        }

        // If there is excess, logically we should store it in student 'balance' or create a special 'Deposit' transaction
        // For now, it stays in 'excess_amount' of this transaction for record keeping.
        // TODO: Add 'balance' column to students table or 'wallets' table for using this excess later.
    }
}
