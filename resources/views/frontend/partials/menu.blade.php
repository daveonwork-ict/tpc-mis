    <!-- HEADER::START -->
    <header>
        <div id="sticky-header" class="header_area">
            <div class="header_topbar_area">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="header_topbar_wrapper">
                                <div class="header_topbar_left">
                                    <a href="https://www.gov.ph/" target="_blank" rel="noopener" class="tpc-govph">GOV.PH</a>
                                    <p><i class="fas fa-map-marker-alt"></i> {{ settingLocale('address') }}</p>
                                    <p><i class="far fa-envelope"></i> <a href="mailto:{{ setting('email') }}" class="text-reset">{{ setting('email') }}</a></p>
                                </div>
                                <div class="header_topbar_right">
                                    <div class="header_topbar_social d-none d-sm-flex">
                                        @foreach ($sections['social_links']->data ?? [] as $item)
                                        <a target="_blank" href="{{ $item['link'] }}"><i class="{{ $item['icon'] }}"></i></a>
                                        @endforeach
                                    </div>
                                    <div class="login__regiter d-flex align-items-center">
                                        @if(!\Auth::check())
                                        <a href="{{route('login')}}">{{ ___('frontend.Login') }} </a>
                                        @else
                                            @if(\Auth::user()->role_id == 6)
                                                <a href="{{route('student-panel-dashboard.index')}}">{{ ___('frontend.Dashboard') }} </a>

                                            @elseif(\Auth::user()->role_id == 7)
                                                <a href="{{route('parent-panel-dashboard.index')}}">{{ ___('frontend.Dashboard') }} </a>
                                            @else
                                                <a href="{{route('dashboard')}}">{{ ___('frontend.Dashboard') }} </a>

                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="header__wrapper">
                            <!-- header__left__start  -->
                            <div class="header__left d-flex align-items-center">
                                <div class="logo_img">
                                    <a href="{{ route('frontend.home') }}">
                                        <img class="logo light_logo " src="{{ @globalAsset(setting('dark_logo'), 'dark.png') }}" alt="{{ settingLocale('application_name') }}">
                                        <img class="logo dark_logo" src="{{ @globalAsset(setting('light_logo'), 'light.png') }}" alt="{{ settingLocale('application_name') }}">
                                        @if (setting('province_logo'))
                                            <img class="tpc-province-seal" src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="{{ ___('frontend.seal_of_the_province_of_tarlac') }}">
                                        @endif
                                        <span class="tpc-wordmark">
                                            <small>{{ ___('frontend.republic_of_the_philippines') }}</small>
                                            <strong>{{ settingLocale('application_name') }}</strong>
                                            <span>{{ ___('frontend.province_of_tarlac') }}</span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                            <!-- header__left__start  -->

                            <!-- main_menu_start  -->
                            <div class="main_menu text-right d-none d-lg-block">
                                <nav>
                                    <ul id="mobile-menu">
                                        <li><a href="{{ route('frontend.home') }}">{{ ___('frontend.Home') }}</a></li>
                                        <li><a href="{{ route('frontend.about') }}">{{ ___('frontend.About') }}</a></li>
                                        <li><a href="{{ route('frontend.news') }}">{{ ___('frontend.News') }}</a></li>
                                        <li><a href="{{ route('frontend.events') }}">{{ ___('frontend.Events') }}</a></li>
                                        <li><a href="{{ route('frontend.notices') }}">{{ ___('frontend.notices') }}</a></li>
                                        <li><a href="{{ route('frontend.contact') }}">{{ ___('frontend.contact_us') }}</a></li>
                                        <li class="d-lg-none"><a href="{{ route('frontend.apply') }}">{{ ___('frontend.online_admission') }}</a></li>
                                        @foreach ($header_pages as $header)
                                            <li><a href="{{ route('frontend.page',$header->slug) }}">{{ @$header->defaultTranslate->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </nav>
                            </div>
                            <!-- main_menu_start  -->

                            <!-- header__right_start  -->
                            <div class="header__right">
                                <div class="contact_wrap d-flex align-items-center">
                                    <div class="contact_btn d-none d-lg-flex gap_15 align-items-center">
                                        <a href="{{ route('frontend.apply') }}" class="tpc-btn tpc-btn--primary">{{ ___('frontend.online_admission') }}</a>
                                    </div>

                                </div>
                            </div>
                            <!-- header__right_end  -->
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mobile_menu d-block d-lg-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!--/ HEADER::END -->
