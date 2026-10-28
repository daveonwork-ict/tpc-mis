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

        <form action="{{ route('admissions.applications.update', $data['application']) }}" method="post">
            @csrf
            @method('PUT')
            @include('backend.admissions.applications._form')
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-24">
                <p class="text-muted mb-0 small">{{ ___('admissions.edit_note') }}</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('admissions.applications.show', $data['application']) }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                    <button type="submit" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ ___('common.update') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
