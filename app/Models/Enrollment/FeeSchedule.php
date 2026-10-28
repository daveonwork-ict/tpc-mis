<?php

namespace App\Models\Enrollment;

use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\BaseModel;

class FeeSchedule extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'free_tuition_covered' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function scopeApplyingTo($query, int $programId, ?int $termId)
    {
        return $query->where('status', 1)
            ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $programId))
            ->when(
                $termId,
                fn ($q) => $q->where(fn ($w) => $w->whereNull('term_id')->orWhere('term_id', $termId)),
                fn ($q) => $q->whereNull('term_id')
            )
            ->orderBy('sort_order')->orderBy('id');
    }
}
