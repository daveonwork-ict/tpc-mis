@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $request = request();
    $canEdit = hasPermission('offering_update');
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', [
            'title' => $data['title'],
            'actions' => $canEdit
                ? '<a href="' . route('enrollment.offerings.create', ['term_id' => $data['termId'], 'section_code' => $request->section_code]) . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-plus"></i> ' . e(___('enrollment.add_offering')) . '</a>'
                : null,
        ])

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('enrollment.term') }}</label>
                        <select name="term_id" class="form-select ot-input" onchange="this.form.section_code.value=''; this.form.submit()">
                            @foreach ($data['terms'] as $t)
                                <option value="{{ $t->id }}" @selected($data['termId'] === $t->id)>{{ $t->name }}{{ $t->enrollment_open ? ' • ' . ___('enrollment.open_for_enrollment') : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('enrollment.program') }}</label>
                        <select name="program_id" class="form-select ot-input">
                            <option value="">{{ ___('enrollment.all') }}</option>
                            @foreach ($data['programs'] as $p)
                                <option value="{{ $p->id }}" @selected($request->program_id == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">{{ ___('enrollment.section') }}</label>
                        <select name="section_code" class="form-select ot-input">
                            <option value="">{{ ___('enrollment.all') }}</option>
                            @foreach ($data['sections'] as $code)
                                <option value="{{ $code }}" @selected($request->section_code === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">{{ ___('enrollment.course') }}</label>
                        <input type="text" name="q" value="{{ $request->q }}" class="form-control ot-input" placeholder="{{ ___('enrollment.code_or_title') }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        @if ($canEdit)
            <details class="card ot-card mb-24 tpc-enr-details" @if ($data['curricula']->isNotEmpty() && $data['offerings']->isEmpty()) open @endif>
                <summary class="card-header"><h4 class="mb-0"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ ___('enrollment.generate_section') }}</h4></summary>
                <div class="card-body">
                    @if ($data['curricula']->isEmpty())
                        <p class="mb-0 text-muted">{{ ___('enrollment.generate_needs_curriculum') }}
                            @if (hasPermission('enrollment_setup_read'))<a href="{{ route('enrollment.setup.curricula') }}">{{ ___('enrollment.curricula') }}</a>@endif
                        </p>
                    @else
                        <p class="small text-muted">{{ ___('enrollment.generate_help') }}</p>
                        <form action="{{ route('enrollment.offerings.generate') }}" method="post" class="row g-3 align-items-end">
                            @csrf
                            <input type="hidden" name="term_id" value="{{ $data['termId'] }}">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">{{ ___('enrollment.curriculum') }}</label>
                                <select name="curriculum_id" class="form-select ot-input" required>
                                    @foreach ($data['curricula'] as $c)
                                        <option value="{{ $c->id }}">{{ $c->program->name }} — {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">{{ ___('enrollment.year_level') }}</label>
                                <select name="year_level" class="form-select ot-input">
                                    @foreach (Enrollment::YEAR_LEVELS as $y)
                                        <option value="{{ $y }}">{{ ___('enrollment.year_' . $y) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label">{{ ___('enrollment.section_code') }}</label>
                                <input type="text" name="section_code" class="form-control ot-input" placeholder="{{ ___('enrollment.section_code_hint') }}" maxlength="30" required>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label">{{ ___('enrollment.capacity') }}</label>
                                <input type="number" name="capacity" class="form-control ot-input" min="1" max="1000">
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('enrollment.generate') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </details>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('enrollment.course') }}</th>
                                    <th>{{ ___('enrollment.section') }}</th>
                                    <th>{{ ___('enrollment.schedule') }}</th>
                                    <th>{{ ___('enrollment.faculty') }}</th>
                                    <th class="text-center">{{ ___('enrollment.seats') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if ($canEdit)<th class="action">{{ ___('common.action') }}</th>@endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['offerings'] as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row->subject->code }}</strong>
                                            <div class="small">{{ $row->subject->name }} · {{ Enrollment::units($row->subject->total_units) }} {{ ___('enrollment.units_lower') }}</div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $row->section_code }}</span>
                                            <div class="small text-muted">{{ $row->program->name ?? ___('enrollment.any_program') }}@if ($row->year_level) · {{ ___('enrollment.year_' . $row->year_level) }}@endif</div>
                                        </td>
                                        <td class="small">
                                            @forelse ($row->schedules as $slot)
                                                <div><span class="text-nowrap">{{ $slot->label }}</span>@if ($slot->room) · {{ $slot->room }}@endif</div>
                                            @empty
                                                <span class="tpc-pill tpc-pill--warning">{{ ___('enrollment.tba') }}</span>
                                            @endforelse
                                        </td>
                                        <td class="small">{{ $row->faculty_name ?? ___('enrollment.tba') }}</td>
                                        <td class="text-center">
                                            <span class="{{ $row->capacity && $row->seats >= $row->capacity ? 'text-danger fw-bold' : '' }}">{{ $row->seats }}{{ $row->capacity ? ' / ' . $row->capacity : '' }}</span>
                                        </td>
                                        <td>@include('backend.enrollment.partials.status', ['status' => $row->status])</td>
                                        @if ($canEdit)
                                            <td class="action">
                                                <div class="d-flex gap-2 flex-nowrap">
                                                    <a href="{{ route('enrollment.offerings.edit', $row) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                    @if (!$row->seats)
                                                        <form action="{{ route('enrollment.offerings.delete', $row) }}" method="post" onsubmit="return confirm(@js(___('enrollment.confirm_delete_offering')))">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color p-5">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0">{{ ___('enrollment.no_offerings') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end py-3">
                        {!! $data['offerings']->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
