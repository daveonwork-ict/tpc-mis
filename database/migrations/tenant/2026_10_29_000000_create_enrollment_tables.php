<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->decimal('lecture_units', 4, 1)->default(0)->after('code');
            $table->decimal('lab_units', 4, 1)->default(0)->after('lecture_units');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('name')->comment('links the legacy program-year row to an admissions program')->constrained('programs')->nullOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable()->after('program_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->unique()->constrained('persons')->nullOnDelete();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('max_units', 4, 1)->nullable()->after('authority_reference')->comment('maximum regular load per term; null means no limit');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->boolean('enrollment_open')->default(false)->after('admission_open');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('inquiry_id')->constrained('students')->nullOnDelete();
        });

        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('effective_session_id')->nullable()->constrained('sessions')->nullOnDelete();
            $table->string('authority_reference')->nullable()->comment('CMO or board resolution adopting this curriculum');
            $table->string('status', 10)->default('draft')->comment('draft, active or retired');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->unsignedTinyInteger('year_level');
            $table->string('semester', 10);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['curriculum_id', 'subject_id']);
        });

        Schema::create('curriculum_requisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_course_id')->constrained('curriculum_courses')->cascadeOnDelete();
            $table->foreignId('subject_id')->comment('the course that must be passed or taken together')->constrained('subjects');
            $table->string('type', 15)->default('prerequisite')->comment('prerequisite or corequisite');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['curriculum_course_id', 'subject_id']);
        });

        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('program_id')->nullable()->comment('null means open to every program')->constrained('programs')->nullOnDelete();
            $table->string('section_code', 30);
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->foreignId('faculty_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 10)->default('open')->comment('open, closed or dissolved');
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['term_id', 'subject_id', 'section_code']);
            $table->index(['term_id', 'section_code']);
        });

        Schema::create('offering_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('offerings')->cascadeOnDelete();
            $table->unsignedTinyInteger('day')->comment('1 = Monday … 7 = Sunday');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->string('type', 10)->nullable()->comment('lecture or lab');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('enrollment_no', 30)->nullable()->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms');
            $table->foreignId('program_id')->constrained('programs');
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->nullOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('block', 30)->nullable();
            $table->string('student_type', 20);
            $table->string('status', 20)->default('advising');
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();

            $table->decimal('total_units', 5, 1)->default(0);
            $table->string('overload_reason')->nullable();
            $table->boolean('free_tuition')->default(false)->comment('RA 10931 coverage applied at assessment');
            $table->decimal('assessed_total', 12, 2)->default(0);
            $table->decimal('subsidy_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);

            $table->foreignId('advised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('advised_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['student_id', 'term_id']);
            $table->index(['term_id', 'status']);
        });

        Schema::create('enrollment_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained('offerings');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->decimal('lecture_units', 4, 1)->default(0);
            $table->decimal('lab_units', 4, 1)->default(0);
            $table->string('status', 10)->default('enlisted')->comment('enlisted or dropped');
            $table->text('override_reason')->nullable();
            $table->string('final_grade', 10)->nullable();
            $table->string('result', 15)->nullable()->comment('passed, failed, incomplete or dropped');
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id']);
        });

        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 20)->comment('tuition, laboratory, miscellaneous or other');
            $table->string('basis', 20)->comment('per_unit, per_lab_unit, per_lab_course or fixed');
            $table->decimal('amount', 12, 2);
            $table->foreignId('program_id')->nullable()->comment('null applies to every program')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->comment('null applies to every term')->constrained('terms')->cascadeOnDelete();
            $table->boolean('free_tuition_covered')->default(false)->comment('waived when RA 10931 coverage applies');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('enrollment_assessment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('fee_schedule_id')->nullable()->constrained('fee_schedules')->nullOnDelete();
            $table->string('name');
            $table->string('category', 20);
            $table->string('basis', 20);
            $table->decimal('quantity', 6, 1);
            $table->decimal('rate', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->decimal('subsidy', 12, 2)->default(0);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('course_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->string('source_school')->nullable();
            $table->string('source_course')->nullable();
            $table->string('grade', 10)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('credited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['student_id', 'subject_id']);
        });

        Schema::create('enrollment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('action', 50);
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_status_logs');
        Schema::dropIfExists('course_credits');
        Schema::dropIfExists('enrollment_assessment_lines');
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('enrollment_courses');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('offering_schedules');
        Schema::dropIfExists('offerings');
        Schema::dropIfExists('curriculum_requisites');
        Schema::dropIfExists('curriculum_courses');
        Schema::dropIfExists('curricula');

        Schema::table('applications', fn (Blueprint $table) => $table->dropConstrainedForeignId('student_id'));
        Schema::table('terms', fn (Blueprint $table) => $table->dropColumn('enrollment_open'));
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('max_units'));
        Schema::table('students', fn (Blueprint $table) => $table->dropConstrainedForeignId('person_id'));
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn('year_level');
        });
        Schema::table('subjects', fn (Blueprint $table) => $table->dropColumn(['lecture_units', 'lab_units']));
    }
};
