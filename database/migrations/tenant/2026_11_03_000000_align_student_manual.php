<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('tpccat_points', 4, 2)->nullable()->after('general_average');
            $table->decimal('financial_points', 4, 2)->nullable()->after('tpccat_points');
            $table->boolean('sector_verified')->default(false)->after('financial_points');
            $table->json('sector_categories')->nullable()->after('sector_verified');
            $table->boolean('tarlac_resident')->default(false)->after('sector_categories');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('minimum_gwa', 5, 2)->nullable()->after('max_units');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->date('midterm_on')->nullable()->after('end_date');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('academic_standing', 20)->default('regular')->after('status');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('is_graduating')->default(false)->after('student_type');
            $table->boolean('credit_waived')->default(false)->after('is_graduating');
        });

        Schema::table('enrollment_courses', function (Blueprint $table) {
            $table->date('removal_on')->nullable()->after('final_grade');
            $table->boolean('standing_passing')->nullable()->after('removal_on');
            $table->boolean('unofficial_drop')->default(false)->after('standing_passing');
        });

        Schema::table('offering_attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutes_late')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('offering_attendances', fn (Blueprint $table) => $table->dropColumn('minutes_late'));
        Schema::table('enrollment_courses', fn (Blueprint $table) => $table->dropColumn(['removal_on', 'standing_passing', 'unofficial_drop']));
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn(['is_graduating', 'credit_waived']));
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('academic_standing'));
        Schema::table('terms', fn (Blueprint $table) => $table->dropColumn('midterm_on'));
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('minimum_gwa'));
        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn([
            'tpccat_points', 'financial_points', 'sector_verified', 'sector_categories', 'tarlac_resident',
        ]));
    }
};
