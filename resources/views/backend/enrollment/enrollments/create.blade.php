@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $application = $data['application'];
    $student = $data['student'];
    $defaults = $data['defaults'];
    $person = $application?->person ?? $student?->person;
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'parent' => [___('enrollment.enrollments'), route('enrollment.enrollments.index')],
        ])

        <div class="tpc-adm-summary mb-24">
            <div class="tpc-adm-summary__main">
                <div class="tpc-adm-kicker">{{ $application ? ___('enrollment.new_student') : ___('enrollment.continuing_student') }}</div>
                <h3>{{ $person?->full_name ?? trim($student->first_name . ' ' . $student->last_name) }}</h3>
                <p>
                    @if ($application)
                        {{ $application->application_no }} · {{ $application->program->name }} · {{ ___('admissions.' . $application->applicant_type) }}
                    @else
                        {{ $student->student_code ?: ___('enrollment.no_student_number_yet') }}@if ($data['last']) · {{ ___('enrollment.last_term') }}: {{ $data['last']->batch->name ?? $data['last']->term?->name }}@endif
                    @endif
                </p>
            </div>
            @if ($application?->status === 'conditional')
                <div class="tpc-adm-summary__meta">
                    <div><span>{{ ___('enrollment.admission') }}</span>@include('backend.admissions.partials.status', ['status' => $application->status])</div>
                </div>
            @endif
        </div>

        @if ($application?->status === 'conditional' && $application->latestDecision?->conditions)
            <div class="alert alert-warning"><strong>{{ ___('enrollment.admission_conditions') }}:</strong> {{ $application->latestDecision->conditions }}</div>
        @endif

        @php $tesda = ($defaults['track'] ?? null) === 'tesda'; @endphp
        @if (! $tesda && $data['terms']->isEmpty())
            <div class="alert alert-warning">
                {{ ___('enrollment.no_term_open') }}
                @if (hasPermission('enrollment_setup_read'))<a href="{{ route('enrollment.setup.settings') }}">{{ ___('enrollment.enrollment_settings') }}</a>@endif
            </div>
        @elseif ($tesda && $data['batches']->isEmpty())
            <div class="alert alert-warning">
                {{ ___('enrollment.no_open_batch') }}
                @if (hasPermission('offering_read'))<a href="{{ route('enrollment.batches.index') }}">{{ ___('enrollment.training_batches') }}</a>@endif
            </div>
        @else
            <form action="{{ route('enrollment.enrollments.store') }}" method="post" class="card ot-card">
                @csrf
                @if ($application)
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                @else
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                @endif
                <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.enrollment_details') }}</h4></div>
                <div class="card-body">
                    @if ($tesda)
                        <div class="row g-3">
                            <div class="col-lg-6 col-md-6">
                                <label class="form-label">{{ ___('enrollment.qualification') }} <span class="text-danger">*</span></label>
                                <select name="program_id" class="form-select ot-input" required>
                                    @foreach ($data['programs'] as $p)
                                        <option value="{{ $p->id }}" @selected(old('program_id', $defaults['program_id']) == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label class="form-label">{{ ___('enrollment.training_batch') }} <span class="text-danger">*</span></label>
                                <select name="batch_id" class="form-select ot-input" required>
                                    @foreach ($data['batches'] as $batch)
                                        <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>{{ $batch->name }} · {{ $batch->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label class="form-label">{{ ___('enrollment.student_type') }} <span class="text-danger">*</span></label>
                                <select name="student_type" class="form-select ot-input">
                                    @foreach (\App\Enums\Admission::applicantTypes('tesda') as $type)
                                        <option value="{{ $type }}" @selected(old('student_type', $defaults['student_type']) === $type)>{{ ___('enrollment.type_' . $type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                                <input type="text" name="remarks" value="{{ old('remarks') }}" class="form-control ot-input" maxlength="1000">
                            </div>
                        </div>
                    @else
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">{{ ___('enrollment.term') }} <span class="text-danger">*</span></label>
                            <select name="term_id" class="form-select ot-input" required>
                                @foreach ($data['terms'] as $t)
                                    <option value="{{ $t->id }}" @selected(old('term_id', $defaults['term_id']) == $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">{{ ___('enrollment.program') }} <span class="text-danger">*</span></label>
                            <select name="program_id" class="form-select ot-input" id="program" required>
                                @foreach ($data['programs'] as $p)
                                    <option value="{{ $p->id }}" @selected(old('program_id', $defaults['program_id']) == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">{{ ___('enrollment.curriculum') }}</label>
                            <select name="curriculum_id" class="form-select ot-input" id="curriculum">
                                <option value="">{{ ___('enrollment.no_curriculum_yet') }}</option>
                                @foreach ($data['curricula'] as $c)
                                    <option value="{{ $c->id }}" data-program="{{ $c->program_id }}" @selected(old('curriculum_id', $defaults['curriculum_id']) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">{{ ___('enrollment.year_level') }} <span class="text-danger">*</span></label>
                            <select name="year_level" class="form-select ot-input">
                                @foreach (Enrollment::YEAR_LEVELS as $y)
                                    <option value="{{ $y }}" @selected(old('year_level', $defaults['year_level']) == $y)>{{ ___('enrollment.year_' . $y) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">{{ ___('enrollment.student_type') }} <span class="text-danger">*</span></label>
                            <select name="student_type" class="form-select ot-input">
                                @foreach (Enrollment::STUDENT_TYPES as $type)
                                    <option value="{{ $type }}" @selected(old('student_type', $defaults['student_type']) === $type)>{{ ___('enrollment.type_' . $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">{{ ___('enrollment.block') }}</label>
                            <input type="text" name="block" value="{{ old('block') }}" class="form-control ot-input" maxlength="30" placeholder="{{ ___('enrollment.block_hint') }}">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                            <input type="text" name="remarks" value="{{ old('remarks') }}" class="form-control ot-input" maxlength="1000">
                        </div>
                    </div>
                    @endif
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('enrollment.enrollments.create') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                        <button class="btn btn-lg ot-btn-primary" type="submit">{{ $tesda ? ___('enrollment.start_eligibility') : ___('enrollment.start_advising') }} <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
@push('script')
    <script>
        (function () {
            const program = document.getElementById('program');
            const curriculum = document.getElementById('curriculum');
            if (!program || !curriculum) return;
            function filter() {
                let keep = false;
                curriculum.querySelectorAll('option[data-program]').forEach(function (option) {
                    const match = option.dataset.program === program.value;
                    option.hidden = !match;
                    option.disabled = !match;
                    if (match && option.selected) keep = true;
                });
                if (!keep) {
                    const first = curriculum.querySelector('option[data-program="' + program.value + '"]');
                    curriculum.value = first ? first.value : '';
                }
            }
            program.addEventListener('change', filter);
            filter();
        })();
    </script>
@endpush
