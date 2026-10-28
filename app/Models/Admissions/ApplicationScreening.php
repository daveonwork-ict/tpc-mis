<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\User;

class ApplicationScreening extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'screened_on' => 'date',
    ];

    public function screener()
    {
        return $this->belongsTo(User::class, 'screened_by');
    }
}
