<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_service_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->date('student_signed_on')->nullable();
            $table->foreignId('student_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guardian_name')->nullable();
            $table->date('guardian_signed_on')->nullable();
            $table->foreignId('guardian_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('president_signed_on')->nullable();
            $table->foreignId('president_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('waived_on')->nullable();
            $table->string('waiver_authority', 500)->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_service_agreements');
    }
};
