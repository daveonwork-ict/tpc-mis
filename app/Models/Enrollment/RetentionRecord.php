<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;

class RetentionRecord extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'enrolled_units'  => 'float',
        'failed_units'    => 'float',
        'failure_percent' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
