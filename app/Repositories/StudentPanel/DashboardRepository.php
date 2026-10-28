<?php

namespace App\Repositories\StudentPanel;

use App\Enums\Enrollment as EnrollmentStatus;
use App\Models\Enrollment\Enrollment;
use App\Models\Event;
use App\Models\NoticeBoard;
use App\Models\StudentInfo\Student;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\StudentPanel\DashboardInterface;
use App\Repositories\StudentPanel\SubjectListRepository;

class DashboardRepository implements DashboardInterface
{
    protected $subjectRepo;

     public function __construct(SubjectListRepository $subjectRepo)
    {
        $this->subjectRepo = $subjectRepo;
    }
    public function index()
    {
        try {
            $student = Student::where('user_id', Auth::user()->id)->first();
            $data['student'] = $student;
            $data['enrollment'] = $student
                ? Enrollment::with('program', 'term', 'batch', 'enlisted.offering.schedules', 'enlisted.offering.faculty', 'enlisted.subject')
                    ->where('student_id', $student->id)
                    ->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)
                    ->latest('id')->first()
                : null;
            $data['gwa'] = $student ? app(EnrollmentService::class)->gwa($student) : null;
            $data['totalNotice'] = 0;
            $data['totalEvent'] = 0;
            $data['events'] = collect();

            try {
                $classSection = SessionClassStudent::where('session_id', setting('session'))
                    ->where('student_id', @$student->id)
                    ->first();
                $data['totalNotice'] = NoticeBoard::where('status', 1)
                    ->whereJsonContains('visible_to', (string) Auth::user()->role_id)
                    ->where(fn ($q) => $q->where('class_id', @$classSection->classes_id)->orWhereNull('class_id'))
                    ->count();
                $data['totalEvent'] = Event::where('session_id', setting('session'))
                    ->active()->where('date', '>=', date('Y-m-d'))
                    ->count();
                $data['events'] = Event::where('session_id', setting('session'))
                    ->active()->where('date', '>=', date('Y-m-d'))
                    ->orderBy('date')
                    ->take(5)
                    ->get();
            } catch (\Throwable $th) {
            }

            return $data;
        } catch (\Throwable $th) {
            report($th);

            return false;
        }
    }
}
