<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\User;

class EnrollmentStatusLog extends BaseModel
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
