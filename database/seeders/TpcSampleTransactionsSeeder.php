<?php

namespace Database\Seeders;

use App\Enums\Admission;
use App\Models\Academic\Subject;
use App\Models\Admissions\Inquiry;
use App\Models\Admissions\Person;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\OfferingSchedule;
use App\Models\Enrollment\TrainingBatch;
use App\Models\Role;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use App\Models\User;
use App\Services\Admissions\ApplicationService;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sample office transactions for the open term. Every record is produced by the
 * same services the staff screens use. Fee amounts are not invented: none are
 * scheduled, so an assessment has nothing due. No portal password is created,
 * because these people have no email address.
 */
class TpcSampleTransactionsSeeder extends Seeder
{
    private int $userId = 1;

    private ApplicationService $admissions;

    private EnrollmentService $enrollments;

    /** @var array<int, array<string, Offering>> */
    private array $sections = [];

    public function run(): void
    {
        if (Person::where('first_name', 'Ana')->where('last_name', 'Reyes')->whereDate('date_of_birth', '2008-03-02')->exists()) {
            $this->completeProfiles();
            $this->command?->warn('Sample transactions are already loaded. Student profiles were filled in.');

            return;
        }

        $this->admissions = app(ApplicationService::class);
        $this->enrollments = app(EnrollmentService::class);

        DB::transaction(function () {
            $second = $this->term(1, 'second');
            $first = $this->term(1, 'first');
            $this->openSection($first);
            $this->openSection($second);

            $this->inquiries();
            $this->applicationsStillOpen($second);
            $this->enrollmentsInProgress($second);
            $this->finishedFirstSemester($first, $second);
            $this->trainee();
        });

        $this->completeProfiles();
        $this->command?->info('Sample transactions loaded for 2nd Semester, AY 2026-2027.');
    }

