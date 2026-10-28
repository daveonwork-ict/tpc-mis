
    <!-- FOOTER::START  -->
    <footer class="home_three_footer">
        <div class="main_footer_wrap">
            <div class="container">
                 <div class="row">
                    <div class="col-lg-4 col-md-12">
                        <div class="footer_widget pr-lg-4">
                            <a href="{{ route('frontend.home') }}" class="footer_logo">
                                <img src="{{ @globalAsset(setting('light_logo'), '154X38.webp') }}" alt="{{ settingLocale('application_name') }}">
                                @if (setting('province_logo'))
                                    <img class="tpc-province-seal" src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="{{ ___('frontend.seal_of_the_province_of_tarlac') }}">
                                @endif
                                <span class="tpc-wordmark">
                                    <strong>{{ settingLocale('application_name') }}</strong>
                                    <span>{{ ___('frontend.province_of_tarlac') }}</span>
                                </span>
                            </a>
                            @php
                                $footerAbout = '';
                                foreach (preg_split('/(?<=[.!?])\s+(?=[A-Z])/', trim((string) settingLocale('school_about'))) as $sentence) {
                                    if ($footerAbout !== '' && mb_strlen($footerAbout . ' ' . $sentence) > 260) {
                                        break;
                                    }
                                    $footerAbout = trim($footerAbout . ' ' . $sentence);
                                }
                            @endphp
                            <p class="description_text">{{ $footerAbout }}</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="footer_widget">
                            <div class="footer_title">
                                <h3>{{ ___('frontend.the_college') }}</h3>
                            </div>
                            <ul class="footer_links">
                                <li><a href="{{ route('frontend.about') }}">{{ ___('frontend.About') }}</a></li>
                                <li><a href="{{ route('frontend.news') }}">{{ ___('frontend.News') }}</a></li>
                                <li><a href="{{ route('frontend.events') }}">{{ ___('frontend.Events') }}</a></li>
                                <li><a href="{{ route('frontend.notices') }}">{{ ___('frontend.notices') }}</a></li>
                                <li><a href="{{ route('frontend.contact') }}">{{ ___('frontend.contact_us') }}</a></li>
                                @foreach ($footer_pages as $page)
                                    <li><a href="{{ route('frontend.page',$page->slug) }}">{{ @$page->defaultTranslate->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="footer_widget">
                            <div class="footer_title">
                                <h3>{{ ___('frontend.services') }}</h3>
                            </div>
                            <ul class="footer_links">
                                <li><a href="{{ route('frontend.apply') }}">{{ ___('frontend.online_admission') }}</a></li>
                                <li><a href="{{ route('frontend.apply.status') }}">{{ ___('admissions.application_status') }}</a></li>
                                <li><a href="{{ route('loginStudent') }}" target="_blank">{{ ___('frontend.Student Login') }}</a></li>
                                <li><a href="{{ route('loginGuardian') }}" target="_blank">{{ ___('frontend.Guardian Login') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <div class="footer_widget">
                            <div class="footer_title">
                                <h3>{{ ___('frontend.contact_us') }}</h3>
                            </div>
                            <ul class="tpc-footer-contact">
                                <li><i class="fas fa-map-marker-alt"></i><span>{{ settingLocale('address') }}</span></li>
                                <li><i class="far fa-envelope"></i><a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>
                            </ul>
                            <div class="social__Links">
                                @foreach ($sections['social_links']->data ?? [] as $item)
                                    <a target="_blank" rel="noopener" href="{{ $item['link'] }}" aria-label="{{ $item['name'] ?? '' }}"><i class="{{ $item['icon'] }}"></i></a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tpc-govfooter">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-12">
                        <div class="tpc-govfooter__brand">
                            <h4>{{ ___('frontend.republic_of_the_philippines') }}</h4>
                            <p>{{ ___('frontend.public_domain_notice') }}</p>
                            <p class="tpc-phst">
                                <span>{{ ___('frontend.philippine_standard_time') }}</span>
                                <time id="tpc-phst-clock" aria-live="off"></time>
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <h5>{{ ___('frontend.about_govph') }}</h5>
                        <p class="tpc-govfooter__text">{{ ___('frontend.about_govph_text') }}</p>
                        <ul>
                            <li><a href="https://www.gov.ph/" target="_blank" rel="noopener">GOV.PH</a></li>
                            <li><a href="https://data.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.open_data_portal') }}</a></li>
                            <li><a href="https://www.officialgazette.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.official_gazette') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <h5>{{ ___('frontend.government_links') }}</h5>
                        <ul>
                            <li><a href="https://op-proper.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.office_of_the_president') }}</a></li>
                            <li><a href="https://ovp.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.office_of_the_vice_president') }}</a></li>
                            <li><a href="https://web.senate.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.senate_of_the_philippines') }}</a></li>
                            <li><a href="https://www.congress.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.house_of_representatives') }}</a></li>
                            <li><a href="https://sc.judiciary.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.supreme_court') }}</a></li>
                            <li><a href="https://ca.judiciary.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.court_of_appeals') }}</a></li>
                            <li><a href="https://sb.judiciary.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.sandiganbayan') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <h5>{{ ___('frontend.related_agencies') }}</h5>
                        <ul>
                            <li><a href="https://tarlac.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.provincial_government_of_tarlac') }}</a></li>
                            <li><a href="https://ched.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.commission_on_higher_education') }}</a></li>
                            <li><a href="https://www.tesda.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.tesda_full') }}</a></li>
                            <li><a href="https://privacy.gov.ph/" target="_blank" rel="noopener">{{ ___('frontend.national_privacy_commission') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright_area p-0">
            <div class="container">
                <div class="tpc-legalbar">
                    <p>{{ settingLocale('footer_text') }}</p>
                    @if (count($policy_pages ?? []))
                        <ul>
                            @foreach ($policy_pages as $page)
                                <li><a href="{{ route('frontend.page', $page->slug) }}">{{ @$page->defaultTranslate->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </footer>
    <script>
        (function () {
            var el = document.getElementById('tpc-phst-clock');
            if (!el || !window.Intl) return;
            var fmt = new Intl.DateTimeFormat('en-PH', {
                timeZone: 'Asia/Manila', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: 'numeric', minute: '2-digit', second: '2-digit'
            });
            var tick = function () { el.textContent = fmt.format(new Date()); };
            tick();
            setInterval(tick, 1000);
        })();
    </script>
    <!-- FOOTER::END  -->
