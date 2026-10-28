<table class="table">
    <thead>
        <tr>
            <th class="table_th">{{ ___('common.Si') }}</th>
            <th class="table_th">{{ ___('common.name') }}</th>
            <th class="table_th w-30">{{ ___('common.childs') }}</th>
            <th class="table_th">{{ ___('common.phone') }}</th>
            <th class="table_th">{{ ___('common.email') }}</th>
            <th class="table_th">{{ ___('common.Profession') }}</th>
            <th class="table_th">{{ ___('common.Relationship') }}</th>
            <th class="table_th">{{ ___('common.address') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['parents'] as $key => $item)
        <tr>
            <td class="table_td">{{ $key + 1 }}</td>
            <td class="table_td">{{ @$item->guardian_name }}</td>
            <td class="table_td">
                @foreach ($item->children->mapWithKeys(fn($s) => [$s->id => $s->first_name.' '.$s->last_name.' - '.$s->admission_no]) as $studentInfo)
                    <span style="display:block; margin-bottom:2px;">{{ $studentInfo }}</span>
                @endforeach
            </td>
            <td class="table_td">{{ @$item->guardian_mobile }}</td>
            <td class="table_td">{{ @$item->guardian_email }}</td>
            <td class="table_td">{{ @$item->guardian_profession }}</td>
            <td class="table_td">{{ @$item->guardian_relation ?? 'N/A' }}</td>
            <td class="table_td">{{ @$item->guardian_address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
