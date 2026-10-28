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
                            <li class="breadcrumb-item" aria-current="page"><a
                                    href="{{ route('users.index') }}">{{ ___('student_info.staff_list') }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ ___('common.add_new') }}</li>
                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}
        <div class="card ot-card">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-body">
                <form action="{{ route('users.importStore') }}" enctype="multipart/form-data" method="post"
                    id="visitForm">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">

                                <!-- Notice Section -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <code
                                        class="text-danger">{{ ___('student_info.Must be follw below instructions & Before Inserting data please download sample file and fill it as required') }}</code>
                                    <div>
                                        <a href="{{ route('users.sampleDownload') }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-download"></i> {{ ___('student_info.Sample File') }}
                                        </a>
                                    </div>
                                </div>

                                <!-- Instructions List -->

                                <code> {{ ___('student_info.Staff Email must be unique') }}</code>
                                <code> {{ ___('student_info.Phone must be unique') }}</code>
                                <code> {{ ___('student_info.Staff ID must be unique') }}</code>
                                <code> {{ ___('student_info.Maritual Status') }} : <strong>1 = Married, 2 = Unmarried</strong></code>
                                <code> {{ ___('student_info.Default Password') }} : <strong>123456</strong></code>
                            </div>

                            <div class="row mt-10">
                                <div class="col-md-3">
                                    <label for="validationServer04" class="form-label">{{ ___('student_info.Role') }}
                                        <span class="fillable">*</span></label>
                                    <select id="getSections"
                                        class="nice-select niceSelect bordered_style wide @error('role') is-invalid @enderror"
                                        name="role" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="">{{ ___('student_info.select_role') }}</option>
                                        @foreach ($data['roles'] as $role)
                                            <option {{ old('role') == $role->id ? 'selected' : '' }}
                                                value="{{ $role->id }}">{{ $role->name }}
                                        @endforeach
                                        </option>
                                    </select>

                                    @error('role')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                                <div class="col-md-3">
                                    <label for="validationServer04" class="form-label">{{ ___('student_info.Designation') }}
                                        <span class="fillable">*</span></label>
                                    <select id="designation"
                                        class="nice-select niceSelect bordered_style wide @error('designation') is-invalid @enderror"
                                        name="designation" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="">{{ ___('student_info.select_designation') }}</option>
                                        @foreach ($data['designations'] as $designation)
                                            <option {{ old('designation') == $designation->id ? 'selected' : '' }}
                                                value="{{ $designation->id }}">{{ $designation->name }}
                                        @endforeach
                                        </option>
                                    </select>

                                    @error('designation')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                                <div class="col-md-3">
                                    <label for="validationServer04" class="form-label">{{ ___('student_info.Department') }}
                                        <span class="fillable">*</span></label>
                                    <select id="departments"
                                        class="nice-select niceSelect bordered_style wide @error('department') is-invalid @enderror"
                                        name="department" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="">{{ ___('student_info.select_department') }}</option>
                                        @foreach ($data['departments'] as $department)
                                            <option {{ old('department') == $department->id ? 'selected' : '' }}
                                                value="{{ $department->id }}">{{ $department->name }}
                                        @endforeach
                                        </option>
                                    </select>

                                    @error('department')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                <div class="col-md-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.File') }}
                                        {{ ___('common.(100 x 100 px)') }}<span class="fillable"> *</span></label>
                                    <div class="ot_fileUploader left-side mb-0">
                                        <input class="form-control" type="text" placeholder="{{ ___('common.File') }}"
                                            readonly="" id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse">{{ ___('common.browse') }}</label>
                                         <input type="file" class="d-none" name="file" id="fileBrouse">
                                        </button>
                                    </div>
                                    @error('file')
                                        <span class="text-danger">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>



                            <div class="row">
                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.submit') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            var fileInp1 = document.getElementById("fileBrouse1");
            if (fileInp1) {
                fileInp1.addEventListener("change", showFileName);

                function showFileName(event) {
                    var fileInp = event.srcElement;
                    var fileName = fileInp.files[0].name;
                    document.getElementById("placeholder1").placeholder = fileName;
                }
            }

            function checkCheckboxState() {
                var isChecked = $('#previous_school').prop('checked');
                console.log(isChecked)
                if (isChecked) {
                    $('#previous_school_info').removeClass('d-none');
                    $('#previous_school_doc').removeClass('d-none');
                } else {
                    $('#previous_school_info').addClass('d-none');
                    $('#previous_school_doc').addClass('d-none');
                }
            }

            $('#previous_school').change(checkCheckboxState);
            checkCheckboxState();
        });
    </script>
@endpush