    /**
     * Fill the staff profile for each sample student. These addresses and
     * family names are sample records. They do not open a student login.
     */
    private function completeProfiles(): void
    {
        $profiles = [
            ['Paolo', 'Garcia', '2008-06-05', 'paolo.garcia.sample@tpc.test', 1, 5, 'Ramon Garcia', '09171000006', 'Luz Garcia', '09172000006', 'Mother'],
            ['Rosa', 'Diaz', '2008-07-06', 'rosa.diaz.sample@tpc.test', 1, 3, 'Andres Diaz', '09171000007', 'Maria Diaz', '09172000007', 'Mother'],
            ['Elena', 'Ramos', '2008-08-07', 'elena.ramos.sample@tpc.test', 3, 1, 'Carlos Ramos', '09171000008', 'Teresa Ramos', '09172000008', 'Mother'],
            ['Carlo', 'Mendoza', '2008-09-08', 'carlo.mendoza.sample@tpc.test', 1, 5, 'Ramon Mendoza', '09171000009', 'Elena Mendoza', '09172000009', 'Mother'],
            ['Nico', 'Bautista', '2007-11-12', 'nico.bautista.sample@tpc.test', 3, 1, 'Miguel Bautista', '09171000010', 'Teresa Bautista', '09172000010', 'Mother'],
            ['Liza', 'Navarro', '2007-12-13', 'liza.navarro.sample@tpc.test', 1, 7, 'Pedro Navarro', '09171000011', 'Carmen Navarro', '09172000011', 'Mother'],
            ['Rita', 'Gomez', '2006-01-20', 'rita.gomez.sample@tpc.test', 4, 5, 'Jose Gomez', '09171000012', 'Luz Gomez', '09172000012', 'Mother'],
        ];

        foreach ($profiles as $index => [$first, $last, $born, $email, $religionId, $bloodId, $father, $fatherMobile, $mother, $motherMobile, $relation]) {
            $student = Student::where('first_name', $first)->where('last_name', $last)->whereDate('dob', $born)->first();
            if (! $student) {
                continue;
            }

            $address = 'Sample residence, Tibag, Tarlac City, Tarlac';
            $student->email = $email;
            $student->religion_id = $religionId;
            $student->blood_group_id = $bloodId;
            $student->student_category_id = 1;
            $student->category_id = 1;
            $student->previous_school = 1;
            $student->previous_school_info = $student->department_id === 6
                ? 'Sample Senior High School, 2024'
                : 'Sample Senior High School, 2026';
            $student->spoken_lang_at_home = 'Filipino';
            $student->health_status = 'No known medical condition. Sample record.';
            $student->emergency_contact = $mother . ' ' . $motherMobile;
            $student->residance_address = $address;
            $student->place_of_birth = $student->place_of_birth ?: 'Tarlac City';
            $student->nationality = $student->nationality ?: 'Filipino';
            if ($student->student_code) {
                $student->roll_no = $index - 2;
            }
            $student->save();

            $student->person?->update([
                'email' => $email,
                'nationality' => 'Filipino',
                'place_of_birth' => 'Tarlac City',
            ]);

            $guardian = $student->parent_guardian_id
                ? ParentGuardian::find($student->parent_guardian_id)
                : null;
            if (! $guardian) {
                $role = Role::find(7);
                $user = User::firstOrCreate(
                    ['email' => str_replace('.sample@', '.guardian.sample@', $email)],
                    [
                        'name' => $mother,
                        'phone' => $motherMobile,
                        'password' => Hash::make(Str::password(12, symbols: false)),
                        'email_verified_at' => now(),
                        'role_id' => 7,
                        'permissions' => $role?->permissions ?? [],
                        'status' => 1,
                        'uuid' => (string) Str::uuid(),
                    ]
                );
                $guardian = ParentGuardian::create([
                    'user_id' => $user->id,
                    'status' => 1,
                ]);
                $student->parent_guardian_id = $guardian->id;
                $student->save();
            }

            $guardian->update([
                'father_name' => $father,
                'father_mobile' => $fatherMobile,
                'father_profession' => 'Sample occupation',
                'father_nationality' => 'Filipino',
                'mother_name' => $mother,
                'mother_mobile' => $motherMobile,
                'mother_profession' => 'Sample occupation',
                'guardian_name' => $mother,
                'guardian_mobile' => $motherMobile,
                'guardian_email' => str_replace('.sample@', '.guardian.sample@', $email),
                'guardian_relation' => $relation,
                'guardian_profession' => 'Sample occupation',
                'guardian_address' => $address,
                'guardian_place_of_work' => 'Sample workplace, Tarlac City',
                'guardian_position' => 'Sample occupation',
            ]);

            SessionClassStudent::where('student_id', $student->id)->update([
                'section_id' => 1,
                'roll' => $student->roll_no,
            ]);
        }
    }

    private function inquiries(): void
    {
        Inquiry::create([
            'name'       => 'Sample Caller',
            'channel'    => 'walk_in',
            'track'      => Admission::TRACK_CHED,
            'program_id' => $this->program('Information Technology')->id,
            'message'    => 'Sample inquiry about the Information Technology program.',
            'status'     => 'new',
            'created_by' => $this->userId,
        ]);

        $inquiry = Inquiry::create([
            'name'       => 'Ana Reyes',
            'channel'    => 'walk_in',
            'track'      => Admission::TRACK_CHED,
            'program_id' => $this->program('Health')->id,
            'message'    => 'Sample walk-in who then filed an application.',
            'status'     => 'new',
            'created_by' => $this->userId,
        ]);

        $this->apply('Ana', 'Reyes', 'female', '2008-03-02', '09170000001', 'Health', 'freshman', $this->term(1, 'second'), '91', $inquiry->id);
    }

    private function applicationsStillOpen(Term $term): void
    {
        $luis = $this->apply('Luis', 'Cruz', 'male', '2008-04-03', '09170000002', 'Information Technology', 'freshman', $term, '88');
        $this->score($luis, 25, 12);
        $this->verify($luis, true);

        $mara = $this->apply('Mara', 'Santos', 'female', '2008-05-04', '09170000003', 'Agriculture', 'freshman', $term, '90');
        $this->score($mara, 20, 10);
        $this->verify($mara, false);
        $this->admissions->decide($mara->fresh('requirements'), 'conditional', 'Submit the medical certificate before enrollment.', null, $this->userId);

        $bea = $this->apply('Bea', 'Flores', 'female', '2008-02-01', '09170000004', 'Business and Management', 'freshman', $term, '78');
        $this->admissions->decide($bea->fresh(), 'rejected', null, 'The senior high school general average is below 80.', $this->userId);

        $omar = $this->apply('Omar', 'Santos', 'male', '2008-02-11', '09170000005', 'Agriculture', 'freshman', $term, '85');
        $this->admissions->withdraw($omar->fresh(), 'The applicant withdrew the sample application.', $this->userId);
    }

