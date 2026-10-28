@php
    $agreement = $student->returnServiceAgreement;
    $obligation = \App\Services\StudentManual::serviceObligation($enrollment);
    $service = rtrim(rtrim(number_format($obligation['service'], 1, '.', ''), '0'), '.');
    $canRecord = hasPermission('enrollment_advise');
    $canEndorse = hasPermission('enrollment_validate');
    $bothSigned = $agreement?->student_signed_on && $agreement?->guardian_signed_on;
@endphp
<div class="card ot-card mb-24">
    <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.rsa_title') }}</h4></div>
    <div class="card-body">
        <p class="small text-muted">{{ ___('enrollment.rsa_help') }}</p>
        <p class="small mb-3">
            {{ ___('enrollment.rsa_years') }}: <strong>{{ $obligation['years'] }}</strong><br>
            {{ ___('enrollment.rsa_service') }}: <strong>{{ $service }}</strong>
            @unless ($obligation['this_year_counted'])
                <span class="d-block text-muted">{{ ___('enrollment.rsa_counts_when_validated') }}</span>
            @endunless
        </p>

        @if ($agreement?->waived_on)
            <div class="alert alert-info small">
                {{ ___('enrollment.rsa_waived') }}
                <div>{{ $agreement->waiver_authority }}</div>
                <div class="text-muted">{{ $agreement->waived_on->format('d M Y') }}</div>
            </div>
            @if ($canEndorse)
                <form action="{{ route('enrollment.enrollments.return-service', $enrollment) }}" method="post" class="mb-3">
                    @csrf
                    <input type="hidden" name="action" value="clear_waiver">
                    <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.rsa_clear_waiver') }}</button>
                </form>
            @endif
        @endif

        <dl class="tpc-adm-dl">
            <dt>{{ ___('enrollment.rsa_student') }}</dt>
            <dd>{{ $agreement?->student_signed_on ? $agreement->student_signed_on->format('d M Y') : '—' }}</dd>
            <dt>{{ ___('enrollment.rsa_guardian') }}</dt>
            <dd>
                {{ $agreement?->guardian_signed_on ? $agreement->guardian_signed_on->format('d M Y') : '—' }}
                @if ($agreement?->guardian_name)<div class="small text-muted">{{ $agreement->guardian_name }}</div>@endif
            </dd>
            <dt>{{ ___('enrollment.rsa_president') }}</dt>
            <dd>{{ $agreement?->president_signed_on ? $agreement->president_signed_on->format('d M Y') : '—' }}</dd>
        </dl>

        @if ($canRecord && ! $agreement?->waived_on)
            <form action="{{ route('enrollment.enrollments.return-service', $enrollment) }}" method="post" class="mb-2">
                @csrf
                <input type="hidden" name="action" value="student">
                <label class="form-label">{{ ___('enrollment.rsa_student') }}</label>
                <input type="date" name="signed_on" value="{{ $agreement?->student_signed_on?->format('Y-m-d') ?? now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control ot-input mb-2">
                <button class="btn btn-sm ot-btn-primary" type="submit">{{ $agreement?->student_signed_on ? ___('enrollment.rsa_update') : ___('enrollment.rsa_record_student') }}</button>
            </form>
            <form action="{{ route('enrollment.enrollments.return-service', $enrollment) }}" method="post" class="mb-2">
                @csrf
                <input type="hidden" name="action" value="guardian">
                <label class="form-label">{{ ___('enrollment.rsa_guardian') }}</label>
                <input type="text" name="guardian_name" value="{{ $agreement?->guardian_name ?: ($student->parent?->guardian_name ?? '') }}" class="form-control ot-input mb-2" maxlength="190" placeholder="{{ ___('enrollment.rsa_guardian_name') }}">
                <input type="date" name="signed_on" value="{{ $agreement?->guardian_signed_on?->format('Y-m-d') ?? now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control ot-input mb-2">
                <button class="btn btn-sm ot-btn-primary" type="submit">{{ $agreement?->guardian_signed_on ? ___('enrollment.rsa_update') : ___('enrollment.rsa_record_guardian') }}</button>
            </form>
        @endif

        @if ($canEndorse && $bothSigned && ! $agreement?->waived_on)
            <form action="{{ route('enrollment.enrollments.return-service', $enrollment) }}" method="post" class="mb-3">
                @csrf
                <input type="hidden" name="action" value="president">
                <label class="form-label">{{ ___('enrollment.rsa_president') }}</label>
                <input type="date" name="signed_on" value="{{ $agreement?->president_signed_on?->format('Y-m-d') ?? now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control ot-input mb-2">
                <button class="btn btn-sm ot-btn-primary" type="submit">{{ $agreement?->president_signed_on ? ___('enrollment.rsa_update') : ___('enrollment.rsa_record_president') }}</button>
            </form>
        @elseif ($canEndorse && ! $bothSigned && ! $agreement?->waived_on)
            <p class="small text-muted">{{ ___('enrollment.rsa_sign_first') }}</p>
        @endif

        @if ($canEndorse && ! $agreement?->waived_on)
            <form action="{{ route('enrollment.enrollments.return-service', $enrollment) }}" method="post">
                @csrf
                <input type="hidden" name="action" value="waive">
                <label class="form-label">{{ ___('enrollment.rsa_waiver') }}</label>
                <textarea name="waiver_authority" rows="2" class="form-control ot-input mb-2" maxlength="500" placeholder="{{ ___('enrollment.rsa_waiver_authority') }}"></textarea>
                <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.rsa_record_waiver') }}</button>
            </form>
        @endif
    </div>
</div>
