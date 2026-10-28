@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $student = $data['student'];
    $person = $student->person;
    $curriculum = $data['curriculum'];
    $statusFor = $data['statusFor'];
    $canCredit = hasPermission('enrollment_validate');
    $badges = ['passed' => 'success', 'credited' => 'success', 'in_progress' => 'info', 'failed' => 'danger', 'incomplete' => 'warning', 'dropped' => 'secondary', 'not_taken' => 'secondary'];

    $earned = 0;
    $required = 0;
    if ($curriculum) {
        foreach ($curriculum->courses as $c) {
            $required += $c->subject->total_units;
            if (in_array($statusFor($c->subject_id)[0], ['passed', 'credited'], true)) {
                $earned += $c->subject->total_units;
            }
        }
    }
@endphp
@section('content')
    <div class="page-content tpc-adm tpc-enr">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'parent' => [___('enrollment.enrollments'), route('enrollment.enrollments.index')],
            'actions' => hasPermission('enrollment_create')
                ? '<a href="' . route('enrollment.enrollments.create', ['student' => $student->id]) . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-plus"></i> ' . e(___('enrollment.enroll_for_a_term')) . '</a>'
                : null,
        ])

        <div class="tpc-adm-summary mb-24">
            <div class="tpc-adm-summary__main">
                <div class="tpc-adm-kicker">{{ $student->student_code ?: ___('enrollment.no_student_number_yet') }}</div>
                <h3>{{ $person?->full_name ?? $student->full_name }}</h3>
                <p>{{ $curriculum ? $curriculum->program->name . ' · ' . $curriculum->name : ___('enrollment.no_curriculum_assigned') }}</p>
            </div>
            <div class="tpc-adm-summary__meta">
                @if ($curriculum)
                    <div><span>{{ ___('enrollment.units_earned') }}</span><strong>{{ Enrollment::units($earned) }} / {{ Enrollment::units($required) }}</strong></div>
                    <div><span>{{ ___('enrollment.progress') }}</span><strong>{{ $required > 0 ? round($earned / $required * 100) : 0 }}%</strong></div>
                @endif
                <div><span>{{ ___('enrollment.gwa') }}</span><strong>{{ $data['gwa'] !== null ? number_format($data['gwa'], 2) : '—' }}</strong></div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <form method="get" class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                    <label class="form-label mb-0">{{ ___('enrollment.evaluate_against') }}</label>
                    <select name="curriculum_id" class="form-select ot-input w-auto" onchange="this.form.submit()">
                        <option value="">—</option>
                        @foreach ($data['curricula'] as $c)
                            <option value="{{ $c->id }}" @selected($curriculum?->id === $c->id)>{{ $c->program->name }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                </form>

                @forelse ($data['groups'] as $key => $courses)
                    @php([$year, $semester] = explode('|', $key))
                    <div class="card ot-card mb-24">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.year_' . $year) }} · {{ ___('admissions.semester_' . $semester) }}</h4></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered role-table tpc-enr-table">
                                    <thead class="thead">
                                        <tr>
                                            <th>{{ ___('enrollment.course') }}</th>
                                            <th class="text-center">{{ ___('enrollment.units') }}</th>
                                            <th>{{ ___('enrollment.requisites') }}</th>
                                            <th>{{ ___('common.status') }}</th>
                                            <th>{{ ___('enrollment.grade') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">
                                        @foreach ($courses as $course)
                                            @php([$state, $detail] = $statusFor($course->subject_id))
                                            <tr>
                                                <td><strong>{{ $course->subject->code }}</strong><div class="small">{{ $course->subject->name }}</div></td>
                                                <td class="text-center">{{ Enrollment::units($course->subject->total_units) }}</td>
                                                <td class="small">
                                                    {{ $course->requisites->map(fn ($r) => ($r->type === 'corequisite' ? ___('enrollment.co_short') . ' ' : '') . $r->subject->code)->implode(', ') ?: '—' }}
                                                </td>
                                                <td><span class="tpc-pill tpc-pill--{{ $badges[$state] ?? 'secondary' }}">{{ ___('enrollment.eval_' . $state) }}</span></td>
                                                <td>{{ $state === 'in_progress' ? '' : ($detail ?: '—') }}<span class="small text-muted">{{ $state === 'in_progress' ? $detail : '' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card ot-card">
                        <div class="card-body text-center gray-color p-5">{{ ___('enrollment.choose_curriculum_to_evaluate') }}</div>
                    </div>
                @endforelse
            </div>

            <div class="col-xl-4">
                <div class="card ot-card mb-24">
                    <div class="card-header">
                        <h4 class="mb-0">{{ ___('enrollment.credited_courses') }}</h4>
                        <p class="small text-muted mb-0 mt-1">{{ ___('enrollment.credited_courses_help') }}</p>
                    </div>
                    <div class="card-body">
                        @forelse ($data['credits'] as $credit)
                            <div class="tpc-adm-entry">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong>{{ $credit->subject->code }}</strong>
                                    @if ($canCredit)
                                        <form action="{{ route('enrollment.students.credits.delete', $credit) }}" method="post" onsubmit="return confirm(@js(___('enrollment.confirm_remove_credit')))">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0" type="submit">{{ ___('enrollment.remove') }}</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="small">{{ $credit->source_course ?: '—' }}@if ($credit->grade) · {{ $credit->grade }}@endif</div>
                                <div class="small text-muted">{{ $credit->source_school ?: '—' }} · {{ $credit->creditor->name ?? '—' }}</div>
                                @if ($credit->remarks)<div class="small">{{ $credit->remarks }}</div>@endif
                            </div>
                        @empty
                            <p class="text-muted">{{ ___('enrollment.no_credits') }}</p>
                        @endforelse

                        @if ($canCredit)
                            <form action="{{ route('enrollment.students.credits.save', $student) }}" method="post" class="mt-3">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">{{ ___('enrollment.course_to_credit') }}</label>
                                    <select name="subject_id" class="form-select ot-input" required>
                                        <option value="">—</option>
                                        @foreach ($data['subjects'] as $s)
                                            <option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ ___('enrollment.source_school') }}</label>
                                    <input type="text" name="source_school" class="form-control ot-input" maxlength="255" value="{{ $student->enrollments->first()?->application?->last_school }}">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-8">
                                        <label class="form-label">{{ ___('enrollment.source_course') }}</label>
                                        <input type="text" name="source_course" class="form-control ot-input" maxlength="255">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">{{ ___('enrollment.grade') }}</label>
                                        <input type="text" name="grade" class="form-control ot-input" maxlength="10">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                                    <input type="text" name="remarks" class="form-control ot-input" maxlength="1000">
                                </div>
                                <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.credit_course') }}</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card ot-card">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.enrollment_history') }}</h4></div>
                    <div class="card-body">
                        @forelse ($student->enrollments as $e)
                            <a href="{{ route('enrollment.enrollments.show', $e) }}" class="tpc-adm-entry d-block text-reset">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <strong>{{ $e->batch->name ?? $e->term?->name }}</strong>
                                    @include('backend.enrollment.partials.status', ['status' => $e->status])
                                </div>
                                <div class="small text-muted">{{ $e->program->name }}@if ($e->year_level) · {{ ___('enrollment.year_' . $e->year_level) }} · {{ Enrollment::units($e->total_units) }} {{ ___('enrollment.units_lower') }}@endif</div>
                            </a>
                        @empty
                            <p class="text-muted mb-0">{{ ___('enrollment.no_enrollments') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
