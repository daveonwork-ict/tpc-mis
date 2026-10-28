@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Models\Enrollment\TrainingBatch;
    $batch = $data['batch'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => $data['title']])

        <div class="row g-4">
            @if (hasPermission('offering_update'))
                <div class="col-xl-4">
                    <form action="{{ route('enrollment.batches.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $batch->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $batch->exists ? ___('enrollment.edit_batch') : ___('enrollment.add_batch') }}</h4></div>
                        <div class="card-body">
                            @if ($data['programs']->isEmpty())
                                <div class="alert alert-warning">{{ ___('enrollment.no_tesda_program') }}</div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.qualification') }} <span class="text-danger">*</span></label>
                                <select name="program_id" class="form-select ot-input" required>
                                    @foreach ($data['programs'] as $program)
                                        <option value="{{ $program->id }}" @selected(old('program_id', $batch->program_id) == $program->id)>{{ $program->name }}</option>
                                    @endforeach
                                </select>
                                @error('program_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.academic_year') }} <span class="text-danger">*</span></label>
                                <select name="session_id" class="form-select ot-input" required>
                                    @foreach ($data['sessions'] as $session)
                                        <option value="{{ $session->id }}" @selected(old('session_id', $batch->session_id) == $session->id)>{{ $session->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-5">
                                    <label class="form-label">{{ ___('enrollment.batch_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="code" value="{{ old('code', $batch->code) }}" class="form-control ot-input" required maxlength="30">
                                    @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-7">
                                    <label class="form-label">{{ ___('enrollment.slots') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="slots" value="{{ old('slots', $batch->slots) }}" class="form-control ot-input" required min="1" max="500">
                                    @error('slots')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.batch_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $batch->name) }}" class="form-control ot-input" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.start_date') }}</label>
                                    <input type="date" name="start_date" value="{{ old('start_date', $batch->start_date?->format('Y-m-d')) }}" class="form-control ot-input">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.end_date') }}</label>
                                    <input type="date" name="end_date" value="{{ old('end_date', $batch->end_date?->format('Y-m-d')) }}" class="form-control ot-input">
                                    @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.schedule') }}</label>
                                <input type="text" name="schedule" value="{{ old('schedule', $batch->schedule) }}" class="form-control ot-input" placeholder="{{ ___('enrollment.schedule_hint') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.venue') }}</label>
                                <input type="text" name="venue" value="{{ old('venue', $batch->venue) }}" class="form-control ot-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.trainer') }}</label>
                                <select name="trainer_id" class="form-select ot-input">
                                    <option value="">—</option>
                                    @foreach ($data['trainers'] as $trainer)
                                        <option value="{{ $trainer->id }}" @selected(old('trainer_id', $batch->trainer_id) == $trainer->id)>{{ $trainer->last_name }}, {{ $trainer->first_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('common.status') }}</label>
                                <select name="status" class="form-select ot-input">
                                    <option value="{{ TrainingBatch::OPEN }}" @selected(old('status', $batch->status) === TrainingBatch::OPEN)>{{ ___('enrollment.batch_open') }}</option>
                                    <option value="{{ TrainingBatch::CLOSED }}" @selected(old('status', $batch->status) === TrainingBatch::CLOSED)>{{ ___('enrollment.batch_closed') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                                <textarea name="remarks" rows="2" class="form-control ot-input">{{ old('remarks', $batch->remarks) }}</textarea>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($batch->exists)
                                    <a href="{{ route('enrollment.batches.index') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                                @endif
                                <button class="btn btn-lg ot-btn-primary" type="submit" @disabled($data['programs']->isEmpty())>{{ ___('enrollment.save') }}</button>
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
                                        <th>{{ ___('enrollment.training_batch') }}</th>
                                        <th>{{ ___('enrollment.qualification') }}</th>
                                        <th>{{ ___('enrollment.schedule') }}</th>
                                        <th class="text-center">{{ ___('enrollment.batch_seats') }}</th>
                                        <th>{{ ___('common.status') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['batches'] as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row->name }}</strong>
                                                <div class="small text-muted">{{ $row->code }} · {{ $row->session->name ?? '—' }}</div>
                                            </td>
                                            <td>{{ $row->program->name ?? '—' }}</td>
                                            <td>
                                                {{ $row->schedule ?: '—' }}
                                                <div class="small text-muted">
                                                    @if ($row->start_date){{ $row->start_date->format('d M Y') }}@endif
                                                    @if ($row->end_date) – {{ $row->end_date->format('d M Y') }}@endif
                                                    @if ($row->venue)<br>{{ $row->venue }}@endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $row->seats_taken }} / {{ $row->slots }}</td>
                                            <td>
                                                <span class="tpc-pill tpc-pill--{{ $row->status === TrainingBatch::OPEN ? 'success' : 'secondary' }}">{{ ___('enrollment.batch_' . $row->status) }}</span>
                                            </td>
                                            <td class="action">
                                                @if (hasPermission('offering_update'))
                                                    <a href="{{ route('enrollment.batches.index', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                    @if ($row->seats_taken == 0)
                                                        <form action="{{ route('enrollment.batches.delete', $row) }}" method="post" class="d-inline" onsubmit="return confirm(@js(___('enrollment.confirm_delete_batch')))">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color p-5">
                                                <p class="mb-0">{{ ___('enrollment.no_batches') }}</p>
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
