<?php

namespace App\Http\Controllers\StudentPanel;

use App\Enums\Enrollment as EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment\Enrollment;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;

class SubjectListController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', Auth::id())->first();
        $enrollment = $student
            ? Enrollment::with('enlisted.offering.schedules', 'enlisted.offering.faculty', 'enlisted.subject', 'term', 'batch')
                ->where('student_id', $student->id)
                ->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)
                ->latest('id')
                ->first()
            : null;

        return view('student-panel.subject-list', compact('enrollment'));
    }
}
