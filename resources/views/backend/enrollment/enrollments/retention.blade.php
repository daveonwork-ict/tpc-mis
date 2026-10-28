@php
    $retention = $student->retentionRecords()->with('enrollment.term')->get()->sortBy(function ($row) {
        $rank = ['first' => 1, 'second' => 2, 'midyear' => 3][$row->enrollment->term->semester ?? ''] ?? 9;

        return sprintf('%08d-%d-%08d', (int) ($row->enrollment->term->session_id ?? 0), $rank, (int) $row->enrollment->term_id);
    })->last();
@endphp
@if ($retention)
    <p class="small mb-3">
        <strong>{{ $retention->enrollment->term->name ?? '' }}</strong>
        — {{ rtrim(rtrim(number_format($retention->failure_percent, 2), '0'), '.') }}%
        {{ ___('enrollment.retention_of_units') }}
        ({{ \App\Enums\Enrollment::units($retention->failed_units) }} / {{ \App\Enums\Enrollment::units($retention->enrolled_units) }}).
        {{ ___('enrollment.standing_' . $retention->standing) }}.
        @if ($retention->standing === 'probation' && $retention->band === null && (float) $retention->failed_units <= 0)
            {{ ___('enrollment.retention_may_lift') }}
        @endif
    </p>
@endif
