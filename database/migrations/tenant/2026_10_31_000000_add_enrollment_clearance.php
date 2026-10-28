<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('scholarship_total', 12, 2)->default(0)->after('net_total');
            $table->decimal('paid_total', 12, 2)->default(0)->after('scholarship_total');
            $table->decimal('balance', 12, 2)->default(0)->after('paid_total');
            $table->string('funding_outcome', 30)->nullable()->after('balance');
            $table->foreignId('cleared_by')->nullable()->after('assessed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable()->after('cleared_by');
        });

        DB::table('enrollments')->update(['balance' => DB::raw('net_total')]);

        Schema::create('enrollment_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('program', 20)->comment('tes, twsp, step, uaqtea or other');
            $table->string('name')->nullable();
            $table->string('reference', 60)->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        Schema::create('enrollment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('or_number', 40)->unique();
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->timestamps();
        });

        DB::table('permissions')->updateOrInsert(
            ['attribute' => 'enrollment_clearance'],
            ['keywords' => json_encode(['clear' => 'enrollment_clear']), 'updated_at' => now(), 'created_at' => now()]
        );

        $grant = ['enrollment_read', 'enrollment_assess', 'enrollment_clear'];
        $role = DB::table('roles')->where('id', 4)->first();
        if ($role) {
            $this->grant('roles', 4, $grant);
        }
        foreach (DB::table('users')->where('role_id', 4)->pluck('id') as $userId) {
            $this->grant('users', $userId, $grant);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_payments');
        Schema::dropIfExists('enrollment_scholarships');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cleared_by');
            $table->dropColumn(['cleared_at', 'scholarship_total', 'paid_total', 'balance', 'funding_outcome']);
        });

        DB::table('permissions')->where('attribute', 'enrollment_clearance')->delete();
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
