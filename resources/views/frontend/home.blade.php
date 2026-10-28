@extends('frontend.master')
@section('title')
    {{ settingLocale('application_name') }}
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

    $latestNews = $data['latestNews'];
    $featuredNews = $latestNews->first();
    $otherNews = $latestNews->slice(1, 3);
@endphp

@section('main')
    <!-- HERO::START -->
    <section class="tpc-hero">
        <div class="banner_active owl-carousel">
            @foreach ($data['sliders'] as $item)
                <div class="tpc-hero__slide" data-background="{{ @globalAsset(@$item->upload->path, '1920X700.webp') }}">
                    <div class="container">
                        <div class="tpc-hero__content">
                            <span class="tpc-eyebrow tpc-eyebrow--light">{{ ___('frontend.provincial_government_of_tarlac') }}</span>
                            <h1>{{ @$item->defaultTranslate->name }}</h1>
                            <p>{{ @$item->defaultTranslate->description }}</p>
                            <div class="tpc-hero__actions">
                                <a href="{{ route('frontend.about') }}" class="tpc-btn tpc-btn--gold">{{ ___('frontend.learn_more') }}</a>
                                <a href="{{ route('frontend.contact') }}" class="tpc-btn tpc-btn--ghost">{{ ___('frontend.contact_us') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    <!-- HERO::END -->

    <!-- FACTS::START -->
    @if ($data['counters']->count())
        <section class="tpc-facts">
            <div class="container">
                <div class="tpc-facts__card">
                    @foreach ($data['counters'] as $item)
                        <div class="tpc-facts__item">
                            <strong>{{ @$item->defaultTranslate->total_count }}</strong>
                            <span>{{ @$item->defaultTranslate->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- FACTS::END -->

    <!-- ABOUT::START -->
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
                        <div class="tpc-statements">
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
                        <a href="{{ route('frontend.about') }}" class="tpc-btn tpc-btn--primary">
                            {{ ___('frontend.learn_more') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ABOUT::END -->

    <!-- SCHOOLS::START -->
    <section class="tpc-section tpc-section--tint">
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

    <!-- EXPLORE::START -->
    <section class="tpc-section tpc-explore">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-lg-2">
                    <figure class="tpc-media-frame tpc-media-frame--right">
                        <img src="{{ @globalAsset(@$sections['explore']->upload->path, '512X512.webp') }}"
                            alt="{{ @$sections['explore']->defaultTranslate->name }}">
                    </figure>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <span class="tpc-eyebrow">{{ ___('frontend.campus_and_programs') }}</span>
                    <h2 class="tpc-title">{{ @$sections['explore']->defaultTranslate->name }}</h2>
                    <p class="tpc-lead">{{ @$sections['explore']->defaultTranslate->description }}</p>

                    <div class="nav tpc-tabs" id="nav-tab" role="tablist">
                        @foreach ($sectionData('explore') as $key => $item)
                            <a class="nav-item nav-link {{ $key == 0 ? 'active' : '' }}" id="item{{ $key }}-tab"
                                data-toggle="tab" href="#item{{ $key }}" role="tab"
                                aria-controls="item{{ $key }}" aria-selected="{{ $key == 0 ? 'true' : 'false' }}">{{ $item['tab'] }}</a>
                        @endforeach
                    </div>
                    <div class="tab-content tpc-tab-content" id="nav-tabContent">
                        @foreach ($sectionData('explore') as $key => $item)
                            <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}" id="item{{ $key }}"
                                role="tabpanel" aria-labelledby="item{{ $key }}-tab">
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- EXPLORE::END -->

    <!-- WHY::START -->
    <section class="tpc-section tpc-why">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <span class="tpc-eyebrow tpc-eyebrow--light">{{ ___('frontend.public_service_education') }}</span>
                    <h2 class="tpc-title tpc-title--light">{{ @$sections['why_choose_us']->defaultTranslate->name }}</h2>
                    <p class="tpc-lead tpc-lead--light">{{ @$sections['why_choose_us']->defaultTranslate->description }}</p>
                </div>
                <div class="col-lg-7">
                    <div class="tpc-why__grid">
                        @foreach ($sectionData('why_choose_us') as $item)
                            <div class="tpc-why__item">
                                <i class="fas fa-check"></i>
                                <p>{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- WHY::END -->

    <!-- EVENTS::START -->
    @if ($data['comingEvents']->count())
        <section class="tpc-section">
            <div class="container">
                <div class="tpc-section-head tpc-section-head--split">
                    <div>
                        <span class="tpc-eyebrow">{{ ___('frontend.Events') }}</span>
                        <h2 class="tpc-title">{{ @$sections['coming_up']->defaultTranslate->name }}</h2>
                        <p class="tpc-lead">{{ @$sections['coming_up']->defaultTranslate->description }}</p>
                    </div>
                    <a href="{{ route('frontend.events') }}" class="tpc-link">{{ ___('frontend.view_all_events') }} <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="row g-4">
                    @foreach ($data['comingEvents'] as $item)
                        <div class="col-lg-6">
                            <a href="{{ route('frontend.events-detail', $item->id) }}" class="tpc-card tpc-event">
                                <div class="tpc-event__date">
                                    <strong>{{ \Carbon\Carbon::parse($item->date)->format('d') }}</strong>
                                    <span>{{ \Carbon\Carbon::parse($item->date)->format('M Y') }}</span>
                                </div>
                                <div>
                                    <h3>{{ @$item->defaultTranslate->title }}</h3>
                                    <span class="tpc-meta"><i class="far fa-clock"></i> {{ timeFormat($item->start_time) }} – {{ timeFormat($item->end_time) }}</span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- EVENTS::END -->

    <!-- NEWS::START -->
    @if ($featuredNews)
        <section class="tpc-section tpc-section--tint">
            <div class="container">
                <div class="tpc-section-head tpc-section-head--split">
                    <div>
                        <span class="tpc-eyebrow">{{ ___('frontend.newsroom') }}</span>
                        <h2 class="tpc-title">{{ @$sections['news']->defaultTranslate->name }}</h2>
                        <p class="tpc-lead">{{ @$sections['news']->defaultTranslate->description }}</p>
                    </div>
                    <a href="{{ route('frontend.news') }}" class="tpc-link">{{ ___('frontend.view_all_news') }} <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <article class="tpc-card tpc-news-feature">
                            <a href="{{ route('frontend.news-detail', $featuredNews->id) }}" class="tpc-news-feature__thumb">
                                <img src="{{ @globalAsset(@$featuredNews->upload->path, '600X480.webp') }}" alt="{{ @$featuredNews->defaultTranslate->title }}">
                            </a>
                            <div class="tpc-news-feature__body">
                                <span class="tpc-meta"><i class="far fa-calendar-alt"></i> {{ dateFormat($featuredNews->date) }}</span>
                                <h3><a href="{{ route('frontend.news-detail', $featuredNews->id) }}">{{ @$featuredNews->defaultTranslate->title }}</a></h3>
                                <p>{{ Str::limit(trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', @$featuredNews->defaultTranslate->description))), 220) }}</p>
                                <a href="{{ route('frontend.news-detail', $featuredNews->id) }}" class="tpc-link">{{ ___('frontend.read_more') }} <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </article>
                    </div>
                    <div class="col-lg-5">
                        <div class="tpc-news-list">
                            @foreach ($otherNews as $item)
                                <a href="{{ route('frontend.news-detail', $item->id) }}" class="tpc-card tpc-news-item">
                                    <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="{{ @$item->defaultTranslate->title }}">
                                    <div>
                                        <span class="tpc-meta"><i class="far fa-calendar-alt"></i> {{ dateFormat($item->date) }}</span>
                                        <h3>{{ @$item->defaultTranslate->title }}</h3>
                                        <span class="tpc-link">{{ ___('frontend.read_more') }} <i class="fas fa-arrow-right"></i></span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!-- NEWS::END -->

    <!-- GALLERY::START -->
    @if ($data['gallery']->count())
        <section class="tpc-section gallery_area">
            <div class="container">
                <div class="tpc-section-head">
                    <span class="tpc-eyebrow">{{ ___('frontend.campus_life') }}</span>
                    <h2 class="tpc-title">{{ @$sections['our_gallery']->defaultTranslate->name }}</h2>
                    <p class="tpc-lead">{{ @$sections['our_gallery']->defaultTranslate->description }}</p>
                </div>
                @if ($data['galleryCategory']->count() > 1)
                    <div class="portfolio-menu tpc-filters">
                        <button class="active" data-filter="*">{{ ___('frontend.All') }}</button>
                        @foreach ($data['galleryCategory'] as $item)
                            <button data-filter=".cat-{{ $item->id }}">{{ @$item->defaultTranslate->name }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="row grid g-3 tpc-gallery-grid">
                    @foreach ($data['gallery']->take(8) as $item)
                        <div class="col-lg-3 col-md-4 col-6 grid-item cat-{{ $item->gallery_category_id }}">
                            <a href="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" class="tpc-gallery-item popup-image">
                                <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="">
                                <span><i class="fas fa-expand"></i></span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- GALLERY::END -->

    <!-- CTA::START -->
    <section class="tpc-cta">
        <div class="container">
            <div class="tpc-cta__inner">
                <div>
                    <h2>{{ ___('frontend.admission_inquiries') }}</h2>
                    <p>{{ ___('frontend.admission_inquiries_text') }}</p>
                </div>
                <div class="tpc-cta__actions">
                    <a href="{{ route('frontend.apply') }}" class="tpc-btn tpc-btn--gold">{{ ___('frontend.online_admission') }}</a>
                    <a href="{{ route('frontend.contact') }}" class="tpc-btn tpc-btn--ghost">{{ ___('frontend.contact_us') }}</a>
                </div>
            </div>
        </div>
    </section>
    <!-- CTA::END -->
@endsection
