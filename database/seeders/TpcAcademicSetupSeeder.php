<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TpcAcademicSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->academicYears();
            $this->programs();
            $this->blocks();
            $this->shifts();
            $this->exams();
            $this->courses();
            $this->schools();
            $this->positions();
            $this->religions();
            $this->categories();
            $this->passMark();
        });
    }

    private function academicYears(): void
    {
        $years = [
            1 => ['AY 2026-2027', '2026-08-01', '2027-05-31'],
            2 => ['AY 2027-2028', '2027-08-01', '2028-05-31'],
            3 => ['AY 2028-2029', '2028-08-01', '2029-05-31'],
        ];

        foreach ($years as $id => [$name, $start, $end]) {
            DB::table('sessions')->where('id', $id)->update([
                'name' => $name,
                'start_date' => $start,
                'end_date' => $end,
                'updated_at' => now(),
            ]);
            $this->translate('session_translates', 'session_id', $id, $name);
        }
    }

    private function programs(): void
    {
        $names = [];
        foreach (['Health', 'Information Technology', 'Agriculture', 'Business and Management', 'Hospitality and Tourism'] as $program) {
            foreach (['First Year', 'Second Year', 'Third Year', 'Fourth Year'] as $year) {
                $names[] = $program . ', ' . $year;
            }
        }
        $names[] = 'TCLASS';

        $sessionIds = DB::table('sessions')->pluck('id');

        foreach ($names as $name) {
            $id = DB::table('classes')->where('name', $name)->value('id');
            if (!$id) {
                $id = DB::table('classes')->insertGetId([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
            $this->translate('class_translates', 'class_id', $id, $name);

            foreach ($sessionIds as $sessionId) {
                $setupId = DB::table('class_setups')
                    ->where('session_id', $sessionId)
                    ->where('classes_id', $id)
                    ->value('id');
                if (!$setupId) {
                    $setupId = DB::table('class_setups')->insertGetId([
                        'session_id' => $sessionId,
                        'classes_id' => $id,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'branch_id' => 1,
                    ]);
                }
            }
        }
    }

    private function blocks(): void
    {
        foreach (['Block A', 'Block B'] as $name) {
            $id = DB::table('sections')->where('name', $name)->value('id');
            if (!$id) {
                $id = DB::table('sections')->insertGetId([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
            $this->translate('section_translates', 'section_id', $id, $name);
        }

        $sectionIds = DB::table('sections')->whereIn('name', ['Block A', 'Block B'])->pluck('id');
        $setupIds = DB::table('class_setups')->pluck('id');
        foreach ($setupIds as $setupId) {
            foreach ($sectionIds as $sectionId) {
                $exists = DB::table('class_setup_childrens')
                    ->where('class_setup_id', $setupId)
                    ->where('section_id', $sectionId)
                    ->exists();
                if (!$exists) {
                    DB::table('class_setup_childrens')->insert([
                        'class_setup_id' => $setupId,
                        'section_id' => $sectionId,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'branch_id' => 1,
                    ]);
                }
            }
        }
    }

    private function shifts(): void
    {
        $id = DB::table('shifts')->where('name', 'Day')->value('id');
        if (!$id) {
            $id = DB::table('shifts')->insertGetId([
                'name' => 'Day',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'branch_id' => 1,
            ]);
        }
        $this->translate('shift_translates', 'shift_id', $id, 'Day');
    }

    private function exams(): void
    {
        foreach (['Prelim', 'Midterm', 'Finals'] as $name) {
            if (!DB::table('exam_types')->where('name', $name)->exists()) {
                DB::table('exam_types')->insert([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
        }
    }

    private function courses(): void
    {
        $courses = [
            ['GE-US', 'Understanding the Self'],
            ['GE-RPH', 'Readings in Philippine History'],
            ['GE-TCW', 'The Contemporary World'],
            ['GE-MMW', 'Mathematics in the Modern World'],
            ['GE-PC', 'Purposive Communication'],
            ['GE-AA', 'Art Appreciation'],
            ['GE-STS', 'Science, Technology and Society'],
            ['GE-ETH', 'Ethics'],
            ['RIZAL', 'Life and Works of Rizal'],
            ['NSTP-1', 'National Service Training Program 1'],
            ['NSTP-2', 'National Service Training Program 2'],
        ];

        foreach ($courses as [$code, $name]) {
            if (!DB::table('subjects')->where('code', $code)->exists()) {
                DB::table('subjects')->insert([
                    'name' => $name,
                    'code' => $code,
                    'type' => 1,
                    'status' => 1,
                    'is_optional' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
        }
    }

    private function schools(): void
    {
        $names = [
            1 => 'School of Health',
            2 => 'School of Information Technology',
            3 => 'School of Agriculture',
        ];
        foreach ($names as $id => $name) {
            DB::table('departments')->where('id', $id)->update(['name' => $name, 'updated_at' => now()]);
        }
        foreach (['School of Business and Management', 'School of Hospitality and Tourism', 'TCLASS'] as $name) {
            if (!DB::table('departments')->where('name', $name)->exists()) {
                DB::table('departments')->insert([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
        }
    }

    private function positions(): void
    {
        $names = [
            1 => 'Professor',
            2 => 'College President',
            3 => 'Vice President',
            4 => 'Faculty',
            5 => 'Dean',
            6 => 'Accountant',
            7 => 'Librarian',
            8 => 'Registrar',
            9 => 'Laboratory Technician',
            10 => 'Administrative Aide',
            11 => 'Driver',
            12 => 'Utility Worker',
            13 => 'Security',
            14 => 'Maintenance',
            15 => 'Guidance Counselor',
        ];
        foreach ($names as $id => $name) {
            DB::table('designations')->where('id', $id)->update(['name' => $name, 'updated_at' => now()]);
        }
        if (!DB::table('designations')->where('name', 'Program Chairperson')->exists()) {
            DB::table('designations')->insert([
                'name' => 'Program Chairperson',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'branch_id' => 1,
            ]);
        }
    }

    private function religions(): void
    {
        $rename = [1 => 'Roman Catholic', 2 => 'Islam', 3 => 'Protestant'];
        foreach ($rename as $id => $name) {
            DB::table('religions')->where('id', $id)->update(['name' => $name, 'updated_at' => now()]);
        }
        foreach (['Iglesia ni Cristo', 'Buddhism', 'Others'] as $name) {
            if (!DB::table('religions')->where('name', $name)->exists()) {
                DB::table('religions')->insert([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
        }
    }

    private function categories(): void
    {
        foreach (['Regular', 'Irregular', 'Transferee'] as $name) {
            if (!DB::table('student_categories')->where('name', $name)->exists()) {
                DB::table('student_categories')->insert([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => 1,
                ]);
            }
        }
    }

    private function passMark(): void
    {
        foreach (DB::table('sessions')->pluck('id') as $sessionId) {
            DB::table('examination_settings')->updateOrInsert(
                ['name' => 'average_pass_marks', 'session_id' => $sessionId],
                ['value' => '75', 'updated_at' => now(), 'created_at' => now(), 'branch_id' => 1]
            );
        }
    }

    private function translate(string $table, string $foreign, int $id, string $name): void
    {
        DB::table($table)->updateOrInsert(
            [$foreign => $id, 'locale' => 'en'],
            ['name' => $name, 'updated_at' => now(), 'created_at' => now(), 'branch_id' => 1]
        );
    }
}
