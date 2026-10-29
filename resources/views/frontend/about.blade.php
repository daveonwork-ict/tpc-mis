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
                        <h2 class="tpc-title">About Tarlac Provincial College</h2>
                        <p class="tpc-lead">The Province of Tarlac protects and promotes the right of its people to quality education at all levels, and makes that education accessible through a system that meets local and sectoral needs.</p>
                        <p class="tpc-lead mb-0">Education is one of the province’s five pillars of good governance, together with job generation, healthcare, agriculture and food security, and environmental protection. Under the banner “Edukasyon muna, Tao muna,” the provincial administration treats accessible, equal, and quality education as the way to prepare Tarlaqueños for work and to support the growth of the province.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- STATEMENT::END -->

    <section class="tpc-section tpc-section--tint">
        <div class="container">
            <div class="tpc-purpose">
                <article class="tpc-purpose__vision">
                    <span class="tpc-eyebrow">Vision</span>
                    <p>Tarlac Provincial College is the center of educational opportunities for Tarlaqueños.</p>
                </article>
                <div class="tpc-purpose__grid">
                    <article class="tpc-card tpc-purpose__card">
                        <h2>Mission</h2>
                        <ol>
                            <li>Produce competent, committed, and compassionate graduates through quality, accessible, and inclusive education.</li>
                            <li>Build pathways to translate education into meaningful employment and contribution to society.</li>
                            <li>Partner with communities for the advancement of local development and global engagement.</li>
                        </ol>
                    </article>
                    <article class="tpc-card tpc-purpose__card">
                        <h2>Goals</h2>
                        <ol>
                            <li>Produce graduates with strong disciplinary foundations, employability, local-rootedness, and a sense of service, aligned with global standards.</li>
                            <li>Provide opportunities that meet the needs of the people and respond to their changing social and economic conditions.</li>
                            <li>Sustain the College through good governance and continuous quality assurance in academic and administrative systems.</li>
                        </ol>
                    </article>
                </div>
            </div>
            <h2 class="tpc-title tpc-title--sm">Core values</h2>
            <p class="tpc-lead">Truth. Progress. Compassion. These values shape the intellect and the character of learners and guide decisions, relationships, and the culture of the College.</p>
            <div class="row g-4">
                <div class="col-md-4">
                    <article class="tpc-card tpc-value">
                        <h3>Truth</h3>
                        <p>The College upholds integrity, transparency, and accountability in academic and administrative work. It fosters honesty, ethical leadership, and decisions based on evidence.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="tpc-card tpc-value">
                        <h3>Progress</h3>
                        <p>The College champions innovation, continuous improvement, and excellence. It welcomes change and looks for practical answers to new educational, technological, and social needs.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="tpc-card tpc-value">
                        <h3>Compassion</h3>
                        <p>The College nurtures empathy, inclusivity, and service. It puts the welfare of students and communities first, and keeps education accessible, humane, and open to those who are marginalized.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="tpc-section">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <span class="tpc-eyebrow">What a TPC graduate carries</span>
                    <h2 class="tpc-title tpc-title--sm">Graduate attributes</h2>
                    <ol class="tpc-manual__attrs">
                        <li><b>Competence.</b> Critical thinking, technical skill, and evidence-based practice for the needs of Tarlac communities, while remaining globally competitive.</li>
                        <li><b>Social responsibility.</b> Cultural sensitivity and ethical responsibility for sustainable, inclusive, and people-centered development, aligned with the Tarlac Provincial Development Framework.</li>
                        <li><b>Lifelong learning.</b> Research, innovation, and digital literacy used to keep learning and to add to knowledge.</li>
                        <li><b>Resiliency.</b> Flexibility, perseverance, and professionalism in changing conditions.</li>
                        <li><b>Collaboration.</b> Partnerships with people, communities, and organizations, locally and globally, toward shared goals.</li>
                    </ol>
                </div>
                <div class="col-lg-7">
                    <article class="tpc-logo-panel">
                        <img class="tpc-college-logo" src="{{ @globalAsset(setting('light_logo'), 'light.png') }}" alt="Seal of Tarlac Provincial College">
                        <div class="tpc-logo-panel__copy">
                            <span class="tpc-eyebrow">College logo</span>
                            <h2 class="tpc-title tpc-title--sm">Collaboration, growth, and service</h2>
                            <p class="tpc-lead">The logo stands for collaboration, growth, and service in accessible, quality higher education. Its circle means wholeness, continuity, and a lasting commitment to lifelong learning and community development. The shield at the center means protection, tradition, excellence, and institutional strength.</p>
                            <p class="tpc-lead mb-0">The double blue ring means unity and permanence. “Tarlac Provincial College” and “Provincial Government of Tarlac” are written on the upper and lower arcs, tying the College to the provincial government.</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

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
