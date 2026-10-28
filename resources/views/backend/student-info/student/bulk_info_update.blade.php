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
                                    href="{{ route('student.index') }}">{{ ___('student_info.student_list') }}</a>
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
                <div class="row mb-3">
                    <div class="col-lg-12">
                        <div class="row">

                            <!-- Notice Section -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <code class="text-danger fs-20">You Must be follw below instructions & Before upload excel
                                    please download file and fill it as required then upload new file as required</code>
                                <div>

                                </div>
                            </div>

                            <!-- Instructions List -->


                        </div>

                        <div class="row mt-20">
                            <div class="card-header">
                                <h5> Step 1</h5>
                            </div>
                            <div class="col-md-6">
                                <label for="validationServer04" class="form-label">{{ ___('student_info.class') }}
                                    <span class="fillable">*</span></label>
                                <select id="getSections"
                                    class="nice-select niceSelect bordered_style class_select wide @error('class') is-invalid @enderror"
                                    name="class" id="validationServer04" aria-describedby="validationServer04">
                                    <option value="">{{ ___('student_info.all_class') }}</option>
                                    @foreach ($data['classes'] as $item)
                                        <option {{ old('class') == $item->id ? 'selected' : '' }}
                                            value="{{ $item->class->id }}">{{ $item->class->name }}
                                    @endforeach
                                    </option>
                                </select>

                                @error('class')
                                    <div id="validationServer04" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div class="col-md-6">
                                <label for="validationServer04" class="form-label">{{ ___('student_info.section') }}
                                </label>
                                <select id="getSections"
                                    class="nice-select sections niceSelect bordered_style section_select wide @error('section') is-invalid @enderror"
                                    name="section" id="validationServer04" aria-describedby="validationServer04Feedback">
                                    <option value="">{{ ___('student_info.select_section') }}</option>
                                </select>
                                @error('section')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-12 mt-24">
                                <div class="text-end">
                                    <button class="btn btn-lg ot-btn-primary" onClick="downloadCsv(event)"><span><i
                                                class="fa-solid fa-file-download"></i>
                                        </span>{{ ___('common.download') }}</button>
                                    <span class="loader spinner-border spinner-border-sm ms-2 d-none loader"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Notice Section -->
                        <div class="d-flex justify-content-between align-items-center mb-3 text-center mt-5">
                            <code class="text-danger">Please download the Excel file from Step 1 and update only the
                                information inside the rows as needed.
                                Do not change or rename any of the column headers. After making your updates, upload the
                                file again in Step 2. </code>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        <div class="card ot-card mt-10">
            <div class="card-header">
                <h5> Step 2</h5>
            </div>
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
                <form action="{{ route('student.bulkCsvUpdate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-3">

                        <div class="col-lg-12">
                            <div class="row">

                                <!-- Notice Section -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <code
                                        class="text-danger">{{ ___('student_info.Must be follw below instructions & Before Inserting data please download sample file and fill it as required') }}</code>

                                </div>

                                <!-- Instructions List -->
                                <code> Student Admission Number must be unique</code>
                                <code> Student Email must be unique</code>
                                <code> Student Username must be unique</code>
                                <code> Student Mobile must be unique</code>
                                <code> Guardian Email must be unique</code>
                                <code> Guardian Username must be unique</code>
                                <code> Guardian Mobile must be unique</code>
                                <code> {{ ___('student_info.Guardian Email must be unique') }}</code>

                                <code> {{ ___('student_info.Religion') }}: 1 = Islam, 2
                                    = Hindu, 3 = Christian</code>

                                <code>{{ ___('student_info.Gender') }}: 1 = Male, 2 =
                                    Female</code>

                                <code>{{ ___('student_info.Blood') }}: 1 = A+, 2 = A-,
                                    3 = B+, 4 = B-, 5 = O+, 6 = O-, 7 = AB+, 8 = AB-</code>
                                <code>
                                    {{ ___('student_info.Student Category') }}:
                                    @foreach ($data['categories'] as $key => $item)
                                        {{ $item->id }} = {{ $item->name }}
                                    @endforeach
                                </code>

                                <code>
                                    {{ ___('student_info.Attend School Previously') }}: 1 = Yes, 0 = No
                                </code>
                            </div>

                            <div class="row mt-20">
                                <div class="col-md-12">
                                    <label for="exampleDataList" class="form-label ">Modified Excel File
                                        <span class="fillable"> *</span></label>
                                    <div class="ot_fileUploader left-side mb-0">
                                        <input class="form-control" type="text" placeholder="Modified Excel File"
                                            readonly="" id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control" name="file"
                                                id="fileBrouse">
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
                                        <button class="btn btn-lg ot-btn-primary" type="submit"><span><i
                                                    class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.submit') }}</button>
                                        <span class="loader spinner-border spinner-border-sm ms-2 d-none loader"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
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

        });


        function downloadCsv(event) {
            event.preventDefault();

            const classId = document.querySelector('.class_select').value;
            const sectionId = document.querySelector('.section_select').value;

            const loader = document.querySelector('.loader');
            loader.classList.remove('d-none');

            const url = '{{ route('student.bulkCsvDownload') }}';

            // Build form data for POST
            const formData = new FormData();
            formData.append('class', classId);
            formData.append('section', sectionId);
            formData.append('_token', '{{ csrf_token() }}');

            fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to download");
                    }
                    return response.blob();
                })
                .then(blob => {

                    // Create download link
                    const downloadUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');

                    a.href = downloadUrl;

                    // Optional: Set filename dynamically
                    // a.download = "bulk_student_export.csv";

                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    window.URL.revokeObjectURL(downloadUrl);
                })
                .catch(error => {
                    console.error("Download error:", error);
                    alert("Error downloading file.");
                })
                .finally(() => {
                    loader.classList.add('d-none'); // hide loader
                });
        }
    </script>
@endpush
