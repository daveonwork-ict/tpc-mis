<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\User;

class ApplicationRequirement extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_required'  => 'boolean',
        'reviewed_at'  => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function requirementType()
    {
        return $this->belongsTo(RequirementType::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
