@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $curriculum = $data['curriculum'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => $data['title']])
        @include('backend.enrollment.setup._tabs')

        <div class="row g-4">
            @if (hasPermission('enrollment_setup_update'))
                <div class="col-xl-4">
                    <form action="{{ route('enrollment.setup.curricula.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $curriculum->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $curriculum->exists ? ___('enrollment.edit_curriculum') : ___('enrollment.add_curriculum') }}</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.program') }} <span class="text-danger">*</span></label>
                                <select name="program_id" class="form-select ot-input" required>
                                    <option value="">—</option>
                                    @foreach ($data['programs'] as $p)
                                        <option value="{{ $p->id }}" @selected(old('program_id', $curriculum->program_id) == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.curriculum_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $curriculum->name) }}" class="form-control ot-input" placeholder="{{ ___('enrollment.curriculum_name_hint') }}" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.effective_from') }}</label>
                                    <select name="effective_session_id" class="form-select ot-input">
                                        <option value="">—</option>
                                        @foreach ($data['sessions'] as $s)
                                            <option value="{{ $s->id }}" @selected(old('effective_session_id', $curriculum->effective_session_id) == $s->id)>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        @foreach (Enrollment::CURRICULUM_STATUSES as $s)
                                            <option value="{{ $s }}" @selected(old('status', $curriculum->status) === $s)>{{ ___('enrollment.' . $s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.authority_reference') }}</label>
                                <input type="text" name="authority_reference" value="{{ old('authority_reference', $curriculum->authority_reference) }}" class="form-control ot-input" placeholder="{{ ___('enrollment.curriculum_authority_hint') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control ot-input">{{ old('notes', $curriculum->notes) }}</textarea>
                            </div>
                            <p class="small text-muted">{{ ___('enrollment.curriculum_status_help') }}</p>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($curriculum->exists)
                                    <a href="{{ route('enrollment.setup.curricula') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                                @endif
                                <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save') }}</button>
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
                                        <th>{{ ___('enrollment.curriculum') }}</th>
                                        <th class="text-center">{{ ___('enrollment.courses') }}</th>
                                        <th>{{ ___('common.status') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['curricula'] as $row)
                                        <tr>
                                            <td>
                                                <a href="{{ route('enrollment.setup.curricula.show', $row) }}" class="fw-semibold">{{ $row->name }}</a>
                                                <div class="small">{{ $row->program->name }}</div>
                                                <div class="small text-muted">
                                                    {{ $row->effectiveSession->name ?? ___('enrollment.no_effective_year') }}
                                                    @if ($row->authority_reference) · {{ $row->authority_reference }}@endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $row->courses_count }}</td>
                                            <td>@include('backend.enrollment.partials.status', ['status' => $row->status])</td>
                                            <td class="action">
                                                <div class="d-flex gap-2 flex-nowrap">
                                                    <a href="{{ route('enrollment.setup.curricula.show', $row) }}" class="btn btn-sm ot-btn-primary">{{ ___('enrollment.open') }}</a>
                                                    @if (hasPermission('enrollment_setup_update'))
                                                        <a href="{{ route('enrollment.setup.curricula', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color p-5">
                                                <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                                <p class="mb-0">{{ ___('enrollment.no_curricula') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
