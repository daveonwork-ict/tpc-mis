@extends('frontend.master')
@section('title')
    {{ ___('admissions.application_status') }}
@endsection

@php
    use App\Enums\Admission;
    use App\Http\Controllers\Frontend\ApplyController;
@endphp

@section('main')
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="breadcam_wrap text-center">
                        <h3>{{ ___('admissions.application_status') }}</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="{{ route('frontend.apply') }}" class="breadcrumb-item">{{ ___('admissions.apply_title') }}</a>
                            <a href="#" class="breadcrumb-item">{{ ___('admissions.application_status') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="tpc-section tpc-section--tint tpc-apply">
        <div class="container">
            @if (!$application)
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-9">
                        <form action="{{ route('frontend.apply.status.lookup') }}" method="post" class="tpc-apply__card">
                            @csrf
                            <span class="tpc-eyebrow">{{ ___('admissions.track_your_application') }}</span>
                            <h2 class="tpc-title tpc-title--sm">{{ ___('admissions.check_status') }}</h2>
                            <p class="tpc-lead mb-4">{{ ___('admissions.lookup_intro') }}</p>

                            @if ($errors->any())
                                <div class="tpc-apply__alert" role="alert">{{ $errors->first() }}</div>
                            @endif

                            <label class="tpc-apply__label" for="f_application_no">{{ ___('admissions.application_no') }} <span>*</span></label>
                            <input id="f_application_no" type="text" name="application_no" value="{{ old('application_no') }}" class="tpc-apply__input mb-3" placeholder="TPC-2026-000001" required autocomplete="off">

                            <label class="tpc-apply__label" for="f_dob">{{ ___('admissions.date_of_birth') }} <span>*</span></label>
                            <input id="f_dob" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="tpc-apply__input mb-4" required>

                            <button type="submit" class="tpc-btn tpc-btn--primary w-100">{{ ___('admissions.check_status') }}</button>
                            <p class="tpc-apply__note mt-3 mb-0 text-center">
                                {{ ___('admissions.no_application_yet') }} <a href="{{ route('frontend.apply') }}">{{ ___('admissions.apply_now') }}</a>
                            </p>
                        </form>
                    </div>
                </div>
            @else
                @php
                    $required = $application->requirements->where('is_required', true);
                    $done = $required->whereIn('status', [Admission::REQ_VERIFIED, Admission::REQ_WAIVED])->count();
                    $closed = $application->isClosed();
                    $steps = [Admission::SUBMITTED, Admission::REQUIREMENTS_PENDING, Admission::FOR_EVALUATION, 'decision'];
                    $position = match ($application->status) {
                        Admission::SUBMITTED => 0,
                        Admission::REQUIREMENTS_PENDING => 1,
                        Admission::FOR_EVALUATION => 2,
                        default => 3,
                    };
                @endphp

                @if (session('applied'))
                    <div class="tpc-apply__success" role="status">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>{{ ___('admissions.application_received') }}</strong>
                            <p>{!! str_replace(':number', '<b>' . e(session('applied')) . '</b>', e(___('admissions.application_received_text'))) !!}</p>
                        </div>
                    </div>
                @endif
                @if (session('uploaded'))
                    <div class="tpc-apply__success" role="status">
                        <i class="fas fa-check-circle"></i>
                        <div><strong>{{ ___('admissions.file_received') }}</strong><p>{{ session('uploaded') }}</p></div>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="tpc-apply__alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="tpc-apply__card">
                            <div class="tpc-apply__status-head">
                                <div>
                                    <span class="tpc-eyebrow">{{ $application->application_no }}</span>
                                    <h2 class="tpc-title tpc-title--sm mb-1">{{ $application->person->full_name }}</h2>
                                    <p class="tpc-apply__note mb-0">
                                        {{ $application->program->name }}
                                        @if ($application->term) · {{ $application->term->name }}@endif
                                    </p>
                                </div>
                                <span class="tpc-apply__pill tpc-apply__pill--{{ Admission::badge($application->status) }}">{{ ___('admissions.' . $application->status) }}</span>
                            </div>

                            @unless (in_array($application->status, [Admission::WITHDRAWN, Admission::DEFERRED, Admission::REJECTED], true))
                                <ol class="tpc-apply__progress">
                                    @foreach ($steps as $i => $step)
                                        <li class="{{ $i < $position || ($i === $position && $i === 3) ? 'is-done' : ($i === $position ? 'is-current' : '') }}">
                                            <span>{{ $i + 1 }}</span>{{ ___('admissions.progress_' . $step) }}
                                        </li>
                                    @endforeach
                                </ol>
                            @endunless

                            <div class="tpc-apply__message tpc-apply__message--{{ Admission::badge($application->status) }}">
                                {{ ___('admissions.public_' . $application->status) }}
                                @if ($application->status === Admission::CONDITIONAL && $application->latestDecision?->conditions)
                                    <div class="mt-2"><b>{{ ___('admissions.conditions') }}:</b> {{ $application->latestDecision->conditions }}</div>
                                @endif
                            </div>

                            <h4 class="tpc-apply__subhead">
                                {{ ___('admissions.your_documents') }}
                                <small>{{ $done }} / {{ $required->count() }} {{ ___('admissions.required_documents_cleared') }}</small>
                            </h4>
                            <div class="tpc-apply__docs">
                                @foreach ($application->requirements as $item)
                                    <div class="tpc-apply__doc tpc-apply__doc--status">
                                        <div>
                                            <b>{{ $item->requirementType->name }}</b>
                                            <em class="{{ $item->is_required ? 'is-required' : '' }}">{{ $item->is_required ? ___('admissions.required') : ___('admissions.optional') }}</em>
                                            @if ($item->status === Admission::REQ_REJECTED && $item->remarks)
                                                <small class="text-danger">{{ $item->remarks }}</small>
                                            @elseif ($item->requirementType->description)
                                                <small>{{ $item->requirementType->description }}</small>
                                            @endif
                                        </div>
                                        <div class="tpc-apply__doc-actions">
                                            <span class="tpc-apply__pill tpc-apply__pill--{{ Admission::badge($item->status) }}">{{ ___('admissions.req_' . $item->status) }}</span>
                                            @if (!$closed && in_array($item->status, ApplyController::UPLOADABLE, true))
                                                <form action="{{ route('frontend.apply.status.upload', $item) }}" method="post" enctype="multipart/form-data">
                                                    @csrf
                                                    <label class="tpc-apply__upload">
                                                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required onchange="this.form.submit()">
                                                        <i class="fas fa-upload"></i> {{ $item->file_path ? ___('admissions.replace') : ___('admissions.upload') }}
                                                    </label>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p class="tpc-apply__note mt-3 mb-0">{{ ___('admissions.file_rule') }} {{ ___('admissions.originals_note') }}</p>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <aside class="tpc-apply__aside">
                            <div class="tpc-apply__box">
                                <h4>{{ ___('admissions.keep_this') }}</h4>
                                <p>{{ ___('admissions.keep_this_text') }}</p>
                                <div class="tpc-apply__number">{{ $application->application_no }}</div>
                            </div>
                            <div class="tpc-apply__box tpc-apply__box--navy">
                                <h4>{{ ___('admissions.need_help') }}</h4>
                                <p>{{ ___('admissions.need_help_text') }}</p>
                                <a href="{{ route('frontend.contact') }}" class="tpc-btn tpc-btn--gold w-100">{{ ___('frontend.contact_us') }}</a>
                            </div>
                            <form action="{{ route('frontend.apply.status.exit') }}" method="post" class="text-center">
                                @csrf
                                <button type="submit" class="tpc-apply__linkbtn"><i class="fas fa-sign-out-alt"></i> {{ ___('admissions.close_session') }}</button>
                            </form>
                        </aside>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
