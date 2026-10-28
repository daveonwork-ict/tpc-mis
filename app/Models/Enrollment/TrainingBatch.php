<?php

namespace App\Models\Enrollment;

use App\Enums\Enrollment as EnrollmentStatus;
use App\Models\Admissions\Program;
use App\Models\BaseModel;
use App\Models\Session;
use App\Models\Staff\Staff;

class TrainingBatch extends BaseModel
{
    const OPEN   = 'open';
    const CLOSED = 'closed';

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Staff::class, 'trainer_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'batch_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::OPEN);
    }

    public function activeEnrollmentCount(): int
    {
        return $this->enrollments()->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)->count();
    }
}
