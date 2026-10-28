<?php

namespace App\Models\Enrollment;

use App\Models\Academic\Subject;
use App\Models\BaseModel;

class CurriculumRequisite extends BaseModel
{
    protected $guarded = ['id'];

    public function curriculumCourse()
    {
        return $this->belongsTo(CurriculumCourse::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
