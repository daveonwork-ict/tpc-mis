<?php

namespace App\Models\Enrollment;

use App\Enums\Enrollment as EnrollmentStatus;
use App\Models\Academic\Subject;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\BaseModel;
use App\Models\Staff\Staff;

class Offering extends BaseModel
{
    protected $guarded = ['id'];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Staff::class, 'faculty_id');
    }

    public function schedules()
    {
        return $this->hasMany(OfferingSchedule::class)->orderBy('day')->orderBy('start_time');
    }

    public function enrollmentCourses()
    {
        return $this->hasMany(EnrollmentCourse::class);
    }

    /** Seats taken by students whose enrollment is still alive. */
    public function activeSeats()
    {
        return $this->enrollmentCourses()
            ->where('enrollment_courses.status', 'enlisted')
            ->whereHas('enrollment', fn ($q) => $q->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES));
    }

    public function getFacultyNameAttribute(): ?string
    {
        return $this->faculty ? trim($this->faculty->first_name . ' ' . $this->faculty->last_name) : null;
    }

    public function getScheduleTextAttribute(): string
    {
        return $this->schedules->map(fn ($s) => $s->label)->implode('; ');
    }

    public function getRoomTextAttribute(): string
    {
        return $this->schedules->pluck('room')->filter()->unique()->implode(', ');
    }
}