    private function enrollmentsInProgress(Term $term): void
    {
        $this->carry($this->admit('Paolo', 'Garcia', 'male', '2008-06-05', '09170000006', 'Business and Management', $term, '86', 30, 18), $term, 'new', 'assessment');
        $this->carry($this->admit('Rosa', 'Diaz', 'female', '2008-07-06', '09170000007', 'Hospitality and Tourism', $term, '93', 22, 15), $term, 'new', 'assessed');
        $this->carry($this->admit('Elena', 'Ramos', 'female', '2008-08-07', '09170000008', 'Health', $term, '89', 28, 20), $term, 'new', 'cleared');
        $this->carry($this->admit('Carlo', 'Mendoza', 'male', '2008-09-08', '09170000009', 'Information Technology', $term, '94', 33, 24, true), $term, 'new', 'enrolled');
    }

    private function finishedFirstSemester(Term $first, Term $second): void
    {
        $first->update(['enrollment_open' => true]);

        $nico = $this->carry(
            $this->admit('Nico', 'Bautista', 'male', '2007-11-12', '09170000010', 'Information Technology', $first, '92', 27, 16),
            $first,
            'new',
            'enrolled'
        );
        $this->grades($nico, [
            'GE-US' => '2.00', 'GE-RPH' => '2.25', 'GE-TCW' => '2.50',
            'GE-MMW' => '5.00', 'GE-PC' => '5.00', 'NSTP-1' => '5.00',
        ]);

        $liza = $this->carry(
            $this->admit('Liza', 'Navarro', 'female', '2007-12-13', '09170000011', 'Health', $first, '95', 31, 21),
            $first,
            'new',
            'enrolled'
        );
        $this->grades($liza, [
            'GE-US' => '1.50', 'GE-RPH' => '1.50', 'GE-TCW' => '1.50',
            'GE-MMW' => '1.50', 'GE-PC' => '1.50', 'NSTP-1' => '1.50',
        ]);

        $first->update(['enrollment_open' => false]);

        $nicoNow = $this->enrollments->start([
            'term_id'      => $second->id,
            'program_id'   => $nico->program_id,
            'year_level'   => 1,
            'student_type' => 'continuing',
            'block'        => 'Block A',
        ], $this->userId, null, $nico->student);
        $extra = $this->take($nicoNow, ['GE-MMW', 'GE-PC', 'NSTP-1', 'GE-AA', 'GE-STS', 'GE-ETH']);
        $blocked = false;
        try {
            $this->enrollments->submitForAssessment($nicoNow->fresh(), null, false, $this->userId);
        } catch (ValidationException $e) {
            $blocked = true;
        }
        if (! $blocked) {
            throw new \RuntimeException('A student on probation was allowed to submit 18 units.');
        }
        $this->enrollments->drop($nicoNow->fresh(), $extra, $this->userId);
        $this->through($nicoNow->fresh(), 'enrolled', false);

        $lizaNow = $this->enrollments->start([
            'term_id'      => $second->id,
            'program_id'   => $liza->program_id,
            'year_level'   => 1,
            'student_type' => 'continuing',
            'block'        => 'Block A',
        ], $this->userId, null, $liza->student);
        $this->take($lizaNow, ['GE-AA', 'GE-STS', 'GE-ETH', 'RIZAL', 'NSTP-2']);
        $this->enrollments->recordAcademicScholarship($lizaNow->fresh(), [
            'program'          => 'honorific',
            'discipline_clear' => true,
        ], $this->userId);
        $this->through($lizaNow->fresh(), 'enrolled', false);
    }

