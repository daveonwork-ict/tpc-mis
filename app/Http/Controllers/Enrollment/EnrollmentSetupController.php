<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\Enrollment\Curriculum;
use App\Models\Enrollment\CurriculumCourse;
use App\Models\Enrollment\CurriculumRequisite;
use App\Models\Enrollment\FeeSchedule;
use App\Models\Session;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentSetupController extends Controller
{
    use NullsBlankInput;

    public function __construct(private EnrollmentService $service)
    {
    }

    public function curricula(Request $request)
    {
        return view('backend.enrollment.setup.curricula', ['data' => [
            'title'      => ___('enrollment.curricula'),
            'curricula'  => Curriculum::with('program', 'effectiveSession')->withCount('courses')
                ->when($request->program_id, fn ($q, $id) => $q->where('program_id', $id))
                ->orderBy('program_id')->orderByDesc('id')->get(),
            'curriculum' => $request->edit ? Curriculum::findOrFail($request->edit) : new Curriculum(['status' => 'draft']),
            'programs'   => $this->chedPrograms(),
            'sessions'   => Session::orderBy('id')->get(),
        ]]);
    }

    public function saveCurriculum(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'                   => ['nullable', 'exists:curricula,id'],
            'program_id'           => ['required', Rule::exists('programs', 'id')->where('track', Admission::TRACK_CHED)],
            'name'                 => ['required', 'string', 'max:150'],
            'effective_session_id' => ['nullable', 'exists:sessions,id'],
            'authority_reference'  => ['nullable', 'string', 'max:255'],
            'status'               => ['required', Rule::in(Enrollment::CURRICULUM_STATUSES)],
            'notes'                => ['nullable', 'string', 'max:2000'],
        ]));

        $curriculum = Curriculum::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('enrollment.setup.curricula.show', $curriculum)->with('success', ___('enrollment.saved'));
    }

    public function curriculum(Curriculum $curriculum)
    {
        $curriculum->load('program', 'effectiveSession', 'courses.subject', 'courses.requisites.subject');
        $usedIds = $curriculum->courses->pluck('subject_id');

        return view('backend.enrollment.setup.curriculum', ['data' => [
            'title'      => $curriculum->name,
            'curriculum' => $curriculum,
            'groups'     => $curriculum->courses->groupBy(fn ($c) => $c->year_level . '|' . $c->semester),
            'available'  => Subject::active()->whereNotIn('id', $usedIds)->orderBy('code')->get(),
        ]]);
    }

    public function addCurriculumCourse(Request $request, Curriculum $curriculum)
    {
        $data = $this->nullBlanks($request->validate([
            'subject_id'    => ['nullable', 'required_without:new_name', 'exists:subjects,id',
                Rule::unique('curriculum_courses', 'subject_id')->where('curriculum_id', $curriculum->id)],
            'new_code'      => ['nullable', 'required_with:new_name', 'string', 'max:30', 'unique:subjects,code'],
            'new_name'      => ['nullable', 'required_without:subject_id', 'string', 'max:150'],
            'lecture_units' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'lab_units'     => ['nullable', 'numeric', 'min:0', 'max:12'],
            'year_level'    => ['required', Rule::in(Enrollment::YEAR_LEVELS)],
            'semester'      => ['required', Rule::in(Admission::SEMESTERS)],
        ], [], [
            'subject_id' => ___('enrollment.course'),
            'new_code'   => ___('enrollment.course_code'),
            'new_name'   => ___('enrollment.course_title'),
        ]));

        $subjectId = $data['subject_id'] ?? Subject::create([
            'code'          => $data['new_code'],
            'name'          => $data['new_name'],
            'lecture_units' => $data['lecture_units'] ?? 0,
            'lab_units'     => $data['lab_units'] ?? 0,
            'type'          => 1,
            'status'        => 1,
        ])->id;

        CurriculumCourse::create([
            'curriculum_id' => $curriculum->id,
            'subject_id'    => $subjectId,
            'year_level'    => $data['year_level'],
            'semester'      => $data['semester'],
            'sort_order'    => (int) $curriculum->courses()->max('sort_order') + 1,
        ]);

        return back()->with('success', ___('enrollment.course_added_to_curriculum'))
            ->with('last_year', $data['year_level'])->with('last_semester', $data['semester']);
    }

    public function removeCurriculumCourse(CurriculumCourse $course)
    {
        CurriculumRequisite::whereHas('curriculumCourse', fn ($q) => $q->where('curriculum_id', $course->curriculum_id))
            ->where('subject_id', $course->subject_id)->delete();
        $course->delete();

        return back()->with('success', ___('enrollment.course_removed_from_curriculum'));
    }

    public function saveRequisite(Request $request, CurriculumCourse $course)
    {
        $data = $request->validate([
            'subject_id' => ['required', Rule::exists('curriculum_courses', 'subject_id')->where('curriculum_id', $course->curriculum_id),
                Rule::notIn([$course->subject_id]),
                Rule::unique('curriculum_requisites', 'subject_id')->where('curriculum_course_id', $course->id)],
            'type'       => ['required', Rule::in(Enrollment::REQUISITE_TYPES)],
        ], [], ['subject_id' => ___('enrollment.requisite_course')]);

        $course->requisites()->create($data);

        return back()->with('success', ___('enrollment.requisite_added'));
    }

    public function deleteRequisite(CurriculumRequisite $requisite)
    {
        $requisite->delete();

        return back()->with('success', ___('enrollment.requisite_removed'));
    }

    public function courses(Request $request)
    {
        return view('backend.enrollment.setup.courses', ['data' => [
            'title'   => ___('enrollment.course_catalog'),
            'courses' => Subject::query()
                ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")))
                ->orderBy('code')->paginate(30)->withQueryString(),
            'course'  => $request->edit ? Subject::findOrFail($request->edit) : new Subject(['status' => 1]),
        ]]);
    }

    public function saveCourse(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'            => ['nullable', 'exists:subjects,id'],
            'code'          => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($request->id)],
            'name'          => ['required', 'string', 'max:150'],
            'lecture_units' => ['required', 'numeric', 'min:0', 'max:12'],
            'lab_units'     => ['required', 'numeric', 'min:0', 'max:12'],
            'status'        => ['required', Rule::in([0, 1])],
        ]));

        $id = $data['id'] ?? null;
        unset($data['id']);
        $id ? Subject::findOrFail($id)->update($data) : Subject::create($data + ['type' => 1]);

        return redirect()->route('enrollment.setup.courses')->with('success', ___('enrollment.saved'));
    }

    public function fees(Request $request)
    {
        return view('backend.enrollment.setup.fees', ['data' => [
            'title'    => ___('enrollment.fee_schedule'),
            'fees'     => FeeSchedule::with('program', 'term')->orderBy('sort_order')->orderBy('id')->get(),
            'fee'      => $request->edit ? FeeSchedule::findOrFail($request->edit) : new FeeSchedule(['status' => 1, 'basis' => 'fixed', 'category' => 'miscellaneous']),
            'programs' => Program::where('status', 1)->orderBy('name')->get(),
            'terms'    => Term::with('session')->orderByDesc('session_id')->orderBy('id')->get(),
        ]]);
    }

    public function saveFee(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'                   => ['nullable', 'exists:fee_schedules,id'],
            'name'                 => ['required', 'string', 'max:150'],
            'category'             => ['required', Rule::in(Enrollment::FEE_CATEGORIES)],
            'basis'                => ['required', Rule::in(Enrollment::FEE_BASES)],
            'amount'               => ['required', 'numeric', 'min:0', 'max:9999999'],
            'program_id'           => ['nullable', 'exists:programs,id'],
            'term_id'              => ['nullable', 'exists:terms,id'],
            'free_tuition_covered' => ['nullable', 'boolean'],
            'sort_order'           => ['nullable', 'integer', 'min:0', 'max:999'],
            'status'               => ['required', Rule::in([0, 1])],
        ]));
        $data['free_tuition_covered'] = (bool) ($data['free_tuition_covered'] ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        FeeSchedule::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('enrollment.setup.fees')->with('success', ___('enrollment.saved'));
    }

    public function deleteFee(FeeSchedule $fee)
    {
        $fee->delete();

        return back()->with('success', ___('enrollment.fee_deleted'));
    }

    public function settings()
    {
        $openTerm = Term::with('session')->where('enrollment_open', true)->first() ?? Term::with('session')->latest('id')->first();

        return view('backend.enrollment.setup.settings', ['data' => [
            'title'   => ___('enrollment.enrollment_settings'),
            'format'  => $this->service->settingValue('student_number_format') ?: Enrollment::STUDENT_NUMBER_FORMAT,
            'padding' => $this->service->settingValue('student_number_padding') ?: Enrollment::STUDENT_NUMBER_PADDING,
            'next'    => $openTerm ? $this->service->nextStudentNumber($openTerm) : null,
            'terms'   => Term::with('session')->orderBy('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->get(),
        ]]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'student_number_format'  => ['required', 'string', 'max:40', 'regex:/\{SEQ\}/', 'regex:/^[A-Za-z0-9\-\/\.\{\}]+$/'],
            'student_number_padding' => ['required', 'integer', 'min:3', 'max:8'],
            'enrollment_open'        => ['nullable', 'array'],
            'enrollment_open.*'      => ['integer', 'exists:terms,id'],
        ], ['student_number_format.regex' => ___('enrollment.format_rules')]);

        $this->service->saveSetting('student_number_format', $data['student_number_format']);
        $this->service->saveSetting('student_number_padding', (string) $data['student_number_padding']);

        $open = $data['enrollment_open'] ?? [];
        Term::query()->update(['enrollment_open' => false]);
        if ($open) {
            Term::whereIn('id', $open)->update(['enrollment_open' => true]);
        }

        return back()->with('success', ___('enrollment.saved'));
    }

    private function chedPrograms()
    {
        return Program::where('track', Admission::TRACK_CHED)->orderBy('name')->get();
    }
}
