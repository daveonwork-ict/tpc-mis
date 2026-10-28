<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\User;

class EnrollmentScholarship extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function label(): string
    {
        if ($this->program === 'other') {
            return (string) $this->name;
        }

        $program = ___('enrollment.scholarship_' . $this->program);

        return filled($this->name) ? $program . ' — ' . $this->name : $program;
    }
}
