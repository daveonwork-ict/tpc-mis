<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('preceding_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('program', 30);
            $table->string('sponsor', 190)->nullable();
            $table->decimal('preceding_units', 5, 1);
            $table->decimal('preceding_gwa', 4, 2);
            $table->boolean('discipline_clear')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'voided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_scholarships');
    }
};
