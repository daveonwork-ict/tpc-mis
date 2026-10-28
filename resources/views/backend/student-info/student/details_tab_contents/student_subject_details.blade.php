<div class="p-3">
    <div class="row g-4">

        <!-- Table Section -->
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ ___('academic.subject') }}</h5>
                </div>
                <div class="card-body table-responsive">
                      <table class="table table-bordered class-table">
                        <thead class="thead">
                            <tr>
                                <th class="serial">{{ ___('common.sr_no') }}</th>
                                <th class="purchase">{{ ___('common.name') }}</th>
                                <th class="purchase">{{ ___('academic.code') }}</th>
                                <th class="purchase">{{ ___('academic.type') }}</th>
                                <th class="purchase">{{ ___('academic.teacher') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @if($subjects)
                                @foreach ($subjects->subjectTeacher as $key => $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td class="serial">{{ ++$key }}</td>
                                        <td>{{ $row->subject->name }}
                                            @if($row->subject->is_optional == 1) <span class="badge badge-primary">{{ ___('academic.optional') }}</span> @endif
                                        </td>
                                        <td>{{ $row->subject->code }}</td>
                                        <td>
                                            @if ($row->subject->type == App\Enums\SubjectType::THEORY)
                                            {{ ___('academic.theory') }}
                                            @elseif ($row->subject->type == App\Enums\SubjectType::PRACTICAL)
                                            {{ ___('academic.practical') }}
                                            @endif
                                        </td>
                                        <td>{{ $row->teacher->first_name }} {{ $row->teacher->last_name }} <br> <small>{{ $row->teacher->email }}</small></td>

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
            </div>
        </div>

        <!-- Modal Placeholder -->
        <div id="view-modal">
            <div class="modal fade" id="modalCustomizeWidth" tabindex="-1" aria-labelledby="modalWidth" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    {{-- Modal content will be injected dynamically --}}
                </div>
            </div>
        </div>
    </div>
</div>
