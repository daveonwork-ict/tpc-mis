@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    $program = $data['program'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', ['title' => $data['title']])
        @include('backend.admissions.setup._tabs')

        <div class="row g-4">
            @if (hasPermission('admission_setup_update'))
                <div class="col-xl-4">
                    <form action="{{ route('admissions.setup.programs.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $program->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $program->exists ? ___('admissions.edit_program') : ___('admissions.add_program') }}</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.track') }}</label>
                                <select name="track" class="form-select ot-input">
                                    @foreach (Admission::TRACKS as $t)
                                        <option value="{{ $t }}" @selected(old('track', $program->track) === $t)>{{ ___('admissions.track_' . $t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.program_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $program->name) }}" class="form-control ot-input" required>
                                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-5">
                                    <label class="form-label">{{ ___('admissions.code') }}</label>
                                    <input type="text" name="code" value="{{ old('code', $program->code) }}" class="form-control ot-input">
                                </div>
                                <div class="col-7">
                                    <label class="form-label">{{ ___('admissions.department') }}</label>
                                    <select name="department_id" class="form-select ot-input">
                                        <option value="">—</option>
                                        @foreach ($data['departments'] as $d)
                                            <option value="{{ $d->id }}" @selected(old('department_id', $program->department_id) == $d->id)>{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.authority_reference') }}</label>
                                <input type="text" name="authority_reference" value="{{ old('authority_reference', $program->authority_reference) }}" class="form-control ot-input" placeholder="{{ ___('admissions.authority_reference_hint') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.training_hours') }}</label>
                                <input type="number" name="training_hours" value="{{ old('training_hours', $program->training_hours) }}" step="0.5" min="1" max="10000" class="form-control ot-input">
                                <div class="small text-muted mt-1">{{ ___('enrollment.training_hours_help') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.entry_requirements') }}</label>
                                <textarea name="entry_requirements" rows="3" class="form-control ot-input">{{ old('entry_requirements', $program->entry_requirements) }}</textarea>
                                <div class="small text-muted mt-1">{{ ___('enrollment.entry_requirements_help') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.max_units_per_term') }}</label>
                                <input type="number" name="max_units" value="{{ old('max_units', $program->max_units) }}" step="0.5" min="1" max="60" class="form-control ot-input" placeholder="{{ ___('enrollment.no_limit') }}">
                                <div class="small text-muted mt-1">{{ ___('enrollment.max_units_help') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.minimum_gwa') }}</label>
                                <input type="number" name="minimum_gwa" value="{{ old('minimum_gwa', $program->minimum_gwa) }}" step="0.01" min="80" max="100" class="form-control ot-input">
                                <div class="small text-muted mt-1">{{ ___('admissions.minimum_gwa_help') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.description') }}</label>
                                <textarea name="description" rows="3" class="form-control ot-input">{{ old('description', $program->description) }}</textarea>
                            </div>
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        <option value="1" @selected($program->status == 1)>{{ ___('common.active') }}</option>
                                        <option value="0" @selected($program->exists && $program->status == 0)>{{ ___('common.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="accepting_applications" value="1" id="accepting" @checked(old('accepting_applications', $program->accepting_applications))>
                                        <label class="form-check-label" for="accepting">{{ ___('admissions.accepting_applications') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($program->exists)
                                    <a href="{{ route('admissions.setup.programs') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
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
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('admissions.program') }}</th>
                                        <th>{{ ___('admissions.department') }}</th>
                                        <th>{{ ___('admissions.authority_reference') }}</th>
                                        <th>{{ ___('admissions.applications') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @foreach ($data['programs'] as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row->name }}</strong>
                                                <div class="small text-muted">{{ ___('admissions.track_' . $row->track) }}@if ($row->code) · {{ $row->code }}@endif</div>
                                            </td>
                                            <td>{{ $row->department->name ?? '—' }}</td>
                                            <td>
                                                @if ($row->authority_reference)
                                                    {{ $row->authority_reference }}
                                                @else
                                                    <span class="tpc-pill tpc-pill--warning">{{ ___('admissions.to_be_confirmed') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!$row->status)
                                                    <span class="tpc-pill tpc-pill--secondary">{{ ___('common.inactive') }}</span>
                                                @else
                                                    <span class="tpc-pill tpc-pill--{{ $row->accepting_applications ? 'success' : 'secondary' }}">{{ $row->accepting_applications ? ___('admissions.open') : ___('admissions.closed') }}</span>
                                                @endif
                                            </td>
                                            <td class="action">
                                                @if (hasPermission('admission_setup_update'))
                                                    <a href="{{ route('admissions.setup.programs', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
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
