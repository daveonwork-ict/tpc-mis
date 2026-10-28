<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment as Status;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\Enrollment\Curriculum;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\OfferingSchedule;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfferingController extends Controller
{
    use NullsBlankInput;

    public function index(Request $request)
    {
        $terms = $this->terms();
        $termId = $request->term_id ?: ($terms->firstWhere('enrollment_open', true)->id ?? $terms->first()->id ?? null);

        $offerings = Offering::select('offerings.*')
            ->with('subject', 'program', 'faculty', 'schedules')
            ->withCount(['enrollmentCourses as seats' => fn ($q) => $q->where('enrollment_courses.status', 'enlisted')
                ->whereHas('enrollment', fn ($e) => $e->whereNotIn('status', Status::CLOSED_STATUSES))])
            ->join('subjects', 'subjects.id', '=', 'offerings.subject_id')
            ->where('offerings.term_id', $termId)
            ->when($request->program_id, fn ($q, $id) => $q->where('offerings.program_id', $id))
            ->when($request->section_code, fn ($q, $code) => $q->where('offerings.section_code', $code))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('subjects.code', 'like', "%{$term}%")->orWhere('subjects.name', 'like', "%{$term}%")))
            ->orderBy('offerings.section_code')->orderBy('subjects.code')
            ->paginate(40)->withQueryString();

        return view('backend.enrollment.offerings.index', ['data' => [
            'title'     => ___('enrollment.course_offerings'),
            'offerings' => $offerings,
            'termId'    => (int) $termId,
            'terms'     => $terms,
            'programs'  => $this->programs(),
            'sections'  => Offering::where('term_id', $termId)->distinct()->orderBy('section_code')->pluck('section_code'),
            'curricula' => Curriculum::with('program')->active()->orderBy('program_id')->get(),
        ]]);
    }

    public function create(Request $request)
    {
        return $this->form(new Offering([
            'term_id'      => $request->term_id,
            'section_code' => $request->section_code,
            'status'       => 'open',
        ]));
    }

    public function edit(Offering $offering)
    {
        return $this->form($offering->load('schedules'));
    }

    public function store(Request $request)
    {
        [$data, $slots] = $this->validated($request);

        $offering = DB::transaction(function () use ($data, $slots) {
            $offering = Offering::create($data);
            $this->syncSchedules($offering, $slots);

            return $offering;
        });

        return redirect()->route('enrollment.offerings.index', ['term_id' => $offering->term_id, 'section_code' => $offering->section_code])
            ->with('success', ___('enrollment.offering_saved'));
    }

    public function update(Request $request, Offering $offering)
    {
        [$data, $slots] = $this->validated($request, $offering);

        DB::transaction(function () use ($offering, $data, $slots) {
            $offering->update($data);
            $this->syncSchedules($offering, $slots);
        });

        return redirect()->route('enrollment.offerings.index', ['term_id' => $offering->term_id, 'section_code' => $offering->section_code])
            ->with('success', ___('enrollment.offering_saved'));
    }

    public function delete(Offering $offering)
    {
        if ($offering->enrollmentCourses()->exists()) {
            return back()->with('danger', ___('enrollment.offering_has_students'));
        }

        $offering->delete();

        return back()->with('success', ___('enrollment.offering_deleted'));
    }

    /**
     * Creates one offering per curriculum course for a year level and semester, all under one section code.
     */
    public function generate(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'term_id'       => ['required', 'exists:terms,id'],
            'curriculum_id' => ['required', 'exists:curricula,id'],
            'year_level'    => ['required', Rule::in(Status::YEAR_LEVELS)],
            'section_code'  => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'capacity'      => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]));

        $term = Term::findOrFail($data['term_id']);
        $curriculum = Curriculum::findOrFail($data['curriculum_id']);
        $courses = $curriculum->courses()->where('year_level', $data['year_level'])->where('semester', $term->semester)->get();

        if ($courses->isEmpty()) {
            return back()->with('danger', ___('enrollment.no_curriculum_courses_for_term'));
        }

        $created = 0;
        foreach ($courses as $course) {
            $offering = Offering::firstOrCreate(
                ['term_id' => $term->id, 'subject_id' => $course->subject_id, 'section_code' => $data['section_code']],
                ['program_id' => $curriculum->program_id, 'year_level' => $data['year_level'], 'capacity' => $data['capacity'] ?? null, 'status' => 'open']
            );
            $created += $offering->wasRecentlyCreated ? 1 : 0;
        }

        return redirect()->route('enrollment.offerings.index', ['term_id' => $term->id, 'section_code' => $data['section_code']])
            ->with('success', $created . ' ' . ___('enrollment.offerings_created') . ' ' . ___('enrollment.add_schedules_next'));
    }

    private function form(Offering $offering)
    {
        return view('backend.enrollment.offerings.form', ['data' => [
            'title'    => $offering->exists ? ___('enrollment.edit_offering') : ___('enrollment.add_offering'),
            'offering' => $offering,
            'terms'    => $this->terms(),
            'subjects' => Subject::active()->orderBy('code')->get(),
            'programs' => $this->programs(),
            'faculty'  => Staff::where('status', 1)->orderBy('last_name')->get(),
        ]]);
    }

    /**
     * @return array{0: array, 1: array<int, array>}
     */
    private function validated(Request $request, ?Offering $offering = null): array
    {
        $data = $this->nullBlanks($request->validate([
            'term_id'              => ['required', 'exists:terms,id'],
            'subject_id'           => ['required', 'exists:subjects,id',
                Rule::unique('offerings')->where('term_id', $request->term_id)->where('section_code', $request->section_code)->ignore($offering?->id)],
            'program_id'           => ['nullable', Rule::exists('programs', 'id')->where('track', Admission::TRACK_CHED)],
            'section_code'         => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'year_level'           => ['nullable', Rule::in(Status::YEAR_LEVELS)],
            'capacity'             => ['nullable', 'integer', 'min:1', 'max:1000'],
            'faculty_id'           => ['nullable', 'exists:staff,id'],
            'status'               => ['required', Rule::in(Status::OFFERING_STATUSES)],
            'remarks'              => ['nullable', 'string', 'max:255'],
            'schedules'            => ['nullable', 'array', 'max:8'],
            'schedules.*.day'      => ['nullable', Rule::in(array_keys(Status::DAYS))],
            'schedules.*.start'    => ['nullable', 'required_with:schedules.*.day', 'date_format:H:i'],
            'schedules.*.end'      => ['nullable', 'required_with:schedules.*.day', 'date_format:H:i'],
            'schedules.*.room'     => ['nullable', 'string', 'max:50'],
            'schedules.*.type'     => ['nullable', Rule::in(Status::SCHEDULE_TYPES)],
        ], ['subject_id.unique' => ___('enrollment.offering_exists')]));

        $slots = collect($data['schedules'] ?? [])->filter(fn ($s) => filled($s['day'] ?? null))->values()
            ->map(fn ($s) => $this->nullBlanks($s))->all();
        unset($data['schedules']);

        foreach ($slots as $i => $slot) {
            if ($slot['end'] <= $slot['start']) {
                throw ValidationException::withMessages(["schedules.$i.end" => ___('enrollment.end_after_start')]);
            }
        }

        $this->guardDoubleBooking($data, $slots, $offering);

        return [$data, $slots];
    }

    private function guardDoubleBooking(array $data, array $slots, ?Offering $offering): void
    {
        if (! $slots) {
            return;
        }

        $others = OfferingSchedule::with('offering.subject')
            ->whereHas('offering', fn ($q) => $q->where('term_id', $data['term_id'])->where('status', '!=', 'dissolved')
                ->when($offering, fn ($w) => $w->where('id', '!=', $offering->id)))
            ->get();

        foreach ($slots as $i => $slot) {
            $candidate = new OfferingSchedule(['day' => (int) $slot['day'], 'start_time' => $slot['start'] . ':00', 'end_time' => $slot['end'] . ':00']);

            foreach ($others as $other) {
                if (! $candidate->overlaps($other)) {
                    continue;
                }

                $label = ($other->offering->subject->code ?? '') . ' ' . $other->offering->section_code;
                if (filled($slot['room'] ?? null) && strcasecmp(trim($slot['room']), trim((string) $other->room)) === 0) {
                    throw ValidationException::withMessages(["schedules.$i.room" => ___('enrollment.room_double_booked') . ': ' . $label]);
                }
                if (! empty($data['faculty_id']) && (int) $other->offering->faculty_id === (int) $data['faculty_id']) {
                    throw ValidationException::withMessages(['faculty_id' => ___('enrollment.faculty_double_booked') . ': ' . $label]);
                }
            }
        }
    }

    private function syncSchedules(Offering $offering, array $slots): void
    {
        $offering->schedules()->delete();

        foreach ($slots as $slot) {
            $offering->schedules()->create([
                'day'        => $slot['day'],
                'start_time' => $slot['start'],
                'end_time'   => $slot['end'],
                'room'       => $slot['room'] ?? null,
                'type'       => $slot['type'] ?? null,
            ]);
        }
    }

    private function terms()
    {
        return Term::with('session')->where('status', 1)->orderByDesc('enrollment_open')->orderBy('session_id')
            ->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->get();
    }

    private function programs()
    {
        return Program::where('track', Admission::TRACK_CHED)->orderBy('name')->get();
    }
}
