@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    use App\Enums\Enrollment;
    $curriculum = $data['curriculum'];
    $canEdit = hasPermission('enrollment_setup_update');
    $totalUnits = $curriculum->courses->sum(fn ($c) => $c->subject->total_units);
    $lastYear = old('year_level', session('last_year', 1));
    $lastSemester = old('semester', session('last_semester', 'first'));
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $curriculum->name,
            'parent' => [___('enrollment.curricula'), route('enrollment.setup.curricula')],
            'actions' => $canEdit
                ? '<a href="' . route('enrollment.setup.curricula', ['edit' => $curriculum->id]) . '" class="btn btn-lg ot-btn-primary-outline"><i class="fa-solid fa-pen"></i> ' . e(___('enrollment.edit_details')) . '</a>'
                : null,
        ])

        <div class="tpc-adm-summary mb-24">
            <div class="tpc-adm-summary__main">
                <div class="tpc-adm-kicker">{{ ___('enrollment.curriculum') }}</div>
                <h3>{{ $curriculum->program->name }}</h3>
                <p>{{ $curriculum->effectiveSession->name ?? ___('enrollment.no_effective_year') }}@if ($curriculum->authority_reference) · {{ $curriculum->authority_reference }}@endif</p>
            </div>
            <div class="tpc-adm-summary__meta">
                <div><span>{{ ___('common.status') }}</span>@include('backend.enrollment.partials.status', ['status' => $curriculum->status])</div>
                <div><span>{{ ___('enrollment.courses') }}</span><strong>{{ $curriculum->courses->count() }}</strong></div>
                <div><span>{{ ___('enrollment.total_units') }}</span><strong>{{ Enrollment::units($totalUnits) }}</strong></div>
            </div>
        </div>

        @if ($curriculum->status === 'draft')
            <div class="alert alert-warning">{{ ___('enrollment.curriculum_draft_notice') }}</div>
        @endif

        <div class="row g-4">
            @if ($canEdit)
                <div class="col-xl-4">
                    <form action="{{ route('enrollment.setup.curricula.courses.add', $curriculum) }}" method="post" class="card ot-card" id="add-course">
                        @csrf
                        <div class="card-header"><h4 class="mb-0">{{ ___('enrollment.add_course_to_curriculum') }}</h4></div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.year_level') }}</label>
                                    <select name="year_level" class="form-select ot-input">
                                        @foreach (Enrollment::YEAR_LEVELS as $y)
                                            <option value="{{ $y }}" @selected($lastYear == $y)>{{ ___('enrollment.year_' . $y) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.semester') }}</label>
                                    <select name="semester" class="form-select ot-input">
                                        @foreach (Admission::SEMESTERS as $s)
                                            <option value="{{ $s }}" @selected($lastSemester === $s)>{{ ___('admissions.semester_' . $s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.existing_course') }}</label>
                                <select name="subject_id" class="form-select ot-input">
                                    <option value="">{{ ___('enrollment.choose_or_create_below') }}</option>
                                    @foreach ($data['available'] as $s)
                                        <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->code }} — {{ $s->name }} ({{ Enrollment::units($s->total_units) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tpc-enr-or"><span>{{ ___('enrollment.or_new_course') }}</span></div>
                            <div class="row g-3 mb-3">
                                <div class="col-5">
                                    <label class="form-label">{{ ___('enrollment.course_code') }}</label>
                                    <input type="text" name="new_code" value="{{ old('new_code') }}" class="form-control ot-input" maxlength="30">
                                </div>
                                <div class="col-7">
                                    <label class="form-label">{{ ___('enrollment.course_title') }}</label>
                                    <input type="text" name="new_name" value="{{ old('new_name') }}" class="form-control ot-input" maxlength="150">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.lecture_units') }}</label>
                                    <input type="number" name="lecture_units" value="{{ old('lecture_units', 0) }}" step="0.5" min="0" max="12" class="form-control ot-input">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.lab_units') }}</label>
                                    <input type="number" name="lab_units" value="{{ old('lab_units', 0) }}" step="0.5" min="0" max="12" class="form-control ot-input">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-lg ot-btn-primary" type="submit"><i class="fa-solid fa-plus"></i> {{ ___('enrollment.add_course') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="col">
                @forelse ($data['groups'] as $key => $courses)
                    @php([$year, $semester] = explode('|', $key))
                    <div class="card ot-card mb-24">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ ___('enrollment.year_' . $year) }} · {{ ___('admissions.semester_' . $semester) }}</h4>
                            <span class="small text-muted">{{ Enrollment::units($courses->sum(fn ($c) => $c->subject->total_units)) }} {{ ___('enrollment.units_lower') }}</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered role-table tpc-enr-table">
                                    <thead class="thead">
                                        <tr>
                                            <th>{{ ___('enrollment.course') }}</th>
                                            <th class="text-center">{{ ___('enrollment.lec') }}</th>
                                            <th class="text-center">{{ ___('enrollment.lab') }}</th>
                                            <th class="text-center">{{ ___('enrollment.units') }}</th>
                                            <th>{{ ___('enrollment.requisites') }}</th>
                                            @if ($canEdit)<th class="action">{{ ___('common.action') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">
                                        @foreach ($courses as $course)
                                            <tr>
                                                <td>
                                                    <strong>{{ $course->subject->code }}</strong>
                                                    <div class="small">{{ $course->subject->name }}</div>
                                                </td>
                                                <td class="text-center">{{ Enrollment::units($course->subject->lecture_units) }}</td>
                                                <td class="text-center">{{ Enrollment::units($course->subject->lab_units) }}</td>
                                                <td class="text-center fw-semibold">{{ Enrollment::units($course->subject->total_units) }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @forelse ($course->requisites as $req)
                                                            <span class="tpc-enr-req tpc-enr-req--{{ $req->type }}" title="{{ ___('enrollment.' . $req->type) }}">
                                                                {{ $req->type === 'corequisite' ? ___('enrollment.co_short') : ___('enrollment.pre_short') }}
                                                                {{ $req->subject->code }}
                                                                @if ($canEdit)
                                                                    <form action="{{ route('enrollment.setup.curricula.requisites.delete', $req) }}" method="post" class="d-inline">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" aria-label="{{ ___('enrollment.remove') }}">&times;</button>
                                                                    </form>
                                                                @endif
                                                            </span>
                                                        @empty
                                                            <span class="text-muted small">{{ ___('enrollment.none') }}</span>
                                                        @endforelse
                                                    </div>
                                                    @if ($canEdit && $curriculum->courses->count() > 1)
                                                        <details class="tpc-enr-addreq mt-1">
                                                            <summary><i class="fa-solid fa-plus"></i> {{ ___('enrollment.add') }}</summary>
                                                            <form action="{{ route('enrollment.setup.curricula.requisites.save', $course) }}" method="post">
                                                                @csrf
                                                                <select name="subject_id" class="form-select form-select-sm" required aria-label="{{ ___('enrollment.requisite_course') }}">
                                                                    <option value="">{{ ___('enrollment.requisite_course') }}</option>
                                                                    @foreach ($curriculum->courses as $other)
                                                                        @continue($other->id === $course->id || $course->requisites->contains('subject_id', $other->subject_id))
                                                                        <option value="{{ $other->subject_id }}">{{ $other->subject->code }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="type" class="form-select form-select-sm" aria-label="{{ ___('enrollment.type') }}">
                                                                    @foreach (Enrollment::REQUISITE_TYPES as $t)
                                                                        <option value="{{ $t }}">{{ ___('enrollment.' . $t) }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.save') }}</button>
                                                            </form>
                                                        </details>
                                                    @endif
                                                </td>
                                                @if ($canEdit)
                                                    <td class="action">
                                                        <form action="{{ route('enrollment.setup.curricula.courses.delete', $course) }}" method="post" onsubmit="return confirm(@js(___('enrollment.confirm_remove_course')))">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ ___('enrollment.remove') }}"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card ot-card">
                        <div class="card-body text-center gray-color p-5">
                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                            <p class="mb-0">{{ ___('enrollment.curriculum_empty') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
