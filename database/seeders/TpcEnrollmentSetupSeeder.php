<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Starting data for CHED enrollment. Safe to re-run: existing links, units and term flags are never overwritten.
 */
class TpcEnrollmentSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->permissions();
            $this->linkLegacyClasses();
            $this->generalEducationUnits();
            $this->openCurrentTerm();
        });
    }

    private function permissions(): void
    {
        $permissions = [
            'enrollment_records'    => ['read' => 'enrollment_read', 'create' => 'enrollment_create'],
            'enrollment_advising'   => ['advise' => 'enrollment_advise', 'override' => 'enrollment_override'],
            'enrollment_assessment' => ['assess' => 'enrollment_assess'],
            'enrollment_clearance'  => ['clear' => 'enrollment_clear'],
            'enrollment_registrar'  => ['validate' => 'enrollment_validate'],
            'course_offerings'      => ['read' => 'offering_read', 'update' => 'offering_update'],
            'enrollment_setup'      => ['read' => 'enrollment_setup_read', 'update' => 'enrollment_setup_update'],
        ];

        foreach ($permissions as $attribute => $keywords) {
            DB::table('permissions')->updateOrInsert(
                ['attribute' => $attribute],
                ['keywords' => json_encode($keywords), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $role = DB::table('roles')->where('id', 1)->first();
        if ($role) {
            $current = json_decode($role->permissions ?? '[]', true) ?: [];
            $merged = array_values(array_unique(array_merge($current, ...array_map('array_values', array_values($permissions)))));
            DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($merged)]);
        }
    }

    /**
     * The academic setup created one legacy "program, year" row per program; link them so validated students appear in the Students list.
     */
    private function linkLegacyClasses(): void
    {
        $years = ['First' => 1, 'Second' => 2, 'Third' => 3, 'Fourth' => 4, 'Fifth' => 5];

        foreach (DB::table('programs')->get() as $program) {
            $department = DB::table('departments')->where('id', $program->department_id)->value('name');
            $base = $program->track === 'tesda' ? $department : $program->name;

            foreach (DB::table('classes')->whereNull('program_id')->get() as $class) {
                if ($class->name === $base) {
                    DB::table('classes')->where('id', $class->id)->update(['program_id' => $program->id]);
                    continue;
                }

                foreach ($years as $word => $level) {
                    if ($class->name === "{$base}, {$word} Year") {
                        DB::table('classes')->where('id', $class->id)->update(['program_id' => $program->id, 'year_level' => $level]);
                    }
                }
            }
        }
    }

    /**
     * CMO 20 s. 2013 sets every GE core course at 3 units; RA 9163 sets each NSTP semester at 3 units.
     */
    private function generalEducationUnits(): void
    {
        DB::table('subjects')
            ->where(fn ($q) => $q->where('code', 'like', 'GE-%')->orWhereIn('code', ['RIZAL', 'NSTP-1', 'NSTP-2']))
            ->where('lecture_units', 0)
            ->where('lab_units', 0)
            ->update(['lecture_units' => 3]);
    }

    private function openCurrentTerm(): void
    {
        if (DB::table('terms')->where('enrollment_open', true)->exists()) {
            return;
        }

        DB::table('terms')->where('admission_open', true)->orderBy('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")
            ->limit(1)->update(['enrollment_open' => true]);
    }
}
