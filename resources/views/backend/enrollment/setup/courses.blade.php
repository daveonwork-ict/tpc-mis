@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $course = $data['course'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => $data['title']])
        @include('backend.enrollment.setup._tabs')

        <div class="row g-4">
            @if (hasPermission('enrollment_setup_update'))
                <div class="col-xl-4">
                    <form action="{{ route('enrollment.setup.courses.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $course->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $course->exists ? ___('enrollment.edit_course') : ___('enrollment.add_course') }}</h4></div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-5">
                                    <label class="form-label">{{ ___('enrollment.course_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="code" value="{{ old('code', $course->code) }}" class="form-control ot-input" maxlength="30" required>
                                </div>
                                <div class="col-7">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        <option value="1" @selected(old('status', $course->status) == 1)>{{ ___('common.active') }}</option>
                                        <option value="0" @selected($course->exists && old('status', $course->status) == 0)>{{ ___('common.inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.course_title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $course->name) }}" class="form-control ot-input" maxlength="150" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.lecture_units') }}</label>
                                    <input type="number" name="lecture_units" value="{{ old('lecture_units', $course->lecture_units ?? 0) }}" step="0.5" min="0" max="12" class="form-control ot-input" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.lab_units') }}</label>
                                    <input type="number" name="lab_units" value="{{ old('lab_units', $course->lab_units ?? 0) }}" step="0.5" min="0" max="12" class="form-control ot-input" required>
                                </div>
                            </div>
                            <p class="small text-muted">{{ ___('enrollment.units_change_help') }}</p>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($course->exists)
                                    <a href="{{ route('enrollment.setup.courses') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                                @endif
                                <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="col">
                <div class="card ot-card">
                    <div class="card-body">
                        <form method="get" class="d-flex gap-2 mb-3">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control ot-input" placeholder="{{ ___('enrollment.search_course') }}">
                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('enrollment.course') }}</th>
                                        <th class="text-center">{{ ___('enrollment.lec') }}</th>
                                        <th class="text-center">{{ ___('enrollment.lab') }}</th>
                                        <th class="text-center">{{ ___('enrollment.units') }}</th>
                                        <th>{{ ___('common.status') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['courses'] as $row)
                                        <tr>
                                            <td><strong>{{ $row->code ?: '—' }}</strong><div class="small">{{ $row->name }}</div></td>
                                            <td class="text-center">{{ Enrollment::units($row->lecture_units) }}</td>
                                            <td class="text-center">{{ Enrollment::units($row->lab_units) }}</td>
                                            <td class="text-center fw-semibold">
                                                @if ($row->total_units > 0)
                                                    {{ Enrollment::units($row->total_units) }}
                                                @else
                                                    <span class="tpc-pill tpc-pill--warning">{{ ___('enrollment.units_not_set') }}</span>
                                                @endif
                                            </td>
                                            <td><span class="tpc-pill tpc-pill--{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? ___('common.active') : ___('common.inactive') }}</span></td>
                                            <td class="action">
                                                @if (hasPermission('enrollment_setup_update'))
                                                    <a href="{{ route('enrollment.setup.courses', ['edit' => $row->id] + request()->only('q', 'page')) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="100%" class="text-center gray-color p-5">{{ ___('enrollment.no_courses') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="ot-pagination pagination-content d-flex justify-content-end py-3">
                            {!! $data['courses']->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
