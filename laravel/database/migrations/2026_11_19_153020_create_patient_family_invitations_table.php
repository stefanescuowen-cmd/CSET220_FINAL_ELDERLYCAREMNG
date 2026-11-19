<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_family_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->string('code_hash')->unique();
            $table->string('relationship_to_patient', 60);

            $table->foreignId('created_by_user_id')
                ->constrained('users');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->foreignId('used_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_family_invitations');
    }
};