<?php

namespace App\Models\Enrollment;

use App\Enums\Enrollment as EnrollmentStatus;
use App\Enums\Admission;
use App\Models\Admissions\Application;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use App\Models\User;

class Enrollment extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'free_tuition' => 'boolean',
        'advised_at'   => 'datetime',
        'assessed_at'  => 'datetime',
        'cleared_at'   => 'datetime',
        'validated_at'   => 'datetime',
        'competency_at'  => 'datetime',
        'is_graduating'  => 'boolean',
        'credit_waived'  => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function batch()
    {
        return $this->belongsTo(TrainingBatch::class, 'batch_id');
    }

    public function isTrainee(): bool
    {
        return $this->track === Admission::TRACK_TESDA;
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function courses()
    {
        return $this->hasMany(EnrollmentCourse::class)->orderBy('id');
    }

    public function enlisted()
    {
        return $this->courses()->where('status', 'enlisted');
    }

    public function assessmentLines()
    {
        return $this->hasMany(EnrollmentAssessmentLine::class)->orderBy('id');
    }

    public function scholarships()
    {
        return $this->hasMany(EnrollmentScholarship::class)->orderBy('id');
    }

    public function academicScholarships()
    {
        return $this->hasMany(AcademicScholarship::class)->orderByDesc('id');
    }

    public function payments()
    {
        return $this->hasMany(EnrollmentPayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function statusLogs()
    {
        return $this->hasMany(EnrollmentStatusLog::class)->orderByDesc('id');
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'advised_by');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function clearer()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, EnrollmentStatus::CLOSED_STATUSES, true);
    }

    public function loadIsEditable(): bool
    {
        return $this->status === EnrollmentStatus::ADVISING;
    }
}
