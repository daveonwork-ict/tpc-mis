<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Starting data for the admissions module. Safe to re-run: rows are matched by name and never overwritten.
 */
class TpcAdmissionsSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->programs();
            $this->terms();
            $this->requirements();
            $this->permissions();
        });
    }

    private function programs(): void
    {
        $programs = [
            ['ched', 'Health', 'School of Health'],
            ['ched', 'Information Technology', 'School of Information Technology'],
            ['ched', 'Agriculture', 'School of Agriculture'],
            ['ched', 'Business and Management', 'School of Business and Management'],
            ['ched', 'Hospitality and Tourism', 'School of Hospitality and Tourism'],
            ['tesda', 'TCLASS Training Program', 'TCLASS'],
        ];

        foreach ($programs as [$track, $name, $department]) {
            if (DB::table('programs')->where('name', $name)->exists()) {
                continue;
            }

            DB::table('programs')->insert([
                'track'                  => $track,
                'name'                   => $name,
                'department_id'          => DB::table('departments')->where('name', $department)->value('id'),
                'description'            => $track === 'ched'
                    ? 'Replace with the exact degree title and CHED authority reference.'
                    : 'Replace with the TESDA-registered qualification title and registration number.',
                'accepting_applications' => true,
                'status'                 => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    private function terms(): void
    {
        $labels = ['first' => '1st Semester', 'second' => '2nd Semester', 'midyear' => 'Midyear Term'];
        $open = [[1, 'second'], [2, 'first']];

        foreach (DB::table('sessions')->whereIn('id', [1, 2])->get() as $session) {
            foreach ($labels as $semester => $label) {
                if (DB::table('terms')->where('session_id', $session->id)->where('semester', $semester)->exists()) {
                    continue;
                }

                DB::table('terms')->insert([
                    'session_id'     => $session->id,
                    'semester'       => $semester,
                    'name'           => $label . ', ' . $session->name,
                    'admission_open' => in_array([$session->id, $semester], $open, true),
                    'status'         => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    private function requirements(): void
    {
        $types = [
            'PSA Birth Certificate'                                => 'Photocopy of the PSA-issued birth certificate. Present the original for verification.',
            '2x2 ID photo'                                         => 'Recent photo with white background.',
            'Grade 12 report card (SF9 / Form 138)'                => 'Senior high school report card showing the final grades.',
            'Certificate of Good Moral Character'                  => 'Issued by the last school attended.',
            'Transcript of Records or Certificate of Grades'       => 'Copy for curriculum evaluation. The official transcript is requested by the college.',
            'Honorable Dismissal / Transfer Credentials'           => 'Issued by the previous college.',
            'Permit to Cross-Enroll'                               => 'Approved by the home school registrar, listing the courses to be taken.',
            'Shifting approval'                                    => 'Approved request to shift to another program.',
            'Diploma or certificate of graduation'                 => 'For applicants who already hold a bachelor\'s degree.',
            '1x1 ID photo'                                         => 'Recent photo for the TESDA learner profile.',
            'Proof of educational attainment'                      => 'Diploma, report card, or transcript showing the highest level completed.',
            'Valid government-issued ID'                           => 'Any valid ID with photo.',
            'TPC Admission Form for First Year Students'          => 'Application form for incoming first-year students.',
            'TPC Admission Test (TPCAT) result'                   => 'Result of the TPC admission test.',
            'PSA Marriage Certificate'                            => 'Required only when the applicant is a married woman.',
            'Health Examination / Medical Certificate'            => 'Medical examination result.',
            'Result of Psychological Exam'                        => 'Psychological examination result.',
            'Written pledge to abide by College rules'            => 'Signed pledge to follow the rules of the College.',
            'TPC Admission Form for Transferees'                  => 'Application form for transferees.',
            'TPC Admission Form for Foreign Students'             => 'Application form for foreign students.',
            'Passport with approved student visa'                 => 'Passport showing the approved student visa.',
            'Alien Certificate of Registration'                   => 'Alien Certificate of Registration.',
            'Birth certificate authenticated by the Philippine Foreign Service Post' => 'Birth certificate authenticated by the Philippine Foreign Service Post.',
            'Notarized Affidavit of Support'                      => 'Notarized affidavit of support.',
            'IELTS, TOEFL, or TOEIC result'                       => 'English-proficiency result, when the office asks for it.',
            'NBI Clearance'                                       => 'National Bureau of Investigation clearance.',
            'NICA Clearance'                                      => 'National Intelligence Coordinating Agency clearance.',
            'TPC Admission Form for Cross-Enrollees'              => 'Application form for cross-enrollees.',
            'Physical, health, and psychological examination'     => 'Physical, health, and psychological examination.',
            'Application Form for Returning Students'             => 'Application form for returning students.',
            'Registrar\'s evaluation record'                      => 'Evaluation record from the Registrar.',
            'Application Form for Student Shifters'               => 'Application form for students shifting programs.',
            'Latest Report of Grades'                             => 'Latest report of grades.',
            'Guidance Counselor\'s Report'                        => 'Report of the guidance counselor.',
            'Waiver of academic credit'                           => 'Waiver that the special student earns no academic credit.',
            'Approval of the Vice President for Academic Affairs' => 'Written approval of the Vice President for Academic Affairs.',
        ];

        foreach ($types as $name => $description) {
            if (! DB::table('requirement_types')->where('name', $name)->exists()) {
                DB::table('requirement_types')->insert([
                    'name' => $name, 'description' => $description, 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $rules = [
            ['PSA Birth Certificate', 'ched', null, true],
            ['2x2 ID photo', 'ched', null, true],
            ['Grade 12 report card (SF9 / Form 138)', 'ched', 'freshman', true],
            ['Certificate of Good Moral Character', 'ched', 'freshman', true],
            ['Certificate of Good Moral Character', 'ched', 'transferee', true],
            ['Certificate of Good Moral Character', 'ched', 'second_degree', true],
            ['Transcript of Records or Certificate of Grades', 'ched', 'transferee', true],
            ['Transcript of Records or Certificate of Grades', 'ched', 'second_degree', true],
            ['Honorable Dismissal / Transfer Credentials', 'ched', 'transferee', true],
            ['Permit to Cross-Enroll', 'ched', 'cross_enrollee', true],
            ['Shifting approval', 'ched', 'shiftee', true],
            ['Diploma or certificate of graduation', 'ched', 'second_degree', true],
            ['PSA Birth Certificate', 'tesda', null, true],
            ['1x1 ID photo', 'tesda', null, true],
            ['Proof of educational attainment', 'tesda', null, true],
            ['Valid government-issued ID', 'tesda', null, false],
            ['TPC Admission Form for First Year Students', 'ched', 'freshman', true],
            ['TPC Admission Test (TPCAT) result', 'ched', 'freshman', true],
            ['TPC Admission Test (TPCAT) result', 'ched', 'transferee', true],
            ['TPC Admission Test (TPCAT) result', 'ched', 'foreign', true],
            ['PSA Marriage Certificate', 'ched', 'freshman', false],
            ['Health Examination / Medical Certificate', 'ched', 'freshman', true],
            ['Health Examination / Medical Certificate', 'ched', 'transferee', true],
            ['Result of Psychological Exam', 'ched', 'freshman', true],
            ['Result of Psychological Exam', 'ched', 'transferee', true],
            ['Written pledge to abide by College rules', 'ched', 'transferee', true],
            ['Written pledge to abide by College rules', 'ched', 'foreign', true],
            ['Written pledge to abide by College rules', 'ched', 'cross_enrollee', true],
            ['TPC Admission Form for Transferees', 'ched', 'transferee', true],
            ['TPC Admission Form for Foreign Students', 'ched', 'foreign', true],
            ['Passport with approved student visa', 'ched', 'foreign', true],
            ['Alien Certificate of Registration', 'ched', 'foreign', true],
            ['Birth certificate authenticated by the Philippine Foreign Service Post', 'ched', 'foreign', true],
            ['Notarized Affidavit of Support', 'ched', 'foreign', true],
            ['IELTS, TOEFL, or TOEIC result', 'ched', 'foreign', false],
            ['NBI Clearance', 'ched', 'foreign', true],
            ['NICA Clearance', 'ched', 'foreign', true],
            ['TPC Admission Form for Cross-Enrollees', 'ched', 'cross_enrollee', true],
            ['Physical, health, and psychological examination', 'ched', 'cross_enrollee', true],
            ['Application Form for Returning Students', 'ched', 'returnee', true],
            ['Registrar\'s evaluation record', 'ched', 'returnee', true],
            ['Application Form for Student Shifters', 'ched', 'shiftee', true],
            ['Latest Report of Grades', 'ched', 'shiftee', true],
            ['Guidance Counselor\'s Report', 'ched', 'shiftee', true],
            ['Waiver of academic credit', 'ched', 'special', true],
            ['Approval of the Vice President for Academic Affairs', 'ched', 'special', true],
        ];

        foreach ($rules as [$type, $track, $applicantType, $required]) {
            $typeId = DB::table('requirement_types')->where('name', $type)->value('id');
            $exists = DB::table('requirement_rules')
                ->where('requirement_type_id', $typeId)->where('track', $track)
                ->where('applicant_type', $applicantType)->whereNull('program_id')->exists();

            if (! $exists) {
                DB::table('requirement_rules')->insert([
                    'requirement_type_id' => $typeId,
                    'track'               => $track,
                    'applicant_type'      => $applicantType,
                    'is_required'         => $required,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }
    }

    private function permissions(): void
    {
        $permissions = [
            'admission_inquiries'    => ['read' => 'inquiry_read', 'create' => 'inquiry_create', 'update' => 'inquiry_update', 'delete' => 'inquiry_delete'],
            'admission_applications' => ['read' => 'application_read', 'create' => 'application_create', 'update' => 'application_update'],
            'admission_requirements' => ['verify' => 'requirement_verify'],
            'admission_screening'    => ['create' => 'screening_create'],
            'admission_decisions'    => ['decide' => 'admission_decide'],
            'admission_setup'        => ['read' => 'admission_setup_read', 'update' => 'admission_setup_update'],
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
}
