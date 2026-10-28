<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #000;
        padding: 6px;
        font-size: 12px;
    }
    tr:nth-child(even) {
        background: #f2f2f2;
    }
</style>

<table class="table-striped table-bordered" cellspacing="0">
    <thead>
        <tr>
            @if (in_array('si', $data['columns']))
            <th class="table_th">{{ ___('common.#') }}</th>
            @endif
            @if (in_array('admission_no', $data['columns']))
                <th class="table_th">{{___('student_info.admission_no') }}</th>
            @endif
            @if (in_array('roll_no', $data['columns']))
                <th class="table_th">{{ ___('student_info.roll_no') }}</th>
            @endif
            @if (in_array('name', $data['columns']))
                <th class="table_th">{{ ___('student_info.name')}}</th>
            @endif
            @if (in_array('student_ar_name', $data['columns']))
                <th class="table_th">{{ ___('student_info.arabic_name') }}</th>
            @endif
            @if (in_array('class_section', $data['columns']))
                <th class="table_th">{{ ___('student_info.class') }}</th>
            @endif
            @if (in_array('department', $data['columns']))
                <th class="table_th">{{ ___('student_info.department')}}</th>
            @endif

            @if (in_array('gender', $data['columns']))
                <th class="table_th">{{ ___('student.Gender') }}</th>
            @endif
            @if (in_array('mobile', $data['columns']))
                <th class="table_th">{{___('student_info.mobile')}}</th>
            @endif
            @if (in_array('dob', $data['columns']))
                <th class="table_th">{{ ___('student_info.dob') }}</th>
            @endif
            @if (in_array('religion', $data['columns']))
                <th class="table_th">{{ ___('student_info.religion') }}</th>
            @endif
            @if (in_array('blood_group', $data['columns']))
                <th class="table_th">{{  ___('student_info.blood') }}</th>
            @endif
            @if (in_array('admission_date', $data['columns']))
                <th class="table_th">{{ ___('student_info.admission_date') }}</th>
            @endif
            @if (in_array('previous_school', $data['columns']))
                <th class="table_th">{{ ___('frontend.previous_school_information') }}</th>
            @endif
            @if (in_array('place_of_birth', $data['columns']))
                <th class="table_th">{{ ___('frontend.Place_Of_Birth') }}</th>
            @endif

            @if (in_array('emergency_contact', $data['columns']))
                <th class="table_th">{{ ___('frontend.Emergency_Contact') }}</th>
            @endif
            @if (in_array('health_status', $data['columns']))
                <th class="table_th">{{ ___('common.Health Status') }}</th>
            @endif
            @if (in_array('guardian', $data['columns']))
                <th class="table_th">{{ ___('student_info.guardian') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($data['students'] as $key => $item)
            <tr>
                 @if (in_array('si', $data['columns']))
                <td class="table_td">{{ ++$key }}</td>
                @endif
                @if (in_array('admission_no', $data['columns']))
                    <td class="table_td">{{ @$item->student->admission_no }}</td>
                @endif
                @if (in_array('roll_no', $data['columns']))
                    <td class="table_td">{{ @$item->student->roll_no }}</td>
                @endif
                @if (in_array('name', $data['columns']))
                    <td class="table_td">{{ @$item->student->full_name }}</td>
                @endif
                @if (in_array('student_ar_name', $data['columns']))
                    <td class="table_td">{{ @$item->student->student_ar_name }}</td>
                @endif
                @if (in_array('class_section', $data['columns']))
                    <td class="table_td">{{ @$item->class->name }} ({{ @$item->section->name }})</td>
                @endif
                @if (in_array('department', $data['columns']))
                    <td class="table_td">{{ @$item->student->department->name }}</td>
                @endif
                @if (in_array('gender', $data['columns']))
                    <td class="table_td">{{ @$item->student->gender->name }}</td>
                @endif
                @if (in_array('mobile', $data['columns']))
                    <td class="table_td">{{ @$item->student->mobile ?? 'N/A' }}</td>
                @endif
                @if (in_array('dob', $data['columns']))
                    <td class="table_td">{{ @$item->student->dob ?? 'N/A' }}</td>
                @endif
                @if (in_array('religion', $data['columns']))
                    <td class="table_td">{{ @$item->student->religion->name ?? 'N/A' }}</td>
                @endif
                @if (in_array('blood_group', $data['columns']))
                    <td class="table_td">{{ @$item->student->blood->name ?? 'N/A' }}</td>
                @endif
                @if (in_array('admission_date', $data['columns']))
                    <td class="table_td">{{ @$item->student->admission_date ?? 'N/A' }}</td>
                @endif
                @if (in_array('previous_school', $data['columns']))
                    <td class="table_td">{{ @$item->student->previous_school_info ?? 'N/A' }}</td>
                @endif
                @if (in_array('place_of_birth', $data['columns']))
                    <td class="table_td">{{ @$item->student->place_of_birth ?? 'N/A' }}</td>
                @endif
                @if (in_array('emergency_contact', $data['columns']))
                    <td class="table_td">{{ @$item->student->emergency_contact ?? 'N/A' }}</td>
                @endif
                @if (in_array('health_status', $data['columns']))
                    <td class="table_td">{{ @$item->student->health_status ?? 'N/A' }}</td>
                @endif
                @if (in_array('guardian', $data['columns']))
                    <td class="table_td">{{ @$item->student->parent->guardian_name }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
