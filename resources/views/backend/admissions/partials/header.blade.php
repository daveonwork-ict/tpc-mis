<div class="page-header">
    <div class="row align-items-end g-3">
        <div class="col-md">
            <h4 class="bradecrumb-title mb-1">{{ $title }}</h4>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                <li class="breadcrumb-item">{{ $section ?? ___('admissions.admissions') }}</li>
                @isset($parent)
                    <li class="breadcrumb-item"><a href="{{ $parent[1] }}">{{ $parent[0] }}</a></li>
                @endisset
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </div>
        @isset($actions)
            <div class="col-md-auto d-flex gap-2 flex-wrap">{!! $actions !!}</div>
        @endisset
    </div>
</div>
