<?php

namespace App\Imports;

use App\Models\Role;
use App\Models\User;
use App\Models\Staff\Staff;
use Illuminate\Support\Str;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use \PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\StudentInfo\ParentGuardian;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\StudentInfo\SessionClassStudent;

class StaffImport implements ToModel, WithStartRow, WithValidation
{

    protected $role;
    protected $designation;
    protected $department;

    public function __construct($role, $designation, $department)
    {
        $this->role = $role;
        $this->designation = $designation;
        $this->department = $department;
    }

    /**
     * Start import from row 2 (skip the first row)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [
            '0' => 'required|unique:staff,staff_id',
            '5' => 'required|unique:users,email',
            '9' => 'required|unique:users,phone',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '0.required' => ___('validation.Staff Id is required'),
            '0.unique' => ___('validation.Staff Id must be unique'),

            '5.required' => ___('validation.Email is required'),
            '5.unique'   => ___('validation.Email has already been taken'),

            '9.required' => ___('validation.Phone is required'),
            '9.unique'   => ___('validation.Phone has already been taken'),
        ];
    }


    public function model(array $row)
    {
        $avatarPath = createAvatarAndUpload($row[1], $row[2]);
        $role                       = Role::find($this->role);
        $dateOfBirth = is_numeric($row[7])
            ? Date::excelToDateTimeObject($row[7])
            : \Carbon\Carbon::parse($$row[7])->format('Y-m-d');

        $dateOfJoin = is_numeric($row[8])
            ? Date::excelToDateTimeObject($row[8])
            : \Carbon\Carbon::parse($$row[8])->format('Y-m-d');

        $userStd                    = new User();
        $userStd->name              = $row[1] . ' ' . $row[2];
        $userStd->email             = $row[5];
        $userStd->phone             = $row[9];
        $userStd->password          = Hash::make(123456);
        $userStd->email_verified_at = now();

        $userStd->role_id           = $role->id;
        $userStd->permissions       = $role->permissions;
        $userStd->date_of_birth     = $dateOfBirth;
        $userStd->username          = $row[5];
        $userStd->uuid              = Str::uuid();
        $userStd->upload_id         = createAvatarAndUpload($row[1], $row[2]);
        $result = $userStd->save();

        if ($result) {
            $staff                          = new Staff();
            $staff->upload_id               = $userStd->upload_id;
            $staff->user_id                 = $userStd->id;
            $staff->staff_id                = $row[0];
            $staff->role_id                 = $role->id;
            $staff->designation_id          = $this->designation;
            $staff->department_id           = $this->department;
            $staff->first_name              = $row[1];
            $staff->last_name               = $row[2];
            $staff->father_name             = $row[3];
            $staff->mother_name             = $row[4];
            $staff->email                   = $userStd->email;
            $staff->gender_id               = $row[6] ?? 1;
            $staff->dob                     = $dateOfBirth;
            $staff->joining_date            = $dateOfJoin;
            $staff->phone                   = $userStd->phone;
            $staff->emergency_contact       = $row[10];
            $staff->marital_status          = (int) $row[11];
            $staff->status                  = 1;
            $staff->current_address         = $row[12];
            $staff->permanent_address       = $row[13];
            $staff->basic_salary            = $row[14];
            $staff->upload_documents        = [];
            $staff->save();

            return $staff;
        }
    }
}
