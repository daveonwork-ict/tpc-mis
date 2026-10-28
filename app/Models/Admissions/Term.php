<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\Session;

class Term extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'admission_open' => 'boolean',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'midterm_on'     => 'date',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function scopeOpenForAdmission($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE)->where('admission_open', true);
    }
}
