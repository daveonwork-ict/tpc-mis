<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;
use App\Models\User;

class Inquiry extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'follow_up_on' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
