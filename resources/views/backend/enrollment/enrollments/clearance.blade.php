@php
    use App\Enums\Enrollment;
    $openScholarships = $enrollment->scholarships->whereNull('voided_at');
    $openPayments = $enrollment->payments->whereNull('voided_at');
    $canClear = in_array($enrollment->status, [Enrollment::ASSESSED, Enrollment::CLEARED], true) && hasPermission('enrollment_clear');
    $balance = round((float) $enrollment->balance, 2);
@endphp
<div class="card ot-card mb-24">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">{{ ___('enrollment.clearance') }}</h4>
        @if ($enrollment->cleared_at)
            <span class="small text-muted">{{ $enrollment->cleared_at->format('d M Y h:i A') }} · {{ $enrollment->clearer->name ?? '—' }}</span>
        @endif
    </div>
    <div class="card-body">
        <dl class="tpc-enr-totals">
            <dt>{{ ___('enrollment.amount_due') }}</dt><dd>₱{{ number_format($enrollment->net_total, 2) }}</dd>
            <dt>{{ ___('enrollment.less_scholarship') }}</dt><dd>₱{{ number_format($enrollment->scholarship_total, 2) }}</dd>
            <dt>{{ ___('enrollment.official_receipts') }}</dt><dd>₱{{ number_format($enrollment->paid_total, 2) }}</dd>
            <dt class="net">{{ $balance < 0 ? ___('enrollment.credit') : ___('enrollment.balance') }}</dt>
            <dd class="net">₱{{ number_format(abs($balance), 2) }}</dd>
        </dl>

        @if ($enrollment->funding_outcome)
            <p class="small mt-3 mb-0">{{ ___('enrollment.outcome_' . $enrollment->funding_outcome) }}</p>
        @endif

        @if ($openScholarships->isNotEmpty() || $openPayments->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table table-bordered role-table tpc-enr-table mb-0">
                    <thead class="thead">
                        <tr>
                            <th>{{ ___('enrollment.applied') }}</th>
                            <th>{{ ___('enrollment.reference') }}</th>
                            <th class="text-end">{{ ___('enrollment.amount') }}</th>
                            @if ($canClear)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody class="tbody">
                        @foreach ($openScholarships as $row)
                            <tr>
                                <td>{{ $row->label() }}<div class="small text-muted">{{ $row->recorder->name ?? '—' }}</div></td>
                                <td>{{ $row->reference ?: '—' }}</td>
                                <td class="text-end">₱{{ number_format($row->amount, 2) }}</td>
                                @if ($canClear)
                                    <td>
                                        <form action="{{ route('enrollment.enrollments.scholarships.remove', [$enrollment, $row]) }}" method="post" class="d-flex gap-1">
                                            @csrf
                                            <input type="text" name="reason" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_required') }}" maxlength="255" required>
                                            <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit">{{ ___('enrollment.remove') }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        @foreach ($openPayments as $row)
                            <tr>
                                <td>{{ ___('enrollment.official_receipt') }}<div class="small text-muted">{{ $row->paid_on?->format('d M Y') }} · {{ $row->recorder->name ?? '—' }}</div></td>
                                <td>OR {{ $row->or_number }}</td>
                                <td class="text-end">₱{{ number_format($row->amount, 2) }}</td>
                                @if ($canClear)
                                    <td>
                                        <form action="{{ route('enrollment.enrollments.payments.void', [$enrollment, $row]) }}" method="post" class="d-flex gap-1">
                                            @csrf
                                            <input type="text" name="reason" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_required') }}" maxlength="255" required>
                                            <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit">{{ ___('enrollment.void_receipt') }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($canClear && $balance > 0)
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <form action="{{ route('enrollment.enrollments.scholarships.store', $enrollment) }}" method="post">
                        @csrf
                        <h5 class="mb-2">{{ ___('enrollment.record_scholarship') }}</h5>
                        <select name="program" class="form-select ot-input mb-2" required>
                            @foreach (Enrollment::SCHOLARSHIP_PROGRAMS as $program)
                                <option value="{{ $program }}" @selected(old('program') === $program)>{{ ___('enrollment.scholarship_' . $program) }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.scholarship_name') }}" maxlength="150">
                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.award_reference') }}" maxlength="60">
                        <input type="number" name="amount" value="{{ old('amount') }}" class="form-control ot-input mb-2" min="0.01" max="{{ $balance }}" step="0.01" placeholder="{{ ___('enrollment.amount') }}" required>
                        <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.apply_scholarship') }}</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('enrollment.enrollments.payments.store', $enrollment) }}" method="post">
                        @csrf
                        <h5 class="mb-2">{{ ___('enrollment.record_receipt') }}</h5>
                        <input type="text" name="or_number" value="{{ old('or_number') }}" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.or_number') }}" maxlength="40" required>
                        <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control ot-input mb-2" max="{{ now()->toDateString() }}" required>
                        <input type="number" name="amount" class="form-control ot-input mb-2" min="0.01" max="{{ $balance }}" step="0.01" placeholder="{{ ___('enrollment.amount') }}" required>
                        <input type="text" name="remarks" value="{{ old('remarks') }}" class="form-control ot-input mb-2" placeholder="{{ ___('enrollment.remarks') }}" maxlength="500">
                        <button class="btn btn-sm ot-btn-primary" type="submit">{{ ___('enrollment.record_receipt') }}</button>
                    </form>
                </div>
            </div>
            <p class="small text-muted mt-2 mb-0">{{ ___('enrollment.scholarship_other_help') }}</p>
        @endif

        @if ($enrollment->status === Enrollment::ASSESSED && hasPermission('enrollment_clear') && $balance <= 0)
            <form action="{{ route('enrollment.enrollments.clear', $enrollment) }}" method="post" class="mt-3" onsubmit="return confirm(@js(___('enrollment.confirm_clear')))">
                @csrf
                <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('enrollment.issue_clearance') }}</button>
            </form>
        @endif

        @if ($enrollment->status === Enrollment::CLEARED && hasPermission('enrollment_clear'))
            <form action="{{ route('enrollment.enrollments.revoke', $enrollment) }}" method="post" class="tpc-enr-secondary mt-3">
                @csrf
                <input type="text" name="reason" class="form-control ot-input" placeholder="{{ ___('enrollment.reason_required') }}" maxlength="500" required>
                <button class="btn btn-sm ot-btn-primary-outline" type="submit">{{ ___('enrollment.revoke_clearance') }}</button>
            </form>
        @endif
    </div>
</div>
