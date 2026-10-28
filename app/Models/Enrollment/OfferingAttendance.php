<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use App\Models\User;

class OfferingAttendance extends BaseModel
{
    const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $guarded = ['id'];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
