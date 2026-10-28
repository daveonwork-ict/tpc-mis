<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\Staff\Department;

class Program extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'accepting_applications' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeOpen($query)
    {
        return $query->active()->where('accepting_applications', true);
    }
}
