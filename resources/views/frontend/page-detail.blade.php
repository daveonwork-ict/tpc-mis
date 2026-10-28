@extends('frontend.master')
@section('title')
    {{ @$data['page']->defaultTranslate->name }}
@endsection

@section('main')


<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="breadcam_wrap text-center">
                    <h3>{{ @$data['page']->defaultTranslate->name }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ @$data['page']->defaultTranslate->name }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- bradcam::end  -->

@php
    $docPages = $data['page']->menu_show === 'policy' ? $policy_pages : $footer_pages;
@endphp

<section class="tpc-section tpc-doc">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <article class="tpc-doc__body ck-ditor-img">
                    <p class="tpc-doc__meta">
                        <i class="far fa-calendar-alt"></i>
                        {{ ___('frontend.last_updated') }}: {{ optional($data['page']->updated_at)->format('F j, Y') }}
                    </p>
                    {!! @$data['page']->defaultTranslate->content !!}
                </article>
            </div>
            <div class="col-lg-4">
                <aside class="tpc-doc__aside">
                    @if (count($docPages ?? []))
                        <nav class="tpc-doc__nav" aria-label="{{ ___('frontend.related_pages') }}">
                            <h4>{{ $data['page']->menu_show === 'policy' ? ___('frontend.policies_and_notices') : ___('frontend.the_college') }}</h4>
                            <ul>
                                @foreach ($docPages as $item)
                                    <li>
                                        <a href="{{ route('frontend.page', $item->slug) }}" @class(['is-active' => $item->id === $data['page']->id])>
                                            {{ @$item->defaultTranslate->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                    <div class="tpc-doc__help">
                        <h4>{{ ___('frontend.questions_about_this_page') }}</h4>
                        <p>{{ ___('frontend.questions_about_this_page_text') }}</p>
                        <a href="mailto:{{ setting('email') }}"><i class="far fa-envelope"></i> {{ setting('email') }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>

@endsection
