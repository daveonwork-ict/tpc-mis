@extends('student-panel.partials.master')
@section('title')
    {{ ___('common.Dashboard') }}
@endsection
@php
    use App\Enums\Enrollment;
    $student = $data['student'] ?? null;
    $enrollment = $data['enrollment'] ?? null;
@endphp
@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-xl-8">
            <div class="ot-card mb-24">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0">{{ $enrollment ? ($enrollment->isTrainee() ? ___('enrollment.trainee_registration') : ___('enrollment.enrollment')) : ___('enrollment.enrollment') }}</h4>
                    @if ($enrollment && $enrollment->status === Enrollment::ENROLLED)
                        <a href="{{ route('student-panel.record') }}" target="_blank" class="btn btn-sm ot-btn-primary">{{ $enrollment->isTrainee() ? ___('enrollment.print_registration') : ___('enrollment.print_cor') }}</a>
                    @endif
                </div>
                <div class="card-body">
                    @if (! $student)
                        <p class="text-muted mb-0">{{ ___('enrollment.portal_no_record') }}</p>
                    @elseif (! $enrollment)
                        <p class="mb-1"><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></p>
                        <p class="text-muted mb-0">{{ $student->student_code ?: ___('enrollment.no_student_number_yet') }} · {{ ___('enrollment.portal_no_enrollment') }}</p>
                    @else
                        <p class="mb-1"><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></p>
                        <p class="mb-3">{{ $student->student_code ?: ___('enrollment.on_validation') }} · {{ $enrollment->program->name }} · {{ $enrollment->batch->name ?? $enrollment->term?->name }}</p>
                        <p class="mb-3">@include('backend.enrollment.partials.status', ['status' => $enrollment->status, 'label' => $enrollment->isTrainee() && $enrollment->status === Enrollment::ADVISING ? ___('enrollment.eligibility') : null])</p>
                        @if ($enrollment->assessed_at)
                            <dl class="row mb-3">
                                <dt class="col-6">{{ ___('enrollment.amount_due') }}</dt><dd class="col-6">₱{{ number_format($enrollment->net_total, 2) }}</dd>
                                <dt class="col-6">{{ ___('enrollment.balance') }}</dt><dd class="col-6">₱{{ number_format($enrollment->balance, 2) }}</dd>
                                @if ($enrollment->funding_outcome)
                                    <dt class="col-6">{{ ___('enrollment.funding_outcome') }}</dt><dd class="col-6">{{ ___('enrollment.outcome_' . $enrollment->funding_outcome) }}</dd>
                                @endif
                            </dl>
                        @endif
                        <p class="mb-0"><span class="text-muted">{{ ___('enrollment.gwa') }}</span> <strong>{{ $data['gwa'] !== null ? number_format($data['gwa'], 2) : '—' }}</strong></p>
                    @endif
                </div>
            </div>

            @if ($enrollment && ! $enrollment->isTrainee() && $enrollment->enlisted->isNotEmpty())
                <div class="ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.schedule') }}</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ ___('enrollment.course') }}</th>
                                        <th>{{ ___('enrollment.schedule') }}</th>
                                        <th>{{ ___('enrollment.room') }}</th>
                                        <th class="text-center">{{ ___('enrollment.units') }}</th>
                                        @if ($enrollment->status === Enrollment::ENROLLED)
                                            <th>{{ ___('enrollment.final_grade') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($enrollment->enlisted as $course)
                                        <tr>
                                            <td>{{ $course->subject->code }} · {{ $course->subject->name }}</td>
                                            <td>{{ $course->offering->schedule_text ?: ___('enrollment.schedule_tba') }}</td>
                                            <td>{{ $course->offering->room_text ?: '—' }}</td>
                                            <td class="text-center">{{ Enrollment::units($course->units) }}</td>
                                            @if ($enrollment->status === Enrollment::ENROLLED)
                                                <td>{{ $course->final_grade ?: '—' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif ($enrollment?->isTrainee())
                <div class="ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.training_batch') }}</h4></div>
                    <div class="card-body">
                        <p class="mb-1">{{ $enrollment->batch->name ?? '—' }}@if ($enrollment->batch?->schedule) · {{ $enrollment->batch->schedule }}@endif</p>
                        <p class="text-muted mb-0">{{ $enrollment->batch->venue ?: ___('enrollment.not_yet_recorded') }}</p>
                        @if ($enrollment->competency_result)
                            <p class="mt-2 mb-0">{{ ___('enrollment.competency_result') }} <strong>{{ ___('enrollment.' . $enrollment->competency_result) }}</strong></p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <div class="col-xl-4">
            <div class="ot-card mb-24">
                <div class="card-body">
                    <p class="mb-2">{{ ___('common.notice_board') }} <strong>{{ $data['totalNotice'] ?? 0 }}</strong></p>
                    <p class="mb-0">{{ ___('settings.event') }} <strong>{{ $data['totalEvent'] ?? 0 }}</strong></p>
                </div>
            </div>
            <div class="ot-card mb-24">
                    <div class="card-header"><h4 class="mb-0">{{ ___('dashboard.upcoming_events') }}</h4></div>
                <div class="card-body">
                    @forelse ($data['events'] ?? [] as $event)
                        <p class="mb-2"><strong>{{ $event->title ?? $event->name }}</strong><br><span class="text-muted">{{ $event->date }}</span></p>
                    @empty
                        <p class="text-muted mb-0">{{ ___('dashboard.no_upcoming_events') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
