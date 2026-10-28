@php
    $student = $enrollment->student;
    $person = $student->person;
    $batch = $enrollment->batch;
    $program = $enrollment->program;
    $application = $enrollment->application;
    $hours = $program->training_hours !== null
        ? rtrim(rtrim(number_format((float) $program->training_hours, 1), '0'), '.')
        : ___('admissions.to_be_confirmed');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ___('enrollment.trainee_registration') }} — {{ $student->student_code }}</title>
    <link rel="icon" href="{{ @globalAsset(setting('favicon'), '40X40.webp') }}">
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Manrope", "Segoe UI", Arial, sans-serif; font-size: 11px; color: #1a2433; background: #eef1f5; }
        .sheet { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 12mm; background: #fff; box-shadow: 0 2px 14px rgba(0, 0, 0, .12); }
        .toolbar { text-align: center; margin: 16px 0 0; }
        .toolbar button { background: #003A63; color: #fff; border: 0; padding: 9px 20px; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .head { display: flex; align-items: center; justify-content: center; gap: 14px; border-bottom: 3px solid #003A63; padding-bottom: 8px; position: relative; }
        .head::after { content: ""; position: absolute; left: 0; right: 0; bottom: -6px; height: 2px; background: #E8A818; }
        .head img { width: 62px; height: 62px; object-fit: contain; }
        .head .org { text-align: center; line-height: 1.35; }
        .head .org small { display: block; font-size: 10px; color: #4a5768; }
        .head .org strong { display: block; font-size: 17px; color: #003A63; letter-spacing: .5px; }
        h1 { text-align: center; font-size: 14px; letter-spacing: 1.4px; margin: 16px 0 2px; color: #003A63; }
        .term { text-align: center; font-size: 11px; color: #4a5768; margin-bottom: 12px; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 12px; border: 1px solid #d5dbe3; border-radius: 6px; padding: 8px 10px; }
        .info div { display: flex; gap: 6px; }
        .info span { color: #4a5768; min-width: 118px; }
        .info b { font-weight: 700; }
        .section-title { font-size: 11px; font-weight: 700; color: #003A63; text-transform: uppercase; letter-spacing: .6px; margin: 12px 0 4px; }
        .two { display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; align-items: start; }
        table { width: 100%; border-collapse: collapse; }
        .totals td { border: 0; padding: 2px 0; }
        .totals tr.net td { border-top: 1px solid #1a2433; font-weight: 800; font-size: 12px; padding-top: 4px; }
        td.num { text-align: right; }
        .note { font-size: 9.5px; color: #4a5768; }
        .signs { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 36px; }
        .signs div { text-align: center; }
        .signs .line { border-top: 1px solid #1a2433; padding-top: 3px; font-weight: 700; }
        .signs small { display: block; color: #4a5768; }
        .foot { margin-top: 18px; display: flex; justify-content: space-between; font-size: 9px; color: #6b7686; border-top: 1px dashed #c4ccd6; padding-top: 6px; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 0; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">{{ ___('enrollment.print') }}</button></div>
    <div class="sheet">
        <div class="head">
            <img src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="">
            <div class="org">
                <small>{{ ___('enrollment.republic') }}</small>
                <small>{{ ___('enrollment.province_of_tarlac') }}</small>
                <strong>{{ strtoupper(settingLocale('application_name') ?: 'Tarlac Provincial College') }}</strong>
                <small>{{ $program->department->name ?? ___('enrollment.tclass') }}</small>
            </div>
            <img src="{{ @globalAsset(setting('dark_logo'), '40X40.webp') }}" alt="">
        </div>

        <h1>{{ strtoupper(___('enrollment.trainee_registration')) }}</h1>
        <div class="term">{{ $batch->name ?? '' }}@if ($batch?->code) · {{ $batch->code }}@endif</div>

        <div class="info">
            <div><span>{{ ___('enrollment.student_no') }}</span><b>{{ $student->student_code }}</b></div>
            <div><span>{{ ___('enrollment.enrollment_no') }}</span><b>{{ $enrollment->enrollment_no }}</b></div>
            <div><span>{{ ___('enrollment.name') }}</span><b>{{ $person?->formal_name ?? ($student->last_name . ', ' . $student->first_name) }}</b></div>
            <div><span>{{ ___('admissions.uli') }}</span><b>{{ $person?->uli ?: ___('enrollment.not_yet_recorded') }}</b></div>
            <div><span>{{ ___('enrollment.student_type') }}</span><b>{{ ___('enrollment.type_' . $enrollment->student_type) }}</b></div>
            <div><span>{{ ___('admissions.sex') }}</span><b>{{ $person?->sex ? ___('admissions.' . $person->sex) : '—' }}</b></div>
            <div><span>{{ ___('admissions.date_of_birth') }}</span><b>{{ $person?->date_of_birth?->format('d M Y') ?: '—' }}</b></div>
            <div><span>{{ ___('admissions.civil_status') }}</span><b>{{ $person?->civil_status ? ___('admissions.' . $person->civil_status) : '—' }}</b></div>
            <div><span>{{ ___('admissions.address') }}</span><b>{{ $person?->full_address ?: '—' }}</b></div>
            <div><span>{{ ___('admissions.mobile') }}</span><b>{{ $person?->mobile ?: '—' }}</b></div>
            <div><span>{{ ___('admissions.highest_attainment') }}</span><b>{{ $enrollment->highest_attainment ?: '—' }}</b></div>
            <div><span>{{ ___('admissions.employment_status') }}</span><b>{{ $enrollment->employment_status ?: '—' }}</b></div>
        </div>

        <div class="section-title">{{ ___('enrollment.qualification') }}</div>
        <div class="info">
            <div><span>{{ ___('enrollment.qualification') }}</span><b>{{ $program->name }}</b></div>
            <div><span>{{ ___('enrollment.registration_number') }}</span><b>{{ $program->authority_reference ?: ___('admissions.to_be_confirmed') }}</b></div>
            <div><span>{{ ___('enrollment.training_hours') }}</span><b>{{ $hours }}</b></div>
            <div><span>{{ ___('enrollment.schedule') }}</span><b>{{ $batch->schedule ?: '—' }}</b></div>
            <div><span>{{ ___('enrollment.venue') }}</span><b>{{ $batch->venue ?: '—' }}</b></div>
            <div><span>{{ ___('enrollment.trainer') }}</span><b>{{ $batch?->trainer ? $batch->trainer->last_name . ', ' . $batch->trainer->first_name : '—' }}</b></div>
            <div><span>{{ ___('enrollment.training_dates') }}</span><b>@if ($batch?->start_date){{ $batch->start_date->format('d M Y') }}@if ($batch->end_date) – {{ $batch->end_date->format('d M Y') }}@endif @else — @endif</b></div>
            @if ($application?->guardian_name)
                <div><span>{{ ___('admissions.guardian_name') }}</span><b>{{ $application->guardian_name }}</b></div>
            @endif
        </div>

        <div class="two">
            <div>
                @if ($enrollment->eligibility_remarks)
                    <div class="section-title">{{ ___('enrollment.eligibility') }}</div>
                    <p class="note">{{ $enrollment->eligibility_met ? ___('enrollment.eligibility_met') : ___('enrollment.eligibility_not_met') }}. {{ $enrollment->eligibility_remarks }}</p>
                @endif
                <p class="note">{{ ___('enrollment.registration_note') }}</p>
                @if ($enrollment->competency_result)
                    <p class="note">{{ ___('enrollment.competency_result') }}: {{ ___('enrollment.' . $enrollment->competency_result) }}</p>
                @endif
            </div>
            <div>
                <div class="section-title">{{ ___('enrollment.summary') }}</div>
                <table class="totals">
                    <tr><td>{{ ___('enrollment.total_assessment') }}</td><td class="num">₱{{ number_format($enrollment->assessed_total, 2) }}</td></tr>
                    <tr><td>{{ ___('enrollment.less_ra_10931') }}</td><td class="num">₱{{ number_format($enrollment->subsidy_total, 2) }}</td></tr>
                    <tr><td>{{ ___('enrollment.amount_due') }}</td><td class="num">₱{{ number_format($enrollment->net_total, 2) }}</td></tr>
                    <tr><td>{{ ___('enrollment.less_scholarship') }}</td><td class="num">₱{{ number_format($enrollment->scholarship_total, 2) }}</td></tr>
                    <tr><td>{{ ___('enrollment.official_receipts') }}</td><td class="num">₱{{ number_format($enrollment->paid_total, 2) }}</td></tr>
                    <tr class="net"><td>{{ ___('enrollment.balance') }}</td><td class="num">₱{{ number_format($enrollment->balance, 2) }}</td></tr>
                </table>
                @foreach ($enrollment->scholarships->whereNull('voided_at') as $scholarship)
                    <p class="note">{{ $scholarship->label() }} · ₱{{ number_format($scholarship->amount, 2) }}</p>
                @endforeach
                @foreach ($enrollment->payments->whereNull('voided_at') as $payment)
                    <p class="note">OR {{ $payment->or_number }} · {{ $payment->paid_on?->format('d M Y') }} · ₱{{ number_format($payment->amount, 2) }}</p>
                @endforeach
                @if ($enrollment->funding_outcome)
                    <p class="note">{{ ___('enrollment.outcome_' . $enrollment->funding_outcome) }}</p>
                @endif
            </div>
        </div>

        <div class="signs">
            <div>
                <div class="line">{{ $person?->full_name ?? $student->full_name }}</div>
                <small>{{ ___('enrollment.trainee_signature') }}</small>
            </div>
            <div>
                <div class="line">&nbsp;</div>
                <small>{{ ___('enrollment.college_registrar') }}</small>
            </div>
        </div>

        <div class="foot">
            <span>{{ ___('enrollment.validated_on') }} {{ $enrollment->validated_at?->format('d M Y h:i A') }}</span>
            <span>{{ ___('enrollment.printed_on') }} {{ now()->format('d M Y h:i A') }}</span>
        </div>
    </div>
</body>
</html>
