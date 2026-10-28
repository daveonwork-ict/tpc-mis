@extends('backend.master')
@section('title')
    {{ ___('enrollment.my_classes') }}
@endsection
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => ___('enrollment.my_classes')])

        @if (! $linked)
            <div class="alert alert-warning">{{ ___('enrollment.staff_not_linked') }}</div>
        @endif

        <div class="card ot-card mb-24">
            <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.course_offerings') }}</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead class="thead">
                            <tr>
                                <th>{{ ___('enrollment.course') }}</th>
                                <th>{{ ___('enrollment.term') }}</th>
                                <th>{{ ___('enrollment.schedule') }}</th>
                                <th class="text-center">{{ ___('enrollment.enrolled') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($offerings as $offering)
                                <tr>
                                    <td><a href="{{ route('enrollment.classes.offering', $offering) }}">{{ $offering->subject->code }} {{ $offering->section_code }}</a><div class="small text-muted">{{ $offering->subject->name }}</div></td>
                                    <td>{{ $offering->term->name ?? '—' }}</td>
                                    <td>{{ $offering->schedule_text ?: ___('enrollment.schedule_tba') }}</td>
                                    <td class="text-center">{{ $offering->seats_taken }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted p-4">{{ ___('enrollment.no_classes') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.training_batches') }}</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead class="thead">
                            <tr>
                                <th>{{ ___('enrollment.training_batch') }}</th>
                                <th>{{ ___('enrollment.qualification') }}</th>
                                <th>{{ ___('enrollment.schedule') }}</th>
                                <th class="text-center">{{ ___('enrollment.enrolled') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($batches as $batch)
                                <tr>
                                    <td><a href="{{ route('enrollment.classes.batch', $batch) }}">{{ $batch->name }}</a><div class="small text-muted">{{ $batch->code }}</div></td>
                                    <td>{{ $batch->program->name ?? '—' }}</td>
                                    <td>{{ $batch->schedule ?: '—' }}</td>
                                    <td class="text-center">{{ $batch->seats_taken }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted p-4">{{ ___('enrollment.no_classes') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
