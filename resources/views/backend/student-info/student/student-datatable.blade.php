@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush
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


        <div class="col-12">
            <form action="{{ route('student.datatable-search') }}" method="post" id="marksheed"
                enctype="multipart/form-data">
                @csrf
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                        <div
                            class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                            <!-- table_searchBox -->
                            <div class="single_large_selectBox">
                                <select id="getSections"
                                    class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                    name="class">
                                    <option value="">{{ ___('student_info.select_class') }}</option>
                                    @foreach ($data['classes'] as $item)
                                        <option
                                            {{ old('class', @$data['request']->class) == $item->class->id ? 'selected' : '' }}
                                            value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                    @endforeach
                                </select>
                                @error('class')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="single_large_selectBox">
                                <select
                                    class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                    name="section">
                                    <option value="">{{ ___('student_info.select_section') }}</option>
                                    @foreach ($data['sections'] as $item)
                                        <option
                                            {{ old('section', @$data['request']->section) == $item->section->id ? 'selected' : '' }}
                                            value="{{ $item->section->id }}">{{ $item->section->name }}</option>
                                    @endforeach
                                </select>
                                @error('section')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="single_large_selectBox">
                                <input class="form-control ot-input" name="keyword" list="datalistOptions"
                                    id="exampleDataList" placeholder="{{ ___('student_info.enter_keyword') }}"
                                    value="{{ old('keyword', @$data['request']->keyword) }}">
                            </div>

                            <button class="btn btn-lg ot-btn-primary" type="submit">
                                {{ ___('common.Search') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if (isset($data['request']))
            <input type="hidden" id="filterClass" value="{{ @$data['request']['class'] }}">
            <input type="hidden" id="filterSection" value="{{ @$data['request']['section'] }}">
            <input type="hidden" id="filterKeyword" value="{{ @$data['request']['keyword'] }}">
        @endif


        <!--  table content start -->
        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    <div class="d-flex gap-2 align-items-center">

                        @if (hasPermission('student_create'))
                            <a href="{{ route('student.create') }}" class="btn btn-lg ot-btn-primary">
                                <span><i class="fa-solid fa-plus"></i> </span>
                                <span>{{ ___('common.add') }}</span>
                            </a>
                        @endif
                        @if (hasPermission('student_read'))
                            {{-- Export Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-lg ot-btn-primary dropdown-toggle" type="button" id="exportDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-file-export"></i> {{ ___('common.export') }}
                                </button>

                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li>
                                        <button type="button" class="dropdown-item" onclick="exportData('pdf')">
                                            <i class="fa-solid fa-file-pdf"></i> PDF
                                            <span
                                                class="loader spinner-border spinner-border-sm ms-2 d-none pdf-loader"></span>
                                        </button>
                                    </li>

                                    <li>
                                        <button type="button" class="dropdown-item" onclick="exportData('csv')">
                                            <i class="fa-solid fa-file-csv"></i> CSV
                                            <span
                                                class="loader spinner-border spinner-border-sm ms-2 d-none csv-loader"></span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        @endif

                        <div class="dropdown">
                            <button class="btn btn-lg ot-btn-primary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <ul class="dropdown-menu p-3" style="min-width:200px;">
                                <li>
                                    <input type="checkbox" id="select-all-columns"> Select All
                                </li>
                                <li>
                                    <input type="checkbox" class="toggle-vis" data-column="0" data-item="si" checked>
                                    {{ ___('common.si') }}
                                </li>
                                <li><input type="checkbox" class="toggle-vis" data-column="1" data-item="admission_no"
                                        checked>
                                    {{ ___('student_info.admission_no') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="2" data-item="roll_no"
                                        checked>
                                    {{ ___('student_info.roll_no') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="3" data-item="department"
                                        checked>
                                    {{ ___('student_info.Department') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="4" data-item="name" checked>
                                    {{ ___('student_info.name') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="5"
                                        data-item="student_ar_name" checked>
                                    {{ ___('student_info.arabic_name') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="6" data-item="class_section"
                                        checked>
                                    {{ ___('academic.class') }} ({{ ___('academic.section') }})</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="7" data-item="guardian"
                                        checked>
                                    {{ ___('student_info.guardian') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="8" data-item="gender"
                                        checked>
                                    {{ ___('common.gender') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="9" data-item="mobile"
                                        checked>
                                    {{ ___('student_info.mobile') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="10" data-item="dob" checked>
                                    {{ ___('student_info.dob') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="11"
                                        data-item="admission_date">
                                    {{ ___('student_info.admission_date') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="12"
                                        data-item="previous_school">
                                    {{ ___('student_info.previous_school') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="13"
                                        data-item="place_of_birth">
                                    {{ ___('student_info.place_of_birth') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="14" data-item="religion">
                                    {{ ___('student_info.religion') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="15" data-item="blood_group">
                                    {{ ___('student_info.blood_group') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="16"
                                        data-item="emergency_contact">
                                    {{ ___('student_info.emergency_contact') }}</li>
                                <li><input type="checkbox" class="toggle-vis" data-column="17"
                                        data-item="health_status"> {{ ___('student.Health') }}</li>
                                @if (hasPermission('student_update') || hasPermission('student_delete'))
                                    <li><input type="checkbox" class="toggle-vis" data-column="18">
                                        {{ ___('common.action') }}</li>
                                @endif
                            </ul>
                        </div>

                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="studentTable" class="table table-bordered role-table table-striped dt-responsive">
                            <thead class="thead">
                                <tr>
                                    <th class="serial">{{ ___('common.si') }}</th>
                                    <th class="purchase">{{ ___('student_info.admission_no') }}</th>
                                    <th class="purchase">{{ ___('student_info.roll_no') }}</th>
                                    <th class="purchase">{{ ___('student_info.department') }}</th>
                                    <th class="purchase">{{ ___('student_info.name') }}</th>
                                    <th class="purchase">{{ ___('student_info.arabic_name') }}</th>
                                    <th class="purchase">{{ ___('academic.class') }} ({{ ___('academic.section') }})</th>
                                    <th class="purchase">{{ ___('student_info.guardian') }}</th>
                                    <th class="purchase">{{ ___('student.Gender') }}</th>
                                    <th class="purchase">{{ ___('student_info.mobile') }}</th>
                                    <th class="purchase">{{ ___('student_info.dob') }}</th>
                                    <th class="purchase">{{ ___('student_info.admission_date') }}</th>
                                    <th class="purchase">{{ ___('frontend.previous_school_information') }}</th>
                                    <th class="purchase">{{ ___('frontend.Place_Of_Birth') }}</th>
                                    <th class="purchase">{{ ___('student_info.religion') }}</th>
                                    <th class="purchase">{{ ___('student_info.blood') }}</th>
                                    <th class="purchase">{{ ___('frontend.Emergency_Contact') }}</th>
                                    <th class="purchase">{{ ___('common.Health Status') }}</th>
                                    @if (hasPermission('student_update') || hasPermission('student_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <!--  table end -->
                    <!--  pagination start -->

                    <!--  pagination end -->
                </div>


            </div>
        </div>
        <!--  table content end -->

    </div>
@endsection



@include('backend.partials.delete-ajax')
@push('script')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        const STORAGE_KEY = "{{ 'student_datatable_cols_v2-' . auth()->user()->uuid }}";

        $(document).ready(function() {

            // Load saved column visibility from localStorage
            function loadColumnVisibility() {
                let saved = localStorage.getItem(STORAGE_KEY);
                return saved ? JSON.parse(saved) : null;
            }

            // Save column visibility to localStorage
            function saveColumnVisibility() {
                let data = {};

                $('.toggle-vis').each(function() {
                    let colIndex = $(this).data('column');
                    data[colIndex] = $(this).is(':checked');
                });

                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

            // Apply saved visibility on page load
            function applySavedVisibility(table) {
                let saved = loadColumnVisibility();
                if (!saved) return;

                $('.toggle-vis').each(function() {
                    let colIndex = $(this).data('column');
                    let isVisible = saved[colIndex] !== false; // default true

                    $(this).prop('checked', isVisible);
                    table.column(colIndex).visible(isVisible);
                });

                updateSelectAllCheckbox();
            }

            // Update select-all checkbox
            function updateSelectAllCheckbox() {
                let allChecked = true;
                $('.toggle-vis').each(function() {
                    if (!$(this).is(':checked')) allChecked = false;
                });
                $('#select-all-columns').prop('checked', allChecked);
            }

            // 👉 DataTable Initialization
            var table = $('#studentTable').DataTable({
                processing: true,
                serverSide: false,

                ajax: {
                    url: "{{ route('student.datatable') }}",
                    data: function(d) {
                        d.class = $('#filterClass').val();
                        d.section = $('#filterSection').val();
                        d.keyword = $('#filterKeyword').val();
                    }
                },
                responsive: true,
                autoWidth: true,
                pageLength: 15,
                lengthMenu: [15, 30, 40, 50, 100, 200, 500, 1000, 2000, 5000],

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false
                    },
                    {
                        data: 'student.admission_no',
                        name: 'student.admission_no'
                    },
                    {
                        data: 'roll',
                        name: 'roll'
                    },
                    {
                        data: 'student.department.name',
                        name: 'student.department.name'
                    },
                    {
                        data: 'name',
                        name: 'student.first_name'
                    },
                    {
                        data: 'student.student_ar_name',
                        name: 'student.student_ar_name'
                    },
                    {
                        data: 'class_section',
                        name: 'class.name'
                    },
                    {
                        data: 'student.parent.guardian_name',
                        name: 'student.parent.guardian_name'
                    },
                    {
                        data: 'student.gender.name',
                        name: 'student.gender.name'
                    },
                    {
                        data: 'student.mobile',
                        name: 'student.mobile'
                    },
                    {
                        data: 'student.dob',
                        name: 'student.dob'
                    },
                    {
                        data: 'admission_date',
                        name: 'student.admission_date'
                    },
                    {
                        data: 'student.previous_school_info',
                        name: 'student.previous_school_info'
                    },
                    {
                        data: 'student.place_of_birth',
                        name: 'student.place_of_birth'
                    },
                    {
                        data: 'student.religion.name',
                        name: 'student.religion.name'
                    },
                    {
                        data: 'student.blood.name',
                        name: 'student.blood.name'
                    },
                    {
                        data: 'student.emergency_contact',
                        name: 'student.emergency_contact'
                    },
                    {
                        data: 'student.health_status',
                        name: 'student.health_status'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ],

                columnDefs: [{
                    targets: [11, 12, 13, 14, 15, 16, 17],
                    visible: false
                }],

                order: [
                    [1, 'asc']
                ]
            });

            // 🔄 Apply previously saved visibility
            applySavedVisibility(table);

            // SELECT ALL checkbox
            $('#select-all-columns').on('change', function() {
                let checked = $(this).is(':checked');

                $('.toggle-vis')
                    .prop('checked', checked)
                    .trigger('change');

                saveColumnVisibility();
            });

            // Individual column toggle
            $('.toggle-vis').on('change', function() {
                let column = table.column($(this).data('column'));
                column.visible($(this).is(':checked'));

                updateSelectAllCheckbox();
                saveColumnVisibility();
            });

        });


        // EXPORT FUNCTION (unchanged)
        function exportData(type) {
            let datatable_search = $('#studentTable_filter input[type="search"]').val();
            let url = type === 'pdf' ?
                "{{ route('student-datatable.export-pdf') }}" :
                "{{ route('student-datatable.export-csv') }}";


            let filters = {
                class: $('#filterClass').val(),
                section: $('#filterSection').val(),
                keyword: $('#filterKeyword').val(),
                datatable_search: datatable_search,
                _token: '{{ csrf_token() }}'
            };

            let columns = [];
            $('.toggle-vis:checked').each(function() {
                let item = $(this).data('item');
                if (item) columns.push(item);
            });

            filters.columns = columns;
            if (type === 'pdf') {
                $('.pdf-loader').removeClass('d-none');
            } else {
                $('.csv-loader').removeClass('d-none');
            }



            $.ajax({
                url: url,
                method: "POST",
                data: filters,
                xhrFields: {
                    responseType: 'blob'
                },

                success: function(response, status, xhr) {
                    $('.pdf-loader').addClass('d-none');
                    $('.csv-loader').addClass('d-none');
                    let mime = xhr.getResponseHeader("Content-Type");
                    let extension = type === 'pdf' ? "pdf" : "csv";

                    let blob = new Blob([response], {
                        type: mime
                    });
                    let link = document.createElement('a');

                    link.href = window.URL.createObjectURL(blob);
                    link.download = "students_export." + extension;
                    link.click();
                },
                error: function(xhr) {
                    $('#exportLoader').hide();
                    console.log(xhr.responseText);
                    alert("Export failed!");
                }
            });
        }
    </script>
@endpush
