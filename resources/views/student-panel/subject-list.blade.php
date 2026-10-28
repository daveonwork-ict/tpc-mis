@extends('student-panel.partials.master')

@section('title')
{{ ___('settings.subject') }}
@endsection

@section('content')
<div class="page-content">

    <!--  table content start -->
    <div class="table-content table-basic">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ ___('settings.subject') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered class-table">
                        <thead class="thead">
                            <tr>
                                <th class="serial">{{ ___('common.sr_no') }}</th>
                                <th class="purchase">{{ ___('common.name') }}</th>
                                <th class="purchase">{{ ___('academic.code') }}</th>
                                <th class="purchase">{{ ___('enrollment.schedule') }}</th>
                                <th class="purchase">{{ ___('academic.teacher') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @if($enrollment && $enrollment->enlisted->isNotEmpty())
                                @foreach ($enrollment->enlisted as $key => $row)
                                    <tr>
                                        <td class="serial">{{ $key + 1 }}</td>
                                        <td>{{ $row->subject->name }}</td>
                                        <td>{{ $row->subject->code }}</td>
                                        <td>{{ $row->offering->schedule_text ?: '—' }}</td>
                                        <td>{{ trim(($row->offering->faculty->first_name ?? '') . ' ' . ($row->offering->faculty->last_name ?? '')) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="100%" class="text-center gray-color">
                                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        <p class="mb-0 text-center text-secondary font-size-90">
                                            {{ ___('common.please_add_new_entity_regarding_this_table') }}
                                        </p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
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
