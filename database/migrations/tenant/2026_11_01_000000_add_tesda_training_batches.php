<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('training_hours', 7, 1)->nullable()->after('authority_reference');
            $table->text('entry_requirements')->nullable()->after('description');
        });

        Schema::create('training_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('schedule')->nullable();
            $table->string('venue')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->unsignedSmallInteger('slots');
            $table->string('status', 10)->default('open');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['program_id', 'code']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
        });

        DB::statement('ALTER TABLE enrollments MODIFY term_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE enrollments MODIFY year_level TINYINT UNSIGNED NULL');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('track', 10)->default('ched')->after('enrollment_no');
            $table->foreignId('batch_id')->nullable()->after('term_id')->constrained('training_batches')->nullOnDelete();
            $table->boolean('eligibility_met')->nullable()->after('remarks');
            $table->string('eligibility_remarks', 500)->nullable()->after('eligibility_met');
            $table->string('highest_attainment')->nullable()->after('eligibility_remarks');
            $table->string('employment_status')->nullable()->after('highest_attainment');
            $table->foreign('term_id')->references('id')->on('terms');
            $table->unique(['student_id', 'batch_id']);
            $table->index('track');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropUnique(['student_id', 'batch_id']);
            $table->dropIndex(['track']);
            $table->dropColumn(['track', 'batch_id', 'eligibility_met', 'eligibility_remarks', 'highest_attainment', 'employment_status']);
        });

        Schema::dropIfExists('training_batches');

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['training_hours', 'entry_requirements']);
        });
    }
};
