@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    $term = $data['term'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', ['title' => $data['title']])
        @include('backend.admissions.setup._tabs')

        <div class="row g-4">
            @if (hasPermission('admission_setup_update'))
                <div class="col-xl-4">
                    <form action="{{ route('admissions.setup.terms.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $term->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $term->exists ? ___('admissions.edit_term') : ___('admissions.add_term') }}</h4></div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-7">
                                    <label class="form-label">{{ ___('settings.sessions') }}</label>
                                    <select name="session_id" class="form-select ot-input" required>
                                        @foreach ($data['sessions'] as $s)
                                            <option value="{{ $s->id }}" @selected(old('session_id', $term->session_id) == $s->id)>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">{{ ___('admissions.semester') }}</label>
                                    <select name="semester" class="form-select ot-input" required>
                                        @foreach (Admission::SEMESTERS as $sem)
                                            <option value="{{ $sem }}" @selected(old('semester', $term->semester) === $sem)>{{ ___('admissions.semester_' . $sem) }}</option>
                                        @endforeach
                                    </select>
                                    @error('semester')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.display_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $term->name) }}" class="form-control ot-input" placeholder="1st Semester, AY 2027-2028" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('admissions.start_date') }}</label>
                                    <input type="date" name="start_date" value="{{ old('start_date', $term->start_date?->format('Y-m-d')) }}" class="form-control ot-input">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('admissions.end_date') }}</label>
                                    <input type="date" name="end_date" value="{{ old('end_date', $term->end_date?->format('Y-m-d')) }}" class="form-control ot-input">
                                    @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.midterm_on') }}</label>
                                <input type="date" name="midterm_on" value="{{ old('midterm_on', $term->midterm_on?->format('Y-m-d')) }}" class="form-control ot-input">
                                <div class="small text-muted mt-1">{{ ___('admissions.midterm_on_help') }}</div>
                            </div>
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        <option value="1" @selected($term->status == 1)>{{ ___('common.active') }}</option>
                                        <option value="0" @selected($term->exists && $term->status == 0)>{{ ___('common.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="admission_open" value="1" id="admission_open" @checked(old('admission_open', $term->admission_open))>
                                        <label class="form-check-label" for="admission_open">{{ ___('admissions.open_for_applications') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($term->exists)
                                    <a href="{{ route('admissions.setup.terms') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                                @endif
                                <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('admissions.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="col">
                <div class="card ot-card">
                    <div class="card-body">
                        <p class="small text-muted">{{ ___('admissions.terms_note') }}</p>
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('admissions.term') }}</th>
                                        <th>{{ ___('admissions.dates') }}</th>
                                        <th>{{ ___('admissions.applications') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @foreach ($data['terms'] as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row->name }}</strong>
                                                <div class="small text-muted">{{ $row->session->name ?? '' }} · {{ ___('admissions.semester_' . $row->semester) }}</div>
                                            </td>
                                            <td>
                                                @if ($row->start_date)
                                                    {{ $row->start_date->format('d M Y') }} – {{ $row->end_date?->format('d M Y') }}
                                                @else
                                                    <span class="text-muted">{{ ___('admissions.not_set') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!$row->status)
                                                    <span class="tpc-pill tpc-pill--secondary">{{ ___('common.inactive') }}</span>
                                                @else
                                                    <span class="tpc-pill tpc-pill--{{ $row->admission_open ? 'success' : 'secondary' }}">{{ $row->admission_open ? ___('admissions.open') : ___('admissions.closed') }}</span>
                                                @endif
                                            </td>
                                            <td class="action">
                                                @if (hasPermission('admission_setup_update'))
                                                    <a href="{{ route('admissions.setup.terms', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
