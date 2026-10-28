@php
    use App\Enums\Enrollment;
    $batch = $enrollment->batch;
    $program = $enrollment->program;
    $canAdvise = $status === Enrollment::ADVISING && hasPermission('enrollment_advise');
    $canEditProfile = ! $enrollment->isClosed() && hasPermission('enrollment_advise');
@endphp
<div class="card ot-card mb-24">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">{{ ___('enrollment.qualification') }}</h4>
        @if ($batch)
            <span class="small text-muted">{{ $batch->seats_taken ?? $batch->activeEnrollmentCount() }} / {{ $batch->slots }} {{ ___('enrollment.seats_lower') }}</span>
        @endif
    </div>
    <div class="card-body">
        <dl class="tpc-adm-dl">
            <dt>{{ ___('enrollment.qualification') }}</dt><dd>{{ $program->name }}</dd>
            <dt>{{ ___('enrollment.registration_number') }}</dt>
            <dd>{{ $program->authority_reference ?: ___('admissions.to_be_confirmed') }}</dd>
            <dt>{{ ___('enrollment.training_hours') }}</dt>
            <dd>{{ $program->training_hours !== null ? rtrim(rtrim(number_format((float) $program->training_hours, 1), '0'), '.') : ___('admissions.to_be_confirmed') }}</dd>
            <dt>{{ ___('enrollment.training_batch') }}</dt>
            <dd>
                {{ $batch->name ?? '—' }}@if ($batch?->code) · {{ $batch->code }}@endif
                @if ($batch?->schedule)<br><span class="small text-muted">{{ $batch->schedule }}</span>@endif
                @if ($batch?->venue)<br><span class="small text-muted">{{ $batch->venue }}</span>@endif
                @if ($batch?->start_date)
                    <br><span class="small text-muted">{{ $batch->start_date->format('d M Y') }}@if ($batch->end_date) – {{ $batch->end_date->format('d M Y') }}@endif</span>
                @endif
            </dd>
            <dt>{{ ___('enrollment.trainer') }}</dt>
            <dd>{{ $batch?->trainer ? $batch->trainer->last_name . ', ' . $batch->trainer->first_name : '—' }}</dd>
            @if ($program->entry_requirements)
                <dt>{{ ___('enrollment.entry_requirements') }}</dt>
                <dd style="white-space: pre-wrap;">{{ $program->entry_requirements }}</dd>
            @endif
        </dl>
        <p class="small text-muted mb-0">{{ ___('enrollment.assessment_uses_fees') }} {{ ___('enrollment.bsrs_outside') }}</p>
    </div>
</div>

<div class="card ot-card mb-24">
    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.eligibility') }}</h4></div>
    <div class="card-body">
        @if ($enrollment->eligibility_met === null)
            <p class="mb-2"><span class="tpc-pill tpc-pill--warning">{{ ___('enrollment.eligibility_not_recorded') }}</span></p>
        @elseif ($enrollment->eligibility_met)
            <p class="mb-2"><span class="tpc-pill tpc-pill--success">{{ ___('enrollment.eligibility_met') }}</span></p>
        @else
            <p class="mb-2"><span class="tpc-pill tpc-pill--danger">{{ ___('enrollment.eligibility_not_met') }}</span></p>
        @endif
        @if ($enrollment->eligibility_remarks)
            <p class="small">{{ $enrollment->eligibility_remarks }}</p>
        @endif
        @if ($canAdvise)
            <form action="{{ route('enrollment.enrollments.eligibility', $enrollment) }}" method="post">
                @csrf
                <div class="d-flex gap-3 mb-2">
                    <label class="form-check">
                        <input class="form-check-input" type="radio" name="eligibility_met" value="1" @checked(old('eligibility_met', $enrollment->eligibility_met ? '1' : null) === '1') required>
                        <span class="form-check-label">{{ ___('enrollment.eligibility_met') }}</span>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="radio" name="eligibility_met" value="0" @checked(old('eligibility_met') === '0' || ($enrollment->eligibility_met !== null && ! $enrollment->eligibility_met && old('eligibility_met') === null))>
                        <span class="form-check-label">{{ ___('enrollment.eligibility_not_met') }}</span>
                    </label>
                </div>
                <textarea name="eligibility_remarks" rows="2" class="form-control ot-input mb-2" maxlength="500" placeholder="{{ ___('enrollment.eligibility_remarks_hint') }}">{{ old('eligibility_remarks', $enrollment->eligibility_remarks) }}</textarea>
                <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.record_eligibility') }}</button>
            </form>
        @elseif ($enrollment->eligibility_met === null && ! $program->entry_requirements)
            <p class="small text-muted mb-0">{{ ___('enrollment.entry_requirements_blank') }}</p>
        @endif
    </div>
</div>

<div class="card ot-card mb-24">
    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.learner_profile') }}</h4></div>
    <div class="card-body">
        @if ($canEditProfile)
            <form action="{{ route('enrollment.enrollments.learner', $enrollment) }}" method="post">
                @csrf
                <div class="mb-2">
                    <label class="form-label">{{ ___('admissions.uli') }}</label>
                    <input type="text" name="uli" value="{{ old('uli', $person?->uli) }}" class="form-control ot-input" maxlength="30">
                    <div class="small text-muted mt-1">{{ ___('enrollment.uli_help') }}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ ___('admissions.highest_attainment') }}</label>
                    <input type="text" name="highest_attainment" value="{{ old('highest_attainment', $enrollment->highest_attainment) }}" class="form-control ot-input" maxlength="190">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ___('admissions.employment_status') }}</label>
                    <input type="text" name="employment_status" value="{{ old('employment_status', $enrollment->employment_status) }}" class="form-control ot-input" maxlength="190" placeholder="{{ ___('enrollment.employment_hint') }}">
                </div>
                <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.save') }}</button>
            </form>
        @else
            <dl class="tpc-adm-dl">
                <dt>{{ ___('admissions.uli') }}</dt><dd>{{ $person?->uli ?: ___('enrollment.not_yet_recorded') }}</dd>
                <dt>{{ ___('admissions.highest_attainment') }}</dt><dd>{{ $enrollment->highest_attainment ?: '—' }}</dd>
                <dt>{{ ___('admissions.employment_status') }}</dt><dd>{{ $enrollment->employment_status ?: '—' }}</dd>
            </dl>
        @endif
    </div>
</div>
