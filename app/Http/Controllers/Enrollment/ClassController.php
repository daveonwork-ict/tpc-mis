<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Enrollment as Status;
use App\Http\Controllers\Controller;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\EnrollmentCourse;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\OfferingAttendance;
use App\Models\Enrollment\TrainingBatch;
use App\Models\Staff\Staff;
use App\Services\Enrollment\EnrollmentService;
use App\Services\StudentManual;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function __construct(private EnrollmentService $service)
    {
    }

    public function index()
    {
        $staffId = $this->staffId();

        $offerings = Offering::with('subject', 'term', 'schedules', 'program')
            ->withCount('activeSeats as seats_taken')
            ->where('status', '!=', 'dissolved')
            ->when(! $this->seesAll(), fn ($q) => $q->where('faculty_id', $staffId ?: 0))
            ->orderByDesc('term_id')
            ->orderBy('section_code')
            ->get();

        $batches = TrainingBatch::with('program', 'session')
            ->withCount(['enrollments as seats_taken' => fn ($q) => $q->where('status', Status::ENROLLED)])
            ->when(! $this->seesAll(), fn ($q) => $q->where('trainer_id', $staffId ?: 0))
            ->orderByDesc('id')
            ->get();

        return view('backend.enrollment.classes.index', [
            'offerings' => $offerings,
            'batches'   => $batches,
            'linked'    => $this->seesAll() || (bool) $staffId,
        ]);
    }

    public function offering(Request $request, Offering $offering)
    {
        $this->guardOffering($offering);
        $offering->load('subject', 'term', 'schedules', 'faculty');
        $date = $this->date($request);

        $roster = EnrollmentCourse::with('enrollment.student.person', 'enrollment.term', 'subject')
            ->where('offering_id', $offering->id)
            ->where('status', 'enlisted')
            ->whereHas('enrollment', fn ($q) => $q->where('status', Status::ENROLLED))
            ->get()
            ->each(fn ($course) => StudentManual::closeExpiredRemoval($course))
            ->sortBy(fn ($course) => $course->enrollment->student->last_name)
            ->values();

        $marks = OfferingAttendance::where('offering_id', $offering->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $absence = [];
        foreach ($roster as $course) {
            $absence[$course->enrollment->student_id] = StudentManual::absence($offering, $course->enrollment->student_id);
        }

        return view('backend.enrollment.classes.show', [
            'kind'     => 'offering',
            'offering' => $offering,
            'batch'    => null,
            'title'    => trim(($offering->subject->code ?? '') . ' ' . $offering->section_code),
            'subtitle' => $offering->subject->name . ' · ' . ($offering->term->name ?? ''),
            'roster'   => $roster,
            'marks'    => $marks,
            'absence'  => $absence,
            'date'     => $date,
        ]);
    }

    public function saveAttendance(Request $request, Offering $offering)
    {
        $this->guardOffering($offering);
        $date = $this->date($request);
        $this->storeMarks($request, $this->offeringStudentIds($offering), $date, $offering->id, null);
        $warning = $this->absenceWarning($offering);

        return back()->with('success', ___('enrollment.attendance_saved'))->with('warning', $warning);
    }

    public function saveGrades(Request $request, Offering $offering)
    {
        $this->guardOffering($offering);
        $data = $request->validate([
            'grades'               => ['required', 'array'],
            'grades.*.final_grade'      => ['nullable', 'string', 'max:10'],
            'grades.*.removal_on'       => ['nullable', 'date'],
            'grades.*.standing_passing' => ['nullable', 'boolean'],
        ]);

        $rows = [];
        foreach ($data['grades'] as $id => $row) {
            $rows[(int) $id] = [
                'final_grade'      => $this->blank($row['final_grade'] ?? null),
                'removal_on'       => $row['removal_on'] ?? null,
                'standing_passing' => $request->boolean('grades.' . $id . '.standing_passing'),
            ];
        }

        $changed = $this->service->saveClassGrades($offering, $rows, auth()->id());

        return back()->with('success', $changed . ' ' . ___('enrollment.results_saved'));
    }

    public function batch(Request $request, TrainingBatch $batch)
    {
        $this->guardBatch($batch);
        $batch->load('program', 'session', 'trainer');
        $date = $this->date($request);

        $roster = Enrollment::with('student.person')
            ->where('batch_id', $batch->id)
            ->where('status', Status::ENROLLED)
            ->get()
            ->sortBy(fn ($row) => $row->student->last_name)
            ->values();

        $marks = OfferingAttendance::where('batch_id', $batch->id)
            ->whereDate('attendance_date', $date)
            ->pluck('status', 'student_id');

        return view('backend.enrollment.classes.show', [
            'kind'     => 'batch',
            'offering' => null,
            'batch'    => $batch,
            'title'    => $batch->name,
            'subtitle' => ($batch->program->name ?? '') . ($batch->schedule ? ' · ' . $batch->schedule : ''),
            'roster'   => $roster,
            'marks'    => $marks,
            'date'     => $date,
        ]);
    }

    public function saveBatchAttendance(Request $request, TrainingBatch $batch)
    {
        $this->guardBatch($batch);
        $date = $this->date($request);
        $ids = Enrollment::where('batch_id', $batch->id)->where('status', Status::ENROLLED)->pluck('student_id')->all();
        $this->storeMarks($request, $ids, $date, null, $batch->id);

        return back()->with('success', ___('enrollment.attendance_saved'));
    }

    public function saveCompetency(Request $request, TrainingBatch $batch)
    {
        $this->guardBatch($batch);
        $data = $request->validate([
            'competency'   => ['required', 'array'],
            'competency.*' => ['nullable', Rule::in(['competent', 'not_yet_competent'])],
        ]);

        $rows = Enrollment::where('batch_id', $batch->id)->where('status', Status::ENROLLED)->get()->keyBy('id');
        foreach ($data['competency'] as $id => $result) {
            $enrollment = $rows->get((int) $id);
            if (! $enrollment) {
                throw ValidationException::withMessages(['competency' => ___('enrollment.not_your_class')]);
            }
            $result = $this->blank($result);
            if ($result === $enrollment->competency_result) {
                continue;
            }
            $enrollment->forceFill([
                'competency_result' => $result,
                'competency_by'     => $result ? auth()->id() : null,
                'competency_at'     => $result ? now() : null,
            ])->save();
        }

        return back()->with('success', ___('enrollment.competency_saved'));
    }

    private function storeMarks(Request $request, array $studentIds, string $date, ?int $offeringId, ?int $batchId): void
    {
        $minutes = collect($request->input('late_minutes', []))
            ->map(fn ($value) => $value === '' || $value === null ? null : $value)
            ->all();
        $request->merge(['late_minutes' => $minutes]);

        $data = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'marks'           => ['nullable', 'array'],
            'marks.*'         => ['nullable', Rule::in(OfferingAttendance::STATUSES)],
            'late_minutes'    => ['nullable', 'array'],
            'late_minutes.*'  => ['nullable', 'integer', 'min:0', 'max:300'],
        ]);

        $allowed = array_map('intval', $studentIds);
        foreach ($data['marks'] ?? [] as $studentId => $status) {
            $studentId = (int) $studentId;
            if (! in_array($studentId, $allowed, true)) {
                throw ValidationException::withMessages(['marks' => ___('enrollment.not_your_class')]);
            }
            $key = [
                'offering_id'     => $offeringId,
                'batch_id'        => $batchId,
                'student_id'      => $studentId,
                'attendance_date' => $date,
            ];
            if ($this->blank($status) === null) {
                OfferingAttendance::where($key)->delete();
                continue;
            }
            $minutes = $status === 'late' ? ($data['late_minutes'][$studentId] ?? null) : null;
            OfferingAttendance::updateOrCreate($key, [
                'status'       => $status,
                'minutes_late' => $minutes === '' ? null : $minutes,
                'recorded_by'  => auth()->id(),
            ]);
        }
    }

    private function offeringStudentIds(Offering $offering): array
    {
        return EnrollmentCourse::where('offering_id', $offering->id)
            ->where('status', 'enlisted')
            ->whereHas('enrollment', fn ($q) => $q->where('status', Status::ENROLLED))
            ->pluck('enrollment_id')
            ->pipe(fn ($ids) => Enrollment::whereIn('id', $ids)->pluck('student_id')->all());
    }

    private function date(Request $request): string
    {
        $date = $request->validate([
            'attendance_date' => ['nullable', 'date', 'before_or_equal:today'],
        ])['attendance_date'] ?? now()->toDateString();

        return $date;
    }

    public function confirmAbsence(Request $request, Offering $offering)
    {
        $this->guardOffering($offering);
        $data = $request->validate(['student_id' => ['required', 'integer']]);
        $this->service->dropForAbsence($offering, (int) $data['student_id'], auth()->id());

        return back()->with('success', ___('enrollment.absence_drop_saved'));
    }

    private function absenceWarning(Offering $offering): ?string
    {
        $names = [];
        foreach ($this->offeringStudentIds($offering) as $studentId) {
            $report = StudentManual::absence($offering, (int) $studentId);
            if ($report && $report['drop']) {
                $names[] = (string) $studentId;
            }
        }

        return $names === [] ? null : ___('enrollment.absence_warning');
    }

    private function guardOffering(Offering $offering): void
    {
        if ($this->seesAll()) {
            return;
        }
        abort_unless($offering->faculty_id && (int) $offering->faculty_id === (int) $this->staffId(), 403);
    }

    private function guardBatch(TrainingBatch $batch): void
    {
        if ($this->seesAll()) {
            return;
        }
        abort_unless($batch->trainer_id && (int) $batch->trainer_id === (int) $this->staffId(), 403);
    }

    private function seesAll(): bool
    {
        return in_array((int) auth()->user()->role_id, [1, 2], true);
    }

    private function staffId(): ?int
    {
        return Staff::where('user_id', auth()->id())->value('id');
    }

    private function blank($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }
}
