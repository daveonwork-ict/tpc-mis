@php
    use App\Services\StudentManual;
    $matrix = StudentManual::matrix($app);
@endphp
<div class="card ot-card mb-24">
    <div class="card-header"><h4 class="mb-0">{{ ___('admissions.admission_matrix') }}</h4></div>
    <div class="card-body">
        <p class="small text-muted">{{ ___('admissions.matrix_help') }}</p>
        @if ($matrix['ineligible'])
            <div class="alert alert-danger small">{{ ___('admissions.matrix_' . $matrix['reason']) }}</div>
        @elseif ($matrix['total'] === null)
            <div class="alert alert-warning small">{{ ___('admissions.matrix_incomplete') }}</div>
        @endif
        <dl class="tpc-adm-dl">
            <dt>{{ ___('admissions.academic_points') }}</dt>
            <dd>{{ $matrix['academic'] === null ? '—' : $matrix['academic'] . ' / 20' }}</dd>
            <dt>{{ ___('admissions.sector_points') }}</dt>
            <dd>{{ $matrix['sector'] }} / 15</dd>
            <dt>{{ ___('admissions.tpccat_points') }}</dt>
            <dd>{{ $matrix['tpccat'] === null ? '—' : $matrix['tpccat'] . ' / 35' }}</dd>
            <dt>{{ ___('admissions.financial_points') }}</dt>
            <dd>{{ $matrix['financial'] === null ? '—' : $matrix['financial'] . ' / 30' }}</dd>
            <dt>{{ ___('admissions.matrix_total') }}</dt>
            <dd>{{ $matrix['total'] === null ? '—' : $matrix['total'] . ' / 100' }}</dd>
        </dl>
        @if ($canUpdate && ! $closed)
            <form action="{{ route('admissions.applications.matrix', $app) }}" method="post" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">{{ ___('admissions.tpccat_points') }}</label>
                    <input type="number" name="tpccat_points" value="{{ old('tpccat_points', $app->tpccat_points) }}" min="0" max="35" step="0.01" class="form-control ot-input">
                    <div class="small text-muted">{{ ___('admissions.tpccat_help') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ ___('admissions.financial_points') }}</label>
                    <input type="number" name="financial_points" value="{{ old('financial_points', $app->financial_points) }}" min="0" max="30" step="0.01" class="form-control ot-input">
                    <div class="small text-muted">{{ ___('admissions.financial_help') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">{{ ___('admissions.tarlac_resident') }}</label>
                    <label class="small"><input type="checkbox" name="tarlac_resident" value="1" @checked(old('tarlac_resident', $app->tarlac_resident))> {{ ___('admissions.tarlac_resident_help') }}</label>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ ___('admissions.sector_categories') }}</label>
                    <div class="row g-2">
                        @foreach (StudentManual::SECTORS as $sector)
                            <div class="col-md-6">
                                <label class="small">
                                    <input type="checkbox" name="sector_categories[]" value="{{ $sector }}" @checked(in_array($sector, old('sector_categories', (array) $app->sector_categories), true))>
                                    {{ ___('admissions.sector_' . $sector) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <label class="small d-block mt-2"><input type="checkbox" name="sector_verified" value="1" @checked(old('sector_verified', $app->sector_verified))> {{ ___('admissions.sector_verified') }}</label>
                </div>
                <div class="col-12">
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('admissions.save_matrix') }}</button>
                </div>
            </form>
        @endif
    </div>
</div>
