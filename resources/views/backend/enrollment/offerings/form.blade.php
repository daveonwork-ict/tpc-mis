@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $offering = $data['offering'];
    $slots = old('schedules', $offering->exists
        ? $offering->schedules->map(fn ($s) => ['day' => $s->day, 'start' => substr($s->start_time, 0, 5), 'end' => substr($s->end_time, 0, 5), 'room' => $s->room, 'type' => $s->type])->all()
        : []);
    $slots = array_values($slots);
    while (count($slots) < 3) {
        $slots[] = ['day' => '', 'start' => '', 'end' => '', 'room' => '', 'type' => ''];
    }
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'parent' => [___('enrollment.course_offerings'), route('enrollment.offerings.index', ['term_id' => $offering->term_id])],
        ])

        <form action="{{ $offering->exists ? route('enrollment.offerings.update', $offering) : route('enrollment.offerings.store') }}" method="post">
            @csrf
            @if ($offering->exists) @method('PUT') @endif
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="card ot-card">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.offering_details') }}</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.term') }} <span class="text-danger">*</span></label>
                                <select name="term_id" class="form-select ot-input" required>
                                    @foreach ($data['terms'] as $t)
                                        <option value="{{ $t->id }}" @selected(old('term_id', $offering->term_id) == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.course') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select ot-input" required>
                                    <option value="">—</option>
                                    @foreach ($data['subjects'] as $s)
                                        <option value="{{ $s->id }}" @selected(old('subject_id', $offering->subject_id) == $s->id)>{{ $s->code }} — {{ $s->name }} ({{ Enrollment::units($s->total_units) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.section_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="section_code" value="{{ old('section_code', $offering->section_code) }}" class="form-control ot-input" placeholder="{{ ___('enrollment.section_code_hint') }}" maxlength="30" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.capacity') }}</label>
                                    <input type="number" name="capacity" value="{{ old('capacity', $offering->capacity) }}" min="1" max="1000" class="form-control ot-input">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-7">
                                    <label class="form-label">{{ ___('enrollment.for_program') }}</label>
                                    <select name="program_id" class="form-select ot-input">
                                        <option value="">{{ ___('enrollment.any_program') }}</option>
                                        @foreach ($data['programs'] as $p)
                                            <option value="{{ $p->id }}" @selected(old('program_id', $offering->program_id) == $p->id)>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">{{ ___('enrollment.year_level') }}</label>
                                    <select name="year_level" class="form-select ot-input">
                                        <option value="">—</option>
                                        @foreach (Enrollment::YEAR_LEVELS as $y)
                                            <option value="{{ $y }}" @selected(old('year_level', $offering->year_level) == $y)>{{ ___('enrollment.year_' . $y) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-7">
                                    <label class="form-label">{{ ___('enrollment.faculty') }}</label>
                                    <select name="faculty_id" class="form-select ot-input">
                                        <option value="">{{ ___('enrollment.tba') }}</option>
                                        @foreach ($data['faculty'] as $f)
                                            <option value="{{ $f->id }}" @selected(old('faculty_id', $offering->faculty_id) == $f->id)>{{ $f->last_name }}, {{ $f->first_name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($data['faculty']->isEmpty())
                                        <div class="small text-muted mt-1">{{ ___('enrollment.no_faculty_yet') }}</div>
                                    @endif
                                </div>
                                <div class="col-5">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        @foreach (Enrollment::OFFERING_STATUSES as $s)
                                            <option value="{{ $s }}" @selected(old('status', $offering->status) === $s)>{{ ___('enrollment.' . $s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                                <input type="text" name="remarks" value="{{ old('remarks', $offering->remarks) }}" class="form-control ot-input" maxlength="255">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="card ot-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ ___('enrollment.schedule') }}</h4>
                            <button type="button" class="btn btn-sm ot-btn-primary-outline" id="add-slot"><i class="fa-solid fa-plus"></i> {{ ___('enrollment.add_meeting') }}</button>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">{{ ___('enrollment.schedule_help') }}</p>
                            <div id="slots">
                                @foreach ($slots as $i => $slot)
                                    <div class="tpc-enr-slot">
                                        <select name="schedules[{{ $i }}][day]" class="form-select ot-input" aria-label="{{ ___('enrollment.day') }}">
                                            <option value="">{{ ___('enrollment.day') }}</option>
                                            @foreach (Enrollment::DAYS as $num => $day)
                                                <option value="{{ $num }}" @selected(($slot['day'] ?? '') == $num)>{{ ___('enrollment.day_' . $day) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="time" name="schedules[{{ $i }}][start]" value="{{ $slot['start'] ?? '' }}" class="form-control ot-input" aria-label="{{ ___('enrollment.start') }}">
                                        <input type="time" name="schedules[{{ $i }}][end]" value="{{ $slot['end'] ?? '' }}" class="form-control ot-input" aria-label="{{ ___('enrollment.end') }}">
                                        <input type="text" name="schedules[{{ $i }}][room]" value="{{ $slot['room'] ?? '' }}" class="form-control ot-input" placeholder="{{ ___('enrollment.room') }}" maxlength="50">
                                        <select name="schedules[{{ $i }}][type]" class="form-select ot-input" aria-label="{{ ___('enrollment.type') }}">
                                            <option value="">—</option>
                                            @foreach (Enrollment::SCHEDULE_TYPES as $type)
                                                <option value="{{ $type }}" @selected(($slot['type'] ?? '') === $type)>{{ ___('enrollment.' . $type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end mt-24">
                        <a href="{{ route('enrollment.offerings.index', ['term_id' => $offering->term_id]) }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                        <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('script')
    <script>
        document.getElementById('add-slot').addEventListener('click', function () {
            const slots = document.getElementById('slots');
            if (slots.children.length >= 8) return;
            const row = slots.children[0].cloneNode(true);
            const index = slots.children.length;
            row.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/schedules\[\d+\]/, 'schedules[' + index + ']');
                field.value = '';
            });
            slots.appendChild(row);
        });
    </script>
@endpush
