<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offering_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->nullable()->constrained('offerings')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('training_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 10);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();

            $table->unique(['offering_id', 'student_id', 'attendance_date'], 'offering_attendance_day');
            $table->unique(['batch_id', 'student_id', 'attendance_date'], 'batch_attendance_day');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('competency_result', 20)->nullable();
            $table->foreignId('competency_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('competency_at')->nullable();
        });

        DB::table('permissions')->updateOrInsert(
            ['attribute' => 'class_delivery'],
            ['keywords' => json_encode(['read' => 'class_read', 'update' => 'class_update']), 'updated_at' => now(), 'created_at' => now()]
        );

        $grant = ['class_read', 'class_update'];
        if (DB::table('roles')->where('id', 5)->exists()) {
            $this->grant('roles', 5, $grant);
        }
        foreach (DB::table('users')->where('role_id', 5)->pluck('id') as $userId) {
            $this->grant('users', $userId, $grant);
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competency_by');
            $table->dropColumn(['competency_result', 'competency_at']);
        });
        Schema::dropIfExists('offering_attendances');
        DB::table('permissions')->where('attribute', 'class_delivery')->delete();
    }

    private function grant(string $table, int $id, array $keywords): void
    {
        $current = json_decode(DB::table($table)->where('id', $id)->value('permissions') ?? '[]', true) ?: [];
        $merged = array_values(array_unique(array_merge($current, $keywords)));
        if ($merged !== array_values($current)) {
            DB::table($table)->where('id', $id)->update(['permissions' => json_encode($merged)]);
        }
    }
};
