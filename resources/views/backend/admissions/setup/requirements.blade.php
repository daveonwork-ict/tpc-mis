@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Admission;
    $type = $data['type'];
    $canEdit = hasPermission('admission_setup_update');
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', ['title' => $data['title']])
        @include('backend.admissions.setup._tabs')

        <div class="row g-4 mb-24">
            @if ($canEdit)
                <div class="col-xl-4">
                    <form action="{{ route('admissions.setup.requirements.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $type->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $type->exists ? ___('admissions.edit_document') : ___('admissions.add_document') }}</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.document_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $type->name) }}" class="form-control ot-input" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('admissions.instructions_for_applicant') }}</label>
                                <textarea name="description" rows="3" class="form-control ot-input">{{ old('description', $type->description) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('common.status') }}</label>
                                <select name="status" class="form-select ot-input">
                                    <option value="1" @selected($type->status == 1)>{{ ___('common.active') }}</option>
                                    <option value="0" @selected($type->exists && $type->status == 0)>{{ ___('common.inactive') }}</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($type->exists)
                                    <a href="{{ route('admissions.setup.requirements') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
                                @endif
                                <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('admissions.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
            <div class="col">
                <div class="card ot-card">
                    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.documents') }}</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('admissions.document_name') }}</th>
                                        <th>{{ ___('admissions.rules') }}</th>
                                        <th>{{ ___('common.status') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @foreach ($data['types'] as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row->name }}</strong>
                                                @if ($row->description)<div class="small text-muted">{{ $row->description }}</div>@endif
                                            </td>
                                            <td>{{ $row->rules_count }}</td>
                                            <td><span class="tpc-pill tpc-pill--{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? ___('common.active') : ___('common.inactive') }}</span></td>
                                            <td class="action">
                                                @if ($canEdit)
                                                    <a href="{{ route('admissions.setup.requirements', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header">
                <h4 class="mb-1">{{ ___('admissions.who_must_submit') }}</h4>
                <p class="small text-muted mb-0">{{ ___('admissions.rules_note') }}</p>
            </div>
            <div class="card-body">
                @if ($canEdit)
                    <form action="{{ route('admissions.setup.rules.save') }}" method="post" class="row g-3 align-items-end mb-4" data-rule-form>
                        @csrf
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">{{ ___('admissions.document_name') }}</label>
                            <select name="requirement_type_id" class="form-select ot-input" required>
                                @foreach ($data['types']->where('status', 1) as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">{{ ___('admissions.track') }}</label>
                            <select name="track" class="form-select ot-input" data-rule-track>
                                @foreach (Admission::TRACKS as $t)
                                    <option value="{{ $t }}">{{ ___('admissions.track_' . $t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">{{ ___('admissions.applicant_type') }}</label>
                            <select name="applicant_type" class="form-select ot-input" data-rule-filter>
                                <option value="">{{ ___('admissions.all_applicant_types') }}</option>
                                @foreach (Admission::APPLICANT_TYPES as $track => $types)
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}" data-track="{{ $track }}">{{ ___('admissions.' . $t) }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">{{ ___('admissions.program') }}</label>
                            <select name="program_id" class="form-select ot-input" data-rule-filter>
                                <option value="">{{ ___('admissions.all_programs') }}</option>
                                @foreach ($data['programs'] as $p)
                                    <option value="{{ $p->id }}" data-track="{{ $p->track }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="is_required" value="1" id="is_required" checked>
                                <label class="form-check-label" for="is_required">{{ ___('admissions.required') }}</label>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('admissions.add_rule') }}</button>
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead class="thead">
                            <tr>
                                <th>{{ ___('admissions.track') }}</th>
                                <th>{{ ___('admissions.applicant_type') }}</th>
                                <th>{{ ___('admissions.program') }}</th>
                                <th>{{ ___('admissions.document_name') }}</th>
                                <th>{{ ___('admissions.required') }}</th>
                                <th class="action">{{ ___('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @foreach ($data['rules'] as $rule)
                                <tr id="row_{{ $rule->id }}">
                                    <td>{{ ___('admissions.track_' . $rule->track) }}</td>
                                    <td>{{ $rule->applicant_type ? ___('admissions.' . $rule->applicant_type) : ___('admissions.all_applicant_types') }}</td>
                                    <td>{{ $rule->program->name ?? ___('admissions.all_programs') }}</td>
                                    <td>{{ $rule->requirementType->name }}</td>
                                    <td><span class="tpc-pill tpc-pill--{{ $rule->is_required ? 'info' : 'secondary' }}">{{ $rule->is_required ? ___('admissions.required') : ___('admissions.optional') }}</span></td>
                                    <td class="action">
                                        @if ($canEdit)
                                            <a href="javascript:void(0);" onclick="delete_row('admissions/setup/rules/delete', {{ $rule->id }})" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('backend.partials.delete-ajax')
@endsection

@push('script')
    <script>
        (function () {
            var form = document.querySelector('[data-rule-form]');
            if (!form) return;
            var track = form.querySelector('[data-rule-track]');
            function apply() {
                form.querySelectorAll('[data-rule-filter]').forEach(function (select) {
                    Array.prototype.forEach.call(select.options, function (o) {
                        if (!o.dataset.track) return;
                        var match = o.dataset.track === track.value;
                        o.hidden = !match;
                        o.disabled = !match;
                        if (!match && o.selected) select.value = '';
                    });
                });
            }
            track.addEventListener('change', apply);
            apply();
        })();
    </script>
@endpush
