@include('backend.admissions.partials.header', ['section' => ___('enrollment.enrollment')])
@if ($errors->any())
    <div class="alert alert-danger tpc-enr-errors">
        <ul class="mb-0">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
