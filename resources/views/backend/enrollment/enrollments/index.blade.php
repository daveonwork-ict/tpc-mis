@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $request = request();
    $query = ['term_id' => $data['termId'] ?? ''] + $request->only('program_id', 'year_level', 'q');
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'actions' => hasPermission('enrollment_create')
                ? '<a href="' . route('enrollment.enrollments.create') . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-plus"></i> ' . e(___('enrollment.start_enrollment')) . '</a>'
                : null,
        ])

        @if ($data['awaiting'] && hasPermission('enrollment_create'))
            <a href="{{ route('enrollment.enrollments.create') }}" class="tpc-enr-banner mb-24">
                <i class="las la-user-graduate"></i>
                <span><strong>{{ $data['awaiting'] }}</strong> {{ ___('enrollment.admitted_awaiting') }}</span>
                <span class="ms-auto">{{ ___('enrollment.start_enrollment') }} <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        @endif

        <div class="tpc-adm-chips mb-24">
            <a href="{{ route('enrollment.enrollments.index', $query) }}" class="{{ !$request->status ? 'active' : '' }}">
                {{ ___('enrollment.all') }} <b>{{ $data['counts']->sum() }}</b>
            </a>
            @foreach (Enrollment::STATUSES as $status)
                <a href="{{ route('enrollment.enrollments.index', $query + ['status' => $status]) }}" class="{{ $request->status === $status ? 'active' : '' }}">
                    {{ ___('enrollment.' . $status) }} <b>{{ $data['counts'][$status] ?? 0 }}</b>
                </a>
            @endforeach
        </div>

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('enrollment.search') }}</label>
                        <input type="text" name="q" value="{{ $request->q }}" class="form-control ot-input" placeholder="{{ ___('enrollment.search_placeholder') }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('enrollment.term') }}</label>
                        <select name="term_id" class="form-select ot-input">
                            <option value="">{{ ___('enrollment.all_terms') }}</option>
                            @foreach ($data['terms'] as $t)
                                <option value="{{ $t->id }}" @selected($data['termId'] == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('enrollment.program') }}</label>
                        <select name="program_id" class="form-select ot-input">
                            <option value="">{{ ___('enrollment.all') }}</option>
                            @foreach ($data['programs'] as $p)
                                <option value="{{ $p->id }}" @selected($request->program_id == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-3">
                        <label class="form-label">{{ ___('enrollment.year') }}</label>
                        <select name="year_level" class="form-select ot-input">
                            <option value="">—</option>
                            @foreach (Enrollment::YEAR_LEVELS as $y)
                                <option value="{{ $y }}" @selected($request->year_level == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <input type="hidden" name="status" value="{{ $request->status }}">
                        <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('enrollment.student') }}</th>
                                    <th>{{ ___('enrollment.program') }}</th>
                                    <th>{{ ___('enrollment.term') }}</th>
                                    <th class="text-center">{{ ___('enrollment.units') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['enrollments'] as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row->student->last_name }}, {{ $row->student->first_name }}</strong>
                                            <div class="small text-muted">{{ $row->student->student_code ?: ___('enrollment.no_student_number_yet') }} · {{ $row->enrollment_no }}</div>
                                        </td>
                                        <td>
                                            {{ $row->program->name }}
                                            <div class="small text-muted">
                                                @if ($row->isTrainee())
                                                    {{ $row->batch->code ?? '' }} · {{ ___('enrollment.type_' . $row->student_type) }}
                                                @else
                                                    {{ ___('enrollment.year_' . $row->year_level) }}@if ($row->block) · {{ $row->block }}@endif · {{ ___('enrollment.type_' . $row->student_type) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $row->batch->name ?? $row->term?->name }}</td>
                                        <td class="text-center">{{ $row->isTrainee() ? '—' : Enrollment::units($row->total_units) }}</td>
                                        <td>@include('backend.enrollment.partials.status', ['status' => $row->status, 'label' => $row->isTrainee() && $row->status === Enrollment::ADVISING ? ___('enrollment.eligibility') : null])</td>
                                        <td class="action">
                                            <a href="{{ route('enrollment.enrollments.show', $row) }}" class="btn btn-sm ot-btn-primary">{{ ___('enrollment.open') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color p-5">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0">{{ ___('enrollment.no_enrollments') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end py-3">
                        {!! $data['enrollments']->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
