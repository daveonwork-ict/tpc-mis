@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($canEdit = hasPermission('enrollment_setup_update'))
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => $data['title']])
        @include('backend.enrollment.setup._tabs')

        <form action="{{ route('enrollment.setup.settings.save') }}" method="post">
            @csrf
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card ot-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.student_number') }}</h4></div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-8">
                                    <label class="form-label">{{ ___('enrollment.format') }}</label>
                                    <input type="text" name="student_number_format" value="{{ old('student_number_format', $data['format']) }}" class="form-control ot-input" maxlength="40" @disabled(!$canEdit)>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">{{ ___('enrollment.sequence_digits') }}</label>
                                    <input type="number" name="student_number_padding" value="{{ old('student_number_padding', $data['padding']) }}" min="3" max="8" class="form-control ot-input" @disabled(!$canEdit)>
                                </div>
                            </div>
                            <p class="small text-muted mb-2">{{ ___('enrollment.format_help') }}</p>
                            @if ($data['next'])
                                <p class="mb-0">{{ ___('enrollment.next_student_number') }}: <strong>{{ $data['next'] }}</strong></p>
                            @endif
                            <div class="alert alert-warning small mt-3 mb-0">{{ ___('enrollment.student_number_confirm') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card ot-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.terms_open_for_enrollment') }}</h4></div>
                        <div class="card-body">
                            <p class="small text-muted">{{ ___('enrollment.terms_open_help') }}</p>
                            @foreach ($data['terms'] as $term)
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" name="enrollment_open[]" value="{{ $term->id }}" id="term-{{ $term->id }}" @checked($term->enrollment_open) @disabled(!$canEdit)>
                                    <label class="form-check-label" for="term-{{ $term->id }}">{{ $term->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @if ($canEdit)
                <div class="d-flex justify-content-end mt-24">
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save') }}</button>
                </div>
            @endif
        </form>
    </div>
@endsection
