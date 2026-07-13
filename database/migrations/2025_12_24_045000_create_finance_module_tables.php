<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Data: Finance Types (Jenis Tagihan: SPP, Gedung, dll)
        Schema::create('finance_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // Per unit (SD/SMP/SMA)
            $table->string('name'); // SPP, Uang Gedung, Seragam
            $table->decimal('amount', 15, 2)->default(0); // Default amount
            $table->enum('billing_cycle', ['monthly', 'once', 'yearly'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Master Data: Finance Accounts (Rekening Bank Yayasan)
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name'); // Bank Jatim
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Add VA Number to Students table (Static VA)
        Schema::table('students', function (Blueprint $table) {
            $table->string('va_number')->nullable()->after('nisn')->index(); // Indexed for faster lookup during import
        });

        // 4. Student Discounts (Beasiswa/Potongan Khusus)
        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('finance_type_id')->constrained('finance_types')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable(); // Fixed discount amount
            $table->decimal('percentage', 5, 2)->nullable(); // Discount Percentage (0-100)
            $table->string('description')->nullable(); // Alasan beasiswa
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // 5. Student Bills (Tagihan Siswa per Item)
        Schema::create('student_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('finance_type_id')->nullable()->constrained('finance_types')->nullOnDelete();
            $table->string('bill_code')->unique(); // INV/2024/01/0001
            $table->string('description'); // SPP Januari 2024
            $table->date('billing_date'); // Tanggal tagihan
            $table->date('due_date')->nullable(); // Jatuh tempo
            
            $table->decimal('original_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2); // original - discount
            
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Transactions (Uang Masuk / Pembayaran)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete(); // Nullable if payment from unknown source (should check) but logical to associate to student
            $table->foreignId('user_id')->nullable()->constrained('users'); // Admin/Treasurer who input (Null if system/import)
            $table->foreignId('finance_account_id')->nullable()->constrained('finance_accounts'); // Ke rekening mana
            
            $table->string('transaction_code')->unique(); // TRX/2024/01/0001
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'manual_va', 'other'])->default('cash');
            $table->string('source')->default('manual'); // 'manual', 'import_bank_jatim', etc
            $table->string('reference_id')->nullable(); // Bank Transaction ID
            $table->text('notes')->nullable();
            $table->timestamp('transaction_date');
            
            $table->decimal('allocated_amount', 15, 2)->default(0); // How much has been used for bills
            $table->decimal('excess_amount', 15, 2)->default(0); // Sisa dana (Deposit)
            
            $table->timestamps();
        });

        // 7. Bill Payments (Pivot: Transaction pays Bill)
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('student_bill_id')->constrained('student_bills')->cascadeOnDelete();
            $table->decimal('amount', 15, 2); // Amount from this transaction allocated to this bill
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('student_bills');
        Schema::dropIfExists('student_discounts');
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('va_number');
        });
        
        Schema::dropIfExists('finance_accounts');
        Schema::dropIfExists('finance_types');
    }
};
