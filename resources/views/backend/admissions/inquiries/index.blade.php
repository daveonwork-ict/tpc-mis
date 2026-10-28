@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($request = request())
    <div class="page-content tpc-adm">
        @include('backend.admissions.partials.header', [
            'title' => $data['title'],
            'actions' => hasPermission('inquiry_create')
                ? '<a href="' . route('admissions.inquiries.create') . '" class="btn btn-lg ot-btn-primary"><i class="fa-solid fa-plus"></i> ' . e(___('admissions.new_inquiry')) . '</a>'
                : null,
        ])

        <form method="get" class="card ot-card mb-24">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">{{ ___('admissions.search') }}</label>
                        <input type="text" name="q" value="{{ $request->q }}" class="form-control ot-input" placeholder="{{ ___('admissions.inquiry_search_placeholder') }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('admissions.status') }}</label>
                        <select name="status" class="form-select ot-input">
                            <option value="">{{ ___('admissions.all') }}</option>
                            @foreach (\App\Enums\Admission::INQUIRY_STATUSES as $status)
                                <option value="{{ $status }}" @selected($request->status === $status)>{{ ___('admissions.inquiry_' . $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ ___('admissions.channel') }}</label>
                        <select name="channel" class="form-select ot-input">
                            <option value="">{{ ___('admissions.all') }}</option>
                            @foreach (\App\Enums\Admission::INQUIRY_CHANNELS as $channel)
                                <option value="{{ $channel }}" @selected($request->channel === $channel)>{{ ___('admissions.channel_' . $channel) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <button class="btn btn-lg ot-btn-primary w-100" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('admissions.name') }}</th>
                                    <th>{{ ___('admissions.contact') }}</th>
                                    <th>{{ ___('admissions.interest') }}</th>
                                    <th>{{ ___('admissions.channel') }}</th>
                                    <th>{{ ___('admissions.follow_up_on') }}</th>
                                    <th>{{ ___('admissions.status') }}</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['inquiries'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td class="fw-semibold">
                                            {{ $row->name }}
                                            @if ($row->assignee)<div class="small text-muted">{{ ___('admissions.assigned_to') }}: {{ $row->assignee->name }}</div>@endif
                                        </td>
                                        <td>{{ $row->mobile }}<div class="small text-muted text-break">{{ $row->email }}</div></td>
                                        <td>
                                            {{ $row->program->name ?? ($row->track ? ___('admissions.track_' . $row->track) : '—') }}
                                            @if ($row->message)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($row->message, 80) }}</div>@endif
                                        </td>
                                        <td>{{ ___('admissions.channel_' . $row->channel) }}</td>
                                        <td class="{{ $row->follow_up_on?->isPast() && !in_array($row->status, ['converted', 'closed']) ? 'text-danger fw-semibold' : '' }}">
                                            {{ $row->follow_up_on?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td><span class="tpc-pill tpc-pill--{{ \App\Enums\Admission::badge($row->status) }}">{{ ___('admissions.inquiry_' . $row->status) }}</span></td>
                                        <td class="action">
                                            <div class="d-flex gap-2 flex-nowrap">
                                                @if ($row->application)
                                                    <a href="{{ route('admissions.applications.show', $row->application) }}" class="btn btn-sm ot-btn-primary-outline">{{ $row->application->application_no }}</a>
                                                @elseif (hasPermission('application_create'))
                                                    <a href="{{ route('admissions.applications.create', ['inquiry' => $row->id]) }}" class="btn btn-sm ot-btn-primary">{{ ___('admissions.start_application') }}</a>
                                                @endif
                                                @if (hasPermission('inquiry_update'))
                                                    <a href="{{ route('admissions.inquiries.edit', $row) }}" class="btn btn-sm ot-btn-primary-outline"><i class="fa-solid fa-pen"></i></a>
                                                @endif
                                                @if (!$row->application_id && hasPermission('inquiry_delete'))
                                                    <a href="javascript:void(0);" onclick="delete_row('admissions/inquiries/delete', {{ $row->id }})" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can"></i></a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color p-5">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0">{{ ___('admissions.no_inquiries') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end py-3">
                        {!! $data['inquiries']->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('backend.partials.delete-ajax')
@endsection
