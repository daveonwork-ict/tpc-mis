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






        <div class="col-12">
            <form action="{{ route('student.search') }}" method="post" id="marksheed" enctype="multipart/form-data">
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
                                        <form action="{{ route('student.export-pdf') }}" method="post" target="_blank"
                                            class="px-3 py-1">
                                            @csrf
                                            <input type="hidden" name="class" value="{{ @$data['request']->class }}">
                                            <input type="hidden" name="section" value="{{ @$data['request']->section }}">
                                            <input type="hidden" name="keyword" value="{{ @$data['request']->keyword }}">
                                            <button type="submit" class="dropdown-item">
                                                <i class="fa-solid fa-file-pdf"></i> PDF
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('student.export-csv') }}" method="post" target="_blank"
                                            class="px-3 py-1">
                                            @csrf
                                            <input type="hidden" name="class" value="{{ @$data['request']->class }}">
                                            <input type="hidden" name="section" value="{{ @$data['request']->section }}">
                                            <input type="hidden" name="keyword" value="{{ @$data['request']->keyword }}">
                                            <button type="submit" class="dropdown-item">
                                                <i class="fa-solid fa-file-csv"></i> CSV
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endif
                        @if (hasPermission('student_read'))
                            {{-- Export Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-lg ot-btn-primary dropdown-toggle" type="button" id="columnDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-filter"></i> {{ ___('common.Columns') }}
                                </button>
                              <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    @foreach ($data['columns'] as $key => $label)
                                        <li>
                                            <div class="form-check">
                                                <input class="form-check-input column-checkbox" type="checkbox"
                                                    name="columns[]" value="{{ $key }}"
                                                    id="col_{{ $key }}" checked>
                                                <label class="form-check-label" for="col_{{ $key }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                    </div>
                </div>


                @if (@$data['students'])
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th class="serial">{{ ___('common.sr_no') }}</th>
                                        <th class="col-admission_no">{{ ___('student_info.admission_no') }}</th>
                                        <th class="col-roll_no">{{ ___('student_info.roll_no') }}</th>
                                        <th class="col-department">{{ ___('student_info.Department') }}</th>
                                        <th class="col-name">{{ ___('student_info.name') }}</th>
                                        <th class="col-class">{{ ___('academic.class') }} ({{ ___('academic.section') }})
                                        </th>
                                        <th class="col-guardian">{{ ___('student_info.guardian') }}</th>
                                        <th class="col-gender">{{ ___('common.gender') }}</th>
                                        <th class="col-mobile">{{ ___('student_info.mobile') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($data['students'] as $key => $row)
                                        <tr>
                                            <td class="serial">{{ ++$key }}</td>
                                            <td class="col-admission_no">{{ @$row->student->admission_no }}</td>
                                            <td class="col-roll_no">{{ @$row->roll }}</td>
                                            <td class="col-department">{{ @$row->student->department->name }}</td>
                                            <td class="col-name">{{ @$row->student->first_name }}
                                                {{ @$row->student->last_name }}
                                            </td>
                                            <td class="col-class">{{ @$row->class->name }} ({{ @$row->section->name }})
                                            </td>
                                            <td class="col-guardian">{{ @$row->student->parent->guardian_name }}</td>
                                            <td class="col-gender">{{ @$row->student->gender->name }}</td>
                                            <td class="col-mobile">{{ @$row->student->mobile }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!--  table end -->
                        <!--  pagination start -->

                        <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-between">
                                    {!! $data['students']->appends(\Request::capture()->except('page'))->links() !!}
                                </ul>
                            </nav>
                        </div>

                        <!--  pagination end -->
                    </div>
                @else
                    <div class="text-center gray-color p-5">
                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                        <p class="mb-0 text-center text-secondary font-size-90">
                            {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                    </div>
                @endif

            </div>
        </div>
        <!--  table content end -->
    </div>
@endsection

@push('script')
    <script>
        document.querySelectorAll('.column-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                let colClass = this.value; // column key = class name
                document.querySelectorAll('.col-' + colClass).forEach(td => {
                    td.style.display = this.checked ? '' : 'none';
                });
            });
        });
    </script>
@endpush
