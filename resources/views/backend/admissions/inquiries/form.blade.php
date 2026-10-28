@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    $inquiry = $data['inquiry'];
    $converted = (bool) $inquiry->application_id;
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', [
            'title' => $data['title'],
            'parent' => [___('admissions.inquiries'), route('admissions.inquiries.index')],
        ])

        <form action="{{ $inquiry->exists ? route('admissions.inquiries.update', $inquiry) : route('admissions.inquiries.store') }}" method="post">
            @csrf
            @if ($inquiry->exists)
                @method('PUT')
            @endif
            <div class="card ot-card mb-24">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ ___('admissions.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $inquiry->name) }}" class="form-control ot-input" required>
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.mobile') }}</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $inquiry->mobile) }}" class="form-control ot-input">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.email') }}</label>
                            <input type="email" name="email" value="{{ old('email', $inquiry->email) }}" class="form-control ot-input">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.channel') }} <span class="text-danger">*</span></label>
                            <select name="channel" class="form-select ot-input" required>
                                @foreach (Admission::INQUIRY_CHANNELS as $c)
                                    <option value="{{ $c }}" @selected(old('channel', $inquiry->channel) === $c)>{{ ___('admissions.channel_' . $c) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.track') }}</label>
                            <select name="track" class="form-select ot-input">
                                <option value="">—</option>
                                @foreach (Admission::TRACKS as $t)
                                    <option value="{{ $t }}" @selected(old('track', $inquiry->track) === $t)>{{ ___('admissions.track_' . $t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ ___('admissions.program') }}</label>
                            <select name="program_id" class="form-select ot-input">
                                <option value="">—</option>
                                @foreach ($data['programs'] as $p)
                                    <option value="{{ $p->id }}" @selected(old('program_id', $inquiry->program_id) == $p->id)>{{ $p->name }} ({{ ___('admissions.track_' . $p->track) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ ___('admissions.question_or_message') }}</label>
                            <textarea name="message" rows="3" class="form-control ot-input">{{ old('message', $inquiry->message) }}</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.status') }}</label>
                            @if ($converted)
                                <input type="text" class="form-control ot-input" value="{{ ___('admissions.inquiry_converted') }}" disabled>
                                <input type="hidden" name="status" value="closed">
                            @else
                                <select name="status" class="form-select ot-input">
                                    @foreach (array_diff(Admission::INQUIRY_STATUSES, ['converted']) as $s)
                                        <option value="{{ $s }}" @selected(old('status', $inquiry->status) === $s)>{{ ___('admissions.inquiry_' . $s) }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ ___('admissions.follow_up_on') }}</label>
                            <input type="date" name="follow_up_on" value="{{ old('follow_up_on', $inquiry->follow_up_on?->format('Y-m-d')) }}" class="form-control ot-input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ ___('admissions.assigned_to') }}</label>
                            <select name="assigned_to" class="form-select ot-input">
                                <option value="">—</option>
                                @foreach ($data['staff'] as $u)
                                    <option value="{{ $u->id }}" @selected(old('assigned_to', $inquiry->assigned_to) == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ ___('admissions.counseling_notes') }}</label>
                            <textarea name="notes" rows="3" class="form-control ot-input">{{ old('notes', $inquiry->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-end mb-24">
                <a href="{{ route('admissions.inquiries.index') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                <button type="submit" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ ___('admissions.save') }}</button>
            </div>
        </form>
    </div>
@endsection
