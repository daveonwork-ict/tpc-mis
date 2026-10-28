<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') — {{ settingLocale('application_name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ @globalAsset(setting('favicon')) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="{{ setting('application_name') }}">
    <meta content="Tarlac Provincial College" name="author" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/icon-fonts.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/plugin.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/style.css?v=tpc3">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/custom.css?v=tpc3">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/auth-tpc.css?v=1">
</head>

<body class="default-theme tpc-auth {{ @findDirectionOfLang() }}" dir="{{ @findDirectionOfLang() }}">
    <main class="auth-page">
        <section class="auth-container">
            <div class="tpc-auth-card">
                <aside class="tpc-auth-aside">
                    <a href="{{ url('/') }}" class="tpc-auth-brand">
                        <img class="tpc-auth-seal" src="{{ @globalAsset(setting('dark_logo'), '40X40.webp') }}" alt="{{ settingLocale('application_name') }}">
                        @if (setting('province_logo'))
                            <img class="tpc-auth-seal" src="{{ @globalAsset(setting('province_logo'), '40X40.webp') }}" alt="{{ ___('common.official_seal_of_the_province_of_tarlac') }}">
                        @endif
                    </a>
                    <p class="tpc-auth-kicker">{{ ___('common.republic_of_the_philippines') }}</p>
                    <h2>{{ settingLocale('application_name') }}</h2>
                    <p class="tpc-auth-province">{{ ___('common.province_of_tarlac') }}</p>
                    <p class="tpc-auth-note">{{ ___('common.college_portal_note') }}</p>
                    <a class="tpc-auth-back" href="{{ url('/') }}"><i class="las la-arrow-left"></i> {{ ___('common.back_to_the_website') }}</a>
                </aside>
                <div class="form-wrapper bg-white d-flex justify-content-center align-items-center flex-column">
                    <div class="form-container d-flex justify-content-center align-items-start flex-column">
                        @yield('content')
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- main content end -->
    <script src="{{ global_asset('backend') }}/assets/js/jquery-3.6.0.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/plugin.js"></script>

    <script src="{{ global_asset('backend') }}/assets/js/show-hide-password.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/custom.js"></script>

    @include('backend.partials.alert-message')
    <!-- vendors js  -->
    @yield('script')

</body>

</html>
