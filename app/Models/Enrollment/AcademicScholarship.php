<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use App\Models\User;

class AcademicScholarship extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'preceding_units'  => 'float',
        'preceding_gwa'    => 'float',
        'discipline_clear' => 'boolean',
        'voided_at'        => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function precedingEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'preceding_enrollment_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
