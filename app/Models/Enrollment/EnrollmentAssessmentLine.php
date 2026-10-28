<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;

class EnrollmentAssessmentLine extends BaseModel
{
    protected $guarded = ['id'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
