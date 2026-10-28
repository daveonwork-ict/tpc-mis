<?php

namespace App\Repositories\ParentPanel;

use App\Interfaces\ParentPanel\DashboardInterface;
use App\Enums\Enrollment as EnrollmentStatus;
use App\Models\Academic\SubjectAssign;
use App\Models\Enrollment\Enrollment;
use App\Services\Enrollment\EnrollmentService;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\Event;
use App\Models\NoticeBoard;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DashboardRepository implements DashboardInterface
{
    public function index()
    {
        try {
            $parent = ParentGuardian::where('user_id', Auth::user()->id)->first();
            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();
            if (Session::get('student_id')) {
                $student = Student::where('id', Session::get('student_id'))->first();
            } else {
                $student = Student::where('parent_guardian_id', $parent->id)->latest()->first();
            }

            Session::put('student_id', @$student->id);
            $data['enrollment'] = $student
                ? Enrollment::with('program', 'term', 'batch')
                    ->where('student_id', $student->id)
                    ->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)
                    ->latest('id')->first()
                : null;
            $data['gwa'] = $student ? app(EnrollmentService::class)->gwa($student) : null;

            if ($student) {
                $classSection = SessionClassStudent::where('session_id', setting('session'))
                    ->where('student_id', @$student->id)
                    ->first();

                $subjectTeacher = SubjectAssign::where('session_id', setting('session'))
                    ->where('classes_id', @$classSection->classes_id)
                    ->where('section_id', @$classSection->section_id)
                    ->first();
                $data['totalSubject'] = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                    ->distinct('subject_id')
                    ->count();
                $data['totalTeacher'] = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                    ->distinct('staff_id')
                    ->count();

                $data['totalNotice'] = $this->noticeCount(@$classSection->classes_id);

                $data['totalEvent'] = Event::where('session_id', setting('session'))
                    ->active()->where('date', '>=', date('Y-m-d'))
                    ->orderBy('date')
                    ->count();
                $data['events'] = Event::where('session_id', setting('session'))
                    ->active()->where('date', '>=', date('Y-m-d'))
                    ->orderBy('date')
                    ->take(5)
                    ->get();
            }
            $data['student'] = $student;

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function search($request)
    {
        try {
            Session::put('student_id', $request->student);

            $parent = ParentGuardian::where('user_id', Auth::user()->id)->first();
            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();
            $student = Student::where('id', Session::get('student_id'))->first();

            $classSection = SessionClassStudent::where('session_id', setting('session'))
                ->where('student_id', @$student->id)
                ->first();

            $subjectTeacher = SubjectAssign::where('session_id', setting('session'))
                ->where('classes_id', @$classSection->classes_id)
                ->where('section_id', @$classSection->section_id)
                ->first();
            $data['totalSubject'] = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                ->distinct('subject_id')
                ->count();
            $data['totalTeacher'] = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                ->distinct('staff_id')
                ->count();
            $data['totalNotice'] = $this->noticeCount(@$classSection->classes_id);
            $data['totalEvent'] = Event::where('session_id', setting('session'))
                ->active()->where('date', '>=', date('Y-m-d'))
                ->orderBy('date')
                ->count();
            $data['events'] = Event::where('session_id', setting('session'))
                ->active()->where('date', '>=', date('Y-m-d'))
                ->orderBy('date')
                ->take(5)
                ->get();
            $data['student'] = $student;

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function noticeCount($classId): int
    {
        return NoticeBoard::where('status', 1)
            ->whereJsonContains('visible_to', (string) Auth::user()->role_id)
            ->where(fn ($q) => $q->where('class_id', $classId)->orWhereNull('class_id'))
            ->count();
    }
}
