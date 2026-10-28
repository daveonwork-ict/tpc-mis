@php
    use App\Enums\Enrollment;
    $student = $enrollment->student;
    $person = $student->person;
    $courses = $enrollment->enlisted;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ___('enrollment.cor') }} — {{ $student->student_code }}</title>
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
        h1 { text-align: center; font-size: 14px; letter-spacing: 2px; margin: 16px 0 2px; color: #003A63; }
        .term { text-align: center; font-size: 11px; color: #4a5768; margin-bottom: 12px; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 12px; border: 1px solid #d5dbe3; border-radius: 6px; padding: 8px 10px; }
        .info div { display: flex; gap: 6px; }
        .info span { color: #4a5768; min-width: 92px; }
        .info b { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d5dbe3; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #003A63; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        td.num, th.num { text-align: right; }
        td.center, th.center { text-align: center; }
        tfoot td { font-weight: 700; background: #f4f6f9; }
        .section-title { font-size: 11px; font-weight: 700; color: #003A63; text-transform: uppercase; letter-spacing: .6px; margin: 12px 0 4px; }
        .two { display: grid; grid-template-columns: 1.3fr 1fr; gap: 16px; align-items: start; }
        .totals { width: 100%; }
        .totals td { border: 0; padding: 2px 0; }
        .totals tr.net td { border-top: 1px solid #1a2433; font-weight: 800; font-size: 12px; padding-top: 4px; }
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
                <small>{{ ___('enrollment.office_of_the_registrar') }}</small>
            </div>
            <img src="{{ @globalAsset(setting('dark_logo'), '40X40.webp') }}" alt="">
        </div>

        <h1>{{ strtoupper(___('enrollment.cor')) }}</h1>
        <div class="term">{{ $enrollment->term->name }}</div>

        <div class="info">
            <div><span>{{ ___('enrollment.student_no') }}</span><b>{{ $student->student_code }}</b></div>
            <div><span>{{ ___('enrollment.enrollment_no') }}</span><b>{{ $enrollment->enrollment_no }}</b></div>
            <div><span>{{ ___('enrollment.name') }}</span><b>{{ $person?->formal_name ?? ($student->last_name . ', ' . $student->first_name) }}</b></div>
            <div><span>{{ ___('enrollment.student_type') }}</span><b>{{ ___('enrollment.type_' . $enrollment->student_type) }}</b></div>
            <div><span>{{ ___('enrollment.program') }}</span><b>{{ $enrollment->program->name }}</b></div>
            <div><span>{{ ___('enrollment.year_and_block') }}</span><b>{{ ___('enrollment.year_' . $enrollment->year_level) }}{{ $enrollment->block ? ' · ' . $enrollment->block : '' }}</b></div>
            <div><span>{{ ___('enrollment.college_unit') }}</span><b>{{ $enrollment->program->department->name ?? '—' }}</b></div>
            <div><span>{{ ___('enrollment.curriculum') }}</span><b>{{ $enrollment->curriculum->name ?? '—' }}</b></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ ___('enrollment.code') }}</th>
                    <th>{{ ___('enrollment.course_title') }}</th>
                    <th>{{ ___('enrollment.section') }}</th>
                    <th>{{ ___('enrollment.schedule') }}</th>
                    <th>{{ ___('enrollment.room') }}</th>
                    <th>{{ ___('enrollment.faculty') }}</th>
                    <th class="center">{{ ___('enrollment.units') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courses as $course)
                    <tr>
                        <td><b>{{ $course->subject->code }}</b></td>
                        <td>{{ $course->subject->name }}</td>
                        <td>{{ $course->offering->section_code }}</td>
                        <td>{!! $course->offering->schedules->map(fn ($s) => e($s->label))->implode('<br>') ?: e(___('enrollment.tba')) !!}</td>
                        <td>{{ $course->offering->room_text ?: ___('enrollment.tba') }}</td>
                        <td>{{ $course->offering->faculty_name ?? ___('enrollment.tba') }}</td>
                        <td class="center">{{ Enrollment::units($course->units) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="num">{{ ___('enrollment.total_units') }}</td>
                    <td class="center">{{ Enrollment::units($enrollment->total_units) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="two">
            <div>
                <div class="section-title">{{ ___('enrollment.assessment') }}</div>
                @if ($enrollment->assessmentLines->isEmpty())
                    <p class="note">{{ ___('enrollment.no_fee_lines') }}</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>{{ ___('enrollment.fee_name') }}</th>
                                <th class="num">{{ ___('enrollment.amount') }}</th>
                                <th class="num">{{ ___('enrollment.subsidy') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enrollment->assessmentLines as $line)
                                <tr>
                                    <td>{{ $line->name }}</td>
                                    <td class="num">{{ number_format($line->amount, 2) }}</td>
                                    <td class="num">{{ $line->subsidy > 0 ? number_format($line->subsidy, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                @foreach ($enrollment->payments->whereNull('voided_at') as $payment)
                    <p class="note">OR {{ $payment->or_number }} · {{ $payment->paid_on?->format('d M Y') }} · ₱{{ number_format($payment->amount, 2) }}</p>
                @endforeach
                @if ($enrollment->funding_outcome)
                    <p class="note">{{ ___('enrollment.outcome_' . $enrollment->funding_outcome) }}</p>
                @endif
                @if ($enrollment->free_tuition)
                    <p class="note">{{ ___('enrollment.cor_ra_10931_note') }}</p>
                @endif
            </div>
        </div>

        <div class="signs">
            <div>
                <div class="line">{{ $person?->full_name ?? $student->full_name }}</div>
                <small>{{ ___('enrollment.student_signature') }}</small>
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
