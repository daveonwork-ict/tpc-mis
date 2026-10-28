@php
    use App\Enums\Admission;
    $app = $data['application'] ?? null;
    $person = $app?->person;
    $inq = $data['inquiry'] ?? null;
    $names = $inq ? preg_split('/\s+/', trim($inq->name)) : [];
    $val = function ($field, $source = null, $fallback = null) use ($app, $person) {
        $model = $source === 'person' ? $person : $app;
        return old($field, $model?->{$field} ?? $fallback);
    };
    $dob = $person?->date_of_birth?->format('Y-m-d');
    $track = $val('track', null, $inq->track ?? Admission::TRACK_CHED);
@endphp

<div class="tpc-adm-form" data-track-form>
    <div class="card ot-card mb-24">
        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.program_choice') }}</h4></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.track') }} <span class="text-danger">*</span></label>
                    <select name="track" class="form-select ot-input" data-track-select @disabled($app)>
                        @foreach (Admission::TRACKS as $t)
                            <option value="{{ $t }}" @selected($track === $t)>{{ ___('admissions.track_' . $t) }}</option>
                        @endforeach
                    </select>
                    @if ($app)
                        <input type="hidden" name="track" value="{{ $track }}">
                    @endif
                    @error('track')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ ___('admissions.program') }} <span class="text-danger">*</span></label>
                    <select name="program_id" class="form-select ot-input" data-program-select>
                        <option value="">{{ ___('common.select') }}</option>
                        @foreach ($data['programs'] as $program)
                            <option value="{{ $program->id }}" data-track="{{ $program->track }}" @selected($val('program_id', null, $inq->program_id ?? null) == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ ___('admissions.applicant_type') }} <span class="text-danger">*</span></label>
                    <select name="applicant_type" class="form-select ot-input" data-type-select>
                        <option value="">{{ ___('common.select') }}</option>
                        @foreach (Admission::APPLICANT_TYPES as $t => $types)
                            @foreach ($types as $type)
                                <option value="{{ $type }}" data-track="{{ $t }}" @selected($val('applicant_type') === $type)>{{ ___('admissions.' . $type) }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('applicant_type')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" data-show-track="ched">
                    <label class="form-label">{{ ___('admissions.term') }} <span class="text-danger">*</span></label>
                    <select name="term_id" class="form-select ot-input">
                        <option value="">{{ ___('common.select') }}</option>
                        @foreach ($data['terms'] as $term)
                            <option value="{{ $term->id }}" @selected($val('term_id') == $term->id)>
                                {{ $term->name }}{{ $term->admission_open ? ' — ' . ___('admissions.open_for_applications') : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('term_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" data-show-track="tesda">
                    <label class="form-label">{{ ___('admissions.preferred_schedule') }}</label>
                    <input type="text" name="preferred_schedule" value="{{ $val('preferred_schedule') }}" class="form-control ot-input" placeholder="{{ ___('admissions.preferred_schedule_hint') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.personal_information') }}</h4></div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ([
                    ['last_name', 3, true, $names ? end($names) : null],
                    ['first_name', 3, true, $names ? implode(' ', array_slice($names, 0, -1)) : null],
                    ['middle_name', 3, false, null],
                    ['suffix', 3, false, null],
                ] as [$field, $col, $required, $fallback])
                    <div class="col-md-{{ $col }}">
                        <label class="form-label">{{ ___('admissions.' . $field) }} @if ($required)<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="{{ $field }}" value="{{ $val($field, 'person', $fallback) }}" class="form-control ot-input" @required($required)>
                        @error($field)<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.sex') }} <span class="text-danger">*</span></label>
                    <select name="sex" class="form-select ot-input" required>
                        <option value="">{{ ___('common.select') }}</option>
                        @foreach (Admission::SEX as $s)
                            <option value="{{ $s }}" @selected($val('sex', 'person') === $s)>{{ ___('admissions.' . $s) }}</option>
                        @endforeach
                    </select>
                    @error('sex')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.date_of_birth') }} <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $dob) }}" max="{{ now()->subDay()->format('Y-m-d') }}" class="form-control ot-input" required>
                    @error('date_of_birth')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.place_of_birth') }}</label>
                    <input type="text" name="place_of_birth" value="{{ $val('place_of_birth', 'person') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.civil_status') }}</label>
                    <select name="civil_status" class="form-select ot-input">
                        <option value="">{{ ___('common.select') }}</option>
                        @foreach (Admission::CIVIL_STATUSES as $s)
                            <option value="{{ $s }}" @selected($val('civil_status', 'person') === $s)>{{ ___('admissions.' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.nationality') }}</label>
                    <input type="text" name="nationality" value="{{ $val('nationality', 'person', 'Filipino') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.mobile') }} <span class="text-danger">*</span></label>
                    <input type="text" name="mobile" value="{{ $val('mobile', 'person', $inq->mobile ?? null) }}" class="form-control ot-input" required>
                    @error('mobile')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.email') }}</label>
                    <input type="email" name="email" value="{{ $val('email', 'person', $inq->email ?? null) }}" class="form-control ot-input">
                    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3" data-show-track="ched">
                    <label class="form-label">{{ ___('admissions.lrn') }}</label>
                    <input type="text" name="lrn" value="{{ $val('lrn', 'person') }}" class="form-control ot-input" inputmode="numeric" maxlength="12">
                    @error('lrn')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3" data-show-track="tesda">
                    <label class="form-label">{{ ___('admissions.uli') }}</label>
                    <input type="text" name="uli" value="{{ $val('uli', 'person') }}" class="form-control ot-input">
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.address') }}</h4></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">{{ ___('admissions.address_line') }}</label>
                    <input type="text" name="address_line" value="{{ $val('address_line', 'person') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.barangay') }} <span class="text-danger">*</span></label>
                    <input type="text" name="barangay" value="{{ $val('barangay', 'person') }}" class="form-control ot-input" required>
                    @error('barangay')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.city_municipality') }} <span class="text-danger">*</span></label>
                    <input type="text" name="city_municipality" value="{{ $val('city_municipality', 'person') }}" class="form-control ot-input" required>
                    @error('city_municipality')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.province') }} <span class="text-danger">*</span></label>
                    <input type="text" name="province" value="{{ $val('province', 'person', 'Tarlac') }}" class="form-control ot-input" required>
                    @error('province')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.zip_code') }}</label>
                    <input type="text" name="zip_code" value="{{ $val('zip_code', 'person') }}" class="form-control ot-input">
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.educational_background') }}</h4></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ ___('admissions.last_school') }}</label>
                    <input type="text" name="last_school" value="{{ $val('last_school') }}" class="form-control ot-input">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ ___('admissions.last_school_address') }}</label>
                    <input type="text" name="last_school_address" value="{{ $val('last_school_address') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.last_school_year') }}</label>
                    <input type="text" name="last_school_year" value="{{ $val('last_school_year') }}" class="form-control ot-input" placeholder="2025-2026">
                </div>
                <div class="col-md-3" data-show-track="ched">
                    <label class="form-label">{{ ___('admissions.last_program_or_strand') }}</label>
                    <input type="text" name="last_program_or_strand" value="{{ $val('last_program_or_strand') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3" data-show-track="ched">
                    <label class="form-label">{{ ___('admissions.general_average') }}</label>
                    <input type="text" name="general_average" value="{{ $val('general_average') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3" data-show-track="tesda">
                    <label class="form-label">{{ ___('admissions.highest_attainment') }}</label>
                    <input type="text" name="highest_attainment" value="{{ $val('highest_attainment') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3" data-show-track="tesda">
                    <label class="form-label">{{ ___('admissions.employment_status') }}</label>
                    <input type="text" name="employment_status" value="{{ $val('employment_status') }}" class="form-control ot-input">
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-header"><h4 class="mb-0">{{ ___('admissions.parent_guardian') }}</h4></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">{{ ___('admissions.guardian_name') }}</label>
                    <input type="text" name="guardian_name" value="{{ $val('guardian_name') }}" class="form-control ot-input">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ ___('admissions.guardian_relationship') }}</label>
                    <input type="text" name="guardian_relationship" value="{{ $val('guardian_relationship') }}" class="form-control ot-input">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ ___('admissions.guardian_mobile') }}</label>
                    <input type="text" name="guardian_mobile" value="{{ $val('guardian_mobile') }}" class="form-control ot-input">
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function () {
            var form = document.querySelector('[data-track-form]');
            if (!form) return;
            var trackSelect = form.querySelector('[data-track-select]');

            function filter(select, track) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (!option.dataset.track) return;
                    var match = option.dataset.track === track;
                    option.hidden = !match;
                    option.disabled = !match;
                    if (!match && option.selected) select.value = '';
                });
            }

            function apply() {
                var track = trackSelect.value;
                filter(form.querySelector('[data-program-select]'), track);
                filter(form.querySelector('[data-type-select]'), track);
                form.querySelectorAll('[data-show-track]').forEach(function (el) {
                    el.style.display = el.dataset.showTrack === track ? '' : 'none';
                });
            }

            trackSelect.addEventListener('change', apply);
            apply();
        })();
    </script>
@endpush
