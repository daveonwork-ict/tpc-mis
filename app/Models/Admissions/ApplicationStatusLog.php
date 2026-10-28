<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\User;

class ApplicationStatusLog extends BaseModel
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
