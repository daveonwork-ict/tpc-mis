@extends('backend.master')
@section('title')
    {{ $title }}
@endsection
@php
    use App\Enums\Enrollment;
    use App\Models\Enrollment\OfferingAttendance;
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $title,
            'parent' => [___('enrollment.my_classes'), route('enrollment.classes.index')],
        ])

        <p class="text-muted mb-24">{{ $subtitle }}</p>
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ ___('enrollment.attendance_date') }}</label>
                        <input type="date" name="attendance_date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="form-control ot-input">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <form method="post" action="{{ $kind === 'offering' ? route('enrollment.classes.attendance', $offering) : route('enrollment.classes.batch-attendance', $batch) }}" class="card ot-card mb-24">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $date }}">
            <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.attendance') }}</h4></div>
            <div class="card-body">
                @if ($roster->isEmpty())
                    <p class="text-muted mb-0">{{ ___('enrollment.no_enrolled_students') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('enrollment.student_no') }}</th>
                                    <th>{{ ___('enrollment.name') }}</th>
                                    <th>{{ ___('enrollment.attendance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach ($roster as $row)
                                    @php
                                        $student = $kind === 'offering' ? $row->enrollment->student : $row->student;
                                        $stored = $marks[$student->id] ?? null;
                                        $mark = is_object($stored) ? $stored->status : (string) $stored;
                                        $minutes = is_object($stored) ? $stored->minutes_late : null;
                                        $report = ($absence ?? [])[$student->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $student->student_code ?: '—' }}</td>
                                        <td>
                                            {{ $student->person->formal_name ?? trim($student->last_name . ', ' . $student->first_name) }}
                                            @if ($report)
                                                <div class="small {{ $report['drop'] ? 'text-danger' : 'text-muted' }}">
                                                    {{ ___('enrollment.absences') }}: {{ $report['absences'] }} / {{ $report['meetings'] }}
                                                    ({{ number_format($report['rate'] * 100, 1) }}%)
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                                <select name="marks[{{ $student->id }}]" class="form-select form-select-sm" style="max-width:180px">
                                                    <option value="">—</option>
                                                    @foreach (OfferingAttendance::STATUSES as $status)
                                                        <option value="{{ $status }}" @selected($mark === $status)>{{ ___('enrollment.mark_' . $status) }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($kind === 'offering')
                                                    <input type="number" name="late_minutes[{{ $student->id }}]" value="{{ $minutes }}" min="0" max="300" class="form-control form-control-sm" style="width:90px" placeholder="{{ ___('enrollment.minutes_late') }}">
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (hasPermission('class_update'))
                        <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save_attendance') }}</button>
                    @endif
                @endif
            </div>
        </form>

        @if ($kind === 'offering' && $roster->isNotEmpty() && hasPermission('class_update'))
            <form method="post" action="{{ route('enrollment.classes.grades', $offering) }}" class="card ot-card">
                @csrf
                <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.final_grade') }}</h4></div>
                <div class="card-body">
                    <p class="small text-muted">{{ ___('enrollment.grade_help') }}</p>
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('enrollment.name') }}</th>
                                    <th>{{ ___('enrollment.final_grade') }}</th>
                                    <th>{{ ___('enrollment.result') }}</th>
                                    <th>{{ ___('enrollment.removal_or_standing') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach ($roster as $course)
                                    <tr>
                                        <td>{{ $course->enrollment->student->person->formal_name ?? $course->enrollment->student->first_name }}</td>
                                        <td><input type="text" name="grades[{{ $course->id }}][final_grade]" value="{{ $course->final_grade }}" class="form-control form-control-sm" maxlength="10" style="width:90px" placeholder="1.00"></td>
                                        <td>
                                            {{ $course->result ? ___('enrollment.' . $course->result) : '—' }}
                                            @if ($course->unofficial_drop)<div class="small text-danger">{{ ___('enrollment.unofficial_drop') }}</div>@endif
                                            @if ($course->final_grade === 'INC' && $course->enrollment->term?->end_date && $course->enrollment->term->end_date->copy()->addYear()->lt(now()->startOfDay()))
                                                <div class="small text-danger">{{ ___('enrollment.inc_overdue') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="date" name="grades[{{ $course->id }}][removal_on]" value="{{ $course->removal_on?->format('Y-m-d') }}" class="form-control form-control-sm mb-1">
                                            <label class="small mb-0"><input type="checkbox" name="grades[{{ $course->id }}][standing_passing]" value="1" @checked($course->standing_passing)> {{ ___('enrollment.standing_passing') }}</label>
                                            @if (($absence[$course->enrollment->student_id]['drop'] ?? false) && hasPermission('class_update'))
                                                <button class="btn btn-sm btn-outline-danger d-block mt-2" type="submit" form="absence-{{ $course->enrollment->student_id }}">{{ ___('enrollment.confirm_absence_drop') }}</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save_results') }}</button>
                </div>
            </form>
            @foreach ($roster as $course)
                @if ($absence[$course->enrollment->student_id]['drop'] ?? false)
                    <form id="absence-{{ $course->enrollment->student_id }}" method="post" action="{{ route('enrollment.classes.absence', $offering) }}">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $course->enrollment->student_id }}">
                    </form>
                @endif
            @endforeach
        @endif

        @if ($kind === 'batch' && $roster->isNotEmpty() && hasPermission('class_update'))
            <form method="post" action="{{ route('enrollment.classes.competency', $batch) }}" class="card ot-card">
                @csrf
                <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.competency_result') }}</h4></div>
                <div class="card-body">
                    <p class="small text-muted">{{ ___('enrollment.competency_help') }}</p>
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('enrollment.name') }}</th>
                                    <th>{{ ___('enrollment.competency_result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach ($roster as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->student->person->formal_name ?? $enrollment->student->first_name }}</td>
                                        <td>
                                            <select name="competency[{{ $enrollment->id }}]" class="form-select form-select-sm" style="max-width:220px">
                                                <option value="">—</option>
                                                <option value="competent" @selected($enrollment->competency_result === 'competent')>{{ ___('enrollment.competent') }}</option>
                                                <option value="not_yet_competent" @selected($enrollment->competency_result === 'not_yet_competent')>{{ ___('enrollment.not_yet_competent') }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save_competency') }}</button>
                </div>
            </form>
        @endif
    </div>
@endsection
