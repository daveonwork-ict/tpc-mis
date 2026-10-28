<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\User;

class AdmissionDecision extends BaseModel
{
    protected $guarded = ['id'];

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
