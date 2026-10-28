@php
    $grant = $enrollment->academicScholarships->firstWhere('voided_at', null);
    $scholarshipCheck = \App\Services\StudentManual::scholarshipEligibility($enrollment);
    $canRecordGrant = hasPermission('enrollment_advise') && ! in_array($enrollment->status, \App\Enums\Enrollment::CLOSED_STATUSES, true);
@endphp
<div class="card ot-card mb-24">
    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.academic_scholarship_title') }}</h4></div>
    <div class="card-body">
        <p class="small text-muted">{{ ___('enrollment.academic_scholarship_help') }}</p>

        @if ($scholarshipCheck['enrollment'])
            <p class="small mb-3">
                <strong>{{ $scholarshipCheck['enrollment']->term->name ?? '' }}</strong>
                — {{ \App\Enums\Enrollment::units($scholarshipCheck['units']) }} {{ ___('enrollment.units_lower') }}
                @if ($scholarshipCheck['gwa'] !== null)
                    · {{ ___('enrollment.academic_scholarship_gwa') }} {{ number_format($scholarshipCheck['gwa'], 2) }}
                @endif
            </p>
        @endif

        @unless ($scholarshipCheck['qualified'])
            <ul class="small mb-3">
                @foreach ($scholarshipCheck['reasons'] as $reason)
                    <li>{{ ___('enrollment.academic_scholarship_reason_' . $reason) }}</li>
                @endforeach
            </ul>
        @endunless

        @if ($grant)
            <p class="mb-1"><strong>{{ ___('enrollment.academic_program_' . $grant->program) }}</strong></p>
            @if ($grant->sponsor)
                <p class="small mb-1">{{ $grant->sponsor }}</p>
            @endif
            <p class="small text-muted mb-3">
                {{ \App\Enums\Enrollment::units($grant->preceding_units) }} {{ ___('enrollment.units_lower') }}
                · {{ number_format($grant->preceding_gwa, 2) }}
                · {{ $grant->recorder->name ?? '—' }}
            </p>
            @if ($canRecordGrant)
                <form action="{{ route('enrollment.enrollments.academic-scholarship', $enrollment) }}" method="post" class="d-flex gap-1">
                    @csrf
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="scholarship_id" value="{{ $grant->id }}">
                    <input type="text" name="reason" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_required') }}" maxlength="255" required>
                    <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit">{{ ___('enrollment.academic_scholarship_remove') }}</button>
                </form>
            @endif
        @elseif ($canRecordGrant && $scholarshipCheck['qualified'])
            <form action="{{ route('enrollment.enrollments.academic-scholarship', $enrollment) }}" method="post">
                @csrf
                <input type="hidden" name="action" value="record">
                <select name="program" class="form-select ot-input mb-2" required>
                    @foreach (\App\Services\StudentManual::SCHOLARSHIP_PROGRAMS as $program)
                        <option value="{{ $program }}">{{ ___('enrollment.academic_program_' . $program) }}</option>
                    @endforeach
                </select>
                <input type="text" name="sponsor" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.academic_scholarship_sponsor') }}" maxlength="190">
                <label class="small d-block mb-2">
                    <input type="checkbox" name="discipline_clear" value="1">
                    {{ ___('enrollment.academic_scholarship_discipline_label') }}
                </label>
                <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.academic_scholarship_record') }}</button>
            </form>
        @endif
    </div>
</div>
