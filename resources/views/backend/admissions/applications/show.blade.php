@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    $app = $data['application'];
    $person = $app->person;
    $closed = $app->isClosed();
    $canUpdate = hasPermission('application_update');
    $canVerify = hasPermission('requirement_verify');
    $required = $app->requirements->where('is_required', true);
    $done = $required->whereIn('status', [Admission::REQ_VERIFIED, Admission::REQ_WAIVED])->count();
    $decidable = in_array($app->status, [Admission::REQUIREMENTS_PENDING, Admission::FOR_EVALUATION, Admission::CONDITIONAL], true);

    $headerActions = '';
    $firstEnrollment = $app->enrollments()->oldest('id')->first();
    if ($firstEnrollment && hasPermission('enrollment_read')) {
        $headerActions .= '<a href="' . route('enrollment.enrollments.show', $firstEnrollment) . '" class="btn btn-lg ot-btn-primary-outline"><i class="fa-solid fa-clipboard-list"></i> ' . e(___('enrollment.view_enrollment')) . '</a>';
    } elseif (! $firstEnrollment && $app->track === Admission::TRACK_CHED && in_array($app->status, [Admission::ADMITTED, Admission::CONDITIONAL], true) && hasPermission('enrollment_create')) {
        $headerActions .= '<a href="' . route('enrollment.enrollments.create', ['application' => $app->id]) . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-user-graduate"></i> ' . e(___('enrollment.start_enrollment')) . '</a>';
    }
    if (! $closed && $canUpdate) {
        $headerActions .= '<a href="' . route('admissions.applications.edit', $app) . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-pen"></i> ' . e(___('admissions.edit_details')) . '</a>';
    }
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', [
            'title' => $app->application_no,
            'parent' => [___('admissions.applications'), route('admissions.applications.index')],
            'actions' => $headerActions ?: null,
        ])

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="tpc-adm-summary mb-24">
            <div class="tpc-adm-summary__main">
                <span class="tpc-adm-kicker">{{ ___('admissions.track_' . $app->track) }} · {{ ___('admissions.' . $app->applicant_type) }}</span>
                <h3>{{ $person->full_name }}</h3>
                <p>{{ $app->program->name }}@if ($app->term) · {{ $app->term->name }}@elseif ($app->preferred_schedule) · {{ $app->preferred_schedule }}@endif</p>
            </div>
            <div class="tpc-adm-summary__meta">
                <div>
                    <span>{{ ___('admissions.status') }}</span>
                    @include('backend.admissions.partials.status', ['status' => $app->status])
                </div>
                <div>
                    <span>{{ ___('admissions.required_documents') }}</span>
                    <strong>{{ $done }} / {{ $required->count() }}</strong>
                </div>
                <div>
                    <span>{{ ___('admissions.submitted') }}</span>
                    <strong>{{ dateFormat($app->submitted_at) }}</strong>
                </div>
                <div>
                    <span>{{ ___('admissions.source') }}</span>
                    <strong>{{ ___('admissions.source_' . $app->source) }}</strong>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                {{-- Requirements checklist --}}
                <div class="card ot-card mb-24">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="mb-0 text-nowrap">{{ ___('admissions.requirements_checklist') }}</h4>
                        <span class="small text-muted">{{ ___('admissions.file_rule') }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse ($app->requirements as $item)
                            <div class="tpc-req">
                                <div class="tpc-req__head">
                                    <div>
                                        <strong>{{ $item->requirementType->name }}</strong>
                                        <span class="tpc-req__tag">{{ $item->is_required ? ___('admissions.required') : ___('admissions.optional') }}</span>
                                        @if ($item->remarks)
                                            <div class="small text-muted mt-1"><i class="fa-regular fa-comment"></i> {{ $item->remarks }}</div>
                                        @endif
                                        @if ($item->reviewer)
                                            <div class="small text-muted">{{ ___('admissions.reviewed_by') }} {{ $item->reviewer->name }} · {{ $item->reviewed_at?->format('d M Y h:i A') }}</div>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                        @if ($item->file_path)
                                            <a href="{{ route('admissions.applications.requirement.file', $item) }}" target="_blank" rel="noopener" class="btn btn-sm ot-btn-primary-outline">
                                                <i class="fa-regular fa-file"></i> {{ ___('admissions.view_file') }}
                                            </a>
                                        @endif
                                        @include('backend.admissions.partials.status', ['status' => $item->status])
                                    </div>
                                </div>

                                @if (!$closed && $item->status !== Admission::REQ_VERIFIED && ($canUpdate || $canVerify))
                                    <div class="tpc-req__actions">
                                        @if ($canUpdate)
                                            <form action="{{ route('admissions.applications.requirement.upload', $item) }}" method="post" enctype="multipart/form-data">
                                                @csrf
                                                <label class="btn btn-sm ot-btn-primary text-nowrap mb-0 tpc-req__upload">
                                                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required onchange="this.form.submit()">
                                                    <i class="fa-solid fa-upload"></i> {{ $item->file_path ? ___('admissions.replace') : ___('admissions.upload') }}
                                                </label>
                                            </form>
                                        @endif
                                        @if ($canVerify)
                                            <form action="{{ route('admissions.applications.requirement.review', $item) }}" method="post" class="tpc-req__review">
                                                @csrf
                                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="{{ ___('admissions.remarks_placeholder') }}">
                                                <button name="status" value="verified" class="btn btn-sm btn-success text-nowrap" type="submit" title="{{ $item->file_path ? '' : ___('admissions.verify_original_hint') }}">{{ ___('admissions.verify') }}</button>
                                                <button name="status" value="rejected" class="btn btn-sm btn-outline-danger text-nowrap" type="submit">{{ ___('admissions.reject') }}</button>
                                                @if ($item->is_required && $item->status !== Admission::REQ_WAIVED)
                                                    <button name="status" value="waived" class="btn btn-sm btn-outline-secondary text-nowrap" type="submit">{{ ___('admissions.waive') }}</button>
                                                @endif
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="p-4 mb-0 text-muted">{{ ___('admissions.no_requirements_configured') }}</p>
                        @endforelse
                    </div>
                </div>

                @include('backend.admissions.applications.partials.matrix')

                {{-- Screening --}}
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.screening') }}</h4></div>
                    <div class="card-body">
                        @forelse ($app->screenings as $s)
                            <div class="tpc-adm-entry">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <strong>{{ ___('admissions.screening_' . $s->type) }}@if ($s->score) · {{ $s->score }}@endif</strong>
                                    @include('backend.admissions.partials.status', ['status' => $s->result])
                                </div>
                                @if ($s->remarks)<p class="mb-1">{{ $s->remarks }}</p>@endif
                                <div class="small text-muted">{{ $s->screened_on ? dateFormat($s->screened_on) : dateFormat($s->created_at) }} · {{ $s->screener->name ?? '—' }}</div>
                            </div>
                        @empty
                            <p class="text-muted">{{ ___('admissions.no_screening_yet') }}</p>
                        @endforelse

                        @if (!$closed && hasPermission('screening_create'))
                            <form action="{{ route('admissions.applications.screening', $app) }}" method="post" class="row g-3 mt-1">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label">{{ ___('admissions.screening_type') }}</label>
                                    <select name="type" class="form-select ot-input" required>
                                        @foreach (Admission::SCREENING_TYPES as $t)
                                            <option value="{{ $t }}">{{ ___('admissions.screening_' . $t) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ ___('admissions.result') }}</label>
                                    <select name="result" class="form-select ot-input" required>
                                        @foreach (Admission::SCREENING_RESULTS as $r)
                                            <option value="{{ $r }}">{{ ___('admissions.' . $r) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ ___('admissions.score') }}</label>
                                    <input type="text" name="score" class="form-control ot-input">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ ___('admissions.date') }}</label>
                                    <input type="date" name="screened_on" value="{{ now()->format('Y-m-d') }}" class="form-control ot-input">
                                </div>
                                <div class="col-md-9">
                                    <input type="text" name="remarks" class="form-control ot-input" placeholder="{{ ___('admissions.remarks') }}">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('admissions.record_screening') }}</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Decision --}}
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.admission_decision') }}</h4></div>
                    <div class="card-body">
                        @forelse ($app->decisions as $d)
                            <div class="tpc-adm-entry">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <strong>{{ ___('admissions.decision_' . $d->decision) }}</strong>
                                    <span class="small text-muted">{{ $d->created_at->format('d M Y h:i A') }} · {{ $d->decider->name ?? '—' }}</span>
                                </div>
                                @if ($d->conditions)<p class="mb-0"><b>{{ ___('admissions.conditions') }}:</b> {{ $d->conditions }}</p>@endif
                                @if ($d->reason)<p class="mb-0"><b>{{ ___('admissions.reason') }}:</b> {{ $d->reason }}</p>@endif
                            </div>
                        @empty
                            <p class="text-muted">{{ ___('admissions.no_decision_yet') }}</p>
                        @endforelse

                        @if ($decidable && hasPermission('admission_decide'))
                            @unless ($app->requirementsComplete())
                                <div class="alert alert-warning small">{{ ___('admissions.incomplete_requirements_hint') }}</div>
                            @endunless
                            @php $matrix = \App\Services\StudentManual::matrix($app); @endphp
                            @if ($matrix['ineligible'])
                                <div class="alert alert-danger small">{{ ___('admissions.matrix_' . $matrix['reason']) }}</div>
                            @endif
                            <form action="{{ route('admissions.applications.decision', $app) }}" method="post" class="row g-3 mt-1" data-decision-form>
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label">{{ ___('admissions.decision') }}</label>
                                    <select name="decision" class="form-select ot-input" required>
                                        @foreach (Admission::DECISIONS as $d)
                                            <option value="{{ $d }}" @disabled($d === 'accepted' && !$app->requirementsComplete())>{{ ___('admissions.decision_' . $d) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8" data-decision-field="conditional">
                                    <label class="form-label">{{ ___('admissions.conditions') }}</label>
                                    <textarea name="conditions" rows="2" class="form-control ot-input" placeholder="{{ ___('admissions.conditions_placeholder') }}"></textarea>
                                </div>
                                <div class="col-md-8" data-decision-field="rejected">
                                    <label class="form-label">{{ ___('admissions.reason') }}</label>
                                    <textarea name="reason" rows="2" class="form-control ot-input"></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('admissions.record_decision') }}</button>
                                </div>
                            </form>
                        @elseif (!$closed)
                            <p class="small text-muted mb-0">{{ ___('admissions.decision_available_after_submission') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.applicant') }}</h4></div>
                    <div class="card-body">
                        <dl class="tpc-adm-dl">
                            <dt>{{ ___('admissions.sex') }}</dt><dd>{{ ___('admissions.' . $person->sex) }}</dd>
                            <dt>{{ ___('admissions.date_of_birth') }}</dt><dd>{{ $person->date_of_birth?->format('d M Y') }} ({{ $person->date_of_birth?->age }})</dd>
                            @if ($person->place_of_birth)<dt>{{ ___('admissions.place_of_birth') }}</dt><dd>{{ $person->place_of_birth }}</dd>@endif
                            @if ($person->civil_status)<dt>{{ ___('admissions.civil_status') }}</dt><dd>{{ ___('admissions.' . $person->civil_status) }}</dd>@endif
                            <dt>{{ ___('admissions.mobile') }}</dt><dd>{{ $person->mobile }}</dd>
                            <dt>{{ ___('admissions.email') }}</dt><dd class="text-break">{{ $person->email ?: '—' }}</dd>
                            <dt>{{ ___('admissions.address') }}</dt><dd>{{ $person->full_address }}</dd>
                            @if ($person->lrn)<dt>{{ ___('admissions.lrn') }}</dt><dd>{{ $person->lrn }}</dd>@endif
                            @if ($person->uli)<dt>{{ ___('admissions.uli') }}</dt><dd>{{ $person->uli }}</dd>@endif
                        </dl>
                    </div>
                </div>

                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.educational_background') }}</h4></div>
                    <div class="card-body">
                        <dl class="tpc-adm-dl">
                            <dt>{{ ___('admissions.last_school') }}</dt><dd>{{ $app->last_school ?: '—' }}</dd>
                            @if ($app->last_school_year)<dt>{{ ___('admissions.last_school_year') }}</dt><dd>{{ $app->last_school_year }}</dd>@endif
                            @if ($app->last_program_or_strand)<dt>{{ ___('admissions.last_program_or_strand') }}</dt><dd>{{ $app->last_program_or_strand }}</dd>@endif
                            @if ($app->general_average)<dt>{{ ___('admissions.general_average') }}</dt><dd>{{ $app->general_average }}</dd>@endif
                            @if ($app->highest_attainment)<dt>{{ ___('admissions.highest_attainment') }}</dt><dd>{{ $app->highest_attainment }}</dd>@endif
                            @if ($app->employment_status)<dt>{{ ___('admissions.employment_status') }}</dt><dd>{{ $app->employment_status }}</dd>@endif
                            @if ($app->guardian_name)
                                <dt>{{ ___('admissions.parent_guardian') }}</dt>
                                <dd>{{ $app->guardian_name }}@if ($app->guardian_relationship) ({{ $app->guardian_relationship }})@endif<br>{{ $app->guardian_mobile }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>

                @if ($canUpdate)
                    <div class="card ot-card mb-24">
                        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.application_status') }}</h4></div>
                        <div class="card-body">
                            <form action="{{ route('admissions.applications.status', $app) }}" method="post" onsubmit="return confirm('{{ ___('admissions.confirm_status_change') }}')">
                                @csrf
                                <input type="text" name="remarks" class="form-control ot-input mb-2" placeholder="{{ ___('admissions.remarks') }}">
                                <div class="d-flex gap-2 flex-wrap">
                                    @if (!$closed)
                                        <button name="action" value="defer" class="btn btn-sm btn-outline-secondary" type="submit">{{ ___('admissions.defer') }}</button>
                                        <button name="action" value="withdraw" class="btn btn-sm btn-outline-danger" type="submit">{{ ___('admissions.withdraw') }}</button>
                                    @elseif (in_array($app->status, [Admission::WITHDRAWN, Admission::DEFERRED], true))
                                        <button name="action" value="reopen" class="btn btn-sm ot-btn-primary" type="submit">{{ ___('admissions.reopen') }}</button>
                                    @else
                                        <p class="small text-muted mb-0">{{ ___('admissions.final_status_note') }}</p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.history') }}</h4></div>
                    <div class="card-body">
                        <ol class="tpc-timeline">
                            @foreach ($app->statusLogs as $log)
                                <li>
                                    <strong>{{ ___('admissions.log_' . $log->action) }}</strong>
                                    @if ($log->from_status !== $log->to_status)
                                        <span class="small">→ {{ ___('admissions.' . $log->to_status) }}</span>
                                    @endif
                                    @if ($log->remarks)<div class="small">{{ $log->remarks }}</div>@endif
                                    <div class="small text-muted">{{ $log->created_at?->format('d M Y h:i A') }} · {{ $log->user->name ?? ___('admissions.applicant') }}</div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.querySelector('[data-decision-form]');
            if (!form) return;
            var select = form.querySelector('[name=decision]');
            function apply() {
                form.querySelectorAll('[data-decision-field]').forEach(function (el) {
                    el.style.display = el.dataset.decisionField === select.value ? '' : 'none';
                });
            }
            select.addEventListener('change', apply);
            apply();
        })();
    </script>
@endpush
