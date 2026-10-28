@extends('frontend.master')
@section('title')
    {{ ___('frontend.about_US') }}
@endsection

@php
    $sectionData = function ($key) use ($sections) {
        $data = $sections[$key]->defaultTranslate->data ?? [];
        return is_array($data) ? $data : [];
    };

    $schoolIcon = function ($title) {
        $t = strtolower($title);
        return match (true) {
            str_contains($t, 'health') => 'fa-heartbeat',
            str_contains($t, 'computer'), str_contains($t, 'information') => 'fa-laptop-code',
            str_contains($t, 'agri') => 'fa-seedling',
            str_contains($t, 'business') => 'fa-briefcase',
            str_contains($t, 'hospitality'), str_contains($t, 'tourism') => 'fa-concierge-bell',
            str_contains($t, 'community') => 'fa-hands-helping',
            default => 'fa-graduation-cap',
        };
    };

    $statementIcon = function ($title) {
        $t = strtolower($title);
        return match (true) {
            str_contains($t, 'mission') => 'fa-bullseye',
            str_contains($t, 'vision') => 'fa-eye',
            default => 'fa-landmark',
        };
    };
@endphp

@section('main')
    <!-- bradcam::start  -->
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>{{ ___('frontend.about_US') }}</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">{{ ___('frontend.about_US') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- bradcam::end  -->

    <!-- STATEMENT::START -->
    <section class="tpc-section tpc-about">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <figure class="tpc-media-frame">
                        <img src="{{ @globalAsset(@$sections['statement']->upload->path, '512X512.webp') }}"
                            alt="{{ @$sections['statement']->defaultTranslate->name }}">
                    </figure>
                </div>
                <div class="col-lg-7">
                    <div class="tpc-about__body">
                        <span class="tpc-eyebrow">{{ ___('frontend.about_the_college') }}</span>
                        <h2 class="tpc-title">{{ @$sections['statement']->defaultTranslate->name }}</h2>
                        <div class="tpc-statements mb-0">
                            @foreach ($sectionData('statement') as $item)
                                <div class="tpc-statement">
                                    <div class="tpc-statement__icon"><i class="fas {{ $statementIcon($item['title']) }}"></i></div>
                                    <div>
                                        <h3>{{ $item['title'] }}</h3>
                                        <p>{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- STATEMENT::END -->

    <!-- CHARTER::START -->
    @if ($data['abouts']->count())
        <section class="tpc-section tpc-section--tint">
            <div class="container">
                @foreach ($data['abouts'] as $key => $item)
                    <div class="row align-items-center g-5 tpc-feature-row">
                        <div class="col-lg-6 {{ $key % 2 ? 'order-lg-2' : '' }}">
                            <figure class="tpc-media-frame {{ $key % 2 ? 'tpc-media-frame--right' : '' }}">
                                <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}" alt="{{ @$item->defaultTranslate->name }}">
                            </figure>
                        </div>
                        <div class="col-lg-6 {{ $key % 2 ? 'order-lg-1' : '' }}">
                            <span class="tpc-eyebrow">{{ str_pad($key + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 class="tpc-title tpc-title--sm">{{ @$item->defaultTranslate->name }}</h2>
                            <p class="tpc-lead">{{ @$item->defaultTranslate->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    <!-- CHARTER::END -->

    <!-- SCHOOLS::START -->
    <section class="tpc-section">
        <div class="container">
            <div class="tpc-section-head">
                <span class="tpc-eyebrow">{{ ___('frontend.academic_schools') }}</span>
                <h2 class="tpc-title">{{ @$sections['study_at']->defaultTranslate->name }}</h2>
                <p class="tpc-lead">{{ @$sections['study_at']->defaultTranslate->description }}</p>
            </div>
            <div class="row g-4">
                @foreach ($sectionData('study_at') as $item)
                    <div class="col-lg-4 col-md-6">
                        <article class="tpc-card tpc-school">
                            <div class="tpc-school__icon"><i class="fas {{ $schoolIcon($item['title']) }}"></i></div>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!-- SCHOOLS::END -->

    <!-- FACULTY::START -->
    <section class="tpc-section tpc-section--tint">
        <div class="container">
            <div class="tpc-section-head {{ $data['teachers']->count() ? '' : 'mb-0' }}">
                <span class="tpc-eyebrow">{{ ___('frontend.people') }}</span>
                <h2 class="tpc-title">{{ @$sections['our_teachers']->defaultTranslate->name }}</h2>
                <p class="tpc-lead">{{ @$sections['our_teachers']->defaultTranslate->description }}</p>
            </div>
            @if ($data['teachers']->count())
                <div class="row g-4">
                    @foreach ($data['teachers'] as $item)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="tpc-card tpc-person">
                                <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="{{ @$item->first_name }} {{ @$item->last_name }}">
                                <div class="tpc-person__body">
                                    <h3>{{ @$item->first_name }} {{ @$item->last_name }}</h3>
                                    <span class="tpc-meta">{{ @$item->designation->name }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    <!-- FACULTY::END -->
@endsection
