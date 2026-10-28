<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use App\Models\User;

class ReturnServiceAgreement extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'student_signed_on'   => 'date',
        'guardian_signed_on'  => 'date',
        'president_signed_on' => 'date',
        'waived_on'           => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentRecorder()
    {
        return $this->belongsTo(User::class, 'student_recorded_by');
    }

    public function guardianRecorder()
    {
        return $this->belongsTo(User::class, 'guardian_recorded_by');
    }

    public function presidentRecorder()
    {
        return $this->belongsTo(User::class, 'president_recorded_by');
    }

    public function waiverRecorder()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
