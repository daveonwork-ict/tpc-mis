@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $enrollment = $data['enrollment'];
    $student = $enrollment->student;
    $person = $student->person;
    $status = $enrollment->status;
    $editable = $enrollment->loadIsEditable() && hasPermission('enrollment_advise');
    $enlisted = $enrollment->courses->where('status', 'enlisted');
    $load = $enrollment->isTrainee() ? ['blocked' => false, 'needs_reason' => false, 'message' => null] : \App\Services\StudentManual::loadCheck($enrollment);
    $rsaReady = $enrollment->isTrainee() || \App\Services\StudentManual::agreementReady($enrollment->student->returnServiceAgreement);
    $canGrade = $status === Enrollment::ENROLLED && hasPermission('enrollment_validate');
    $steps = [Enrollment::ADVISING, Enrollment::FOR_ASSESSMENT, Enrollment::ASSESSED, Enrollment::CLEARED, Enrollment::ENROLLED];
    $stepIndex = array_search($status, $steps, true);

    $actions = '';
    if (hasPermission('enrollment_read') && ! $enrollment->isTrainee()) {
        $actions .= '<a href="' . route('enrollment.students.show', $student) . '" class="btn btn-lg ot-btn-primary-outline"><i class="fa-solid fa-list-check"></i> ' . e(___('enrollment.curriculum_evaluation')) . '</a>';
    }
    if ($status === Enrollment::ENROLLED) {
        $printRoute = $enrollment->isTrainee() ? route('enrollment.enrollments.register', $enrollment) : route('enrollment.enrollments.cor', $enrollment);
        $printLabel = $enrollment->isTrainee() ? ___('enrollment.print_registration') : ___('enrollment.print_cor');
        $actions .= '<a href="' . $printRoute . '" target="_blank" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-print"></i> ' . e($printLabel) . '</a>';
    }
