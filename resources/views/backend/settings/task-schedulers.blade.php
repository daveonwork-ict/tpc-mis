@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-header">
                <h4>{{ $data['title'] }}</h4>
            </div>
            <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <Span><strong>{{___("settings.Cron Common Command For all")}}  </strong> : {{___('settings.* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1')}} </Span>
                        </div>
                    </div>

            </div>
        </div>
    </div>
@endsection