    private function trainee(): void
    {
        $program = $this->program('TCLASS Training Program');
        $batch = TrainingBatch::create([
            'program_id' => $program->id,
            'session_id' => 1,
            'code'       => 'TCLASS-A',
            'name'       => 'TCLASS Training Program, Batch A',
            'start_date' => '2026-08-01',
            'schedule'   => 'Day',
            'slots'      => 30,
            'status'     => TrainingBatch::OPEN,
        ]);

        $application = $this->apply('Rita', 'Gomez', 'female', '2006-01-20', '09170000012', 'TCLASS Training Program', 'new_trainee', null, null);
        $application->update([
            'highest_attainment' => 'Senior high school',
            'employment_status'  => 'Not employed',
        ]);
        $this->verify($application, true);
        $this->admissions->decide($application->fresh('requirements'), 'accepted', null, null, $this->userId);

        $enrollment = $this->enrollments->start([
            'track'        => Admission::TRACK_TESDA,
            'batch_id'     => $batch->id,
            'program_id'   => $program->id,
            'student_type' => 'new_trainee',
        ], $this->userId, $application->fresh());
        $this->enrollments->recordEligibility($enrollment, true, 'Sample eligibility recorded by the office.', $this->userId);
        $this->through($enrollment->fresh(), 'enrolled', false);
    }

    private function admit(string $first, string $last, string $sex, string $born, string $mobile, string $program, Term $term, string $average, float $tpccat, float $financial, bool $sector = false): \App\Models\Admissions\Application
    {
        $application = $this->apply($first, $last, $sex, $born, $mobile, $program, 'freshman', $term, $average);
        $this->score($application, $tpccat, $financial, $sector);
        $this->verify($application, true);
        $this->admissions->decide($application->fresh('requirements'), 'accepted', null, null, $this->userId);

        return $application->fresh();
    }

    private function carry(\App\Models\Admissions\Application $application, Term $term, string $studentType, string $stop): \App\Models\Enrollment\Enrollment
    {
        $enrollment = $this->enrollments->start([
            'term_id'      => $term->id,
            'program_id'   => $application->program_id,
            'year_level'   => 1,
            'student_type' => $studentType,
            'block'        => 'Block A',
        ], $this->userId, $application);
        $this->take($enrollment, ['GE-US', 'GE-RPH', 'GE-TCW', 'GE-MMW', 'GE-PC', 'NSTP-1']);

        return $this->through($enrollment->fresh(), $stop);
    }

    private function through(\App\Models\Enrollment\Enrollment $enrollment, string $stop, bool $signAgreement = true): \App\Models\Enrollment\Enrollment
    {
        if ($stop === 'advising') {
            return $enrollment;
        }

        $this->enrollments->submitForAssessment($enrollment, null, false, $this->userId);
        if ($stop === 'assessment') {
            return $enrollment->fresh();
        }

        $this->enrollments->assess($enrollment->fresh(), ! $enrollment->isTrainee(), $this->userId);
        if ($stop === 'assessed') {
            return $enrollment->fresh();
        }

        $this->enrollments->issueClearance($enrollment->fresh(), $this->userId);
        if ($stop === 'cleared') {
            return $enrollment->fresh();
        }

        if ($signAgreement && ! $enrollment->isTrainee()) {
            $guardian = $enrollment->application?->guardian_name ?: 'Sample Guardian';
            $this->enrollments->recordReturnService($enrollment, 'student', [], $this->userId);
            $this->enrollments->recordReturnService($enrollment, 'guardian', ['guardian_name' => $guardian], $this->userId);
            $this->enrollments->recordReturnService($enrollment, 'president', [], $this->userId);
        }

        $this->enrollments->validateEnrollment($enrollment->fresh(), $this->userId);

        return $enrollment->fresh();
    }

