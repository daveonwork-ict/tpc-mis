@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', [
            'title' => $data['title'],
            'parent' => [___('admissions.applications'), route('admissions.applications.index')],
        ])

        @if ($data['inquiry'])
            <div class="alert alert-info">{{ ___('admissions.prefilled_from_inquiry') }}: <strong>{{ $data['inquiry']->name }}</strong></div>
        @endif

        <form action="{{ route('admissions.applications.store') }}" method="post">
            @csrf
            @if ($data['inquiry'])
                <input type="hidden" name="inquiry_id" value="{{ $data['inquiry']->id }}">
            @endif
            @include('backend.admissions.applications._form')
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-24">
                <p class="text-muted mb-0 small">{{ ___('admissions.walk_in_note') }}</p>
                <button type="submit" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ ___('admissions.save_application') }}</button>
            </div>
        </form>
    </div>
@endsection
