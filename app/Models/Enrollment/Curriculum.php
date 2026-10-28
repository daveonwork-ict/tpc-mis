<?php

namespace App\Models\Enrollment;

use App\Models\Admissions\Program;
use App\Models\BaseModel;
use App\Models\Session;

class Curriculum extends BaseModel
{
    protected $table = 'curricula';

    protected $guarded = ['id'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function effectiveSession()
    {
        return $this->belongsTo(Session::class, 'effective_session_id');
    }

    public function courses()
    {
        return $this->hasMany(CurriculumCourse::class)->orderBy('year_level')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