    /**
     * @param  array<string, string>  $grades  subject code => final grade
     */
    private function grades(\App\Models\Enrollment\Enrollment $enrollment, array $grades): void
    {
        $rows = [];
        foreach ($enrollment->enlisted()->with('subject')->get() as $course) {
            $rows[$course->id] = ['final_grade' => $grades[$course->subject->code]];
        }
        $this->enrollments->saveResults($enrollment, $rows, $this->userId);
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function take(\App\Models\Enrollment\Enrollment $enrollment, array $codes): \App\Models\Enrollment\EnrollmentCourse
    {
        $last = null;
        foreach ($codes as $code) {
            $last = $this->enrollments->enlist($enrollment, $this->sections[$enrollment->term_id][$code], $this->userId);
        }

        return $last;
    }

    private function openSection(Term $term): void
    {
        $slots = [
            'GE-US' => [1, '08:00:00', '09:30:00'],
            'GE-RPH' => [2, '08:00:00', '09:30:00'],
            'GE-TCW' => [3, '08:00:00', '09:30:00'],
            'GE-MMW' => [4, '08:00:00', '09:30:00'],
            'GE-PC' => [5, '08:00:00', '09:30:00'],
            'GE-AA' => [1, '10:00:00', '11:30:00'],
            'GE-STS' => [2, '10:00:00', '11:30:00'],
            'GE-ETH' => [3, '10:00:00', '11:30:00'],
            'RIZAL' => [4, '10:00:00', '11:30:00'],
            'NSTP-1' => [5, '10:00:00', '11:30:00'],
            'NSTP-2' => [1, '13:00:00', '14:30:00'],
        ];

        foreach ($slots as $code => [$day, $start, $end]) {
            $subject = Subject::where('code', $code)->firstOrFail();
            $offering = Offering::firstOrCreate(
                ['term_id' => $term->id, 'subject_id' => $subject->id, 'section_code' => 'Block A'],
                ['year_level' => 1, 'status' => 'open']
            );
            OfferingSchedule::firstOrCreate(
                ['offering_id' => $offering->id, 'day' => $day, 'start_time' => $start],
                ['end_time' => $end, 'type' => 'lecture']
            );
            $this->sections[$term->id][$code] = $offering->fresh(['subject', 'schedules']);
        }
    }

    private function apply(string $first, string $last, string $sex, string $born, string $mobile, string $program, string $type, ?Term $term, ?string $average, ?int $inquiryId = null): \App\Models\Admissions\Application
    {
        $programModel = $this->program($program);

        return $this->admissions->submit([
            'first_name'        => $first,
            'last_name'         => $last,
            'sex'               => $sex,
            'date_of_birth'     => $born,
            'place_of_birth'    => 'Tarlac City',
            'civil_status'      => 'single',
            'nationality'       => 'Filipino',
            'mobile'            => $mobile,
            'address_line'      => 'Sample residence',
            'barangay'          => 'Tibag',
            'city_municipality' => 'Tarlac City',
            'province'          => 'Tarlac',
        ], [
            'track'                 => $programModel->track,
            'program_id'            => $programModel->id,
            'applicant_type'        => $type,
            'term_id'               => $term?->id,
            'last_school'           => 'Sample Senior High School',
            'last_school_year'      => '2026',
            'general_average'       => $average,
            'guardian_name'         => $first === 'Carlo' ? 'Elena Mendoza' : 'Sample Guardian',
            'guardian_relationship' => 'Mother',
        ], [], 'walk_in', $this->userId, $inquiryId);
    }

    private function score(\App\Models\Admissions\Application $application, float $tpccat, float $financial, bool $sector = false): void
    {
        $application->update([
            'tpccat_points'     => $tpccat,
            'financial_points'  => $financial,
            'sector_categories' => $sector ? ['first_generation'] : [],
            'sector_verified'   => $sector,
            'tarlac_resident'   => true,
        ]);
    }

    private function verify(\App\Models\Admissions\Application $application, bool $allRequired): void
    {
        $required = $application->requirements()->where('is_required', true)->get();
        if (! $allRequired) {
            $required->pop();
        }
        foreach ($required as $requirement) {
            $this->admissions->reviewRequirement($requirement, Admission::REQ_VERIFIED, null, $this->userId);
        }
    }

    private function program(string $name): Program
    {
        return Program::where('name', $name)->firstOrFail();
    }

    private function term(int $sessionId, string $semester): Term
    {
        return Term::where('session_id', $sessionId)->where('semester', $semester)->firstOrFail();
    }
}
