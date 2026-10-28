@extends('backend.master')

@section('title')
    {{ ___('dashboard.Dashboard') }}
@endsection

@use('App\Enums\Admission')
@use('App\Enums\Enrollment', 'EnrollmentStatus')

@php
    $kpi = $data['kpi'];
    $term = $data['enrollment_term'];
    $canApplications = hasPermission('application_read');
    $canEnrollment = hasPermission('enrollment_read');
    $canInquiries = hasPermission('inquiry_read');
    $canSetup = hasPermission('admission_setup_read');
@endphp

@section('content')
    <div class="page-content tpc-dash">
        <section class="tpc-dash-hero">
            <div class="tpc-dash-seals">
                <img src="{{ @globalAsset(setting('dark_logo'), '40X40.webp') }}" alt="{{ settingLocale('application_name') }}">
                @if (setting('province_logo'))
                    <img src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="{{ ___('common.official_seal_of_the_province_of_tarlac') }}">
                @endif
            </div>
            <div class="tpc-dash-identity">
                <p class="tpc-dash-kicker">{{ ___('common.republic_of_the_philippines') }}</p>
                <h2>{{ settingLocale('application_name') }}</h2>
                <p class="tpc-dash-sub">{{ ___('common.province_of_tarlac') }}</p>
            </div>
            @if ($data['session'])
                <p class="tpc-dash-year">{{ $data['session']->name }}</p>
            @endif
        </section>

        <div class="tpc-terms mb-24">
            <div class="tpc-terms__item">
                <span class="tpc-terms__label"><i class="las la-door-open"></i> {{ ___('dashboard.admission_open_for') }}</span>
                <div class="tpc-terms__chips">
                    @forelse ($data['admission_terms'] as $t)
                        <span class="tpc-pill tpc-pill--success">{{ $t->name }}</span>
                    @empty
                        <span class="tpc-pill tpc-pill--secondary">{{ ___('dashboard.no_term_open') }}</span>
                    @endforelse
                </div>
            </div>
            <div class="tpc-terms__item">
                <span class="tpc-terms__label"><i class="las la-clipboard-list"></i> {{ ___('dashboard.enrollment_open_for') }}</span>
                <div class="tpc-terms__chips">
                    @if ($term)
                        <span class="tpc-pill tpc-pill--success">{{ $term->name }}</span>
                    @else
                        <span class="tpc-pill tpc-pill--secondary">{{ ___('dashboard.no_term_open') }}</span>
                    @endif
                </div>
            </div>
            @if ($canSetup)
                <a class="tpc-terms__link" href="{{ route('admissions.setup.terms') }}">{{ ___('dashboard.manage_terms') }} <i class="las la-arrow-right"></i></a>
            @endif
        </div>

        {{-- Key figures --}}
        <div class="tpc-kpis mb-24">
            @if ($canApplications)
                <a class="tpc-kpi" href="{{ route('admissions.applications.index') }}">
                    <i class="las la-file-signature"></i>
                    <strong>{{ number_format($kpi['applications_in_process']) }}</strong>
                    <span>{{ ___('dashboard.applications_in_process') }}</span>
                </a>
            @endif
            @if ($canEnrollment)
                <a class="tpc-kpi tpc-kpi--gold" href="{{ hasPermission('enrollment_create') ? route('enrollment.enrollments.create') : route('enrollment.enrollments.index') }}">
                    <i class="las la-user-check"></i>
                    <strong>{{ number_format($kpi['awaiting_enrollment']) }}</strong>
                    <span>{{ ___('dashboard.admitted_awaiting_enrollment') }}</span>
                </a>
                <a class="tpc-kpi" href="{{ route('enrollment.enrollments.index', array_filter(['term_id' => $term?->id, 'status' => EnrollmentStatus::ENROLLED])) }}">
                    <i class="las la-user-graduate"></i>
                    <strong>{{ $kpi['enrolled'] === null ? '—' : number_format($kpi['enrolled']) }}</strong>
                    <span>{{ ___('dashboard.enrolled_this_term') }}</span>
                </a>
            @endif
            @if (hasPermission('student_read'))
                <a class="tpc-kpi" href="{{ route('student.index') }}">
                    <i class="las la-users"></i>
                    <strong>{{ number_format($kpi['students']) }}</strong>
                    <span>{{ ___('dashboard.active_students') }}</span>
                </a>
            @endif
            @if (hasPermission('user_read'))
                <a class="tpc-kpi" href="{{ route('users.index') }}">
                    <i class="las la-chalkboard-teacher"></i>
                    <strong>{{ number_format($kpi['staff']) }}</strong>
                    <span>{{ ___('dashboard.faculty_and_staff') }}</span>
                </a>
            @endif
            @if ($canInquiries)
                <a class="tpc-kpi tpc-kpi--gold" href="{{ route('admissions.inquiries.index', ['status' => 'new']) }}">
                    <i class="las la-comments"></i>
                    <strong>{{ number_format($kpi['new_inquiries']) }}</strong>
                    <span>{{ ___('dashboard.new_inquiries') }}</span>
                </a>
            @endif
        </div>

        <div class="row">
            {{-- Admissions pipeline --}}
            @if ($canApplications)
                <div class="col-xl-7 mb-24">
                    <div class="ot-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h4 class="mb-0">{{ ___('dashboard.admissions_pipeline') }}</h4>
                                <small class="text-muted">{{ $data['session'] ? ___('dashboard.applications_for') . ' ' . $data['session']->name : ___('dashboard.all_applications') }}</small>
                            </div>
                            <a class="tpc-dash-link" href="{{ route('admissions.applications.index') }}">{{ ___('dashboard.view_applications') }} <i class="las la-arrow-right"></i></a>
                        </div>
                        <div class="card-body">
                            @if ($data['pipeline']['total'] === 0)
                                <div class="tpc-empty">
                                    <i class="las la-inbox"></i>
                                    <p>{{ ___('dashboard.no_applications_yet') }}</p>
                                    @if (hasPermission('application_create'))
                                        <a class="btn btn-sm ot-btn-primary" href="{{ route('admissions.applications.create') }}">{{ ___('admissions.new_walk_in_application') }}</a>
                                    @endif
                                </div>
                            @else
                                <ul class="tpc-bars">
                                    @foreach ($data['pipeline']['rows'] as $status => $count)
                                        <li>
                                            <a href="{{ route('admissions.applications.index', ['status' => $status]) }}">
                                                <span class="tpc-bars__label">{{ ___('admissions.' . $status) }}</span>
                                                <span class="tpc-bars__track">
                                                    <span class="tpc-bars__fill tpc-bars__fill--{{ Admission::badge($status) }}"
                                                        style="width: {{ $data['pipeline']['max'] ? max($count ? 4 : 0, round($count / $data['pipeline']['max'] * 100)) : 0 }}%"></span>
                                                </span>
                                                <b>{{ number_format($count) }}</b>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="tpc-bars__total">{{ ___('dashboard.total_applications') }}: <b>{{ number_format($data['pipeline']['total']) }}</b></p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Enrollment for the open term --}}
            @if ($canEnrollment)
                <div class="col-xl-5 mb-24">
                    <div class="ot-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h4 class="mb-0">{{ ___('dashboard.enrollment') }}</h4>
                                <small class="text-muted">{{ $term?->name ?? ___('dashboard.no_term_open_for_enrollment') }}</small>
                            </div>
                            <a class="tpc-dash-link" href="{{ route('enrollment.enrollments.index', array_filter(['term_id' => $term?->id])) }}">{{ ___('dashboard.view_enrollments') }} <i class="las la-arrow-right"></i></a>
                        </div>
                        <div class="card-body">
                            @if (! $term)
                                <div class="tpc-empty">
                                    <i class="las la-calendar-times"></i>
                                    <p>{{ ___('dashboard.open_a_term_hint') }}</p>
                                </div>
                            @else
                                @php
                                    $enr = $data['enrollment'];
                                    $enrolledCount = $enr['statuses'][EnrollmentStatus::ENROLLED];
                                    $active = $enrolledCount + $enr['in_progress'];
                                @endphp
                                <div class="tpc-enr-split">
                                    <div>
                                        <strong>{{ number_format($enrolledCount) }}</strong>
                                        <span>{{ ___('enrollment.enrolled') }}</span>
                                    </div>
                                    <div>
                                        <strong>{{ number_format($enr['in_progress']) }}</strong>
                                        <span>{{ ___('dashboard.still_in_process') }}</span>
                                    </div>
                                </div>
                                <div class="tpc-meter" title="{{ ___('enrollment.enrolled') }}">
                                    <span style="width: {{ $active ? round($enrolledCount / $active * 100) : 0 }}%"></span>
                                </div>
                                <div class="tpc-status-grid">
                                    @foreach ($enr['statuses'] as $status => $count)
                                        <a href="{{ route('enrollment.enrollments.index', ['term_id' => $term->id, 'status' => $status]) }}">
                                            <span class="tpc-pill tpc-pill--{{ EnrollmentStatus::badge($status) }}">{{ ___('enrollment.' . $status) }}</span>
                                            <b>{{ number_format($count) }}</b>
                                        </a>
                                    @endforeach
                                </div>

                                @if (hasPermission('offering_read') && $data['offerings'])
                                    @php $off = $data['offerings']; @endphp
                                    <div class="tpc-seats">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0">{{ ___('dashboard.course_offerings') }}</h5>
                                            <a class="tpc-dash-link" href="{{ route('enrollment.offerings.index', ['term_id' => $term->id]) }}">{{ ___('dashboard.manage') }} <i class="las la-arrow-right"></i></a>
                                        </div>
                                        @if ($off['open'] === 0)
                                            <p class="text-muted mb-0">{{ ___('dashboard.no_offerings_yet') }}</p>
                                        @else
                                            <dl class="tpc-seats__facts">
                                                <div><dt>{{ ___('dashboard.open_offerings') }}</dt><dd>{{ number_format($off['open']) }}</dd></div>
                                                <div><dt>{{ ___('dashboard.sections') }}</dt><dd>{{ number_format($off['sections']) }}</dd></div>
                                                <div><dt>{{ ___('enrollment.seats') }}</dt><dd>{{ number_format($off['enlisted']) }}</dd></div>
                                                <div><dt>{{ ___('dashboard.full_offerings') }}</dt><dd>{{ number_format($off['full']) }}</dd></div>
                                            </dl>
                                            @if ($off['fill'] !== null)
                                                <div class="d-flex justify-content-between small text-muted mb-1">
                                                    <span>{{ ___('dashboard.seat_fill') }}</span>
                                                    <span>{{ number_format($off['taken']) }} / {{ number_format($off['capacity']) }} ({{ $off['fill'] }}%)</span>
                                                </div>
                                                <div class="tpc-meter tpc-meter--gold"><span style="width: {{ min(100, $off['fill']) }}%"></span></div>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            @endif

                            @if (hasPermission('offering_read'))
                                @php $batches = $data['batches']; @endphp
                                <div class="tpc-seats {{ $term ? 'mt-4' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">{{ ___('dashboard.training_batches') }}</h5>
                                        <a class="tpc-dash-link" href="{{ route('enrollment.batches.index') }}">{{ ___('dashboard.manage') }} <i class="las la-arrow-right"></i></a>
                                    </div>
                                    @if ($batches['open'] === 0)
                                        <p class="text-muted mb-0">{{ ___('dashboard.no_batches_yet') }}</p>
                                    @else
                                        <dl class="tpc-seats__facts">
                                            <div><dt>{{ ___('dashboard.open_batches') }}</dt><dd>{{ number_format($batches['open']) }}</dd></div>
                                            <div><dt>{{ ___('enrollment.batch_seats') }}</dt><dd>{{ number_format($batches['taken']) }} / {{ number_format($batches['slots']) }}</dd></div>
                                        </dl>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Recent applications --}}
            @if ($canApplications)
                <div class="col-xl-7 mb-24">
                    <div class="ot-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ ___('dashboard.recent_applications') }}</h4>
                            <a class="tpc-dash-link" href="{{ route('admissions.applications.index') }}">{{ ___('dashboard.view_all') }} <i class="las la-arrow-right"></i></a>
                        </div>
                        <div class="card-body p-0">
                            @if ($data['applications']->isEmpty())
                                <div class="tpc-empty"><i class="las la-file-alt"></i><p>{{ ___('dashboard.no_applications_yet') }}</p></div>
                            @else
                                <div class="table-responsive">
                                    <table class="table tpc-dash-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ ___('dashboard.applicant') }}</th>
                                                <th>{{ ___('admissions.program') }}</th>
                                                <th>{{ ___('dashboard.filed') }}</th>
                                                <th>{{ ___('admissions.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data['applications'] as $app)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('admissions.applications.show', $app) }}">{{ $app->person?->full_name ?? '—' }}</a>
                                                        <small>{{ $app->application_no }}</small>
                                                    </td>
                                                    <td>{{ $app->program?->code ?? '—' }}</td>
                                                    <td class="text-nowrap">{{ dateFormat(($app->submitted_at ?? $app->created_at)?->format('Y-m-d')) }}</td>
                                                    <td><span class="tpc-pill tpc-pill--{{ Admission::badge($app->status) }}">{{ ___('admissions.' . $app->status) }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Inquiry follow-ups --}}
            @if ($canInquiries)
                <div class="col-xl-5 mb-24">
                    <div class="ot-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ ___('dashboard.inquiries_to_follow_up') }}</h4>
                            <a class="tpc-dash-link" href="{{ route('admissions.inquiries.index') }}">{{ ___('dashboard.view_all') }} <i class="las la-arrow-right"></i></a>
                        </div>
                        <div class="card-body">
                            @if ($data['follow_ups']->isEmpty())
                                <div class="tpc-empty"><i class="las la-check-circle"></i><p>{{ ___('dashboard.no_open_inquiries') }}</p></div>
                            @else
                                <ul class="tpc-list">
                                    @foreach ($data['follow_ups'] as $inq)
                                        @php $overdue = $inq->follow_up_on?->isPast() && ! $inq->follow_up_on->isToday(); @endphp
                                        <li>
                                            <div class="tpc-list__main">
                                                @if (hasPermission('inquiry_update'))
                                                    <a href="{{ route('admissions.inquiries.edit', $inq) }}">{{ $inq->name }}</a>
                                                @else
                                                    <strong>{{ $inq->name }}</strong>
                                                @endif
                                                <small>{{ $inq->program?->code ?? ___('admissions.track_' . ($inq->track ?: 'ched')) }} · {{ ___('admissions.channel_' . $inq->channel) }}</small>
                                            </div>
                                            <div class="tpc-list__side">
                                                <span class="tpc-pill tpc-pill--{{ Admission::badge($inq->status) }}">{{ ___('admissions.inquiry_' . $inq->status) }}</span>
                                                @if ($inq->follow_up_on)
                                                    <small class="{{ $overdue ? 'text-danger fw-semibold' : '' }}">{{ dateFormat($inq->follow_up_on->format('Y-m-d')) }}</small>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Enrolled by program --}}
            @if ($canEnrollment && $term)
                <div class="col-xl-7 mb-24">
                    <div class="ot-card h-100">
                        <div class="card-header">
                            <h4 class="mb-0">{{ ___('dashboard.enrollment_by_program') }}</h4>
                            <small class="text-muted">{{ $term->name }}</small>
                        </div>
                        <div class="card-body p-0">
                            @if ($data['enrollment']['by_program']->isEmpty())
                                <div class="tpc-empty"><i class="las la-graduation-cap"></i><p>{{ ___('dashboard.no_enrollments_yet') }}</p></div>
                            @else
                                <div class="table-responsive">
                                    <table class="table tpc-dash-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ ___('admissions.program') }}</th>
                                                <th class="text-end">{{ ___('enrollment.enrolled') }}</th>
                                                <th class="text-end">{{ ___('dashboard.still_in_process') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data['enrollment']['by_program'] as $row)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('enrollment.enrollments.index', ['term_id' => $term->id, 'program_id' => $row->program_id]) }}">{{ $row->program?->code }}</a>
                                                        <small>{{ $row->program?->name }}</small>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ number_format($row->enrolled) }}</td>
                                                    <td class="text-end">{{ number_format($row->in_progress) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Events and notices --}}
            @if (hasPermission('event_read') || hasPermission('notice_board_read'))
                <div class="col-xl-5 mb-24">
                    <div class="ot-card h-100">
                        @if (hasPermission('event_read'))
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">{{ ___('dashboard.upcoming_events') }}</h4>
                                <a class="tpc-dash-link" href="{{ route('event.index') }}">{{ ___('dashboard.view_all') }} <i class="las la-arrow-right"></i></a>
                            </div>
                            <div class="card-body">
                                @forelse ($data['events'] as $event)
                                    <div class="tpc-event">
                                        <div class="tpc-event__date">
                                            <b>{{ date('d', strtotime($event->date)) }}</b>
                                            <span>{{ date('M', strtotime($event->date)) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ hasPermission('event_update') ? route('event.edit', $event->id) : route('event.index') }}">{{ Str::limit($event->title, 60) }}</a>
                                            <small>{{ $event->date == date('Y-m-d') ? ___('dashboard.today') : dateFormat($event->date) }}@if ($event->start_time) · {{ timeFormat($event->start_time) }}@endif</small>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">{{ ___('dashboard.no_upcoming_events') }}</p>
                                @endforelse
                            </div>
                        @endif
                        @if (hasPermission('notice_board_read'))
                            <div class="card-header d-flex justify-content-between align-items-center {{ hasPermission('event_read') ? 'border-top' : '' }}">
                                <h4 class="mb-0">{{ ___('dashboard.latest_notices') }}</h4>
                                <a class="tpc-dash-link" href="{{ route('notice-board.index') }}">{{ ___('dashboard.view_all') }} <i class="las la-arrow-right"></i></a>
                            </div>
                            <div class="card-body">
                                @forelse ($data['notices'] as $notice)
                                    <div class="tpc-notice">
                                        <i class="las la-bullhorn"></i>
                                        <div class="min-w-0">
                                            <a href="{{ hasPermission('notice_board_update') ? route('notice-board.edit', $notice->id) : route('notice-board.index') }}">{{ Str::limit($notice->title, 70) }}</a>
                                            <small>{{ dateFormat($notice->publish_date ? date('Y-m-d', strtotime($notice->publish_date)) : $notice->date) }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">{{ ___('dashboard.no_notices') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
