<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      content="Tarlac Provincial College"
      name="description"
    />
    <meta content="Tarlac Provincial College" name="author" />
    <title>@yield('title')</title>

    <!-- bootstrap css -->
    <link rel="stylesheet" href="{{global_asset('backend')}}/assets/css/bootstrap.min.css" />
    <!-- style css -->
    <link rel="stylesheet" href="{{global_asset('backend')}}/assets/css/style.css" />
    <!-- Custom CSS  end -->
  </head>

  <body>
     <!-- main content start -->
    @yield('main')
    <!-- main content end -->
  </body>
</html>
