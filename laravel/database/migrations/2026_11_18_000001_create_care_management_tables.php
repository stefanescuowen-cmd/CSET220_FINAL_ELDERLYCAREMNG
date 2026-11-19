<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number', 30)->nullable()->unique(); $table->decimal('salary', 10, 2)->nullable(); $table->date('hire_date')->nullable(); $table->timestamps();
        });
        Schema::create('patient_groups', function (Blueprint $table) {
            $table->id(); $table->string('name', 60)->unique(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('patient_groups')->nullOnDelete(); $table->date('admission_date');
            $table->string('emergency_contact_name', 120); $table->string('emergency_contact_relationship', 60)->nullable(); $table->string('emergency_contact_phone', 25); $table->timestamps();
        });
        Schema::create('patient_family_members', function (Blueprint $table) {
            $table->id(); $table->foreignId('patient_id')->constrained()->cascadeOnDelete(); $table->foreignId('family_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('family_code_hash'); $table->string('relationship_to_patient', 60)->nullable(); $table->timestamps(); $table->unique(['patient_id', 'family_user_id']);
        });
        Schema::create('rosters', function (Blueprint $table) {
            $table->id(); $table->foreignId('group_id')->constrained('patient_groups')->cascadeOnDelete(); $table->foreignId('supervisor_user_id')->constrained('users')->restrictOnDelete();
            $table->date('roster_date'); $table->timestamps(); $table->unique(['group_id', 'roster_date']);
        });
        Schema::create('roster_doctors', function (Blueprint $table) {
            $table->id(); $table->foreignId('roster_id')->constrained()->cascadeOnDelete(); $table->foreignId('doctor_user_id')->constrained('users')->restrictOnDelete();
            $table->time('available_from')->default('00:00'); $table->time('available_until')->default('23:59'); $table->unique(['roster_id', 'doctor_user_id']);
        });
        Schema::create('roster_caregivers', function (Blueprint $table) {
            $table->id(); $table->foreignId('roster_id')->constrained()->cascadeOnDelete(); $table->foreignId('caregiver_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('caregiver_slot'); $table->unique(['roster_id', 'caregiver_slot']); $table->unique(['roster_id', 'caregiver_user_id']);
        });
        Schema::create('roster_patient_assignments', function (Blueprint $table) {
            $table->id(); $table->foreignId('roster_id')->constrained()->cascadeOnDelete(); $table->foreignId('patient_id')->constrained()->cascadeOnDelete(); $table->foreignId('caregiver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['roster_id', 'patient_id']);
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); $table->foreignId('patient_id')->constrained()->restrictOnDelete(); $table->foreignId('doctor_user_id')->constrained('users')->restrictOnDelete();
            $table->date('appointment_date'); $table->time('appointment_time'); $table->string('status', 20)->default('Scheduled'); $table->boolean('meeting_confirmed')->default(false); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['patient_id', 'appointment_date']); $table->index(['doctor_user_id', 'appointment_date', 'appointment_time'],'appointments_doctor_date_time_idx');
        });
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id(); $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete(); $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescribed_by_user_id')->constrained('users')->restrictOnDelete(); $table->string('status', 20)->default('Draft'); $table->text('doctor_comments')->nullable(); $table->timestamp('approved_at')->nullable(); $table->timestamps();
        });
        Schema::create('prescription_medications', function (Blueprint $table) {
            $table->id(); $table->foreignId('prescription_id')->constrained()->cascadeOnDelete(); $table->string('medication_name', 120); $table->string('dosage', 120); $table->text('instructions')->nullable();
            $table->string('time_slot', 20); $table->date('active_from'); $table->date('active_until')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['prescription_id', 'time_slot']);
        });
        Schema::create('daily_care_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('patient_id')->constrained()->restrictOnDelete(); $table->date('care_date'); $table->string('status', 20)->default('Scheduled');
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('completed_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['patient_id', 'care_date']);
        });
        Schema::create('activity_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('care_record_id')->constrained('daily_care_records')->cascadeOnDelete(); $table->string('activity_type', 40); $table->string('status', 20)->default('Pending');
            $table->dateTime('scheduled_at'); $table->dateTime('completed_at')->nullable(); $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['care_record_id', 'activity_type']); $table->index(['status', 'scheduled_at']);
        });
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('patient_id')->constrained()->restrictOnDelete(); $table->date('period_start'); $table->date('period_end');
            $table->decimal('total_due', 10, 2)->default(0); $table->decimal('amount_paid', 10, 2)->default(0); $table->string('status', 20)->default('Unpaid'); $table->dateTime('generated_at'); $table->timestamps(); $table->unique(['patient_id', 'period_start', 'period_end']);
        });
        Schema::create('billing_line_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('billing_id')->constrained('billing_records')->cascadeOnDelete(); $table->string('charge_type', 30); $table->string('description'); $table->date('service_date')->nullable();
            $table->decimal('quantity', 10, 2)->default(1); $table->decimal('unit_price', 10, 2); $table->decimal('line_total', 10, 2); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('billing_id')->constrained('billing_records')->restrictOnDelete(); $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2); $table->dateTime('payment_date'); $table->string('method', 10)->default('Cash'); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->id(); $table->string('charge_type', 30); $table->decimal('rate_amount', 10, 2); $table->date('effective_from'); $table->date('effective_until')->nullable(); $table->timestamps();
            $table->unique(['charge_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        foreach (['billing_rates', 'payments', 'billing_line_items', 'billing_records', 'activity_records', 'daily_care_records', 'prescription_medications', 'prescriptions', 'appointments', 'roster_patient_assignments', 'roster_caregivers', 'roster_doctors', 'rosters', 'patient_family_members', 'patients', 'patient_groups', 'employees'] as $table) Schema::dropIfExists($table);
    }
};
