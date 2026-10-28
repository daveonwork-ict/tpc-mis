@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'parent' => [___('enrollment.enrollments'), route('enrollment.enrollments.index')],
        ])

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card ot-card h-100">
                    <div class="card-header">
                        <h4 class="mb-0">{{ ___('enrollment.new_students') }}</h4>
                        <p class="small text-muted mb-0 mt-1">{{ ___('enrollment.new_students_help') }}</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('enrollment.applicant') }}</th>
                                        <th>{{ ___('enrollment.program') }}</th>
                                        <th>{{ ___('enrollment.admission') }}</th>
                                        <th class="action"></th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['applications'] as $app)
                                        <tr>
                                            <td>
                                                <strong>{{ $app->person->formal_name }}</strong>
                                                <div class="small text-muted">{{ $app->application_no }}</div>
                                            </td>
                                            <td>
                                                {{ $app->program->name }}
                                                <div class="small text-muted">{{ ___('admissions.' . $app->applicant_type) }} · {{ $app->term->name ?? '—' }}</div>
                                            </td>
                                            <td>@include('backend.admissions.partials.status', ['status' => $app->status])</td>
                                            <td class="action">
                                                <a href="{{ route('enrollment.enrollments.create', ['application' => $app->id]) }}" class="btn btn-sm ot-btn-primary">{{ ___('enrollment.enroll') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="100%" class="text-center gray-color p-4">{{ ___('enrollment.no_admitted_waiting') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card ot-card h-100">
                    <div class="card-header">
                        <h4 class="mb-0">{{ ___('enrollment.continuing_students') }}</h4>
                        <p class="small text-muted mb-0 mt-1">{{ ___('enrollment.continuing_students_help') }}</p>
                    </div>
                    <div class="card-body">
                        <form method="get" class="d-flex gap-2 mb-3">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control ot-input" placeholder="{{ ___('enrollment.student_search_placeholder') }}" minlength="2" required>
                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                        </form>
                        @if (request('q'))
                            <div class="tpc-enr-list">
                                @forelse ($data['students'] as $student)
                                    @php($last = $student->enrollments->first())
                                    <a href="{{ route('enrollment.enrollments.create', ['student' => $student->id]) }}">
                                        <span>
                                            <strong>{{ $student->last_name }}, {{ $student->first_name }}</strong>
                                            <small>{{ $student->student_code ?: ___('enrollment.no_student_number_yet') }}@if ($last) · {{ $last->program->name }}, {{ $last->batch->name ?? $last->term?->name }}@endif</small>
                                        </span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                @empty
                                    <p class="text-muted mb-0">{{ ___('enrollment.no_students_found') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