@endphp
@section('content')
    <div class="page-content tpc-adm tpc-enr">
        @include('backend.enrollment.partials.header', [
            'title' => $enrollment->enrollment_no,
            'parent' => [___('enrollment.enrollments'), route('enrollment.enrollments.index', ['term_id' => $enrollment->term_id])],
            'actions' => $actions ?: null,
        ])

        @if ($portal = session('portal'))
            @if ($portal['opened'] ?? false)
                <div class="alert alert-warning"><strong>{{ ___('enrollment.portal_opened') }}</strong> {{ ___('enrollment.sign_in_with') }} <strong>{{ $portal['signin'] }}</strong> {{ ___('enrollment.or') }} {{ $portal['email'] }}. {{ ___('enrollment.temporary_password') }} <strong>{{ $portal['password'] }}</strong>. {{ ___('enrollment.password_shown_once') }}</div>
            @elseif (($portal['reason'] ?? null) === 'missing_email')
                <div class="alert alert-warning">{{ ___('enrollment.portal_needs_email') }}</div>
            @endif
        @endif
        @if ($guardianPortal = session('guardian_portal'))
            @if ($guardianPortal['opened'] ?? false)
                <div class="alert alert-warning"><strong>{{ ___('enrollment.guardian_opened') }}</strong> {{ $guardianPortal['email'] }}. {{ ___('enrollment.temporary_password') }} <strong>{{ $guardianPortal['password'] }}</strong>. {{ ___('enrollment.password_shown_once') }}</div>
            @endif
        @endif

        <div class="tpc-adm-summary mb-24">
            <div class="tpc-adm-summary__main">
                <div class="tpc-adm-kicker">
                    @if ($enrollment->isTrainee())
                        {{ ___('enrollment.type_' . $enrollment->student_type) }}
                    @else
                        {{ ___('enrollment.year_' . $enrollment->year_level) }} · {{ ___('enrollment.type_' . $enrollment->student_type) }}
                    @endif
                </div>
                <h3>{{ $person?->full_name ?? $student->full_name }}</h3>
                <p>{{ $enrollment->program->name }} · {{ $enrollment->batch->name ?? $enrollment->term->name }}@if ($enrollment->block) · {{ ___('enrollment.block') }} {{ $enrollment->block }}@endif</p>
            </div>
            <div class="tpc-adm-summary__meta">
                <div><span>{{ ___('common.status') }}</span>@include('backend.enrollment.partials.status', ['status' => $status, 'label' => $enrollment->isTrainee() && $status === Enrollment::ADVISING ? ___('enrollment.eligibility') : null])</div>
                <div><span>{{ ___('enrollment.student_no') }}</span><strong>{{ $student->student_code ?: ___('enrollment.on_validation') }}</strong></div>
                @if ($enrollment->isTrainee())
                    <div><span>{{ ___('admissions.uli') }}</span><strong>{{ $person?->uli ?: ___('enrollment.not_yet_recorded') }}</strong></div>
                    <div><span>{{ ___('enrollment.training_batch') }}</span><strong>{{ $enrollment->batch->code ?? '—' }}</strong></div>
                @else
                    <div><span>{{ ___('enrollment.units') }}</span><strong class="{{ ($load['blocked'] || $load['needs_reason']) ? 'text-warning' : '' }}">{{ Enrollment::units($enrollment->total_units) }}{{ $enrollment->program->max_units !== null ? ' / ' . Enrollment::units($enrollment->program->max_units) : '' }}</strong></div>
                    <div><span>{{ ___('enrollment.curriculum') }}</span><strong>{{ $enrollment->curriculum->name ?? '—' }}</strong></div>
                @endif
            </div>
        </div>

        @if (!$enrollment->isClosed())
            <ol class="tpc-enr-steps mb-24">
                @foreach ($steps as $i => $step)
                    <li class="{{ $stepIndex !== false && $i < $stepIndex ? 'done' : '' }} {{ $i === $stepIndex ? 'current' : '' }}">
                        <span>{{ $i + 1 }}</span>{{ $enrollment->isTrainee() && $step === Enrollment::ADVISING ? ___('enrollment.step_eligibility') : ___('enrollment.step_' . $step) }}
                    </li>
                @endforeach
            </ol>
        @endif

        @if (session('block_skipped'))
            <div class="alert alert-warning">
                <strong>{{ ___('enrollment.skipped_from_block') }}</strong>
                <ul class="mb-0 mt-1">
                    @foreach (session('block_skipped') as $label => $reasons)
                        <li><b>{{ $label }}</b>: {{ implode('; ', $reasons) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                @if ($enrollment->isTrainee())
                    @include('backend.enrollment.enrollments.trainee')
                @else
                {{-- Study load --}}
                <div class="card ot-card mb-24">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="mb-0 text-nowrap">{{ ___('enrollment.study_load') }}</h4>
                        <span class="small text-muted">{{ $enlisted->count() }} {{ ___('enrollment.courses_lower') }} · {{ Enrollment::units($enrollment->total_units) }} {{ ___('enrollment.units_lower') }}</span>
                    </div>
                    <div class="card-body">
                        @if ($enlisted->isEmpty())
                            <p class="text-muted mb-0">{{ ___('enrollment.load_empty_help') }}</p>
                        @else
                            @if ($canGrade)<form action="{{ route('enrollment.enrollments.results', $enrollment) }}" method="post">@csrf @endif
                            <div class="table-responsive">
                                <table class="table table-bordered role-table tpc-enr-table">
                                    <thead class="thead">
                                        <tr>
                                            <th>{{ ___('enrollment.course') }}</th>
                                            <th>{{ ___('enrollment.schedule') }}</th>
                                            <th class="text-center">{{ ___('enrollment.units') }}</th>
                                            @if ($status === Enrollment::ENROLLED || $status === Enrollment::WITHDRAWN)
                                                <th>{{ ___('enrollment.final_grade') }}</th>
                                                <th>{{ ___('enrollment.result') }}</th>
                                            @endif
                                            @if ($editable)<th class="action"></th>@endif
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">
                                        @foreach ($enlisted as $course)
                                            <tr>
                                                <td>
                                                    <strong>{{ $course->subject->code }}</strong> <span class="small text-muted">{{ $course->offering->section_code }}</span>
                                                    <div class="small">{{ $course->subject->name }}</div>
                                                    @if ($course->override_reason)
                                                        <div class="small text-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ $course->override_reason }}</div>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @forelse ($course->offering->schedules as $slot)
                                                        <div><span class="text-nowrap">{{ $slot->label }}</span>@if ($slot->room) · {{ $slot->room }}@endif</div>
                                                    @empty
                                                        {{ ___('enrollment.tba') }}
                                                    @endforelse
                                                    <div class="text-muted">{{ $course->offering->faculty_name ?? ___('enrollment.faculty_tba') }}</div>
                                                </td>
                                                <td class="text-center fw-semibold">
                                                    {{ Enrollment::units($course->units) }}
                                                    @if ($status === Enrollment::ENROLLED && hasPermission('enrollment_advise') && ! $course->unofficial_drop)
                                                        <button class="btn btn-sm btn-outline-danger mt-1" type="submit" form="drop-course-{{ $course->id }}">{{ ___('enrollment.drop_course') }}</button>
                                                    @endif
                                                </td>
                                                @if ($status === Enrollment::ENROLLED || $status === Enrollment::WITHDRAWN)
                                                    @if ($canGrade)
                                                        <td>
                                                            <input type="text" name="results[{{ $course->id }}][final_grade]" value="{{ $course->final_grade }}" class="form-control form-control-sm" maxlength="10" style="width:70px">
                                                            <input type="date" name="results[{{ $course->id }}][removal_on]" value="{{ $course->removal_on?->format('Y-m-d') }}" class="form-control form-control-sm mt-1">
                                                            <label class="small"><input type="checkbox" name="results[{{ $course->id }}][standing_passing]" value="1" @checked($course->standing_passing)> {{ ___('enrollment.standing_passing') }}</label>
                                                        </td>
                                                        <td>
                                                            {{ $course->result ? ___('enrollment.' . $course->result) : '—' }}
                                                            @if ($course->unofficial_drop)<div class="small text-danger">{{ ___('enrollment.unofficial_drop') }}</div>@endif
                                                            @if ($course->final_grade === 'INC' && $enrollment->term?->end_date && $enrollment->term->end_date->copy()->addYear()->lt(now()->startOfDay()))
                                                                <div class="small text-danger">{{ ___('enrollment.inc_overdue') }}</div>
                                                            @endif
                                                        </td>
                                                    @else
                                                        <td>{{ $course->final_grade ?: '—' }}</td>
                                                        <td>{!! $course->result ? view('backend.enrollment.partials.status', ['status' => $course->result])->render() : '—' !!}</td>
                                                    @endif
                                                @endif
                                                @if ($editable)
                                                    <td class="action">
                                                        <form action="{{ route('enrollment.enrollments.courses.drop', [$enrollment, $course]) }}" method="post">
                                                            @csrf
                                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ ___('enrollment.remove') }}"><i class="fa-solid fa-xmark"></i></button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($canGrade)
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                                    <span class="small text-muted">{{ ___('enrollment.grade_help') }}</span>
                                    <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.save_results') }}</button>
                                </div>
                                </form>
                            @endif
                            @if ($status === Enrollment::ENROLLED && hasPermission('enrollment_advise'))
                                @foreach ($enlisted as $course)
                                    @if (! $course->unofficial_drop)
                                        <form id="drop-course-{{ $course->id }}" action="{{ route('enrollment.enrollments.courses.drop', [$enrollment, $course]) }}" method="post">
                                            @csrf
                                        </form>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Add courses --}}
                @if ($editable)
                    <div class="card ot-card mb-24" id="add-courses">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.add_courses') }}</h4></div>
                        <div class="card-body">
                            @if ($data['blocks']->isNotEmpty())
                                <form action="{{ route('enrollment.enrollments.block', $enrollment) }}" method="post" class="tpc-enr-block mb-3">
                                    @csrf
                                    <div>
                                        <label class="form-label mb-1">{{ ___('enrollment.load_block') }}</label>
                                        <div class="small text-muted">{{ ___('enrollment.load_block_help') }}</div>
                                    </div>
                                    <select name="block" class="form-select ot-input">
                                        @foreach ($data['blocks'] as $b)
                                            <option value="{{ $b->section_code }}" @selected($enrollment->block === $b->section_code)>{{ $b->section_code }} ({{ $b->courses }})</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.load') }}</button>
                                </form>
                            @endif

                            <form method="get" action="{{ route('enrollment.enrollments.show', $enrollment) }}#add-courses" class="row g-2 align-items-end mb-3">
                                <div class="col-md-5">
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control ot-input" placeholder="{{ ___('enrollment.code_or_title') }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="block" class="form-select ot-input">
                                        <option value="">{{ ___('enrollment.all_sections') }}</option>
                                        @foreach ($data['blocks'] as $b)
                                            <option value="{{ $b->section_code }}" @selected(request('block') === $b->section_code)>{{ $b->section_code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="all" value="1" id="all-programs" @checked(request()->boolean('all'))>
                                        <label class="form-check-label small" for="all-programs">{{ ___('enrollment.other_programs') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-lg ot-btn-primary-outline w-100" type="submit">{{ ___('common.Search') }}</button>
                                </div>
                            </form>

                            @forelse ($data['candidates'] as $offering)
                                @php
                                    $issues = collect($offering->issues);
                                    $blocking = $issues->reject(fn ($i) => in_array($i['code'], Enrollment::OVERRIDABLE_ISSUES, true));
                                @endphp
                                <div class="tpc-enr-offer {{ $issues->isNotEmpty() ? 'has-issues' : '' }}">
                                    <div class="tpc-enr-offer__info">
                                        <strong>{{ $offering->subject->code }}</strong> <span class="tpc-enr-tag">{{ $offering->section_code }}</span>
                                        <span class="small text-muted">{{ Enrollment::units($offering->subject->total_units) }} {{ ___('enrollment.units_lower') }}</span>
                                        <div class="small">{{ $offering->subject->name }}</div>
                                        <div class="small text-muted">
                                            {{ $offering->schedule_text ?: ___('enrollment.schedule_tba') }}
                                            · {{ $offering->seats }}{{ $offering->capacity ? '/' . $offering->capacity : '' }} {{ ___('enrollment.enlisted_lower') }}
                                        </div>
                                        @foreach ($issues as $issue)
                                            <div class="small {{ in_array($issue['code'], Enrollment::OVERRIDABLE_ISSUES, true) ? 'text-warning' : 'text-danger' }}"><i class="fa-solid fa-circle-exclamation"></i> {{ $issue['message'] }}</div>
                                        @endforeach
                                    </div>
                                    <div class="tpc-enr-offer__action">
                                        @if ($issues->isEmpty())
                                            <form action="{{ route('enrollment.enrollments.courses.add', $enrollment) }}" method="post">
                                                @csrf
                                                <input type="hidden" name="offering_id" value="{{ $offering->id }}">
                                                <button class="btn btn-sm ot-btn-primary" type="submit"><i class="fa-solid fa-plus"></i> {{ ___('enrollment.add') }}</button>
                                            </form>
                                        @elseif ($blocking->isEmpty() && $data['canOverride'])
                                            <form action="{{ route('enrollment.enrollments.courses.add', $enrollment) }}" method="post" class="tpc-enr-override">
                                                @csrf
                                                <input type="hidden" name="offering_id" value="{{ $offering->id }}">
                                                <input type="text" name="override_reason" class="form-control form-control-sm" placeholder="{{ ___('enrollment.override_reason') }}" maxlength="500" required>
                                                <button class="btn btn-sm btn-outline-warning" type="submit">{{ ___('enrollment.add_anyway') }}</button>
                                            </form>
                                        @else
                                            <span class="small text-muted">{{ $blocking->isEmpty() ? ___('enrollment.needs_override') : ___('enrollment.cannot_add') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">
                                    {{ ___('enrollment.no_matching_offerings') }}
                                    @if (hasPermission('offering_read'))<a href="{{ route('enrollment.offerings.index', ['term_id' => $enrollment->term_id]) }}">{{ ___('enrollment.course_offerings') }}</a>@endif
                                </p>
                            @endforelse
                        </div>
                    </div>
                @endif
                @endif

                {{-- Assessment --}}
                @if (in_array($status, [Enrollment::FOR_ASSESSMENT, Enrollment::ASSESSED, Enrollment::CLEARED, Enrollment::ENROLLED, Enrollment::WITHDRAWN], true))
                    <div class="card ot-card mb-24">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ ___('enrollment.assessment') }}</h4>
                            @if ($enrollment->assessed_at)
                                <span class="small text-muted">{{ $enrollment->assessed_at->format('d M Y h:i A') }} · {{ $enrollment->assessor->name ?? '—' }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($enrollment->assessed_at)
                                @if ($enrollment->assessmentLines->isEmpty())
                                    <p class="text-muted">{{ ___('enrollment.no_fee_lines') }}</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered role-table tpc-enr-table">
                                            <thead class="thead">
                                                <tr>
                                                    <th>{{ ___('enrollment.fee_name') }}</th>
                                                    <th class="text-end">{{ ___('enrollment.computation') }}</th>
                                                    <th class="text-end">{{ ___('enrollment.amount') }}</th>
                                                    <th class="text-end">{{ ___('enrollment.less_subsidy') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="tbody">
                                                @foreach ($enrollment->assessmentLines as $line)
                                                    <tr>
                                                        <td>{{ $line->name }}<div class="small text-muted">{{ ___('enrollment.fee_' . $line->category) }}</div></td>
                                                        <td class="text-end small">{{ $line->basis === 'fixed' ? ___('enrollment.basis_fixed') : Enrollment::units($line->quantity) . ' × ₱' . number_format($line->rate, 2) }}</td>
                                                        <td class="text-end">₱{{ number_format($line->amount, 2) }}</td>
                                                        <td class="text-end">{{ $line->subsidy > 0 ? '₱' . number_format($line->subsidy, 2) : '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                                <dl class="tpc-enr-totals">
                                    <dt>{{ ___('enrollment.total_assessment') }}</dt><dd>₱{{ number_format($enrollment->assessed_total, 2) }}</dd>
                                    <dt>{{ ___('enrollment.less_ra_10931') }}</dt><dd>₱{{ number_format($enrollment->subsidy_total, 2) }}</dd>
                                    <dt class="net">{{ ___('enrollment.amount_due') }}</dt><dd class="net">₱{{ number_format($enrollment->net_total, 2) }}</dd>
                                </dl>
                            @else
                                <p class="text-muted">{{ ___('enrollment.awaiting_assessment') }}</p>
                            @endif

                            @if (in_array($status, [Enrollment::FOR_ASSESSMENT, Enrollment::ASSESSED], true) && hasPermission('enrollment_assess'))
                                <form action="{{ route('enrollment.enrollments.assess', $enrollment) }}" method="post" class="tpc-enr-assess">
                                    @csrf
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="free_tuition" value="1" id="free-tuition" @checked($enrollment->free_tuition)>
                                        <label class="form-check-label" for="free-tuition">{{ ___('enrollment.apply_ra_10931') }}</label>
                                        <div class="small text-muted">{{ ___('enrollment.apply_ra_10931_help') }}</div>
                                    </div>
                                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ $enrollment->assessed_at ? ___('enrollment.reassess') : ___('enrollment.generate_assessment') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($enrollment->assessed_at)
                    @include('backend.enrollment.enrollments.clearance')
                @endif
            </div>

            <div class="col-xl-4">
                @unless ($enrollment->isTrainee())
                    @include('backend.enrollment.enrollments.return-service')
                    @include('backend.enrollment.enrollments.academic-scholarship')
                @endunless
                {{-- Next step --}}
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.next_step') }}</h4></div>
                    <div class="card-body">
                        @switch($status)
                            @case(Enrollment::ADVISING)
                                @if ($enrollment->isTrainee())
                                    <p class="small">{{ ___('enrollment.next_tesda_advising') }}</p>
                                @else
                                    <p class="small">{{ ___('enrollment.next_advising') }}</p>
                                @endif
                                @if ($enrollment->payments->whereNull('voided_at')->isNotEmpty() || $enrollment->scholarships->whereNull('voided_at')->isNotEmpty())
                                    <div class="alert alert-info small">{{ ___('enrollment.receipts_carry_forward') }}</div>
                                @endif
                                @if (hasPermission('enrollment_advise'))
                                    <form action="{{ route('enrollment.enrollments.submit', $enrollment) }}" method="post" class="mb-3">
                                        @csrf
                                        @if ($load['blocked'] || $load['needs_reason'])
                                            <div class="alert alert-{{ $load['blocked'] ? 'danger' : 'warning' }} small mb-2">{{ $load['message'] }} ({{ Enrollment::units($enrollment->total_units) }})</div>
                                            @if ($load['needs_reason'] && $data['canOverride'])
                                                <input type="text" name="overload_reason" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.overload_reason') }}" maxlength="255" required>
                                            @endif
                                        @endif
                                        <button class="btn btn-lg ot-btn-primary w-100" type="submit" @disabled($enrollment->isTrainee() ? ! $enrollment->eligibility_met : ($enlisted->isEmpty() || $load['blocked'] || ($load['needs_reason'] && !$data['canOverride'])))>{{ ___('enrollment.send_for_assessment') }}</button>
                                    </form>
                                @endif
                                @break
                            @case(Enrollment::FOR_ASSESSMENT)
                                <p class="small">{{ ___('enrollment.next_for_assessment') }}</p>
                                @break
                            @case(Enrollment::ASSESSED)
                                <p class="small">{{ ___('enrollment.next_assessed') }}</p>
                                @break
                            @case(Enrollment::CLEARED)
                                <p class="small">{{ ___('enrollment.next_cleared') }}</p>
                                @if (hasPermission('enrollment_validate'))
                                    @unless ($rsaReady)
                                        <div class="alert alert-warning small">{{ ___('enrollment.rsa_required') }}</div>
                                    @endunless
                                    <form action="{{ route('enrollment.enrollments.validate', $enrollment) }}" method="post" class="mb-3" onsubmit="return confirm(@js(___('enrollment.confirm_validate')))">
                                        @csrf
                                        <button class="btn btn-lg ot-btn-primary w-100" type="submit" @disabled(! $rsaReady)><i class="fa-solid fa-stamp"></i> {{ ___('enrollment.validate_enrollment') }}</button>
                                    </form>
                                @endif
                                @break
                            @case(Enrollment::ENROLLED)
                                <p class="small">{{ $enrollment->isTrainee() ? ___('enrollment.next_enrolled_trainee') : ___('enrollment.next_enrolled') }}</p>
                                <a href="{{ $enrollment->isTrainee() ? route('enrollment.enrollments.register', $enrollment) : route('enrollment.enrollments.cor', $enrollment) }}" target="_blank" class="btn btn-lg ot-btn-primary w-100 mb-3"><i class="fa-solid fa-print"></i> {{ $enrollment->isTrainee() ? ___('enrollment.print_registration') : ___('enrollment.print_cor') }}</a>
                                @break
                            @default
                                <p class="small mb-0">{{ ___('enrollment.final_' . $status) }}</p>
                        @endswitch

                        @if (in_array($status, [Enrollment::FOR_ASSESSMENT, Enrollment::ASSESSED, Enrollment::CLEARED], true) && hasPermission('enrollment_advise'))
                            <form action="{{ route('enrollment.enrollments.return', $enrollment) }}" method="post" class="tpc-enr-secondary">
                                @csrf
                                <input type="text" name="remarks" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_optional') }}" maxlength="500">
                                <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.return_to_advising') }}</button>
                            </form>
                        @endif

                        @if (!$enrollment->isClosed() && hasPermission('enrollment_validate'))
                            @php
                                $statusActions = $status === Enrollment::ENROLLED ? ['reopen', 'withdraw'] : ['cancel'];
                            @endphp
                            <form action="{{ route('enrollment.enrollments.status', $enrollment) }}" method="post" class="tpc-enr-secondary" onsubmit="return confirm(@js(___('enrollment.confirm_status_change')))">
                                @csrf
                                <input type="text" name="remarks" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_required') }}" maxlength="500" required>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach ($statusActions as $action)
                                        <button class="btn btn-sm {{ $action === 'reopen' ? 'ot-btn-primary-outline' : 'btn-outline-danger' }}" type="submit" name="action" value="{{ $action }}">{{ ___('enrollment.action_' . $action) }}</button>
                                    @endforeach
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Details --}}
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.enrollment_details') }}</h4></div>
                    <div class="card-body">
                        @unless ($enrollment->isTrainee())
                            @include('backend.enrollment.enrollments.retention')
                        @endunless
                        @if ($enrollment->isTrainee())
                            <dl class="tpc-adm-dl">
                                <dt>{{ ___('enrollment.training_batch') }}</dt><dd>{{ $enrollment->batch->name ?? '—' }}</dd>
                                @if ($enrollment->remarks)<dt>{{ ___('enrollment.remarks') }}</dt><dd>{{ $enrollment->remarks }}</dd>@endif
                                @if ($enrollment->advised_at)<dt>{{ ___('enrollment.advised_by') }}</dt><dd>{{ $enrollment->adviser->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->advised_at->format('d M Y h:i A') }}</span></dd>@endif
                                @if ($enrollment->cleared_at)<dt>{{ ___('enrollment.cleared_by') }}</dt><dd>{{ $enrollment->clearer->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->cleared_at->format('d M Y h:i A') }}</span></dd>@endif
                                @if ($enrollment->validated_at)<dt>{{ ___('enrollment.validated_by') }}</dt><dd>{{ $enrollment->validator->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->validated_at->format('d M Y h:i A') }}</span></dd>@endif
                            </dl>
                        @elseif ($editable)
                            <form action="{{ route('enrollment.enrollments.details', $enrollment) }}" method="post">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">{{ ___('enrollment.curriculum') }}</label>
                                    <select name="curriculum_id" class="form-select ot-input">
                                        <option value="">{{ ___('enrollment.no_curriculum_yet') }}</option>
                                        @foreach ($data['curricula'] as $c)
                                            <option value="{{ $c->id }}" @selected($enrollment->curriculum_id == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label">{{ ___('enrollment.year_level') }}</label>
                                        <select name="year_level" class="form-select ot-input">
                                            @foreach (Enrollment::YEAR_LEVELS as $y)
                                                <option value="{{ $y }}" @selected($enrollment->year_level == $y)>{{ ___('enrollment.year_' . $y) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">{{ ___('enrollment.block') }}</label>
                                        <input type="text" name="block" value="{{ $enrollment->block }}" class="form-control ot-input" maxlength="30">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ ___('enrollment.student_type') }}</label>
                                    <select name="student_type" class="form-select ot-input">
                                        @foreach (Enrollment::STUDENT_TYPES as $type)
                                            <option value="{{ $type }}" @selected($enrollment->student_type === $type)>{{ ___('enrollment.type_' . $type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ ___('enrollment.academic_standing') }}</label>
                                    <select name="academic_standing" class="form-select ot-input">
                                        @foreach (\App\Services\StudentManual::STANDINGS as $standing)
                                            <option value="{{ $standing }}" @selected(($student->academic_standing ?? 'regular') === $standing)>{{ ___('enrollment.standing_' . $standing) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="small"><input type="checkbox" name="is_graduating" value="1" @checked($enrollment->is_graduating)> {{ ___('enrollment.graduating') }}</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ ___('enrollment.remarks') }}</label>
                                    <textarea name="remarks" rows="2" class="form-control ot-input">{{ $enrollment->remarks }}</textarea>
                                </div>
                                <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.save') }}</button>
                            </form>
                        @else
                            <dl class="tpc-adm-dl">
                                <dt>{{ ___('enrollment.curriculum') }}</dt><dd>{{ $enrollment->curriculum->name ?? '—' }}</dd>
                                <dt>{{ ___('enrollment.block') }}</dt><dd>{{ $enrollment->block ?: '—' }}</dd>
                                @if ($enrollment->overload_reason)<dt>{{ ___('enrollment.overload') }}</dt><dd>{{ $enrollment->overload_reason }}</dd>@endif
                                @if ($enrollment->remarks)<dt>{{ ___('enrollment.remarks') }}</dt><dd>{{ $enrollment->remarks }}</dd>@endif
                                @if ($enrollment->advised_at)<dt>{{ ___('enrollment.advised_by') }}</dt><dd>{{ $enrollment->adviser->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->advised_at->format('d M Y h:i A') }}</span></dd>@endif
                                @if ($enrollment->cleared_at)<dt>{{ ___('enrollment.cleared_by') }}</dt><dd>{{ $enrollment->clearer->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->cleared_at->format('d M Y h:i A') }}</span></dd>@endif
                                @if ($enrollment->validated_at)<dt>{{ ___('enrollment.validated_by') }}</dt><dd>{{ $enrollment->validator->name ?? '—' }}<br><span class="small text-muted">{{ $enrollment->validated_at->format('d M Y h:i A') }}</span></dd>@endif
                            </dl>
                        @endif
                    </div>
                </div>

                @if ($status === Enrollment::ENROLLED && hasPermission('enrollment_validate'))
                    <div class="card ot-card mb-24">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.portal') }}</h4></div>
                        <div class="card-body">
                            @if ($student->user_id)
                                <p class="small mb-3">{{ ___('enrollment.portal_ready') }} <strong>{{ $student->student_code }}</strong> {{ ___('enrollment.or') }} {{ $student->email }}</p>
                            @else
                                <form action="{{ route('enrollment.enrollments.portal', $enrollment) }}" method="post" class="mb-3">
                                    @csrf
                                    <label class="form-label">{{ ___('enrollment.student_email') }}</label>
                                    <input type="email" name="email" value="{{ old('email', $person?->email) }}" class="form-control ot-input mb-2" required>
                                    <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.open_portal') }}</button>
                                </form>
                            @endif
                            @if ($student->parent_guardian_id)
                                <p class="small mb-0">{{ ___('enrollment.guardian_ready') }} {{ $student->parent->guardian_email ?? '' }}</p>
                            @else
                                <form action="{{ route('enrollment.enrollments.guardian', $enrollment) }}" method="post">
                                    @csrf
                                    <label class="form-label">{{ ___('enrollment.guardian_signin') }}</label>
                                    <input type="text" name="guardian_name" value="{{ old('guardian_name', $enrollment->application?->guardian_name) }}" class="form-control ot-input mb-2" placeholder="{{ ___('admissions.guardian_name') }}" required>
                                    <input type="email" name="guardian_email" value="{{ old('guardian_email') }}" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.guardian_email') }}" required>
                                    <input type="text" name="guardian_relation" value="{{ old('guardian_relation', $enrollment->application?->guardian_relationship) }}" class="form-control ot-input mb-2" placeholder="{{ ___('admissions.guardian_relationship') }}">
                                    <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.open_guardian') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Student --}}
                <div class="card ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.student') }}</h4></div>
                    <div class="card-body">
                        <dl class="tpc-adm-dl">
                            @if ($person)
                                <dt>{{ ___('admissions.date_of_birth') }}</dt><dd>{{ $person->date_of_birth?->format('d M Y') ?: '—' }}</dd>
                                <dt>{{ ___('admissions.sex') }}</dt><dd>{{ $person->sex ? ___('admissions.' . $person->sex) : '—' }}</dd>
                                <dt>{{ ___('admissions.mobile') }}</dt><dd>{{ $person->mobile ?: '—' }}</dd>
                                <dt>{{ ___('admissions.email') }}</dt><dd class="text-break">{{ $person->email ?: '—' }}</dd>
                                <dt>{{ ___('admissions.address') }}</dt><dd>{{ $person->full_address ?: '—' }}</dd>
                            @endif
                            @if ($enrollment->application)
                                <dt>{{ ___('enrollment.application') }}</dt>
                                <dd>
                                    @if (hasPermission('application_read'))
                                        <a href="{{ route('admissions.applications.show', $enrollment->application) }}">{{ $enrollment->application->application_no }}</a>
                                    @else
                                        {{ $enrollment->application->application_no }}
                                    @endif
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- History --}}
                <div class="card ot-card">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.history') }}</h4></div>
                    <div class="card-body">
                        <ol class="tpc-timeline">
                            @foreach ($enrollment->statusLogs as $log)
                                <li>
                                    <strong>{{ ___('enrollment.log_' . $log->action) }}</strong>
                                    @if ($log->from_status !== $log->to_status)
                                        <span class="small">→ {{ ___('enrollment.' . $log->to_status) }}</span>
                                    @endif
                                    @if ($log->remarks)<div class="small">{{ $log->remarks }}</div>@endif
                                    <div class="small text-muted">{{ $log->created_at?->format('d M Y h:i A') }} · {{ $log->user->name ?? '—' }}</div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
