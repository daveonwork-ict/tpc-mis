@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($request = request())
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', [
            'title' => $data['title'],
            'actions' => hasPermission('application_create')
                ? '<a href="' . route('admissions.applications.create') . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-plus"></i> ' . e(___('admissions.new_walk_in_application')) . '</a>'
                : null,
        ])

        <div class="tpc-adm-chips mb-24">
            <a href="{{ route('admissions.applications.index') }}" class="{{ !$request->status ? 'active' : '' }}">
                {{ ___('admissions.all') }} <b>{{ $data['counts']->sum() }}</b>
            </a>
            @foreach (\App\Enums\Admission::APPLICATION_STATUSES as $status)
                <a href="{{ route('admissions.applications.index', ['status' => $status]) }}" class="{{ $request->status === $status ? 'active' : '' }}">
                    {{ ___('admissions.' . $status) }} <b>{{ $data['counts'][$status] ?? 0 }}</b>
                </a>
            @endforeach
        </div>

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('admissions.search') }}</label>
                        <input type="text" name="q" value="{{ $request->q }}" class="form-control ot-input" placeholder="{{ ___('admissions.search_placeholder') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">{{ ___('admissions.track') }}</label>
                        <select name="track" class="form-select ot-input">
                            <option value="">{{ ___('admissions.all') }}</option>
                            @foreach (\App\Enums\Admission::TRACKS as $track)
                                <option value="{{ $track }}" @selected($request->track === $track)>{{ ___('admissions.track_' . $track) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('admissions.program') }}</label>
                        <select name="program_id" class="form-select ot-input">
                            <option value="">{{ ___('admissions.all') }}</option>
                            @foreach ($data['programs'] as $program)
                                <option value="{{ $program->id }}" @selected($request->program_id == $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">{{ ___('admissions.term') }}</label>
                        <select name="term_id" class="form-select ot-input">
                            <option value="">{{ ___('admissions.all') }}</option>
                            @foreach ($data['terms'] as $term)
                                <option value="{{ $term->id }}" @selected($request->term_id == $term->id)>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <input type="hidden" name="status" value="{{ $request->status }}">
                        <button class="btn btn-lg ot-btn-primary flex-fill" type="submit">{{ ___('common.Search') }}</button>
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
                                    <th>{{ ___('admissions.application_no') }}</th>
                                    <th>{{ ___('admissions.applicant') }}</th>
                                    <th>{{ ___('admissions.program') }}</th>
                                    <th>{{ ___('admissions.applicant_type') }}</th>
                                    <th>{{ ___('admissions.term') }}</th>
                                    <th>{{ ___('admissions.matrix_total') }}</th>
                                    <th>{{ ___('admissions.status') }}</th>
                                    <th>{{ ___('admissions.submitted') }}</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['applications'] as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->application_no }}</td>
                                        <td>
                                            {{ $row->person->formal_name }}
                                            <div class="small text-muted">{{ $row->person->mobile }}</div>
                                        </td>
                                        <td>
                                            {{ $row->program->name }}
                                            <div class="small text-muted">{{ ___('admissions.track_' . $row->track) }}</div>
                                        </td>
                                        <td>{{ ___('admissions.' . $row->applicant_type) }}</td>
                                        <td>{{ $row->term->name ?? $row->preferred_schedule ?? '—' }}</td>
                                        <td>
                                            {{ $row->matrix_score['total'] === null ? '—' : $row->matrix_score['total'] }}
                                            @if ($row->tarlac_resident)<div class="small text-muted">{{ ___('admissions.tarlac_resident') }}</div>@endif
                                        </td>
                                        <td>@include('backend.admissions.partials.status', ['status' => $row->status])</td>
                                        <td>{{ dateFormat($row->submitted_at) }}<div class="small text-muted">{{ ___('admissions.source_' . $row->source) }}</div></td>
                                        <td class="action">
                                            <a href="{{ route('admissions.applications.show', $row) }}" class="btn btn-sm ot-btn-primary">{{ ___('admissions.open') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color p-5">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0">{{ ___('admissions.no_applications') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end py-3">
                        {!! $data['applications']->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
