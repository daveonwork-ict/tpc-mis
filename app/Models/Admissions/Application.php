<?php

namespace App\Models\Admissions;

use App\Enums\Admission;
use App\Models\BaseModel;
use App\Models\User;

class Application extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'privacy_consent_at' => 'datetime',
        'submitted_at'       => 'datetime',
        'decided_at'         => 'datetime',
        'sector_categories'  => 'array',
        'sector_verified'    => 'boolean',
        'tarlac_resident'    => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function requirements()
    {
        return $this->hasMany(ApplicationRequirement::class)->orderByDesc('is_required')->orderBy('id');
    }

    public function screenings()
    {
        return $this->hasMany(ApplicationScreening::class)->latest();
    }

    public function decisions()
    {
        return $this->hasMany(AdmissionDecision::class)->latest();
    }

    public function statusLogs()
    {
        return $this->hasMany(ApplicationStatusLog::class)->orderByDesc('id');
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\StudentInfo\Student::class);
    }

    public function enrollments()
    {
        return $this->hasMany(\App\Models\Enrollment\Enrollment::class);
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function latestDecision()
    {
        return $this->hasOne(AdmissionDecision::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return in_array($this->status, Admission::CLOSED_STATUSES, true);
    }

    public function requirementsComplete(): bool
    {
        return ! $this->requirements
            ->where('is_required', true)
            ->whereNotIn('status', [Admission::REQ_VERIFIED, Admission::REQ_WAIVED])
            ->count();
    }
}
