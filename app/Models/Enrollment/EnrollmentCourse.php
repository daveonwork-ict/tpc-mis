<?php

namespace App\Models\Enrollment;

use App\Models\Academic\Subject;
use App\Models\BaseModel;

class EnrollmentCourse extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'graded_at'        => 'datetime',
        'removal_on'       => 'date',
        'standing_passing' => 'boolean',
        'unofficial_drop'  => 'boolean',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getUnitsAttribute(): float
    {
        return (float) $this->lecture_units + (float) $this->lab_units;
    }
}
