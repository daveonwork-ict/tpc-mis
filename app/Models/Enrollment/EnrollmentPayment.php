<?php

namespace App\Models\Enrollment;

use App\Models\BaseModel;
use App\Models\User;

class EnrollmentPayment extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'paid_on'   => 'date',
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
}
