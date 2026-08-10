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
        // 1. Extension Profile for Daycare Children (linked 1-to-1 with students)
        Schema::create('daycare_child_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('nickname')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->text('special_conditions')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('routine_notes')->nullable();
            $table->timestamps();
        });

        // 2. Authorized Pickups for Daycare Children
        Schema::create('daycare_authorized_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('name');
            $table->string('relationship'); // Ayah, Ibu, Pengasuh, Supir, Paman, Kakek/Nenek
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Child Attendances & Handover
        Schema::create('daycare_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->string('dropped_off_by')->nullable();
            $table->decimal('check_in_temp', 4, 1)->nullable();
            $table->string('check_in_condition')->default('Sehat'); // Sehat, Batuk, Pilek, Demam, Lebam/Luka
            $table->text('check_in_notes')->nullable();
            $table->string('check_in_photo')->nullable();
            
            $table->time('check_out_time')->nullable();
            $table->string('picked_up_by')->nullable();
            $table->foreignId('authorized_pickup_id')->nullable()->constrained('daycare_authorized_pickups')->onDelete('set null');
            $table->text('check_out_notes')->nullable();
            $table->string('check_out_photo')->nullable();
            
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['student_id', 'date']);
        });

        // 4. Daily Care Log Timeline
        Schema::create('daycare_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('date');
            $table->time('log_time');
            $table->enum('category', [
                'meal', 
                'snack', 
                'milk', 
                'nap_start', 
                'nap_end', 
                'diaper', 
                'activity', 
                'mood', 
                'medication', 
                'incident'
            ]);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('amount_ml')->nullable(); // Milk or Water volume
            $table->string('portion_eaten')->nullable(); // 25%, 50%, 75%, 100%
            $table->string('photo')->nullable();
            $table->foreignId('caregiver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 5. Growth Records (BB, TB, LK & Charting)
        Schema::create('daycare_growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('measurement_date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('head_circumference_cm', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('measured_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 6. Developmental Journal (Monthly/Periodic Growth Assessment)
        Schema::create('daycare_developmental_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('period_month', 7); // e.g. "2026-08"
            $table->text('gross_motor')->nullable();
            $table->text('fine_motor')->nullable();
            $table->text('language_communication')->nullable();
            $table->text('cognitive')->nullable();
            $table->text('socio_emotional')->nullable();
            $table->text('independence')->nullable();
            $table->text('caregiver_summary')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['student_id', 'period_month']);
        });

        // 7. Caregiver Schedules & Room Assignment
        Schema::create('daycare_caregiver_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Caregiver user
            $table->date('date');
            $table->string('shift_name')->default('Full Day'); // Pagi, Siang, Full Day
            $table->string('room_name')->nullable(); // Ruang Baby, Ruang Toddler
            $table->json('assigned_student_ids')->nullable(); // List of assigned children IDs
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daycare_caregiver_schedules');
        Schema::dropIfExists('daycare_developmental_journals');
        Schema::dropIfExists('daycare_growth_records');
        Schema::dropIfExists('daycare_daily_logs');
        Schema::dropIfExists('daycare_attendances');
        Schema::dropIfExists('daycare_authorized_pickups');
        Schema::dropIfExists('daycare_child_profiles');
    }
};
