@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@php
    use App\Enums\Enrollment;
    $fee = $data['fee'];
@endphp
@section('content')
    <div class="page-content tpc-adm">
        @include('backend.enrollment.partials.header', ['title' => $data['title']])
        @include('backend.enrollment.setup._tabs')

        <div class="alert alert-info">{{ ___('enrollment.fee_schedule_help') }}</div>

        <div class="row g-4">
            @if (hasPermission('enrollment_setup_update'))
                <div class="col-xl-4">
                    <form action="{{ route('enrollment.setup.fees.save') }}" method="post" class="card ot-card">
                        @csrf
                        <input type="hidden" name="id" value="{{ $fee->id }}">
                        <div class="card-header"><h4 class="mb-0">{{ $fee->exists ? ___('enrollment.edit_fee') : ___('enrollment.add_fee') }}</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.fee_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $fee->name) }}" class="form-control ot-input" maxlength="150" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.category') }}</label>
                                    <select name="category" class="form-select ot-input">
                                        @foreach (Enrollment::FEE_CATEGORIES as $c)
                                            <option value="{{ $c }}" @selected(old('category', $fee->category) === $c)>{{ ___('enrollment.fee_' . $c) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.basis') }}</label>
                                    <select name="basis" class="form-select ot-input">
                                        @foreach (Enrollment::FEE_BASES as $b)
                                            <option value="{{ $b }}" @selected(old('basis', $fee->basis) === $b)>{{ ___('enrollment.basis_' . $b) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.amount') }} (₱) <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" value="{{ old('amount', $fee->amount) }}" step="0.01" min="0" class="form-control ot-input" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">{{ ___('enrollment.sort_order') }}</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', $fee->sort_order ?? 0) }}" min="0" class="form-control ot-input">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.program') }}</label>
                                <select name="program_id" class="form-select ot-input">
                                    <option value="">{{ ___('enrollment.all_programs') }}</option>
                                    @foreach ($data['programs'] as $p)
                                        <option value="{{ $p->id }}" @selected(old('program_id', $fee->program_id) == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ___('enrollment.term') }}</label>
                                <select name="term_id" class="form-select ot-input">
                                    <option value="">{{ ___('enrollment.all_terms') }}</option>
                                    @foreach ($data['terms'] as $t)
                                        <option value="{{ $t->id }}" @selected(old('term_id', $fee->term_id) == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <div class="small text-muted mt-1">{{ ___('enrollment.fee_term_help') }}</div>
                            </div>
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-6">
                                    <label class="form-label">{{ ___('common.status') }}</label>
                                    <select name="status" class="form-select ot-input">
                                        <option value="1" @selected(old('status', $fee->status) == 1)>{{ ___('common.active') }}</option>
                                        <option value="0" @selected($fee->exists && old('status', $fee->status) == 0)>{{ ___('common.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="free_tuition_covered" value="1" id="covered" @checked(old('free_tuition_covered', $fee->free_tuition_covered))>
                                        <label class="form-check-label" for="covered">{{ ___('enrollment.covered_by_ra_10931') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                @if ($fee->exists)
                                    <a href="{{ route('enrollment.setup.fees') }}" class="btn btn-lg ot-btn-primary-outline">{{ ___('common.cancel') }}</a>
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
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('enrollment.fee_name') }}</th>
                                        <th class="text-end">{{ ___('enrollment.amount') }}</th>
                                        <th>{{ ___('enrollment.applies_to') }}</th>
                                        <th>{{ ___('enrollment.ra_10931') }}</th>
                                        <th class="action">{{ ___('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['fees'] as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row->name }}</strong>
                                                <div class="small text-muted">{{ ___('enrollment.fee_' . $row->category) }}@if (!$row->status) · {{ ___('common.inactive') }}@endif</div>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-nowrap">₱{{ number_format($row->amount, 2) }}</span>
                                                <div class="small text-muted">{{ ___('enrollment.basis_' . $row->basis) }}</div>
                                            </td>
                                            <td class="small">{{ $row->program->name ?? ___('enrollment.all_programs') }}<br>{{ $row->term->name ?? ___('enrollment.all_terms') }}</td>
                                            <td>
                                                @if ($row->free_tuition_covered)
                                                    <span class="tpc-pill tpc-pill--success">{{ ___('enrollment.covered') }}</span>
                                                @else
                                                    <span class="text-muted small">{{ ___('enrollment.not_covered') }}</span>
                                                @endif
                                            </td>
                                            <td class="action">
                                                @if (hasPermission('enrollment_setup_update'))
                                                    <div class="d-flex gap-2 flex-nowrap">
                                                        <a href="{{ route('enrollment.setup.fees', ['edit' => $row->id]) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                        <form action="{{ route('enrollment.setup.fees.delete', $row) }}" method="post" onsubmit="return confirm(@js(___('enrollment.confirm_delete_fee')))">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color p-5">
                                                <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                                <p class="mb-0">{{ ___('enrollment.no_fees') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
