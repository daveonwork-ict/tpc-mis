<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 20)->nullable();
            $table->string('sex', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('civil_status', 20)->nullable();
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('address_line')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('lrn', 20)->nullable()->comment('DepEd Learner Reference Number');
            $table->string('uli', 30)->nullable()->comment('TESDA Unique Learner Identifier');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->index(['last_name', 'first_name', 'date_of_birth']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('track', 10)->comment('ched or tesda');
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('authority_reference')->nullable()->comment('CHED program recognition or TESDA registration number');
            $table->text('description')->nullable();
            $table->boolean('accepting_applications')->default(true);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->string('semester', 10)->comment('first, second or midyear');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('admission_open')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['session_id', 'semester']);
        });

        Schema::create('requirement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('requirement_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_type_id')->constrained('requirement_types')->cascadeOnDelete();
            $table->string('track', 10);
            $table->string('applicant_type', 30)->nullable()->comment('null applies to every applicant type in the track');
            $table->foreignId('program_id')->nullable()->comment('null applies to every program in the track')->constrained('programs')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 30)->nullable()->unique();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('track', 10);
            $table->foreignId('program_id')->constrained('programs');
            $table->string('applicant_type', 30);
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('preferred_schedule')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->string('source', 20)->default('online');

            $table->string('last_school')->nullable();
            $table->string('last_school_address')->nullable();
            $table->string('last_school_year', 20)->nullable();
            $table->string('last_program_or_strand')->nullable();
            $table->string('general_average', 20)->nullable();
            $table->string('highest_attainment')->nullable();
            $table->string('employment_status')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_mobile', 30)->nullable();

            $table->timestamp('privacy_consent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('inquiry_id')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->index(['status', 'track']);
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('channel', 20);
            $table->string('track', 10)->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->date('follow_up_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('requirement_type_id')->constrained('requirement_types');
            $table->boolean('is_required')->default(true);
            $table->string('status', 20)->default('pending');
            $table->string('file_path')->nullable()->comment('private disk, never under public/');
            $table->string('original_name')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['application_id', 'requirement_type_id'], 'application_requirement_unique');
        });

        Schema::create('application_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('type', 20)->comment('eligibility, interview or exam');
            $table->string('result', 20)->comment('passed, failed or pending');
            $table->string('score', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->date('screened_on')->nullable();
            $table->foreignId('screened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('admission_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('decision', 20)->comment('accepted, conditional, pending or rejected');
            $table->text('conditions')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('action', 50);
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_logs');
        Schema::dropIfExists('admission_decisions');
        Schema::dropIfExists('application_screenings');
        Schema::dropIfExists('application_requirements');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('requirement_rules');
        Schema::dropIfExists('requirement_types');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('persons');
    }
};
