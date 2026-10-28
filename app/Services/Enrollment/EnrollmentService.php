<?php

namespace App\Services\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment as Status;
use App\Services\StudentManual;
use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Admissions\Application;
use App\Models\Admissions\Person;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\Enrollment\CourseCredit;
use App\Models\Enrollment\Curriculum;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\ReturnServiceAgreement;
use App\Models\Enrollment\EnrollmentAssessmentLine;
use App\Models\Enrollment\EnrollmentCourse;
use App\Models\Enrollment\EnrollmentPayment;
use App\Models\Enrollment\AcademicScholarship;
use App\Models\Enrollment\EnrollmentScholarship;
use App\Models\Enrollment\EnrollmentStatusLog;
use App\Models\Enrollment\FeeSchedule;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\TrainingBatch;
use App\Models\Gender;
use App\Models\Session;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * Opens an advising record for one student and term. Either an admitted application or an existing student is required.
     */
    public function start(array $data, int $userId, ?Application $application = null, ?Student $student = null): Enrollment
    {
        if (($data['track'] ?? null) === Admission::TRACK_TESDA) {
            return $this->startTrainee($data, $userId, $application, $student);
        }

        $term = Term::findOrFail($data['term_id']);
        if (! $term->enrollment_open) {
            throw ValidationException::withMessages(['term_id' => ___('enrollment.term_not_open')]);
        }

        $this->guardCurriculum($data['curriculum_id'] ?? null, (int) $data['program_id']);

        return DB::transaction(function () use ($data, $userId, $application, $student) {
            if ($application) {
                if (! in_array($application->status, [Admission::ADMITTED, Admission::CONDITIONAL], true) || $application->track !== Admission::TRACK_CHED) {
                    throw ValidationException::withMessages(['application' => ___('enrollment.application_not_admitted')]);
                }

                $student = $application->student ?: $this->studentForPerson($application->person, Program::find($data['program_id']));
                $application->update(['student_id' => $student->id]);
            }

            if (! $student) {
                throw ValidationException::withMessages(['student' => ___('enrollment.student_required')]);
            }

            if (Enrollment::where('student_id', $student->id)->where('term_id', $data['term_id'])->exists()) {
                throw ValidationException::withMessages(['term_id' => ___('enrollment.already_has_enrollment_for_term')]);
            }

            if (in_array($student->academic_standing ?? 'regular', ['dismissed', 'disqualified'], true)) {
                throw ValidationException::withMessages([
                    'student' => ___('enrollment.retention_' . $student->academic_standing),
                ]);
            }

            if (($data['student_type'] ?? null) === 'special') {
                $sessionId = Term::whereKey($data['term_id'])->value('session_id');
                $otherYear = Enrollment::where('student_id', $student->id)
                    ->where('student_type', 'special')
                    ->whereNotIn('status', Status::CLOSED_STATUSES)
                    ->whereHas('term', fn ($term) => $term->where('session_id', '!=', $sessionId))
                    ->exists();
                if ($otherYear) {
                    throw ValidationException::withMessages(['student_type' => ___('enrollment.special_one_year')]);
                }
            }

            $enrollment = Enrollment::create([
                'student_id'     => $student->id,
                'track'          => Admission::TRACK_CHED,
                'term_id'        => $data['term_id'],
                'program_id'     => $data['program_id'],
                'curriculum_id'  => $data['curriculum_id'] ?? null,
                'year_level'     => $data['year_level'],
                'block'          => $data['block'] ?? null,
                'student_type'   => $data['student_type'],
                'credit_waived'  => ($data['student_type'] ?? null) === 'special',
                'remarks'        => $data['remarks'] ?? null,
                'application_id' => $application?->id,
                'status'         => Status::ADVISING,
                'created_by'     => $userId,
            ]);
            $enrollment->enrollment_no = sprintf('ENR-%s-%06d', now()->format('Y'), $enrollment->id);
            $enrollment->save();

            $this->log($enrollment, null, Status::ADVISING, 'started', $application?->application_no, $userId);

            return $enrollment;
        });
    }

    /**
     * Opens a trainee record for one qualification batch. The seat is held until the record is cancelled or withdrawn.
     */
    public function startTrainee(array $data, int $userId, ?Application $application = null, ?Student $student = null): Enrollment
    {
        return DB::transaction(function () use ($data, $userId, $application, $student) {
            $batch = TrainingBatch::with('program')->where('id', $data['batch_id'])->lockForUpdate()->first();
            if (! $batch || $batch->status !== TrainingBatch::OPEN) {
                throw ValidationException::withMessages(['batch_id' => ___('enrollment.batch_not_open')]);
            }
            if ((int) $batch->program_id !== (int) $data['program_id']) {
                throw ValidationException::withMessages(['program_id' => ___('enrollment.batch_program_mismatch')]);
            }
            if (! in_array($data['student_type'], Admission::applicantTypes(Admission::TRACK_TESDA), true)) {
                throw ValidationException::withMessages(['student_type' => ___('enrollment.trainee_type_required')]);
            }

            $taken = Enrollment::where('batch_id', $batch->id)->whereNotIn('status', Status::CLOSED_STATUSES)->lockForUpdate()->count();
            if ($taken >= (int) $batch->slots) {
                throw ValidationException::withMessages(['batch_id' => ___('enrollment.batch_full')]);
            }

            if ($application) {
                if (! in_array($application->status, [Admission::ADMITTED, Admission::CONDITIONAL], true) || $application->track !== Admission::TRACK_TESDA) {
                    throw ValidationException::withMessages(['application' => ___('enrollment.application_not_admitted')]);
                }

                $student = $application->student ?: $this->studentForPerson($application->person, $batch->program);
                $application->update(['student_id' => $student->id]);
            }

            if (! $student) {
                throw ValidationException::withMessages(['student' => ___('enrollment.student_required')]);
            }

            if (Enrollment::where('student_id', $student->id)->where('batch_id', $batch->id)->exists()) {
                throw ValidationException::withMessages(['batch_id' => ___('enrollment.already_in_batch')]);
            }

            if (Enrollment::where('student_id', $student->id)->where('track', Admission::TRACK_TESDA)->whereNotIn('status', Status::CLOSED_STATUSES)->exists()) {
                throw ValidationException::withMessages(['batch_id' => ___('enrollment.already_has_open_training')]);
            }

            $enrollment = Enrollment::create([
                'student_id'          => $student->id,
                'track'               => Admission::TRACK_TESDA,
                'batch_id'            => $batch->id,
                'program_id'          => $batch->program_id,
                'student_type'        => $data['student_type'],
                'remarks'             => $data['remarks'] ?? null,
                'application_id'      => $application?->id,
                'highest_attainment'  => $application?->highest_attainment,
                'employment_status'   => $application?->employment_status,
                'status'              => Status::ADVISING,
                'created_by'          => $userId,
            ]);
            $enrollment->enrollment_no = sprintf('ENR-%s-%06d', now()->format('Y'), $enrollment->id);
            $enrollment->save();

            $this->log($enrollment, null, Status::ADVISING, 'started', $batch->code, $userId);

            return $enrollment;
        });
    }

    public function recordEligibility(Enrollment $enrollment, bool $met, ?string $remarks, int $userId): void
    {
        if (! $enrollment->isTrainee()) {
            throw ValidationException::withMessages(['eligibility' => ___('enrollment.not_a_course_enrollment')]);
        }
        $this->guardEditable($enrollment);

        $enrollment->forceFill([
            'eligibility_met'     => $met,
            'eligibility_remarks' => $remarks,
        ])->save();

        $this->log(
            $enrollment,
            $enrollment->status,
            $enrollment->status,
            'eligibility_recorded',
            $met ? ___('enrollment.eligibility_met') : ___('enrollment.eligibility_not_met'),
            $userId
        );
    }

    public function saveLearner(Enrollment $enrollment, array $data, int $userId): void
    {
        if (! $enrollment->isTrainee() || $enrollment->isClosed()) {
            throw ValidationException::withMessages(['learner' => ___('enrollment.learner_locked')]);
        }

        $person = $enrollment->student->person;
        if ($person) {
            $person->uli = $data['uli'] ?? null;
            $person->save();
        }

        $enrollment->forceFill([
            'highest_attainment' => $data['highest_attainment'] ?? null,
            'employment_status'  => $data['employment_status'] ?? null,
        ])->save();

        $enrollment->application?->update([
            'highest_attainment' => $enrollment->highest_attainment,
            'employment_status'  => $enrollment->employment_status,
        ]);

        $this->log($enrollment, $enrollment->status, $enrollment->status, 'learner_updated', $person?->uli, $userId);
    }

    public function studentForPerson(Person $person, ?Program $program = null): Student
    {
        $existing = Student::where('person_id', $person->id)->first();
        if ($existing) {
            return $existing;
        }

        return Student::create([
            'person_id'         => $person->id,
            'first_name'        => $person->first_name,
            'last_name'         => trim($person->last_name . ' ' . $person->suffix),
            'dob'               => $person->date_of_birth,
            'email'             => $person->email,
            'mobile'            => $person->mobile,
            'gender_id'         => $person->sex ? Gender::where('name', ucfirst($person->sex))->value('id') : null,
            'place_of_birth'    => $person->place_of_birth,
            'nationality'       => $person->nationality,
            'residance_address' => mb_substr($person->full_address, 0, 250) ?: null,
            'department_id'     => $program?->department_id,
            'status'            => 0,
        ]);
    }

    public function updateDetails(Enrollment $enrollment, array $data, int $userId): void
    {
        $this->guardCollege($enrollment);
        $this->guardEditable($enrollment);
        $this->guardCurriculum($data['curriculum_id'] ?? null, $enrollment->program_id);

        $standing = $data['academic_standing'] ?? null;
        unset($data['academic_standing']);
        $data['credit_waived'] = ($data['student_type'] ?? $enrollment->student_type) === 'special';

        $enrollment->update($data);
        if ($standing) {
            $enrollment->student->update(['academic_standing' => $standing]);
        }
        $this->log($enrollment, $enrollment->status, $enrollment->status, 'details_updated', null, $userId);
    }

    /**
     * Everything the load checks need, gathered once per request.
     */
    public function context(Enrollment $enrollment): array
    {
        $enlisted = $enrollment->enlisted()->with('offering.schedules', 'subject')->get();

        $passed = EnrollmentCourse::where('result', 'passed')
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $enrollment->student_id)
                ->where('id', '!=', $enrollment->id)
                ->where('credit_waived', false))
            ->pluck('subject_id');
        $credited = CourseCredit::where('student_id', $enrollment->student_id)->pluck('subject_id');

        $curriculum = $enrollment->curriculum_id
            ? $enrollment->curriculum->courses()->with('requisites.subject')->get()->keyBy('subject_id')
            : null;

        return [
            'enlisted'   => $enlisted,
            'completed'  => $passed->merge($credited)->unique()->values(),
            'curriculum' => $curriculum,
        ];
    }

    /**
     * @return array<int, array{code: string, message: string}>
     */
    public function issuesFor(Enrollment $enrollment, Offering $offering, array $context): array
    {
        $issues = [];
        $add = function (string $code, ?string $detail = null) use (&$issues) {
            $issues[] = ['code' => $code, 'message' => ___('enrollment.issue_' . $code) . ($detail ? ': ' . $detail : '')];
        };

        if ((int) $offering->term_id !== (int) $enrollment->term_id) {
            $add('wrong_term');
        }
        if ($offering->status !== 'open') {
            $add('not_open');
        }
        if ($context['enlisted']->contains('subject_id', $offering->subject_id)) {
            $add('duplicate');
        }
        if ($context['completed']->contains($offering->subject_id)) {
            $add('already_passed');
        }
        if ($offering->program_id && (int) $offering->program_id !== (int) $enrollment->program_id) {
            $add('other_program', $offering->program->name ?? null);
        }

        $curriculumCourse = $context['curriculum']?->get($offering->subject_id);
        if ($context['curriculum'] && ! $curriculumCourse) {
            $add('not_in_curriculum');
        }

        if ($curriculumCourse) {
            $enlistedSubjects = $context['enlisted']->pluck('subject_id');

            $missingPre = $curriculumCourse->requisites->where('type', 'prerequisite')
                ->reject(fn ($r) => $context['completed']->contains($r->subject_id));
            if ($missingPre->isNotEmpty()) {
                $add('prerequisite', $missingPre->map(fn ($r) => $r->subject->code ?: $r->subject->name)->implode(', '));
            }

            $missingCo = $curriculumCourse->requisites->where('type', 'corequisite')
                ->reject(fn ($r) => $context['completed']->contains($r->subject_id) || $enlistedSubjects->contains($r->subject_id));
            if ($missingCo->isNotEmpty()) {
                $add('corequisite', $missingCo->map(fn ($r) => $r->subject->code ?: $r->subject->name)->implode(', '));
            }
        }

        $clashes = [];
        foreach ($offering->schedules as $slot) {
            foreach ($context['enlisted'] as $course) {
                foreach ($course->offering->schedules as $taken) {
                    if ($slot->overlaps($taken)) {
                        $clashes[] = $course->subject->code ?: $course->subject->name;
                    }
                }
            }
        }
        if ($clashes) {
            $add('conflict', implode(', ', array_unique($clashes)));
        }

        if ($offering->capacity && $offering->activeSeats()->count() >= $offering->capacity) {
            $add('full', $offering->capacity);
        }

        return $issues;
    }

    public function enlist(Enrollment $enrollment, Offering $offering, int $userId, ?string $overrideReason = null, bool $canOverride = false): EnrollmentCourse
    {
        $this->guardCollege($enrollment);
        $this->guardEditable($enrollment);

        $issues = collect($this->issuesFor($enrollment, $offering, $this->context($enrollment)));
        $blocking = $issues->reject(fn ($i) => in_array($i['code'], Status::OVERRIDABLE_ISSUES, true));

        if ($blocking->isNotEmpty()) {
            throw ValidationException::withMessages(['course' => $blocking->pluck('message')->all()]);
        }

        if ($issues->isNotEmpty() && (! $canOverride || blank($overrideReason))) {
            $messages = $issues->pluck('message')->all();
            $messages[] = $canOverride ? ___('enrollment.override_reason_needed') : ___('enrollment.override_permission_needed');
            throw ValidationException::withMessages(['course' => $messages]);
        }

        return DB::transaction(function () use ($enrollment, $offering, $userId, $overrideReason, $issues) {
            $subject = $offering->subject;

            $course = EnrollmentCourse::create([
                'enrollment_id'   => $enrollment->id,
                'offering_id'     => $offering->id,
                'subject_id'      => $subject->id,
                'lecture_units'   => $subject->lecture_units,
                'lab_units'       => $subject->lab_units,
                'status'          => 'enlisted',
                'override_reason' => $issues->isNotEmpty() ? $issues->pluck('message')->implode('; ') . ' — ' . $overrideReason : null,
                'added_by'        => $userId,
            ]);

            $this->recalculateUnits($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, $issues->isNotEmpty() ? 'course_overridden' : 'course_added',
                $this->courseLabel($offering) . ($issues->isNotEmpty() ? ' — ' . $overrideReason : ''), $userId);

            return $course;
        });
    }

    /**
     * Adds every clean offering of a block; anything with an issue is skipped and reported for individual handling.
     *
     * @return array{added: array<int, string>, skipped: array<string, array<int, string>>}
     */
    public function loadBlock(Enrollment $enrollment, string $block, int $userId): array
    {
        $this->guardCollege($enrollment);
        $this->guardEditable($enrollment);

        $offerings = Offering::with('subject', 'schedules', 'program')
            ->where('term_id', $enrollment->term_id)
            ->where('section_code', $block)
            ->get()
            ->sortBy(fn ($o) => $o->subject->code ?? $o->subject->name);

        if ($offerings->isEmpty()) {
            throw ValidationException::withMessages(['block' => ___('enrollment.block_has_no_offerings')]);
        }

        $added = [];
        $skipped = [];

        DB::transaction(function () use ($enrollment, $offerings, $userId, $block, &$added, &$skipped) {
            foreach ($offerings as $offering) {
                $issues = $this->issuesFor($enrollment, $offering, $this->context($enrollment));
                $label = $this->courseLabel($offering);

                if ($issues) {
                    $skipped[$label] = array_column($issues, 'message');
                    continue;
                }

                EnrollmentCourse::create([
                    'enrollment_id' => $enrollment->id,
                    'offering_id'   => $offering->id,
                    'subject_id'    => $offering->subject_id,
                    'lecture_units' => $offering->subject->lecture_units,
                    'lab_units'     => $offering->subject->lab_units,
                    'status'        => 'enlisted',
                    'added_by'      => $userId,
                ]);
                $added[] = $label;
            }

            if ($added) {
                $enrollment->block = $block;
                $this->recalculateUnits($enrollment);
            }

            $this->log($enrollment, $enrollment->status, $enrollment->status, 'block_loaded',
                $block . ': ' . count($added) . ' ' . ___('enrollment.added') . ', ' . count($skipped) . ' ' . ___('enrollment.skipped'), $userId);
        });

        return ['added' => $added, 'skipped' => $skipped];
    }

    public function drop(Enrollment $enrollment, EnrollmentCourse $course, int $userId): string
    {
        $this->guardCollege($enrollment);
        abort_unless((int) $course->enrollment_id === (int) $enrollment->id, 404);

        if ($enrollment->status === Status::ENROLLED) {
            return $this->officialDrop($enrollment, $course, $userId);
        }

        $this->guardEditable($enrollment);

        DB::transaction(function () use ($enrollment, $course, $userId) {
            $label = $this->courseLabel($course->offering);
            $course->delete();
            $this->recalculateUnits($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'course_dropped', $label, $userId);
        });

        return 'course_dropped';
    }

    private function officialDrop(Enrollment $enrollment, EnrollmentCourse $course, int $userId): string
    {
        $enrollment->loadMissing('term');
        if (! $enrollment->term?->midterm_on) {
            throw ValidationException::withMessages(['drop' => ___('enrollment.midterm_date_required')]);
        }

        $beforeMidterm = now()->startOfDay()->lt($enrollment->term->midterm_on->copy()->startOfDay());
        $label = $this->courseLabel($course->offering);

        DB::transaction(function () use ($enrollment, $course, $userId, $beforeMidterm, $label) {
            if ($beforeMidterm) {
                $course->update([
                    'status'          => 'dropped',
                    'final_grade'     => null,
                    'result'          => null,
                    'unofficial_drop' => false,
                ]);
                $this->recalculateUnits($enrollment);
            } else {
                $course->update([
                    'status'          => 'enlisted',
                    'final_grade'     => '5.00',
                    'result'          => 'failed',
                    'unofficial_drop' => true,
                    'graded_by'       => $userId,
                    'graded_at'       => now(),
                ]);
            }
            $action = $beforeMidterm ? 'course_dropped' : 'unofficial_drop';
            $this->log($enrollment, $enrollment->status, $enrollment->status, $action, $label, $userId);
        });

        if (! $enrollment->isTrainee() && ! $enrollment->credit_waived) {
            StudentManual::refreshRetention($enrollment->student_id);
        }

        return $beforeMidterm ? 'course_dropped' : 'unofficial_drop';
    }

    public function dropForAbsence(Offering $offering, int $studentId, int $userId): void
    {
        $report = StudentManual::absence($offering, $studentId);
        if (! $report || ! $report['drop']) {
            throw ValidationException::withMessages(['attendance' => ___('enrollment.absence_not_reached')]);
        }

        $course = EnrollmentCourse::with('enrollment')
            ->where('offering_id', $offering->id)
            ->where('status', 'enlisted')
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $studentId)->where('status', Status::ENROLLED))
            ->first();
        if (! $course) {
            throw ValidationException::withMessages(['attendance' => ___('enrollment.not_your_class')]);
        }

        DB::transaction(function () use ($course, $report, $userId) {
            if ($report['failing']) {
                $course->update([
                    'final_grade'     => '5.00',
                    'result'          => 'failed',
                    'unofficial_drop' => true,
                    'graded_by'       => $userId,
                    'graded_at'       => now(),
                ]);
            } else {
                $course->update([
                    'status'          => 'dropped',
                    'final_grade'     => null,
                    'result'          => null,
                    'unofficial_drop' => false,
                ]);
                $this->recalculateUnits($course->enrollment);
            }
            $this->log($course->enrollment, $course->enrollment->status, $course->enrollment->status, 'absence_drop', null, $userId);
        });

        StudentManual::refreshRetention($studentId);
    }

    public function submitForAssessment(Enrollment $enrollment, ?string $overloadReason, bool $canOverride, int $userId): void
    {
        $this->guardEditable($enrollment);

        if ($enrollment->isTrainee()) {
            if ((int) $enrollment->eligibility_met !== 1) {
                throw ValidationException::withMessages(['eligibility' => ___('enrollment.eligibility_required')]);
            }

            $enrollment->forceFill(['advised_by' => $userId, 'advised_at' => now()]);
            $this->transition($enrollment, Status::FOR_ASSESSMENT, 'submitted_for_assessment', null, $userId);

            return;
        }

        if (! $enrollment->enlisted()->exists()) {
            throw ValidationException::withMessages(['load' => ___('enrollment.load_is_empty')]);
        }

        $check = StudentManual::loadCheck($enrollment);
        if ($check['blocked']) {
            throw ValidationException::withMessages(['load' => $check['message']]);
        }
        $overload = $check['needs_reason'];
        if ($overload && (! $canOverride || blank($overloadReason))) {
            throw ValidationException::withMessages(['overload_reason' => ___('enrollment.overload_needs_reason') . ' (' . Status::units($enrollment->total_units) . ')']);
        }

        $enrollment->forceFill([
            'overload_reason' => $overload ? $overloadReason : null,
            'advised_by'      => $userId,
            'advised_at'      => now(),
        ]);
        $this->transition($enrollment, Status::FOR_ASSESSMENT, 'submitted_for_assessment', $overload ? ___('enrollment.overload') . ': ' . $overloadReason : null, $userId);
    }

    public function assess(Enrollment $enrollment, bool $freeTuition, int $userId): void
    {
        if (! in_array($enrollment->status, [Status::FOR_ASSESSMENT, Status::ASSESSED], true)) {
            throw ValidationException::withMessages(['status' => ___('enrollment.not_ready_for_assessment')]);
        }

        DB::transaction(function () use ($enrollment, $freeTuition, $userId) {
            $courses = $enrollment->enlisted()->get();
            $quantities = [
                'per_unit'       => $courses->sum(fn ($c) => $c->units),
                'per_lab_unit'   => $courses->sum(fn ($c) => (float) $c->lab_units),
                'per_lab_course' => $courses->filter(fn ($c) => (float) $c->lab_units > 0)->count(),
                'fixed'          => 1,
            ];

            $enrollment->assessmentLines()->delete();
            $total = 0;
            $subsidy = 0;

            foreach (FeeSchedule::applyingTo($enrollment->program_id, $enrollment->term_id)->get() as $fee) {
                $quantity = $quantities[$fee->basis] ?? 0;
                if ($quantity <= 0) {
                    continue;
                }

                $amount = round($quantity * (float) $fee->amount, 2);
                $waived = $freeTuition && $fee->free_tuition_covered ? $amount : 0;

                EnrollmentAssessmentLine::create([
                    'enrollment_id'   => $enrollment->id,
                    'fee_schedule_id' => $fee->id,
                    'name'            => $fee->name,
                    'category'        => $fee->category,
                    'basis'           => $fee->basis,
                    'quantity'        => $quantity,
                    'rate'            => $fee->amount,
                    'amount'          => $amount,
                    'subsidy'         => $waived,
                ]);

                $total += $amount;
                $subsidy += $waived;
            }

            $enrollment->forceFill([
                'free_tuition'     => $freeTuition,
                'assessed_total'   => $total,
                'subsidy_total'    => $subsidy,
                'net_total'        => $total - $subsidy,
                'assessed_by'      => $userId,
                'assessed_at'      => now(),
                'funding_outcome'  => null,
                'cleared_by'       => null,
                'cleared_at'       => null,
            ]);
            $this->refreshBalance($enrollment);

            $this->transition($enrollment, Status::ASSESSED, 'assessed',
                number_format($total, 2) . ($subsidy > 0 ? ' − ' . number_format($subsidy, 2) . ' (' . ___('enrollment.ra_10931') . ')' : ''), $userId);
        });
    }

    public function validateEnrollment(Enrollment $enrollment, int $userId): array
    {
        if ($enrollment->status !== Status::CLEARED) {
            throw ValidationException::withMessages(['status' => ___('enrollment.not_ready_for_validation')]);
        }
        if (! $enrollment->isTrainee() && ! StudentManual::agreementReady($enrollment->student->returnServiceAgreement)) {
            throw ValidationException::withMessages(['rsa' => ___('enrollment.rsa_required')]);
        }

        return DB::transaction(function () use ($enrollment, $userId) {
            $student = $enrollment->student;

            if (! $student->student_code) {
                $session = $enrollment->term?->session ?? $enrollment->batch?->session;
                $number = $this->nextStudentNumber($enrollment->term, $session);
                $student->student_code = $number;
                $student->admission_no = $number;
            }
            $student->status = 1;
            $student->admission_date = $student->admission_date ?: now()->toDateString();
            $student->department_id = $enrollment->program->department_id ?? $student->department_id;
            $student->save();

            $portal = $this->openStudentPortal($student->fresh());

            $this->syncLegacyPlacement($enrollment);

            $enrollment->forceFill(['validated_by' => $userId, 'validated_at' => now()]);
            $this->transition($enrollment, Status::ENROLLED, 'validated', $student->student_code, $userId);

            return $portal;
        });
    }

    /**
     * Opens the student portal sign-in from the person's email. The temporary password is returned once and is not stored.
     *
     * @return array{opened: bool, reason: ?string, email: ?string, signin: ?string, password: ?string}
     */
    public function openStudentPortal(Student $student): array
    {
        $student->loadMissing('person', 'user');
        if ($student->user_id) {
            return ['opened' => false, 'reason' => 'exists', 'email' => $student->user->email ?? $student->email, 'signin' => $student->student_code, 'password' => null];
        }

        $email = trim((string) ($student->person?->email ?: $student->email));
        if ($email === '') {
            return ['opened' => false, 'reason' => 'missing_email', 'email' => null, 'signin' => null, 'password' => null];
        }
        if (User::where('email', $email)->orWhere('username', $student->student_code)->exists()) {
            return ['opened' => false, 'reason' => 'email_taken', 'email' => $email, 'signin' => null, 'password' => null];
        }

        $plain = Str::password(10, symbols: false);
        $user = new User;
        $user->name = trim($student->first_name . ' ' . $student->last_name);
        $user->email = $email;
        $user->username = $student->student_code;
        $user->admission_no = $student->student_code;
        $user->phone = $student->person?->mobile ?: $student->mobile;
        $user->password = Hash::make($plain);
        $user->email_verified_at = now();
        $user->role_id = 6;
        $user->permissions = $this->rolePermissions(6);
        $user->date_of_birth = $student->dob;
        $user->status = 1;
        $user->uuid = (string) Str::uuid();
        $user->save();

        $student->user_id = $user->id;
        $student->email = $email;
        $student->save();

        return ['opened' => true, 'reason' => null, 'email' => $email, 'signin' => $student->student_code, 'password' => $plain];
    }

    /**
     * @return array{opened: bool, reason: ?string, email: ?string, password: ?string}
     */
    public function openGuardianPortal(Student $student, string $email, string $name, ?string $mobile, ?string $relation): array
    {
        $student->loadMissing('parent');
        if ($student->parent_guardian_id) {
            return ['opened' => false, 'reason' => 'exists', 'email' => $student->parent?->guardian_email, 'password' => null];
        }
        if (User::where('email', $email)->exists()) {
            return ['opened' => false, 'reason' => 'email_taken', 'email' => $email, 'password' => null];
        }

        $plain = Str::password(10, symbols: false);
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->phone = $mobile;
        $user->password = Hash::make($plain);
        $user->email_verified_at = now();
        $user->role_id = 7;
        $user->permissions = $this->rolePermissions(7);
        $user->status = 1;
        $user->uuid = (string) Str::uuid();
        $user->save();

        $guardian = ParentGuardian::create([
            'user_id'            => $user->id,
            'guardian_name'      => $name,
            'guardian_email'     => $email,
            'guardian_mobile'    => $mobile,
            'guardian_relation'  => $relation,
            'status'             => 1,
        ]);

        $student->parent_guardian_id = $guardian->id;
        $student->save();

        return ['opened' => true, 'reason' => null, 'email' => $email, 'password' => $plain];
    }

    public function gwa(Student $student): ?float
    {
        $courses = EnrollmentCourse::query()
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id)->where('status', Status::ENROLLED)->where('credit_waived', false))
            ->where('status', 'enlisted')
            ->get(['final_grade', 'lecture_units', 'lab_units']);

        $weighted = 0.0;
        $units = 0.0;
        foreach ($courses as $course) {
            if (! is_numeric($course->final_grade)) {
                continue;
            }
            $grade = (float) $course->final_grade;
            $courseUnits = (float) $course->lecture_units + (float) $course->lab_units;
            if ($grade < 1 || $grade > 5 || $courseUnits <= 0) {
                continue;
            }
            $weighted += $grade * $courseUnits;
            $units += $courseUnits;
        }

        return $units > 0 ? round($weighted / $units, 2) : null;
    }

    private function rolePermissions(int $roleId): array
    {
        $permissions = Role::find($roleId)?->permissions ?? [];

        return is_array($permissions) ? $permissions : [];
    }

    public function returnToAdvising(Enrollment $enrollment, ?string $remarks, int $userId): void
    {
        if (! in_array($enrollment->status, [Status::FOR_ASSESSMENT, Status::ASSESSED, Status::CLEARED], true)) {
            throw ValidationException::withMessages(['status' => ___('enrollment.cannot_return_to_advising')]);
        }
        if ($this->hasOpenAccount($enrollment)) {
            $this->requireRemarks($remarks);
        }

        DB::transaction(function () use ($enrollment, $remarks, $userId) {
            $this->clearAssessment($enrollment);
            $this->transition($enrollment, Status::ADVISING, 'returned_to_advising', $remarks, $userId);
        });
    }

    public function reopen(Enrollment $enrollment, ?string $remarks, int $userId): void
    {
        if ($enrollment->status !== Status::ENROLLED) {
            throw ValidationException::withMessages(['status' => ___('enrollment.only_enrolled_can_reopen')]);
        }
        $this->requireRemarks($remarks);

        DB::transaction(function () use ($enrollment, $remarks, $userId) {
            $this->clearAssessment($enrollment);
            $this->transition($enrollment, Status::ADVISING, 'reopened_for_adjustment', $remarks, $userId);
        });
    }

    public function cancel(Enrollment $enrollment, ?string $remarks, int $userId): void
    {
        if (! in_array($enrollment->status, [Status::ADVISING, Status::FOR_ASSESSMENT, Status::ASSESSED, Status::CLEARED], true)) {
            throw ValidationException::withMessages(['status' => ___('enrollment.cannot_cancel')]);
        }
        $this->requireRemarks($remarks);

        DB::transaction(function () use ($enrollment, $remarks, $userId) {
            $this->transition($enrollment, Status::CANCELLED, 'cancelled', $remarks, $userId);
            $this->removeLegacyPlacement($enrollment);
        });
    }

    public function withdraw(Enrollment $enrollment, ?string $remarks, int $userId): void
    {
        if ($enrollment->status !== Status::ENROLLED) {
            throw ValidationException::withMessages(['status' => ___('enrollment.only_enrolled_can_withdraw')]);
        }
        $this->requireRemarks($remarks);

        DB::transaction(function () use ($enrollment, $remarks, $userId) {
            $enrollment->enlisted()->whereNull('result')->update(['result' => 'dropped', 'graded_by' => $userId, 'graded_at' => now()]);
            $this->transition($enrollment, Status::WITHDRAWN, 'withdrawn', $remarks, $userId);
            $this->removeLegacyPlacement($enrollment);
        });
    }

    /**
     * @param  array<int, array{final_grade?: ?string, result?: ?string}>  $rows  keyed by enrollment_course id
     */
    public function saveResults(Enrollment $enrollment, array $rows, int $userId): int
    {
        if ($enrollment->status !== Status::ENROLLED) {
            throw ValidationException::withMessages(['status' => ___('enrollment.results_only_when_enrolled')]);
        }

        $changed = 0;
        DB::transaction(function () use ($enrollment, $rows, $userId, &$changed) {
            foreach ($enrollment->enlisted()->get() as $course) {
                if (! array_key_exists($course->id, $rows)) {
                    continue;
                }

                $fields = $this->gradeFields($rows[$course->id]);
                if ($fields['final_grade'] === $course->final_grade && $fields['result'] === $course->result
                    && $fields['removal_on'] === optional($course->removal_on)->toDateString()
                    && (bool) $fields['standing_passing'] === (bool) $course->standing_passing) {
                    continue;
                }

                $course->update($fields + ['graded_by' => $userId, 'graded_at' => now()]);
                StudentManual::closeExpiredRemoval($course->fresh());
                $changed++;
            }

            if ($changed) {
                $this->log($enrollment, $enrollment->status, $enrollment->status, 'results_recorded', $changed . ' ' . ___('enrollment.courses_lower'), $userId);
            }
        });

        if (! $enrollment->isTrainee() && ! $enrollment->credit_waived) {
            StudentManual::refreshRetention($enrollment->student_id);
        }

        return $changed;
    }

    /**
     * @param  array<int, array{final_grade?: ?string, result?: ?string}>  $rows  keyed by enrollment_course id
     */
    public function saveClassGrades(Offering $offering, array $rows, int $userId): int
    {
        $courses = EnrollmentCourse::with('enrollment')
            ->where('offering_id', $offering->id)
            ->where('status', 'enlisted')
            ->whereHas('enrollment', fn ($q) => $q->where('status', Status::ENROLLED))
            ->whereIn('id', array_keys($rows))
            ->get();

        if ($courses->count() !== count($rows)) {
            throw ValidationException::withMessages(['grades' => ___('enrollment.not_your_class')]);
        }

        $changed = 0;
        DB::transaction(function () use ($courses, $rows, $userId, &$changed) {
            $logged = [];
            foreach ($courses as $course) {
                $fields = $this->gradeFields($rows[$course->id]);
                if ($fields['final_grade'] === $course->final_grade && $fields['result'] === $course->result
                    && $fields['removal_on'] === optional($course->removal_on)->toDateString()
                    && (bool) $fields['standing_passing'] === (bool) $course->standing_passing) {
                    continue;
                }
                $course->update($fields + ['graded_by' => $userId, 'graded_at' => now()]);
                StudentManual::closeExpiredRemoval($course->fresh());
                $changed++;
                $logged[$course->enrollment_id] = $course->enrollment;
            }
            foreach ($logged as $enrollment) {
                $this->log($enrollment, $enrollment->status, $enrollment->status, 'results_recorded', null, $userId);
            }
        });

        $courses->pluck('enrollment')->unique('student_id')->each(function ($enrollment) {
            if (! $enrollment->isTrainee() && ! $enrollment->credit_waived) {
                StudentManual::refreshRetention($enrollment->student_id);
            }
        });

        return $changed;
    }

    public function nextStudentNumber(?Term $term = null, ?Session $session = null): string
    {
        $format = $this->settingValue('student_number_format') ?: Status::STUDENT_NUMBER_FORMAT;
        $padding = (int) ($this->settingValue('student_number_padding') ?: Status::STUDENT_NUMBER_PADDING);

        $session = $session ?? $term?->session;
        preg_match('/\d{4}/', (string) $session?->name, $year);
        $format = str_replace('{YYYY}', $year[0] ?? now()->format('Y'), $format);

        [$before, $after] = array_pad(explode('{SEQ}', $format, 2), 2, '');

        $highest = Student::where('student_code', 'like', addcslashes($before, '%_') . '%' . addcslashes($after, '%_'))
            ->lockForUpdate()
            ->pluck('student_code')
            ->map(function ($code) use ($before, $after) {
                $middle = substr($code, strlen($before), strlen($code) - strlen($before) - strlen($after));

                return ctype_digit($middle) ? (int) $middle : 0;
            })
            ->max() ?? 0;

        return $before . str_pad((string) ($highest + 1), $padding, '0', STR_PAD_LEFT) . $after;
    }

    public function settingValue(string $name): ?string
    {
        $value = Setting::where('name', $name)->first()?->value;

        return is_scalar($value) ? (string) $value : null;
    }

    public function saveSetting(string $name, ?string $value): void
    {
        Setting::updateOrCreate(['name' => $name], ['value' => $value]);
        Cache::forget("setting__{$name}");
    }

    public function saveCredit(Student $student, array $data, int $userId): CourseCredit
    {
        $credit = CourseCredit::updateOrCreate(
            ['student_id' => $student->id, 'subject_id' => $data['subject_id']],
            $data + ['credited_by' => $userId]
        );

        return $credit;
    }

    /**
     * Keeps the legacy Students list, attendance and ID cards aware of validated students.
     */
    private function syncLegacyPlacement(Enrollment $enrollment): void
    {
        $sessionId = $enrollment->term?->session_id ?? $enrollment->batch?->session_id;
        if (! $sessionId) {
            return;
        }

        $classId = $enrollment->year_level
            ? Classes::where('program_id', $enrollment->program_id)->where('year_level', $enrollment->year_level)->value('id')
            : null;
        $classId ??= Classes::where('program_id', $enrollment->program_id)->whereNull('year_level')->value('id');
        $sectionId = null;
        if ($enrollment->block) {
            $sectionId = Section::where('name', $enrollment->block)->value('id');
            if (! $sectionId && preg_match('/([A-Za-z])$/', $enrollment->block, $m)) {
                $sectionId = Section::where('name', 'Block ' . strtoupper($m[1]))->value('id');
            }
        }

        $placement = SessionClassStudent::where('session_id', $sessionId)
            ->where('student_id', $enrollment->student_id)
            ->first() ?? new SessionClassStudent();

        $placement->forceFill([
            'session_id' => $sessionId,
            'student_id' => $enrollment->student_id,
            'classes_id' => $classId,
            'section_id' => $sectionId,
        ])->save();
    }

    private function removeLegacyPlacement(Enrollment $enrollment): void
    {
        $sessionId = $enrollment->term?->session_id ?? $enrollment->batch?->session_id;
        if (! $sessionId) {
            return;
        }

        $stillEnrolled = Enrollment::where('student_id', $enrollment->student_id)
            ->where('id', '!=', $enrollment->id)
            ->where('status', Status::ENROLLED)
            ->where(function ($q) use ($sessionId) {
                $q->whereHas('term', fn ($t) => $t->where('session_id', $sessionId))
                    ->orWhereHas('batch', fn ($b) => $b->where('session_id', $sessionId));
            })
            ->exists();

        if (! $stillEnrolled) {
            SessionClassStudent::where('session_id', $sessionId)
                ->where('student_id', $enrollment->student_id)
                ->delete();
        }
    }

    public function addScholarship(Enrollment $enrollment, array $data, int $userId): void
    {
        $this->guardOpenAccount($enrollment);
        $amount = round((float) $data['amount'], 2);
        $this->guardAmount($enrollment, $amount);

        $name = trim((string) ($data['name'] ?? ''));
        if ($data['program'] === 'other' && $name === '') {
            throw ValidationException::withMessages(['name' => ___('enrollment.scholarship_name_required')]);
        }

        DB::transaction(function () use ($enrollment, $data, $amount, $name, $userId) {
            $row = EnrollmentScholarship::create([
                'enrollment_id' => $enrollment->id,
                'program'       => $data['program'],
                'name'          => $name !== '' ? $name : null,
                'reference'     => $data['reference'] ?? null,
                'amount'        => $amount,
                'remarks'       => $data['remarks'] ?? null,
                'recorded_by'   => $userId,
            ]);
            $this->refreshBalance($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'scholarship_recorded', $row->label() . ' · ₱' . number_format($amount, 2), $userId);
            $this->lapseClearanceIfShort($enrollment, $userId);
        });
    }

    public function removeScholarship(Enrollment $enrollment, EnrollmentScholarship $scholarship, ?string $reason, int $userId): void
    {
        $this->guardOpenAccount($enrollment);
        $this->guardOwned($enrollment, $scholarship);
        $this->requireRemarks($reason);
        if ($scholarship->voided_at) {
            throw ValidationException::withMessages(['status' => ___('enrollment.already_voided')]);
        }

        DB::transaction(function () use ($enrollment, $scholarship, $reason, $userId) {
            $scholarship->update(['voided_at' => now(), 'voided_by' => $userId, 'void_reason' => $reason]);
            $this->refreshBalance($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'scholarship_removed', $scholarship->label() . ' · ' . $reason, $userId);
            $this->lapseClearanceIfShort($enrollment, $userId);
        });
    }

    public function recordAcademicScholarship(Enrollment $enrollment, array $data, int $userId): void
    {
        if ($enrollment->isTrainee() || in_array($enrollment->status, Status::CLOSED_STATUSES, true)) {
            throw ValidationException::withMessages(['program' => ___('enrollment.academic_scholarship_college')]);
        }
        if ($enrollment->academicScholarships()->whereNull('voided_at')->exists()) {
            throw ValidationException::withMessages(['program' => ___('enrollment.academic_scholarship_one')]);
        }
        if (! in_array($data['program'], StudentManual::SCHOLARSHIP_PROGRAMS, true)) {
            throw ValidationException::withMessages(['program' => ___('enrollment.academic_scholarship_program')]);
        }

        $sponsor = trim((string) ($data['sponsor'] ?? ''));
        if ($data['program'] === 'government_donor' && $sponsor === '') {
            throw ValidationException::withMessages(['sponsor' => ___('enrollment.academic_scholarship_sponsor_required')]);
        }
        if (empty($data['discipline_clear'])) {
            throw ValidationException::withMessages(['discipline_clear' => ___('enrollment.academic_scholarship_discipline')]);
        }

        $check = StudentManual::scholarshipEligibility($enrollment);
        if (! $check['qualified']) {
            throw ValidationException::withMessages(['program' => ___('enrollment.academic_scholarship_not_qualified')]);
        }

        DB::transaction(function () use ($enrollment, $data, $sponsor, $check, $userId) {
            AcademicScholarship::create([
                'student_id'              => $enrollment->student_id,
                'enrollment_id'           => $enrollment->id,
                'preceding_enrollment_id' => $check['enrollment']->id,
                'program'                 => $data['program'],
                'sponsor'                 => $sponsor !== '' ? $sponsor : null,
                'preceding_units'         => $check['units'],
                'preceding_gwa'           => $check['gwa'],
                'discipline_clear'        => true,
                'recorded_by'             => $userId,
            ]);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'academic_scholarship', ___('enrollment.academic_program_' . $data['program']), $userId);
        });
    }

    public function removeAcademicScholarship(Enrollment $enrollment, AcademicScholarship $scholarship, ?string $reason, int $userId): void
    {
        abort_unless((int) $scholarship->enrollment_id === (int) $enrollment->id, 404);
        $this->requireRemarks($reason);
        if ($scholarship->voided_at) {
            throw ValidationException::withMessages(['status' => ___('enrollment.already_voided')]);
        }

        DB::transaction(function () use ($enrollment, $scholarship, $reason, $userId) {
            $scholarship->update(['voided_at' => now(), 'voided_by' => $userId, 'void_reason' => $reason]);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'academic_scholarship_removed', $reason, $userId);
        });
    }

    public function recordPayment(Enrollment $enrollment, array $data, int $userId): void
    {
        $this->guardOpenAccount($enrollment);
        $amount = round((float) $data['amount'], 2);
        $this->guardAmount($enrollment, $amount);

        $or = trim($data['or_number']);
        if (EnrollmentPayment::where('or_number', $or)->exists()) {
            throw ValidationException::withMessages(['or_number' => ___('enrollment.or_already_used')]);
        }
        if (now()->startOfDay()->lt(\Carbon\Carbon::parse($data['paid_on'])->startOfDay())) {
            throw ValidationException::withMessages(['paid_on' => ___('enrollment.or_date_future')]);
        }

        DB::transaction(function () use ($enrollment, $data, $amount, $or, $userId) {
            EnrollmentPayment::create([
                'enrollment_id' => $enrollment->id,
                'or_number'     => $or,
                'paid_on'       => $data['paid_on'],
                'amount'        => $amount,
                'remarks'       => $data['remarks'] ?? null,
                'recorded_by'   => $userId,
            ]);
            $this->refreshBalance($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'payment_recorded', 'OR ' . $or . ' · ₱' . number_format($amount, 2), $userId);
            $this->lapseClearanceIfShort($enrollment, $userId);
        });
    }

    public function voidPayment(Enrollment $enrollment, EnrollmentPayment $payment, ?string $reason, int $userId): void
    {
        $this->guardOpenAccount($enrollment);
        $this->guardOwned($enrollment, $payment);
        $this->requireRemarks($reason);
        if ($payment->voided_at) {
            throw ValidationException::withMessages(['status' => ___('enrollment.already_voided')]);
        }

        DB::transaction(function () use ($enrollment, $payment, $reason, $userId) {
            $payment->update(['voided_at' => now(), 'voided_by' => $userId, 'void_reason' => $reason]);
            $this->refreshBalance($enrollment);
            $this->log($enrollment, $enrollment->status, $enrollment->status, 'payment_voided', 'OR ' . $payment->or_number . ' · ' . $reason, $userId);
            $this->lapseClearanceIfShort($enrollment, $userId);
        });
    }

    public function issueClearance(Enrollment $enrollment, int $userId): void
    {
        if ($enrollment->status !== Status::ASSESSED || ! $enrollment->assessed_at) {
            throw ValidationException::withMessages(['status' => ___('enrollment.not_ready_for_clearance')]);
        }
        if (round((float) $enrollment->balance, 2) > 0) {
            throw ValidationException::withMessages(['balance' => ___('enrollment.balance_still_due')]);
        }

        DB::transaction(function () use ($enrollment, $userId) {
            $outcome = $this->fundingOutcome($enrollment);
            $enrollment->forceFill([
                'funding_outcome' => $outcome,
                'cleared_by'      => $userId,
                'cleared_at'      => now(),
            ]);
            $this->transition($enrollment, Status::CLEARED, 'cleared', ___('enrollment.outcome_' . $outcome), $userId);
        });
    }

    public function revokeClearance(Enrollment $enrollment, ?string $reason, int $userId): void
    {
        if ($enrollment->status !== Status::CLEARED) {
            throw ValidationException::withMessages(['status' => ___('enrollment.not_cleared')]);
        }
        $this->requireRemarks($reason);

        DB::transaction(function () use ($enrollment, $reason, $userId) {
            $enrollment->forceFill(['funding_outcome' => null, 'cleared_by' => null, 'cleared_at' => null]);
            $this->transition($enrollment, Status::ASSESSED, 'clearance_revoked', $reason, $userId);
        });
    }

    private function clearAssessment(Enrollment $enrollment): void
    {
        $enrollment->assessmentLines()->delete();
        $enrollment->forceFill([
            'assessed_total'    => 0,
            'subsidy_total'     => 0,
            'net_total'         => 0,
            'scholarship_total' => 0,
            'paid_total'        => 0,
            'balance'           => 0,
            'funding_outcome'   => null,
            'assessed_by'       => null,
            'assessed_at'       => null,
            'cleared_by'        => null,
            'cleared_at'        => null,
        ]);
    }

    private function refreshBalance(Enrollment $enrollment): void
    {
        $scholarship = round((float) $enrollment->scholarships()->whereNull('voided_at')->sum('amount'), 2);
        $paid = round((float) $enrollment->payments()->whereNull('voided_at')->sum('amount'), 2);

        $enrollment->forceFill([
            'scholarship_total' => $scholarship,
            'paid_total'        => $paid,
            'balance'           => round((float) $enrollment->net_total - $scholarship - $paid, 2),
        ])->save();
    }

    private function lapseClearanceIfShort(Enrollment $enrollment, int $userId): void
    {
        if ($enrollment->status === Status::CLEARED && round((float) $enrollment->balance, 2) > 0) {
            $enrollment->forceFill(['funding_outcome' => null, 'cleared_by' => null, 'cleared_at' => null]);
            $this->transition($enrollment, Status::ASSESSED, 'clearance_revoked', ___('enrollment.clearance_lapsed'), $userId);
        }
    }

    private function fundingOutcome(Enrollment $enrollment): string
    {
        $net = round((float) $enrollment->net_total, 2);
        $paid = round((float) $enrollment->paid_total, 2);
        $scholarship = round((float) $enrollment->scholarship_total, 2);

        if ($net <= 0 && $enrollment->free_tuition) {
            return 'ra_10931';
        }
        if ($paid <= 0 && $scholarship > 0) {
            return 'scholarship';
        }
        if ($scholarship > 0 && $paid > 0) {
            return 'scholarship_and_payment';
        }
        if ($net <= 0) {
            return 'no_amount_due';
        }

        return 'fully_paid';
    }

    private function guardOpenAccount(Enrollment $enrollment): void
    {
        if (! in_array($enrollment->status, [Status::ASSESSED, Status::CLEARED], true)) {
            throw ValidationException::withMessages(['status' => ___('enrollment.clearance_locked')]);
        }
    }

    private function guardAmount(Enrollment $enrollment, float $amount): void
    {
        $balance = round((float) $enrollment->balance, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ___('enrollment.amount_required')]);
        }
        if ($balance <= 0) {
            throw ValidationException::withMessages(['amount' => ___('enrollment.nothing_due')]);
        }
        if ($amount > $balance) {
            throw ValidationException::withMessages(['amount' => ___('enrollment.amount_exceeds_balance')]);
        }
    }

    private function guardOwned(Enrollment $enrollment, EnrollmentScholarship|EnrollmentPayment $row): void
    {
        abort_unless((int) $row->enrollment_id === (int) $enrollment->id, 404);
    }

    private function hasOpenAccount(Enrollment $enrollment): bool
    {
        return $enrollment->scholarships()->whereNull('voided_at')->exists()
            || $enrollment->payments()->whereNull('voided_at')->exists();
    }

    /**
     * @param  array{final_grade?: ?string, removal_on?: ?string, standing_passing?: mixed}  $row
     * @return array{final_grade: ?string, result: ?string, removal_on: ?string, standing_passing: ?bool, unofficial_drop: bool}
     */
    private function gradeFields(array $row): array
    {
        $grade = StudentManual::normalizeGrade($row['final_grade'] ?? null);
        $standing = filter_var($row['standing_passing'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $removal = $row['removal_on'] ?? null;

        if ($grade === 'INC' && ! $standing) {
            throw ValidationException::withMessages(['grades' => ___('enrollment.inc_needs_passing_standing')]);
        }
        if ($grade === '4.00' && blank($removal)) {
            throw ValidationException::withMessages(['grades' => ___('enrollment.removal_date_required')]);
        }

        return [
            'final_grade'      => $grade,
            'result'           => StudentManual::resultFor($grade),
            'removal_on'       => $grade === '4.00' ? $removal : null,
            'standing_passing' => $grade === 'INC' ? true : null,
            'unofficial_drop'  => false,
        ];
    }

    private function recalculateUnits(Enrollment $enrollment): void
    {
        $enrollment->total_units = $enrollment->enlisted()->get()->sum(fn ($c) => $c->units);
        $enrollment->save();
    }

    private function courseLabel(?Offering $offering): string
    {
        if (! $offering) {
            return '—';
        }

        return trim(($offering->subject->code ?? '') . ' ' . $offering->section_code);
    }

    private function guardCollege(Enrollment $enrollment): void
    {
        if ($enrollment->isTrainee()) {
            throw ValidationException::withMessages(['enrollment' => ___('enrollment.not_a_course_enrollment')]);
        }
    }

    private function guardEditable(Enrollment $enrollment): void
    {
        if (! $enrollment->loadIsEditable()) {
            throw ValidationException::withMessages(['status' => ___('enrollment.load_locked')]);
        }
    }

    private function guardCurriculum(?int $curriculumId, int $programId): void
    {
        if ($curriculumId && ! Curriculum::whereKey($curriculumId)->where('program_id', $programId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['curriculum_id' => ___('enrollment.curriculum_not_for_program')]);
        }
    }

    private function requireRemarks(?string $remarks): void
    {
        if (blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => ___('enrollment.remarks_required')]);
        }
    }

    private function transition(Enrollment $enrollment, string $to, string $action, ?string $remarks, int $userId): void
    {
        $from = $enrollment->status;
        $enrollment->status = $to;
        $enrollment->save();

        $this->log($enrollment, $from, $to, $action, $remarks, $userId);
    }

    public function recordReturnService(Enrollment $enrollment, string $action, array $data, int $userId): void
    {
        $this->guardCollege($enrollment);
        $date = $data['signed_on'] ?? now()->toDateString();
        $agreement = ReturnServiceAgreement::firstOrNew(['student_id' => $enrollment->student_id]);

        if ($action === 'guardian' && blank($data['guardian_name'] ?? null)) {
            throw ValidationException::withMessages(['guardian_name' => ___('enrollment.rsa_guardian_name')]);
        }
        if ($action === 'president' && (! $agreement->student_signed_on || ! $agreement->guardian_signed_on)) {
            throw ValidationException::withMessages(['rsa' => ___('enrollment.rsa_sign_first')]);
        }
        if ($action === 'waive' && blank($data['waiver_authority'] ?? null)) {
            throw ValidationException::withMessages(['waiver_authority' => ___('enrollment.rsa_waiver_authority')]);
        }

        match ($action) {
            'student' => $agreement->forceFill([
                'student_signed_on'   => $date,
                'student_recorded_by' => $userId,
            ]),
            'guardian' => $agreement->forceFill([
                'guardian_name'        => $data['guardian_name'],
                'guardian_signed_on'   => $date,
                'guardian_recorded_by' => $userId,
            ]),
            'president' => $agreement->forceFill([
                'president_signed_on'   => $date,
                'president_recorded_by' => $userId,
            ]),
            'waive' => $agreement->forceFill([
                'waived_on'        => $date,
                'waiver_authority' => $data['waiver_authority'],
                'waived_by'        => $userId,
            ]),
            'clear_waiver' => $agreement->forceFill([
                'waived_on'        => null,
                'waiver_authority' => null,
                'waived_by'        => null,
            ]),
        };

        $agreement->save();
        $this->log($enrollment, $enrollment->status, $enrollment->status, 'rsa_' . $action, null, $userId);
    }

    private function log(Enrollment $enrollment, ?string $from, string $to, string $action, ?string $remarks, ?int $userId): void
    {
        EnrollmentStatusLog::create([
            'enrollment_id' => $enrollment->id,
            'from_status'   => $from,
            'to_status'     => $to,
            'action'        => $action,
            'remarks'       => $remarks,
            'user_id'       => $userId,
            'created_at'    => now(),
        ]);
    }
}
