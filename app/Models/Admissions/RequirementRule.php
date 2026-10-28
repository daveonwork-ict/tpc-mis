<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;

class RequirementRule extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function requirementType()
    {
        return $this->belongsTo(RequirementType::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function scopeApplyingTo($query, string $track, string $applicantType, ?int $programId)
    {
        return $query->where('track', $track)
            ->where(fn ($q) => $q->whereNull('applicant_type')->orWhere('applicant_type', $applicantType))
            ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $programId))
            ->whereHas('requirementType', fn ($q) => $q->active());
    }
}
