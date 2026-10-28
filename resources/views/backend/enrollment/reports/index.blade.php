@extends('backend.master')
@section('title')
    {{ $title }}
@endsection
@php
    $columns = match ($type) {
        'clearance' => ['enrollment.student_no', 'enrollment.name', 'enrollment.program', 'enrollment.term', 'enrollment.amount_due', 'enrollment.less_scholarship', 'enrollment.official_receipts', 'enrollment.balance', 'enrollment.funding_outcome', 'common.status'],
        'batch' => ['enrollment.enrollment_no', 'enrollment.student_no', 'enrollment.name', 'admissions.uli', 'enrollment.student_type', 'enrollment.eligibility', 'common.status'],
        default => ['enrollment.student_no', 'enrollment.name', 'enrollment.program', 'enrollment.year', 'enrollment.student_type', 'enrollment.units', 'common.status'],
    };
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => ___('enrollment.reports'),
            'actions' => '<button class="btn btn-lg ot-btn-primary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> ' . e(___('enrollment.print')) . '</button>',
        ])

        <div class="tpc-adm-chips mb-24">
            @foreach (['enrollment', 'clearance', 'batch'] as $item)
                <a href="{{ route('enrollment.reports.index', ['type' => $item]) }}" class="{{ $type === $item ? 'active' : '' }}">{{ ___('enrollment.report_' . $item) }}</a>
            @endforeach
        </div>

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="row g-3 align-items-end">
                    @if ($type !== 'batch')
                        <div class="col-md-6">
                            <label class="form-label">{{ ___('enrollment.term') }}</label>
                            <select name="term_id" class="form-select ot-input">
                                <option value="">{{ ___('enrollment.all_terms') }}</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label">{{ ___('enrollment.training_batch') }}</label>
                            <select name="batch_id" class="form-select ot-input">
                                @forelse ($batches as $batch)
                                    <option value="{{ $batch->id }}" @selected($batchId == $batch->id)>{{ $batch->name }} · {{ $batch->program->name ?? '' }}</option>
                                @empty
                                    <option value="">{{ ___('enrollment.no_batches') }}</option>
                                @endforelse
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">{{ $title }}</h4></div>
            <div class="card-body">
                <p class="small text-muted">{{ ___('enrollment.report_note') }}</p>
                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead class="thead">
                            <tr>
                                @foreach ($columns as $column)
                                    @php [$file, $key] = explode('.', $column, 2); @endphp
                                    <th>{{ ___($file . '.' . $key) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center text-muted p-4">{{ ___('enrollment.no_enrollments') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0">{{ $rows->count() }} {{ ___('enrollment.rows_lower') }}</p>
            </div>
        </div>
    </div>
@endsection
