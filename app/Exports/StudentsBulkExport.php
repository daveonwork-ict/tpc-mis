<?php

namespace App\Exports;

use App\Models\StudentInfo\Student;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\StudentInfo\SessionClassStudent;

class StudentsBulkExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $class;

    protected $section;

    public function __construct($class = null, $section = null)
    {
        $this->class = $class;
        $this->section = $section;
    }

    public function collection()
    {
        $query = SessionClassStudent::query();

        if (! empty($this->class)) {
            $query->where('classes_id', $this->class);
        }
        if (! empty($this->section)) {
            $query->where('section_id', $this->section);
        }

        $student_ids = $query->pluck('student_id')->toArray();

        return Student::with(['user', 'parent'])
            ->whereIn('id', $student_ids)
            ->get();
    }

    public function headings(): array
    {
        return [
            'admission_no',
            'roll_no',
            'first_name',
            'last_name',
            'student_ar_name',
            'mobile',
            'email',
            'user_name',
            'student_password',
            'dob',
            'admission_date',
            'student_category_id',
            'blood_group_id',
            'gender_id',
            'previous_school',
            'previous_school_info',
            'health_status',
            'rank_in_family',
            'place_of_birth',
            'nationality',
            'cpr_no',
            'spoken_lang_at_home',
            'residance_address',
            'emergency_contact',
            'gurdian_name',
            'gurdian_email',
            'gurdian_username',
            'guardian_mobile',
            'guardian_password',
        ];
    }

    public function map($student): array
    {
        return [
            $student->admission_no,
            $student->roll_no,
            $student->first_name,
            $student->last_name,
            $student->student_ar_name,
            $student->mobile,
            $student->email,
            optional($student->user)->username,
            null,
            $student->dob,
            $student->admission_date,
            $student->student_category_id,
            $student->blood_group_id,
            $student->gender_id,
            $student->previous_school == 1 ? 1 : null,
            $student->previous_school_info,
            $student->health_status,
            $student->rank_in_family,
            $student->place_of_birth,
            $student->nationality,
            $student->cpr_no,
            $student->spoken_lang_at_home,
            $student->residance_address,
            $student->emergency_contact,
            optional($student->parent)->guardian_name,
            optional($student->parent)->guardian_email,
            optional($student->parent)->guardian_username,
            optional($student->parent)->guardian_mobile,
            null,
        ];
    }
}
