<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment as Status;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Admissions\Application;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\Enrollment\CourseCredit;
use App\Models\Enrollment\Curriculum;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\EnrollmentCourse;
use App\Models\Enrollment\EnrollmentPayment;
use App\Models\Enrollment\EnrollmentScholarship;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\TrainingBatch;
use App\Models\StudentInfo\Student;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    use NullsBlankInput;

    public function __construct(private EnrollmentService $service)
    {
    }

    public function index(Request $request)
    {
        $terms = $this->terms();
        $termId = $request->has('term_id') ? $request->term_id : ($terms->firstWhere('enrollment_open', true)->id ?? null);

        $base = Enrollment::query()
            ->when($termId, fn ($q, $id) => $q->where(fn ($w) => $w->where('term_id', $id)->orWhere('track', Admission::TRACK_TESDA)))
            ->when($request->program_id, fn ($q, $id) => $q->where('program_id', $id))
            ->when($request->year_level, fn ($q, $level) => $q->where('year_level', $level))
            ->when($request->q, function ($q, $term) {
                $q->where(fn ($w) => $w->where('enrollment_no', 'like', "%{$term}%")
                    ->orWhereHas('student', fn ($s) => $s->where('student_code', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")));
            });

        return view('backend.enrollment.enrollments.index', ['data' => [
            'title'       => ___('enrollment.enrollments'),
            'enrollments' => (clone $base)->with('student', 'program', 'term', 'batch')
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->latest('id')->paginate(20)->withQueryString(),
            'counts'      => (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'termId'      => $termId,
            'terms'       => $terms,
            'programs'    => Program::where('status', 1)->orderBy('name')->get(),
            'awaiting'    => $this->awaitingApplications()->count(),
        ]]);
    }

    public function create(Request $request)
    {
        $application = $request->application ? Application::with('person', 'program', 'term')->findOrFail($request->application) : null;
        $student = $request->student ? Student::with('person')->findOrFail($request->student) : null;

        if (! $application && ! $student) {
            return view('backend.enrollment.enrollments.pick', ['data' => [
                'title'        => ___('enrollment.start_enrollment'),
                'applications' => $this->awaitingApplications()->with('person', 'program', 'term')->orderBy('decided_at')->get(),
                'students'     => $request->q
                    ? Student::whereNotNull('person_id')->where(fn ($w) => $w->where('student_code', 'like', "%{$request->q}%")
                        ->orWhere('first_name', 'like', "%{$request->q}%")->orWhere('last_name', 'like', "%{$request->q}%"))
                        ->with(['enrollments' => fn ($q) => $q->with('term', 'batch', 'program')->limit(1)])->limit(25)->get()
                    : collect(),
            ]]);
        }

        $last = $student?->enrollments()->with('term', 'batch')->whereNotIn('status', Status::CLOSED_STATUSES)->first();
        $openTerm = Term::where('enrollment_open', true)->orderBy('session_id')->first();
        $track = $application?->track === Admission::TRACK_TESDA || $last?->track === Admission::TRACK_TESDA
            ? Admission::TRACK_TESDA
            : Admission::TRACK_CHED;

        $defaults = $application ? [
            'term_id'      => $application->term?->enrollment_open ? $application->term_id : $openTerm?->id,
            'program_id'   => $application->program_id,
            'year_level'   => 1,
            'student_type' => Status::APPLICANT_TO_STUDENT_TYPE[$application->applicant_type] ?? 'new',
        ] : [
            'term_id'       => $openTerm?->id,
            'program_id'    => $last->program_id ?? null,
            'curriculum_id' => $last->curriculum_id ?? null,
            'year_level'    => $last->year_level ?? 1,
            'student_type'  => 'continuing',
        ];
        $defaults['track'] = $track;
        if ($track === Admission::TRACK_TESDA) {
            $defaults['student_type'] = $application?->applicant_type ?: 'returning_trainee';
            $defaults['year_level'] = null;
        } else {
            $defaults['curriculum_id'] ??= Curriculum::active()->where('program_id', $defaults['program_id'])->latest('id')->value('id');
        }

        return view('backend.enrollment.enrollments.create', ['data' => [
            'title'       => ___('enrollment.start_enrollment'),
            'application' => $application,
            'student'     => $student,
            'last'        => $last,
            'defaults'    => $defaults,
            'terms'       => Term::with('session')->where('enrollment_open', true)->orderBy('session_id')->get(),
            'programs'    => $track === Admission::TRACK_TESDA
                ? Program::where('track', Admission::TRACK_TESDA)->where('status', 1)->orderBy('name')->get()
                : $this->programs(),
            'batches'     => $track === Admission::TRACK_TESDA
                ? TrainingBatch::with('program')->open()
                    ->when($defaults['program_id'], fn ($q, $id) => $q->where('program_id', $id))
                    ->orderBy('name')->get()
                : collect(),
            'curricula'   => Curriculum::active()->with('program')->orderBy('program_id')->get(),
        ]]);
    }

    public function store(Request $request)
    {
        $program = Program::find($request->program_id);
        if ($program && $program->track === Admission::TRACK_TESDA) {
            return $this->storeTrainee($request, $program);
        }

        $data = $this->nullBlanks($request->validate([
            'application_id' => ['nullable', 'required_without:student_id', 'exists:applications,id'],
            'student_id'     => ['nullable', 'required_without:application_id', 'exists:students,id'],
            'term_id'        => ['required', Rule::exists('terms', 'id')->where('enrollment_open', 1)],
            'program_id'     => ['required', Rule::exists('programs', 'id')->where('track', Admission::TRACK_CHED)->where('status', 1)],
            'curriculum_id'  => ['nullable', 'exists:curricula,id'],
            'year_level'     => ['required', Rule::in(Status::YEAR_LEVELS)],
            'block'          => ['nullable', 'string', 'max:30'],
            'student_type'   => ['required', Rule::in(Status::STUDENT_TYPES)],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ], ['term_id.exists' => ___('enrollment.term_not_open')]));

        $application = isset($data['application_id']) ? Application::findOrFail($data['application_id']) : null;
        $student = ! $application && isset($data['student_id']) ? Student::findOrFail($data['student_id']) : null;

        $enrollment = $this->service->start(
            collect($data)->except('application_id', 'student_id')->all(),
            auth()->id(), $application, $student
        );

        return redirect()->route('enrollment.enrollments.show', $enrollment)->with('success', ___('enrollment.enrollment_started'));
    }

    private function storeTrainee(Request $request, Program $program)
    {
        $data = $this->nullBlanks($request->validate([
            'application_id' => ['nullable', 'required_without:student_id', 'exists:applications,id'],
            'student_id'     => ['nullable', 'required_without:application_id', 'exists:students,id'],
            'program_id'     => ['required', Rule::exists('programs', 'id')->where('track', Admission::TRACK_TESDA)->where('status', 1)],
            'batch_id'       => ['required', Rule::exists('training_batches', 'id')->where('program_id', $program->id)->where('status', TrainingBatch::OPEN)],
            'student_type'   => ['required', Rule::in(Admission::applicantTypes(Admission::TRACK_TESDA))],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ], ['batch_id.exists' => ___('enrollment.batch_not_open')]));

        $application = isset($data['application_id']) ? Application::findOrFail($data['application_id']) : null;
        $student = ! $application && isset($data['student_id']) ? Student::findOrFail($data['student_id']) : null;

        $enrollment = $this->service->start(
            collect($data)->except('application_id', 'student_id')->all() + ['track' => Admission::TRACK_TESDA],
            auth()->id(), $application, $student
        );

        return redirect()->route('enrollment.enrollments.show', $enrollment)->with('success', ___('enrollment.enrollment_started'));
    }

    public function show(Request $request, Enrollment $enrollment)
    {
        $enrollment->load('student.person', 'student.parent', 'student.returnServiceAgreement', 'term.session', 'program.department', 'batch.session', 'batch.trainer', 'curriculum', 'application',
            'courses.offering.schedules', 'courses.offering.faculty', 'courses.subject', 'assessmentLines',
            'scholarships.recorder', 'payments.recorder', 'academicScholarships.recorder',
            'statusLogs.user', 'adviser', 'assessor', 'clearer', 'validator');

        if ($enrollment->status === Status::ENROLLED) {
            $enrollment->courses->each(fn ($course) => \App\Services\StudentManual::closeExpiredRemoval($course));
        }

        $candidates = collect();
        $blocks = collect();
        if ($enrollment->loadIsEditable() && ! $enrollment->isTrainee()) {
            $context = $this->service->context($enrollment);
            $enlistedSubjects = $context['enlisted']->pluck('subject_id');

            $candidates = Offering::select('offerings.*')
                ->with('subject', 'schedules', 'program', 'faculty')
                ->withCount(['enrollmentCourses as seats' => fn ($q) => $q->where('enrollment_courses.status', 'enlisted')
                    ->whereHas('enrollment', fn ($e) => $e->whereNotIn('status', Status::CLOSED_STATUSES))])
                ->where('offerings.term_id', $enrollment->term_id)
                ->where('offerings.status', 'open')
                ->whereNotIn('offerings.subject_id', $enlistedSubjects)
                ->when(! $request->boolean('all'), fn ($q) => $q->where(fn ($w) => $w->whereNull('offerings.program_id')->orWhere('offerings.program_id', $enrollment->program_id)))
                ->when($request->block, fn ($q, $block) => $q->where('offerings.section_code', $block))
                ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('subjects.code', 'like', "%{$term}%")->orWhere('subjects.name', 'like', "%{$term}%")))
                ->join('subjects', 'subjects.id', '=', 'offerings.subject_id')
                ->orderBy('offerings.section_code')->orderBy('subjects.code')
                ->limit(60)->get()
                ->each(fn ($o) => $o->issues = $this->service->issuesFor($enrollment, $o, $context));

            $blocks = Offering::where('term_id', $enrollment->term_id)->where('status', 'open')
                ->where(fn ($w) => $w->whereNull('program_id')->orWhere('program_id', $enrollment->program_id))
                ->selectRaw('section_code, COUNT(*) as courses')->groupBy('section_code')->orderBy('section_code')->get();
        }

        return view('backend.enrollment.enrollments.show', ['data' => [
            'title'       => $enrollment->enrollment_no,
            'enrollment'  => $enrollment,
            'candidates'  => $candidates,
            'blocks'      => $blocks,
            'curricula'   => Curriculum::active()->where('program_id', $enrollment->program_id)->get(),
            'canOverride' => hasPermission('enrollment_override'),
        ]]);
    }

    public function updateDetails(Request $request, Enrollment $enrollment)
    {
        $data = $this->nullBlanks($request->validate([
            'curriculum_id' => ['nullable', 'exists:curricula,id'],
            'year_level'    => ['required', Rule::in(Status::YEAR_LEVELS)],
            'block'         => ['nullable', 'string', 'max:30'],
            'student_type'       => ['required', Rule::in(Status::STUDENT_TYPES)],
            'academic_standing'  => ['required', Rule::in(\App\Services\StudentManual::STANDINGS)],
            'remarks'            => ['nullable', 'string', 'max:1000'],
        ]));
        $data['is_graduating'] = $request->boolean('is_graduating');

        $this->service->updateDetails($enrollment, $data, auth()->id());

        return back()->with('success', ___('enrollment.saved'));
    }

    public function addCourse(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'offering_id'     => ['required', 'exists:offerings,id'],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->enlist($enrollment, Offering::with('subject', 'schedules', 'program')->findOrFail($data['offering_id']),
            auth()->id(), $data['override_reason'] ?? null, hasPermission('enrollment_override'));

        return back()->with('success', ___('enrollment.course_enlisted'));
    }

    public function loadBlock(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['block' => ['required', 'string', 'max:30']]);

        $result = $this->service->loadBlock($enrollment, $data['block'], auth()->id());

        return redirect()->route('enrollment.enrollments.show', $enrollment)
            ->with('success', count($result['added']) . ' ' . ___('enrollment.courses_enlisted_from_block'))
            ->with('block_skipped', $result['skipped']);
    }

    public function dropCourse(Enrollment $enrollment, EnrollmentCourse $course)
    {
        $action = $this->service->drop($enrollment, $course, auth()->id());

        return back()->with('success', ___($action === 'unofficial_drop' ? 'enrollment.unofficial_drop_saved' : 'enrollment.course_removed'));
    }

    public function submitForAssessment(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['overload_reason' => ['nullable', 'string', 'max:255']]);

        $this->service->submitForAssessment($enrollment, $data['overload_reason'] ?? null, hasPermission('enrollment_override'), auth()->id());

        return back()->with('success', ___('enrollment.sent_for_assessment'));
    }

    public function assess(Request $request, Enrollment $enrollment)
    {
        $this->service->assess($enrollment, $request->boolean('free_tuition'), auth()->id());

        return back()->with('success', ___('enrollment.assessment_generated'));
    }

    public function addScholarship(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'program'   => ['required', Rule::in(Status::SCHOLARSHIP_PROGRAMS)],
            'name'      => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:60'],
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'remarks'   => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->addScholarship($enrollment, $this->nullBlanks($data), auth()->id());

        return back()->with('success', ___('enrollment.scholarship_recorded'));
    }

    public function removeScholarship(Request $request, Enrollment $enrollment, EnrollmentScholarship $scholarship)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $this->service->removeScholarship($enrollment, $scholarship, $data['reason'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.scholarship_removed'));
    }

    public function recordPayment(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'or_number' => ['required', 'string', 'max:40'],
            'paid_on'   => ['required', 'date'],
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'remarks'   => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->recordPayment($enrollment, $this->nullBlanks($data), auth()->id());

        return back()->with('success', ___('enrollment.payment_recorded'));
    }

    public function voidPayment(Request $request, Enrollment $enrollment, EnrollmentPayment $payment)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $this->service->voidPayment($enrollment, $payment, $data['reason'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.payment_voided'));
    }

    public function issueClearance(Enrollment $enrollment)
    {
        $this->service->issueClearance($enrollment, auth()->id());

        return back()->with('success', ___('enrollment.clearance_issued'));
    }

    public function revokeClearance(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->service->revokeClearance($enrollment, $data['reason'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.clearance_revoked'));
    }

    public function validateEnrollment(Enrollment $enrollment)
    {
        $portal = $this->service->validateEnrollment($enrollment, auth()->id());

        return back()->with('success', ___('enrollment.enrollment_validated'))->with('portal', $portal);
    }

    public function returnService(Request $request, Enrollment $enrollment)
    {
        $request->merge([
            'signed_on' => $request->input('signed_on') === '' ? null : $request->input('signed_on'),
        ]);
        $data = $this->nullBlanks($request->validate([
            'action'           => ['required', Rule::in(['student', 'guardian', 'president', 'waive', 'clear_waiver'])],
            'signed_on'        => ['nullable', 'date', 'before_or_equal:today'],
            'guardian_name'    => ['nullable', 'string', 'max:190'],
            'waiver_authority' => ['nullable', 'string', 'max:500'],
        ]));

        $permission = in_array($data['action'], ['president', 'waive', 'clear_waiver'], true)
            ? 'enrollment_validate'
            : 'enrollment_advise';
        abort_unless(hasPermission($permission), 403);

        $this->service->recordReturnService($enrollment, $data['action'], $data, auth()->id());

        return back()->with('success', ___('enrollment.rsa_saved'));
    }

    public function academicScholarship(Request $request, Enrollment $enrollment)
    {
        abort_unless(hasPermission('enrollment_advise'), 403);

        $data = $this->nullBlanks($request->validate([
            'action'            => ['required', Rule::in(['record', 'remove'])],
            'program'           => ['nullable', 'string', 'max:30'],
            'sponsor'           => ['nullable', 'string', 'max:190'],
            'discipline_clear'  => ['nullable', 'boolean'],
            'reason'            => ['nullable', 'string', 'max:255'],
            'scholarship_id'    => ['nullable', 'integer'],
        ]));
        $data['discipline_clear'] = $request->boolean('discipline_clear');

        if ($data['action'] === 'remove') {
            $scholarship = \App\Models\Enrollment\AcademicScholarship::findOrFail($data['scholarship_id'] ?? 0);
            $this->service->removeAcademicScholarship($enrollment, $scholarship, $data['reason'] ?? null, auth()->id());
        } else {
            $this->service->recordAcademicScholarship($enrollment, $data, auth()->id());
        }

        return back()->with('success', ___('enrollment.academic_scholarship_saved'));
    }

    public function openPortal(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->status === Status::ENROLLED, 404);

        $data = $this->nullBlanks($request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]));

        $student = $enrollment->student;
        $student->person?->update(['email' => $data['email']]);
        $student->email = $data['email'];
        $student->save();

        $portal = $this->service->openStudentPortal($student->fresh());
        if (! $portal['opened'] && ($portal['reason'] ?? null) === 'email_taken') {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => ___('enrollment.email_taken')]);
        }

        return back()->with('portal', $portal)->with('success', $portal['opened'] ? ___('enrollment.portal_opened_short') : ___('enrollment.portal_already_open'));
    }

    public function openGuardian(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->status === Status::ENROLLED, 404);

        $data = $this->nullBlanks($request->validate([
            'guardian_email'    => ['required', 'email', 'max:190'],
            'guardian_name'     => ['required', 'string', 'max:190'],
            'guardian_mobile'   => ['nullable', 'string', 'max:30'],
            'guardian_relation' => ['nullable', 'string', 'max:60'],
        ]));

        $portal = $this->service->openGuardianPortal(
            $enrollment->student,
            $data['guardian_email'],
            $data['guardian_name'],
            $data['guardian_mobile'] ?? null,
            $data['guardian_relation'] ?? null
        );
        if (! $portal['opened'] && ($portal['reason'] ?? null) === 'email_taken') {
            throw \Illuminate\Validation\ValidationException::withMessages(['guardian_email' => ___('enrollment.email_taken')]);
        }

        return back()->with('guardian_portal', $portal)->with('success', $portal['opened'] ? ___('enrollment.guardian_opened_short') : ___('enrollment.guardian_already_open'));
    }

    public function returnToAdvising(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $this->service->returnToAdvising($enrollment, $data['remarks'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.returned_to_advising'));
    }

    public function changeStatus(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'action'  => ['required', Rule::in(['cancel', 'withdraw', 'reopen'])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->{$data['action']}($enrollment, $data['remarks'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.status_updated'));
    }

    public function saveResults(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'results'               => ['required', 'array'],
            'results.*.final_grade' => ['nullable', 'string', 'max:10'],
            'results.*.result'      => ['nullable', Rule::in(Status::RESULTS)],
        ]);

        $rows = collect($data['results'])->map(fn ($row) => $this->nullBlanks($row))->all();
        $changed = $this->service->saveResults($enrollment, $rows, auth()->id());

        return back()->with('success', $changed . ' ' . ___('enrollment.results_saved'));
    }

    public function eligibility(Request $request, Enrollment $enrollment)
    {
        $data = $this->nullBlanks($request->validate([
            'eligibility_met'     => ['required', 'in:0,1'],
            'eligibility_remarks' => ['nullable', 'required_if:eligibility_met,0', 'string', 'max:500'],
        ], [
            'eligibility_remarks.required_if' => ___('enrollment.eligibility_remarks_required'),
        ]));

        $this->service->recordEligibility($enrollment, $data['eligibility_met'] === '1', $data['eligibility_remarks'] ?? null, auth()->id());

        return back()->with('success', ___('enrollment.eligibility_saved'));
    }

    public function learner(Request $request, Enrollment $enrollment)
    {
        $data = $this->nullBlanks($request->validate([
            'uli'                 => ['nullable', 'string', 'max:30'],
            'highest_attainment'  => ['nullable', 'string', 'max:190'],
            'employment_status'   => ['nullable', 'string', 'max:190'],
        ]));

        $this->service->saveLearner($enrollment, $data, auth()->id());

        return back()->with('success', ___('enrollment.profile_saved'));
    }

    public function register(Enrollment $enrollment)
    {
        abort_unless($enrollment->isTrainee() && $enrollment->status === Status::ENROLLED, 404);

        $enrollment->load('student.person', 'batch.session', 'batch.trainer', 'program.department', 'application',
            'validator', 'clearer', 'assessmentLines', 'scholarships', 'payments');

        return view('backend.enrollment.enrollments.register', ['enrollment' => $enrollment]);
    }

    public function cor(Enrollment $enrollment)
    {
        abort_if($enrollment->isTrainee(), 404);
        abort_unless($enrollment->status === Status::ENROLLED, 404);

        $enrollment->load('student.person', 'term.session', 'program.department', 'curriculum', 'validator', 'clearer',
            'enlisted.offering.schedules', 'enlisted.offering.faculty', 'enlisted.subject', 'assessmentLines',
            'scholarships', 'payments');

        return view('backend.enrollment.enrollments.cor', ['enrollment' => $enrollment]);
    }

    public function evaluation(Request $request, Student $student)
    {
        $student->load('person', 'enrollments.term', 'enrollments.batch', 'enrollments.program', 'enrollments.curriculum');

        $curriculumId = $request->curriculum_id ?: $student->enrollments->whereNotIn('status', Status::CLOSED_STATUSES)->first()?->curriculum_id;
        $curriculum = $curriculumId ? Curriculum::with('program', 'courses.subject', 'courses.requisites.subject')->find($curriculumId) : null;

        $taken = EnrollmentCourse::with('enrollment.term')
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id)->whereNotIn('status', Status::CLOSED_STATUSES))
            ->where('status', 'enlisted')
            ->get()
            ->sortByDesc(fn ($c) => $c->enrollment->term_id)
            ->groupBy('subject_id');
        $credits = CourseCredit::with('subject', 'creditor')->where('student_id', $student->id)->get();

        $statusFor = function ($subjectId) use ($taken, $credits) {
            if ($credits->contains('subject_id', $subjectId)) {
                return ['credited', $credits->firstWhere('subject_id', $subjectId)->grade];
            }
            $attempts = $taken->get($subjectId, collect());
            if ($passed = $attempts->firstWhere('result', 'passed')) {
                return ['passed', $passed->final_grade];
            }
            $latest = $attempts->first();
            if ($latest && ! $latest->result) {
                return ['in_progress', $latest->enrollment->term->name];
            }

            return $latest ? [$latest->result, $latest->final_grade] : ['not_taken', null];
        };

        return view('backend.enrollment.enrollments.evaluation', ['data' => [
            'title'      => ___('enrollment.curriculum_evaluation'),
            'student'    => $student,
            'curriculum' => $curriculum,
            'curricula'  => Curriculum::with('program')->whereIn('status', ['active', 'retired'])->orderBy('program_id')->get(),
            'groups'     => $curriculum ? $curriculum->courses->groupBy(fn ($c) => $c->year_level . '|' . $c->semester) : collect(),
            'statusFor'  => $statusFor,
            'credits'    => $credits,
            'gwa'        => $this->service->gwa($student),
            'subjects'   => $curriculum ? $curriculum->courses->pluck('subject')->sortBy('code') : Subject::active()->orderBy('code')->get(),
        ]]);
    }

    public function saveCredit(Request $request, Student $student)
    {
        $data = $this->nullBlanks($request->validate([
            'subject_id'    => ['required', 'exists:subjects,id'],
            'source_school' => ['nullable', 'string', 'max:255'],
            'source_course' => ['nullable', 'string', 'max:255'],
            'grade'         => ['nullable', 'string', 'max:10'],
            'remarks'       => ['nullable', 'string', 'max:1000'],
        ]));

        $this->service->saveCredit($student, $data, auth()->id());

        return back()->with('success', ___('enrollment.credit_saved'));
    }

    public function deleteCredit(CourseCredit $credit)
    {
        $credit->delete();

        return back()->with('success', ___('enrollment.credit_removed'));
    }

    private function awaitingApplications()
    {
        return Application::whereIn('status', [Admission::ADMITTED, Admission::CONDITIONAL])
            ->whereDoesntHave('enrollments');
    }

    private function terms()
    {
        return Term::with('session')->where('status', 1)->orderBy('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->get();
    }

    private function programs()
    {
        return Program::where('track', Admission::TRACK_CHED)->where('status', 1)->orderBy('name')->get();
    }
}
