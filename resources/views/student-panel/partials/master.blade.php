<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <link rel="icon" type="image/x-icon" href="{{ @globalAsset(setting('favicon')) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <input type="hidden" name="url" id="url" value="{{ url('') }}">
    <input type="hidden" name="alert_title" id="alert_title" value="{{ ___('common.are_you_sure') }}">
    <input type="hidden" name="alert_subtitle" id="alert_subtitle"
        value="{{ ___('common.you_wont_be_able_to_revert_this') }}">
    <input type="hidden" name="alert_yes_btn" id="alert_yes_btn" value="{{ ___('common.Yes_Confirm') }}">
    <input type="hidden" name="alert_cancel_btn" id="alert_cancel_btn" value="{{ ___('common.Cancel') }}">

    <meta name="keywords"
        content="admin, admin dashboard, admin template, backend, bootstrap, crm, laravel, laravel admin, web application">
    <meta name="description" content="{{ setting('application_name') }}">
    @if (findDirectionOfLang() == 'rtl')
        <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/bootstrap.rtl.min.css">
    @else
        <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/bootstrap.min.css">
    @endif

    <!-- css  -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/semantic.rtl.min.css">
    <!-- metis menu for sidebar  -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/metisMenu.min.css">
    {{-- Chart js --}}
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/apexcharts.min.css">
    <!-- jvectormap css -->
    <link rel="stylesheet" href="{{ global_asset('backend/vendors/jvectormap/css/jquery-jvectormap-1.2.2.css') }}">

    <link href="{{ global_asset('backend') }}/vendors/summernote/summernote-lite.min.css" rel="stylesheet">

    {{-- All icon-fonts --}}
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/icon-fonts.css">
    <!-- All Plugin  -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/plugin.css">
    <!-- Custom CSS  start -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/style.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/style2.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/custom.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/admin-tpc.css?v=21">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    @stack('css')
</head>

<body class="{{ @findDirectionOfLang() }} default-theme" dir="{{ @findDirectionOfLang() }}">

    <div id="layout-wrapper">
        <!-- start header -->
        @include('student-panel.partials.header')
        <!-- end header -->

        <!-- start sidebar -->
        @include('student-panel.partials.sidebar')
        <!-- end sidebar -->

        <main class="main-content ph-24 ph-lg-32 pt-100 mt-4">
            <!-- start main content -->
            @yield('content')
            <!-- end main content -->

            <!-- start footer -->
            @include('student-panel.partials.footer')
            <!-- end footer -->
        </main>
    </div>

    {{-- theme mode switch --}}
    <script src="{{ global_asset('backend') }}/assets/js/theme.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/popper.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/bootstrap.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/jquery-3.6.0.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/semantic.min.js"></script>
    <!-- Metis menu for sidebar  -->
    <script src="{{ global_asset('backend') }}/assets/js/metisMenu.min.js"></script>
    <!-- jvectormap js -->
    <script src="{{ global_asset('backend/vendors/jvectormap/js/jquery-jvectormap-1.2.2.min.js') }}"></script>
    <script src="{{ global_asset('backend/vendors/jvectormap/js/jquery-jvectormap-us-merc-en.js') }}"></script>
    {{-- Chart --}}
    <script src="{{ global_asset('backend') }}/vendors/apexchart/js/apexcharts.min.js"></script>
    <script src="{{ global_asset('backend') }}/vendors/chartjs/js/chart.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/datepicker.min.js"></script>
    {{-- All Plugin js --}}
    <script src="{{ global_asset('backend') }}/assets/js/plugin.js"></script>
    <!-- Vendor JS end  -->
    <script src="{{ global_asset('backend') }}/assets/js/main.js"></script>
    {{-- Custom Js --}}

    <script src="{{ global_asset('backend') }}/assets/js/custom.js"></script>
    {{-- alert message --}}
    @include('backend.partials.alert-message')
    {{-- delete method --}}
    <script src="{{ global_asset('backend') }}/assets/js/__attendance.js"></script>
    {{-- full calender --}}
    <script src='{{ global_asset('backend') }}/assets/js/index.global.min.js'></script>

    <script src="{{ global_asset('backend') }}/assets/js/toastr.min.js"></script>

    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>

    @if (hasModule('Chat'))
        <script>
            Pusher.logToConsole = true;
            window.pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
                cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
                encrypted: true
            });

            var channel = pusher.subscribe('{{ 'chat_user_notify_' . auth()->user()->uuid }}');
            channel.bind('ChatUserNotify', function(data) {
                console.log('📩 New message:', data);
                document.getElementById('unread_message_counter').textContent = parseInt(document.getElementById(
                    'unread_message_counter').textContent) + 1;

                var audio = new Audio('{{ global_asset('modules/chat/audio/notification.mp3') }}');
                audio.play().catch(e => console.log("Audio play failed:", e));

                // Build message HTML with button
                const content = `
                    <div style="padding:8px 0">
                        <strong>${data.message}</strong><br>
                        <a href="${data.action_url}" target="_blank"
                        style="color:#fff;padding:4px 10px;border-radius:4px;text-decoration:none;margin-top:6px;display:inline-block">
                            see now
                        </a>
                    </div>
                    `;

                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 3000,
                    extendedTimeOut: 2000,
                    tapToDismiss: false,
                    newestOnTop: true,
                    positionClass: 'toast-bottom-right',
                    escapeHtml: false,
                };

                toastr.info(content);
            });
        </script>
    @endif


    @stack('script')


</body>

</html>
