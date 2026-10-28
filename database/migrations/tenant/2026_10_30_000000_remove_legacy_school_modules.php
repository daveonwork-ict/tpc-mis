<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'fees_collects', 'fees_assign_childrens', 'fees_assigns', 'fees_master_childrens', 'fees_masters', 'fees_types', 'fees_groups',
        'assign_fees_discounts', 'sibling_fees_discounts', 'early_payment_discounts', 'student_special_discounts', 'special_discounts',
        'online_admission_payments', 'online_admission_fees_assigns', 'online_admissions',
        'mark_sheet_approvals', 'examination_results', 'marks_register_childrens', 'marks_registers', 'exam_routine_childrens', 'exam_routines',
        'exam_assign_childrens', 'exam_assigns', 'marks_grades',
        'promote_students', 'optional_subject_assigns', 'class_routine_childrens', 'class_routines', 'leave_requests', 'leave_types',
        'homework_questions', 'homework_students', 'homework',
        'lesson_plan_topics', 'lesson_plans', 'lesson_topic_details', 'lesson_topics', 'lesson_details', 'lessons',
        'forum_post_comments', 'forum_posts', 'memory_galleries', 'memories', 'mobile_app_settings', 'system_logs',
    ];

    private array $permissionAttributes = [
        'lesson', 'topic', 'lesson_plan', 'teachers_lesson_plan', 'lesson_plan_overview', 'lesson_plan_report', 'dashboard',
        'promote_students', 'class_routine', 'fees_group', 'fees_type', 'fees_master', 'fees_assign', 'fees_collect', 'discount_setup',
        'marks_grade', 'exam_assign', 'exam_routine', 'marks_register', 'homework', 'exam_setting',
        'marksheet', 'merit_list', 'progress_card', 'due_fees', 'fees_collection',
        'mobile_app_settings', 'software_update', 'payment_gateway_settings', 'tax_setup', 'forums', 'forum_comment', 'memories',
    ];

    // Granted on roles/users by older seeders without a matching permissions row.
    private array $extraKeywords = [
        'class_routine_read', 'class_routine_create', 'class_routine_update', 'class_routine_delete',
        'exam_routine_read', 'exam_routine_create', 'exam_routine_update', 'exam_routine_delete',
        'fees_collesction_read', 'fees_collection_this_month_read', 'revenue_read', 'income_expense_read',
        'attendance_chart_read', 'upcoming_events_read',
    ];

    private array $searchRoutes = [
        'promote_students.index', 'online-admissions.index',
        'fees-group.index', 'fees-type.index', 'fees-master.index', 'fees-assign.index', 'fees-collect.index',
        'exam-type.index', 'marks-grade.index', 'marks-register.index', 'exam-routine.index', 'exam-assign.index', 'examination-settings.index',
        'class-routine.index', 'report-marksheet.index', 'report-merit-list.index', 'report-progress-card.index',
        'report-due-fees.index', 'report-fees-collection.index', 'report-class-routine.index', 'report-exam-routine.index',
        'student-panel-class-routine.index', 'student-panel-exam-routine.index', 'student-panel-marksheet.index', 'student-panel-fees.index',
        'parent-panel-dashboard.index.index', 'parent-panel-class-routine.index', 'parent-panel-exam-routine.index',
        'parent-panel-marksheet.index', 'parent-panel-fees.index',
    ];

    private array $settings = [
        'tax_percentage', 'tax_income_head', 'tax_min_amount', 'tax_max_amount',
        'early_payment_discount_applicable', 'siblings_discount_applicable',
    ];

    public function up(): void
    {
        $this->dropColumn('incomes', 'fees_collect_id', true);
        $this->dropColumn('answers', 'homework_id', true);
        $this->dropColumn('students', 'siblings_discount');

        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        $obsolete = $this->extraKeywords;
        foreach (DB::table('permissions')->whereIn('attribute', $this->permissionAttributes)->pluck('keywords') as $keywords) {
            $obsolete = array_merge($obsolete, array_values((array) json_decode($keywords, true)));
        }
        DB::table('permissions')->whereIn('attribute', $this->permissionAttributes)->delete();

        foreach (['roles', 'users'] as $table) {
            DB::table($table)->whereNotNull('permissions')->orderBy('id')->each(function ($row) use ($table, $obsolete) {
                $current = (array) json_decode($row->permissions, true);
                $kept = array_values(array_diff($current, $obsolete));
                if (count($kept) !== count($current)) {
                    DB::table($table)->where('id', $row->id)->update(['permissions' => json_encode($kept)]);
                }
            });
        }

        DB::table('searches')->whereIn('route_name', $this->searchRoutes)->delete();
        DB::table('settings')->whereIn('name', $this->settings)->delete();
    }

    public function down(): void
    {
        // Irreversible: the legacy modules' code is gone. Restore from the pre-cleanup SQL backup if needed.
    }

    private function dropColumn(string $table, string $column, bool $foreign = false): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $foreign) {
            if ($foreign) {
                $blueprint->dropForeign([$column]);
            }
            $blueprint->dropColumn($column);
        });
    }
};
