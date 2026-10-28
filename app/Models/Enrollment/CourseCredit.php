<?php

namespace App\Models\Enrollment;

use App\Models\Academic\Subject;
use App\Models\BaseModel;
use App\Models\User;

class CourseCredit extends BaseModel
{
    protected $guarded = ['id'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function creditor()
    {
        return $this->belongsTo(User::class, 'credited_by');
    }
}
