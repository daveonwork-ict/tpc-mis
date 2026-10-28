<?php

namespace App\Repositories\StudentPanel;

use App\Interfaces\StudentPanel\SubjectListInterface;
use App\Models\Academic\SubjectAssign;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;

class SubjectListRepository implements SubjectListInterface
{
    public function index()
    {
        try {
            $student = Student::where('user_id', Auth::user()->id)->first();

            return $this->assignedSubjects($student);
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function studentSubjects($student_id)
    {
        try {
            return $this->assignedSubjects(Student::find($student_id));
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function assignedSubjects(Student $student)
    {
        $classSection = SessionClassStudent::where('session_id', setting('session'))->where('student_id', $student->id)->latest()->first();

        return SubjectAssign::with(['subjectTeacher' => fn ($q) => $q->with(['subject', 'teacher'])])
            ->where('session_id', setting('session'))
            ->where('classes_id', $classSection->classes_id)
            ->where('section_id', $classSection->section_id)
            ->first();
    }
}
