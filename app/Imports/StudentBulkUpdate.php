<?php

namespace App\Imports;

use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentBulkUpdate implements ToModel, WithStartRow, WithValidation
{
    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [];
        // return [
        //     '0' => 'required|unique:students,admission_no',
        //     '5' => 'required|unique:students,mobile',
        //     '6' => 'required|unique:users,email',
        //     '7' => 'required|unique:users,username',
        //     '24' => 'required|unique:parent_guardians,guardian_email',
        //     '25' => 'required|unique:users,guardian_username',
        //     '25' => 'required|unique:parent_guardians,guardian_mobile',
        // ];
    }

    // public function customValidationMessages()
    // {
    //     return [
    //         '39.required' => ___('validation.The Students Username is required'),
    //         '39.unique'   => ___('validation.The Students Username has already been taken'),
    //     ];
    // }

    public function model(array $row)
    {
        try {

            // Parse dates
            $dateOfBirth = $row[9]
                ? (is_numeric($row[9])
                    ? Date::excelToDateTimeObject($row[9])->format('Y-m-d')
                    : \Carbon\Carbon::parse($row[9])->format('Y-m-d'))
                : null;

            $admissionDate = $row[10]
                ? (is_numeric($row[10])
                    ? Date::excelToDateTimeObject($row[10])->format('Y-m-d')
                    : \Carbon\Carbon::parse($row[10])->format('Y-m-d'))
                : null;

            // Find student
            $student = Student::where('admission_no', $row[0])->first();
            if (! $student) {
                return null; // Skip if student not found
            }

            /*
            |--------------------------------------------------------------------------
            | Update Student User
            |--------------------------------------------------------------------------
            */
            $student_user = ($student->user);

            if ($student_user) {
                $student_user->email = $row[6] ?? $student_user->email;

                // Added missing column
                $student_user->admission_no = $row[0] ?? $student_user->admission_no;

                $student_user->username = $row[7] ?? $student_user->username;

                if (! empty($row[8])) {
                    $student_user->password = Hash::make($row[8]);
                }

                $student_user->save();
            }

            /*
            |--------------------------------------------------------------------------
            | Update Student Fields
            |--------------------------------------------------------------------------
            */
            $student->fill([
                'admission_no' => $row[0] ?? $student->admission_no,
                'roll_no' => $row[1] ?? $student->roll_no,
                'first_name' => $row[2] ?? $student->first_name,
                'last_name' => $row[3] ?? $student->last_name,
                'student_ar_name' => $row[4] ?? $student->student_ar_name,
                'mobile' => $row[5] ?? $student->mobile,
                'email' => $row[6] ?? $student->email,
                'dob' => $dateOfBirth ?? $student->dob,
                'admission_date' => $admissionDate ?? $student->admission_date,
                'student_category_id' => $row[11] ?? $student->student_category_id,
                'blood_group_id' => $row[12] ?? $student->blood_group_id,
                'gender_id' => $row[13] ?? $student->gender_id,
                'previous_school' => $row[14] ?? $student->previous_school,
                'previous_school_info' => $row[15] ?? $student->previous_school_info,
                'health_status' => $row[16] ?? $student->health_status,
                'rank_in_family' => $row[17] ?? $student->rank_in_family,
                'place_of_birth' => $row[18] ?? $student->place_of_birth,
                'nationality' => $row[19] ?? $student->nationality,
                'cpr_no' => $row[20] ?? $student->cpr_no,
                'spoken_lang_at_home' => $row[21] ?? $student->spoken_lang_at_home,
                'residance_address' => $row[22] ?? $student->residance_address,
                'emergency_contact' => $row[23] ?? $student->emergency_contact,
            ]);

            $student->save();

            /*
            |--------------------------------------------------------------------------
            | Update Parent + Parent User
            |--------------------------------------------------------------------------
            */
            $parent = ($student->parent);



            if ($parent) {
                $parent->guardian_name = $row[24] ?? $parent->guardian_name;
                $parent->guardian_email = $row[25] ?? $parent->guardian_email;
                $parent->guardian_mobile=  $row[27] ?? $parent->guardian_mobile;
                $parent->save();

                $parent_user = ($parent->user);

                if ($parent_user) {
                    $parent_user->email = $parent->guardian_email ?? $parent_user->email;
                    $parent_user->username = $row[26] ?? $parent_user->username;

                    if (! empty($row[28])) {
                        $parent_user->password = Hash::make($row[28]);
                    }

                    $parent_user->save();
                }
            }
            return $student;

        } catch (\Exception $e) {
            info($e->getMessage());
            return null;
        }
    }
}
