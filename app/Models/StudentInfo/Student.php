<?php

namespace App\Models\StudentInfo;

use App\Models\User;
use App\Models\Gender;
use App\Models\Upload;
use App\Models\Religion;
use App\Models\BaseModel;
use App\Models\BloodGroup;
use App\Models\Staff\Staff;
use App\Models\Academic\Shift;
use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Staff\Department;
use Modules\LiveChat\Entities\Message;
use App\Models\Academic\SubjectAssignChildren;
use Modules\VehicleTracker\Entities\EnrollmentReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\BehaviourRecord\Entities\StudentIncidentAssign;
use Modules\VehicleTracker\Entities\StudentRouteEnrollment;

class Student extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected $casts = [
        'upload_documents' => 'array',
    ];

    public function routeEnroll()
    {
        return $this->hasOne(StudentRouteEnrollment::class, 'student_id', 'id');
    }

    public function staffs()
    {
        return $this->hasManyThrough(Staff::class, SubjectAssignChildren::class, 'student_id', 'staff_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function session_class_student()
    {
        return $this->belongsTo(SessionClassStudent::class, 'id', 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function person()
    {
        return $this->belongsTo(\App\Models\Admissions\Person::class);
    }

    public function enrollments()
    {
        return $this->hasMany(\App\Models\Enrollment\Enrollment::class)->orderByDesc('term_id');
    }

    public function returnServiceAgreement()
    {
        return $this->hasOne(\App\Models\Enrollment\ReturnServiceAgreement::class);
    }

    public function retentionRecords()
    {
        return $this->hasMany(\App\Models\Enrollment\RetentionRecord::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class, 'religion_id', 'id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function blood()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_guardian_id', 'id');
    }

    public function sessionStudentDetails()
    {
        return $this->belongsTo(SessionClassStudent::class, 'id', 'student_id');
    }

    public function studentCategory()
    {
        return $this->belongsTo(StudentCategory::class, 'student_category_id', 'id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'sender_id', 'user_id')->latest();
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id')->where('is_seen', 0);
    }

    public function route()
    {
        return $this->hasOne(StudentRouteEnrollment::class, 'student_id', 'id');
    }

    public function pickupReport()
    {
        return $this->hasOne(EnrollmentReport::class, 'student_id', 'id')->where('type', 'pickup');
    }

    public function dropReport()
    {
        return $this->hasOne(EnrollmentReport::class, 'student_id', 'id')->where('type', 'drop');
    }

    public function behaviourRecord()
    {
        return $this->hasMany(StudentIncidentAssign::class)->latest();
    }

    public function class()
{
    return $this->hasOneThrough(
       Classes::class,          // Final model
        SessionClassStudent::class, // Intermediate model
        'student_id',   // Foreign key on session_class_students table
        'id',           // Foreign key on classes table
        'id',           // Local key on students table
        'classes_id'    // Local key on session_class_students table
    );
}

public function section()
{
    return $this->hasOneThrough(
        Section::class,
        SessionClassStudent::class,
        'student_id',
        'id',
        'id',
        'section_id'
    );
}
}
