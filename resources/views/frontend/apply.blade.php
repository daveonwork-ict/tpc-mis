@extends('frontend.master')
@section('title')
    {{ ___('admissions.apply_title') }}
@endsection

@php
    use App\Enums\Admission;
    $programs = $data['programs'];
    $terms = $data['terms'];
    $track = old('track', $programs->contains('track', Admission::TRACK_CHED) && $terms->isNotEmpty() ? Admission::TRACK_CHED : Admission::TRACK_TESDA);
    $field = function ($name, $label, $required = false, $type = 'text', $attrs = '') use ($errors) {
        $error = $errors->first($name);
        return '<label class="tpc-apply__label" for="f_' . $name . '">' . e($label) . ($required ? ' <span>*</span>' : '') . '</label>'
            . '<input id="f_' . $name . '" type="' . $type . '" name="' . $name . '" value="' . e(old($name)) . '" class="tpc-apply__input' . ($error ? ' is-invalid' : '') . '"' . ($required ? ' required' : '') . ' ' . $attrs . '>'
            . ($error ? '<small class="tpc-apply__error">' . e($error) . '</small>' : '');
    };
@endphp

@section('main')
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="breadcam_wrap text-center">
                        <h3>{{ ___('admissions.apply_title') }}</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">{{ ___('admissions.apply_title') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="tpc-section tpc-section--tint tpc-apply">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4 order-2">
                    <aside class="tpc-apply__aside">
                        <div class="tpc-apply__box">
                            <h4>{{ ___('admissions.how_it_works') }}</h4>
                            <ol class="tpc-apply__steps">
                                <li><b>{{ ___('admissions.step_submit') }}</b><span>{{ ___('admissions.step_submit_text') }}</span></li>
                                <li><b>{{ ___('admissions.step_documents') }}</b><span>{{ ___('admissions.step_documents_text') }}</span></li>
                                <li><b>{{ ___('admissions.step_evaluation') }}</b><span>{{ ___('admissions.step_evaluation_text') }}</span></li>
                                <li><b>{{ ___('admissions.step_decision') }}</b><span>{{ ___('admissions.step_decision_text') }}</span></li>
                            </ol>
                        </div>
                        <div class="tpc-apply__box tpc-apply__box--navy">
                            <h4>{{ ___('admissions.already_applied') }}</h4>
                            <p>{{ ___('admissions.already_applied_text') }}</p>
                            <a href="{{ route('frontend.apply.status') }}" class="tpc-btn tpc-btn--gold w-100">{{ ___('admissions.check_status') }}</a>
                        </div>
                        <div class="tpc-apply__box">
                            <h4>{{ ___('admissions.tuition') }}</h4>
                            <p>{{ ___('admissions.tuition_note') }}</p>
                        </div>
                    </aside>
                </div>

                <div class="col-lg-8 order-1">
                    @if ($programs->isEmpty())
                        <div class="tpc-apply__card text-center">
                            <h3 class="tpc-title tpc-title--sm">{{ ___('admissions.applications_closed') }}</h3>
                            <p class="tpc-lead">{{ ___('admissions.applications_closed_text') }}</p>
                            <a href="{{ route('frontend.contact') }}" class="tpc-btn tpc-btn--primary mt-3">{{ ___('frontend.contact_us') }}</a>
                        </div>
                    @else
                        <form action="{{ route('frontend.apply.store') }}" method="post" enctype="multipart/form-data" class="tpc-apply__card" data-apply-form novalidate>
                            @csrf
                            <div class="tpc-apply__hp" aria-hidden="true">
                                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <span class="tpc-eyebrow">{{ ___('admissions.application_form') }}</span>
                            <h2 class="tpc-title tpc-title--sm">{{ ___('admissions.apply_heading') }}</h2>
                            <p class="tpc-lead mb-4">{{ ___('admissions.apply_intro') }}</p>

                            @if ($errors->any())
                                <div class="tpc-apply__alert" role="alert">
                                    <strong>{{ ___('admissions.please_check_form') }}</strong>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- 1. Program --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>1</span>{{ ___('admissions.program_choice') }}</legend>
                                <div class="tpc-apply__tracks">
                                    @foreach (Admission::TRACKS as $t)
                                        @php($available = $programs->contains('track', $t) && ($t !== Admission::TRACK_CHED || $terms->isNotEmpty()))
                                        <label class="tpc-apply__track {{ $available ? '' : 'is-disabled' }}">
                                            <input type="radio" name="track" value="{{ $t }}" @checked($track === $t) @disabled(!$available) data-track-radio>
                                            <span>
                                                <b>{{ ___('admissions.track_' . $t) }}</b>
                                                <small>{{ $available ? ___('admissions.track_' . $t . '_hint') : ___('admissions.not_accepting_now') }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="tpc-apply__label" for="f_program_id">{{ ___('admissions.program') }} <span>*</span></label>
                                        <select id="f_program_id" name="program_id" class="tpc-apply__input @error('program_id') is-invalid @enderror" required data-program-select>
                                            <option value="">{{ ___('admissions.choose_program') }}</option>
                                            @foreach ($programs as $p)
                                                <option value="{{ $p->id }}" data-track="{{ $p->track }}" @selected(old('program_id') == $p->id)>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('program_id')<small class="tpc-apply__error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-5">
                                        <label class="tpc-apply__label" for="f_applicant_type">{{ ___('admissions.i_am_a') }} <span>*</span></label>
                                        <select id="f_applicant_type" name="applicant_type" class="tpc-apply__input @error('applicant_type') is-invalid @enderror" required data-type-select>
                                            <option value="">{{ ___('common.select') }}</option>
                                            @foreach (Admission::APPLICANT_TYPES as $t => $types)
                                                @foreach ($types as $type)
                                                    <option value="{{ $type }}" data-track="{{ $t }}" @selected(old('applicant_type') === $type)>{{ ___('admissions.' . $type) }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error('applicant_type')<small class="tpc-apply__error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-7" data-show-track="ched">
                                        <label class="tpc-apply__label" for="f_term_id">{{ ___('admissions.term_applying_for') }} <span>*</span></label>
                                        <select id="f_term_id" name="term_id" class="tpc-apply__input @error('term_id') is-invalid @enderror">
                                            @if ($terms->count() > 1)
                                                <option value="">{{ ___('common.select') }}</option>
                                            @endif
                                            @foreach ($terms as $term)
                                                <option value="{{ $term->id }}" @selected(old('term_id') == $term->id)>{{ $term->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('term_id')<small class="tpc-apply__error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-7" data-show-track="tesda">
                                        {!! $field('preferred_schedule', ___('admissions.preferred_schedule'), false, 'text', 'placeholder="' . e(___('admissions.preferred_schedule_hint')) . '"') !!}
                                    </div>
                                </div>
                            </fieldset>

                            {{-- 2. Personal --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>2</span>{{ ___('admissions.personal_information') }}</legend>
                                <div class="row g-3">
                                    <div class="col-md-6">{!! $field('last_name', ___('admissions.last_name'), true, 'text', 'autocomplete="family-name"') !!}</div>
                                    <div class="col-md-6">{!! $field('first_name', ___('admissions.first_name'), true, 'text', 'autocomplete="given-name"') !!}</div>
                                    <div class="col-md-6">{!! $field('middle_name', ___('admissions.middle_name')) !!}</div>
                                    <div class="col-md-6">{!! $field('suffix', ___('admissions.suffix'), false, 'text', 'placeholder="Jr., III"') !!}</div>
                                    <div class="col-md-4">
                                        <label class="tpc-apply__label" for="f_sex">{{ ___('admissions.sex') }} <span>*</span></label>
                                        <select id="f_sex" name="sex" class="tpc-apply__input @error('sex') is-invalid @enderror" required>
                                            <option value="">{{ ___('common.select') }}</option>
                                            @foreach (Admission::SEX as $s)
                                                <option value="{{ $s }}" @selected(old('sex') === $s)>{{ ___('admissions.' . $s) }}</option>
                                            @endforeach
                                        </select>
                                        @error('sex')<small class="tpc-apply__error">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4">{!! $field('date_of_birth', ___('admissions.date_of_birth'), true, 'date', 'max="' . now()->subDay()->format('Y-m-d') . '"') !!}</div>
                                    <div class="col-md-4">
                                        <label class="tpc-apply__label" for="f_civil_status">{{ ___('admissions.civil_status') }}</label>
                                        <select id="f_civil_status" name="civil_status" class="tpc-apply__input">
                                            <option value="">{{ ___('common.select') }}</option>
                                            @foreach (Admission::CIVIL_STATUSES as $s)
                                                <option value="{{ $s }}" @selected(old('civil_status') === $s)>{{ ___('admissions.' . $s) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">{!! $field('place_of_birth', ___('admissions.place_of_birth')) !!}</div>
                                    <div class="col-md-4">
                                        <label class="tpc-apply__label" for="f_nationality">{{ ___('admissions.nationality') }}</label>
                                        <input id="f_nationality" type="text" name="nationality" value="{{ old('nationality', 'Filipino') }}" class="tpc-apply__input">
                                    </div>
                                    <div class="col-md-6">{!! $field('email', ___('admissions.email'), true, 'email', 'autocomplete="email"') !!}</div>
                                    <div class="col-md-6">{!! $field('mobile', ___('admissions.mobile'), true, 'tel', 'autocomplete="tel" placeholder="09XX XXX XXXX"') !!}</div>
                                    <div class="col-md-6" data-show-track="ched">{!! $field('lrn', ___('admissions.lrn'), false, 'text', 'inputmode="numeric" maxlength="12"') !!}</div>
                                    <div class="col-md-6" data-show-track="tesda">{!! $field('uli', ___('admissions.uli_if_any')) !!}</div>
                                </div>
                            </fieldset>

                            {{-- 3. Address --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>3</span>{{ ___('admissions.address') }}</legend>
                                <div class="row g-3">
                                    <div class="col-12">{!! $field('address_line', ___('admissions.address_line')) !!}</div>
                                    <div class="col-md-6">{!! $field('barangay', ___('admissions.barangay'), true) !!}</div>
                                    <div class="col-md-6">{!! $field('city_municipality', ___('admissions.city_municipality'), true) !!}</div>
                                    <div class="col-md-6">
                                        <label class="tpc-apply__label" for="f_province">{{ ___('admissions.province') }} <span>*</span></label>
                                        <input id="f_province" type="text" name="province" value="{{ old('province', 'Tarlac') }}" class="tpc-apply__input @error('province') is-invalid @enderror" required>
                                    </div>
                                    <div class="col-md-6">{!! $field('zip_code', ___('admissions.zip_code'), false, 'text', 'inputmode="numeric"') !!}</div>
                                </div>
                            </fieldset>

                            {{-- 4. Education --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>4</span>{{ ___('admissions.educational_background') }}</legend>
                                <div class="row g-3">
                                    <div class="col-md-7">{!! $field('last_school', ___('admissions.last_school')) !!}</div>
                                    <div class="col-md-5">{!! $field('last_school_year', ___('admissions.last_school_year'), false, 'text', 'placeholder="2025-2026"') !!}</div>
                                    <div class="col-12">{!! $field('last_school_address', ___('admissions.last_school_address')) !!}</div>
                                    <div class="col-md-7" data-show-track="ched">{!! $field('last_program_or_strand', ___('admissions.last_program_or_strand')) !!}</div>
                                    <div class="col-md-5" data-show-track="ched">{!! $field('general_average', ___('admissions.general_average')) !!}</div>
                                    <div class="col-md-7" data-show-track="tesda">{!! $field('highest_attainment', ___('admissions.highest_attainment')) !!}</div>
                                    <div class="col-md-5" data-show-track="tesda">{!! $field('employment_status', ___('admissions.employment_status')) !!}</div>
                                </div>
                            </fieldset>

                            {{-- 5. Guardian --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>5</span>{{ ___('admissions.parent_guardian') }}</legend>
                                <div class="row g-3">
                                    <div class="col-md-5">{!! $field('guardian_name', ___('admissions.guardian_name')) !!}</div>
                                    <div class="col-md-3">{!! $field('guardian_relationship', ___('admissions.guardian_relationship')) !!}</div>
                                    <div class="col-md-4">{!! $field('guardian_mobile', ___('admissions.guardian_mobile'), false, 'tel') !!}</div>
                                </div>
                            </fieldset>

                            {{-- 6. Documents --}}
                            <fieldset class="tpc-apply__group">
                                <legend><span>6</span>{{ ___('admissions.documents') }}</legend>
                                <p class="tpc-apply__note">{{ ___('admissions.documents_intro') }}</p>
                                <div class="tpc-apply__docs" data-docs>
                                    <p class="tpc-apply__note mb-0" data-docs-empty>{{ ___('admissions.choose_program_first') }}</p>
                                </div>
                            </fieldset>

                            <label class="tpc-consent">
                                <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))>
                                <span>{!! str_replace(':link', '<a href="' . route('frontend.page', 'privacy-policy') . '" target="_blank">' . e(___('frontend.privacy_policy')) . '</a>', e(___('admissions.privacy_consent_apply'))) !!}</span>
                            </label>
                            <label class="tpc-consent">
                                <input type="checkbox" required>
                                <span>{{ ___('admissions.certify_true') }}</span>
                            </label>

                            <button type="submit" class="tpc-btn tpc-btn--gold tpc-apply__submit">{{ ___('admissions.submit_application') }} <i class="fas fa-arrow-right"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.querySelector('[data-apply-form]');
            if (!form) return;

            var rules = @json($data['rules']);
            var labels = {
                required: @json(___('admissions.required')),
                optional: @json(___('admissions.optional')),
                hint: @json(___('admissions.file_rule')),
                chooseFirst: @json(___('admissions.choose_program_first'))
            };
            var programSelect = form.querySelector('[data-program-select]');
            var typeSelect = form.querySelector('[data-type-select]');
            var docs = form.querySelector('[data-docs]');

            function currentTrack() {
                var checked = form.querySelector('[data-track-radio]:checked');
                return checked ? checked.value : '';
            }

            function filterOptions(select, track) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (!option.dataset.track) return;
                    var match = option.dataset.track === track;
                    option.hidden = !match;
                    option.disabled = !match;
                    if (!match && option.selected) select.value = '';
                });
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function renderDocs() {
                var track = currentTrack();
                var applicant = typeSelect.value;
                var program = parseInt(programSelect.value, 10) || null;
                if (!applicant || !program) {
                    docs.innerHTML = '<p class="tpc-apply__note mb-0">' + escapeHtml(labels.chooseFirst) + '</p>';
                    return;
                }

                var items = {};
                rules.forEach(function (rule) {
                    if (rule.track !== track) return;
                    if (rule.applicant && rule.applicant !== applicant) return;
                    if (rule.program && rule.program !== program) return;
                    var item = items[rule.type] || (items[rule.type] = { name: rule.name, description: rule.description, required: false });
                    item.required = item.required || rule.required;
                });

                var keys = Object.keys(items).sort(function (a, b) { return items[b].required - items[a].required; });
                docs.innerHTML = keys.map(function (key) {
                    var item = items[key];
                    return '<div class="tpc-apply__doc">' +
                        '<div><b>' + escapeHtml(item.name) + '</b> <em class="' + (item.required ? 'is-required' : '') + '">' + (item.required ? labels.required : labels.optional) + '</em>' +
                        (item.description ? '<small>' + escapeHtml(item.description) + '</small>' : '') + '</div>' +
                        '<input type="file" name="documents[' + key + ']" accept=".pdf,.jpg,.jpeg,.png">' +
                        '</div>';
                }).join('') + '<p class="tpc-apply__note mb-0">' + escapeHtml(labels.hint) + '</p>';
            }

            function apply() {
                var track = currentTrack();
                filterOptions(programSelect, track);
                filterOptions(typeSelect, track);
                form.querySelectorAll('[data-show-track]').forEach(function (el) {
                    el.style.display = el.dataset.showTrack === track ? '' : 'none';
                });
                form.querySelector('[name=term_id]').required = track === 'ched';
                renderDocs();
            }

            form.querySelectorAll('[data-track-radio]').forEach(function (radio) { radio.addEventListener('change', apply); });
            programSelect.addEventListener('change', renderDocs);
            typeSelect.addEventListener('change', renderDocs);
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                }
            });
            apply();
        })();
    </script>
@endpush
