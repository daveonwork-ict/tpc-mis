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

<table class="table-striped table-bordered">
    <thead>
        <tr>
            <th class="table_th">{{ ___('common.#') }}</th>
            <th class="table_th">{{ ___('common.Admission') }}</th>
            <th class="table_th">{{ ___('common.Name') }}</th>
            <th class="table_th">{{ ___('common.Roll') }}</th>
            <th class="table_th">{{ ___('common.Class') }}</th>
            <th class="table_th">{{ ___('common.Gender') }}</th>
            <th class="table_th">{{ ___('common.Contact') }}</th>
            <th class="table_th">{{ ___('common.Guardian') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['students'] as $key => $item)
            <tr>
                <td class="table_td">{{ ++$key }}</td>
                <td class="table_td">{{ @$item->student->admission_no }}</td>
                <td class="table_td">{{ @$item->student->first_name }} {{ @$item->student->last_name }}</td>
                <td class="table_td">{{ @$item->roll }}</td>
                <td class="table_td">{{ @$item->class->name }} ({{ @$item->section->name }})</td>
                <td class="table_td">{{ @$item->student->gender->name }}</td>
                <td class="table_td">
                    {{ @$item->student->mobile ?? 'N/A' }}<br>
                    {{ @$item->student->email ?? 'N/A' }}
                </td>
                <td class="table_td">
                    {{ @$item->student->parent->guardian_name }}<br>
                    {{ @$item->student->parent->guardian_mobile }}<br>
                    {{ @$item->student->parent->guardian_email ?? 'N/A' }}<br>
                    {{ @$item->student->parent->guardian_address ?? 'N/A' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
