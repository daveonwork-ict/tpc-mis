<?php

namespace App\Models\Enrollment;

use App\Models\Academic\Subject;
use App\Models\BaseModel;

class CurriculumCourse extends BaseModel
{
    protected $guarded = ['id'];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function requisites()
    {
        return $this->hasMany(CurriculumRequisite::class);
    }
}
