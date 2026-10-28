<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;

class RequirementType extends BaseModel
{
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->hasMany(RequirementRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }
}
