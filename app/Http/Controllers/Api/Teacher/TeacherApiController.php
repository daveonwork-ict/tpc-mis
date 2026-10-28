<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Enums\Settings;
use Illuminate\Http\Request;
use App\Models\Academic\Classes;
use App\Models\Academic\Subject;
use App\Models\Library\IssueBook;
use App\Traits\MobileMenuTrait;
use App\Traits\CommonHelperTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Academic\ClassSetup;
use App\Http\Controllers\Controller;
use App\Traits\ApiReturnFormatTrait;
use App\Models\Attendance\Attendance;
use Illuminate\Support\Facades\Validator;
use App\Models\StudentInfo\SessionClassStudent;
use App\Http\Resources\Student\IssuedBookResource;
use App\Repositories\Attendance\AttendanceRepository;
use App\Http\Resources\Student\StudentProfileResource;
use App\Http\Resources\Teacher\Api\SubjectListResource;
use App\Http\Resources\Teacher\Api\AttendanceListResource;

class TeacherApiController extends Controller
{
    use CommonHelperTrait, ApiReturnFormatTrait, MobileMenuTrait;

    private $attendRepo;

    function __construct(AttendanceRepository $attendRepo)
    {
        $this->attendRepo      = $attendRepo;
    }


    public function menuList()
    {
        return $this->responseWithSuccess(___('alert.success'), $this->mobileMenus('teacher'));
    }


    public function classes()
    {
        try {
            $staff =  auth()->user()->staff;
            $class_ids = $staff->subjectAssigns->pluck('classes_id')->unique();
            $classes = Classes::whereIn('id', $class_ids)->where('status', 1)->select('id', 'name')->get();
            return $this->responseWithSuccess('Teacher Class List', $classes, 200);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), [], 400);
        }
    }


    public function sectionByClassId($id)
    {
        try {
            $sections = ClassSetup::where('classes_id', $id)
                ->where('status', 1)
                ->with('classSetupChildrenAll.section')
                ->get()
                ->flatMap(function ($classSetup) {
                    return $classSetup->classSetupChildrenAll->pluck('section');
                })
                ->unique('id')
                ->values();

            return $this->responseWithSuccess('Section List By Class Id', $sections, 200);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), [], 400);
        }
    }



    public function getSubjectList(Request $request)
    {
        try {
            $subjects =  Subject::whereIn('id', teacherSubjects())->get();

            $subject_resc = SubjectListResource::collection($subjects);

            return $this->responseWithSuccess('Suject List By Class Section Id', $subject_resc, 200);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), [], 400);
        }
    }


    public function searchStudent(Request $request)
    {
        try {

            $validator = Validator::make($request->all(),  [
                'class' => 'required',
                'section' => 'required',
                'date' => 'required_|date'
            ]);

            if ($validator->fails()) {
                return $this->responseWithError('Validation error', $validator->errors(), 422);
            }

            $data['class'] = $request->class;
            $data['section'] = $request->section;
            $data['date'] = $request->date;

            $data['already_taken'] = false;
            $data['attendance_types'] = [
                1 => 'Present',
                2 => 'Late',
                3 => 'Absent',
                4 => 'Half Day',
            ];

            $attendance = Attendance::with('student')->where('session_id', setting('session'))
                ->where('classes_id', $request->class)
                ->where('section_id', $request->section)
                ->where('date', $request->date)
                ->get();

            if (count($attendance)) {
                $data['students'] = AttendanceListResource::collection($attendance);
                $data['already_taken'] = true;
            }

            $student_list = SessionClassStudent::with('student')->where('session_id', setting('session'))
                ->where('classes_id', $request->class)
                ->where('section_id', $request->section)
                ->get();

            $attendanceStudentIds = $attendance->pluck('student_id')->toArray();
            // Filter students missing in $attendance
            $missingStudents = $student_list->filter(function ($student) use ($attendanceStudentIds) {
                return !in_array($student->student_id, $attendanceStudentIds);
            })->sortBy('student_id');

            // Merge the missing students into the attendance collection
            $mergedCollection = $attendance->merge($missingStudents);
            $data['students'] = AttendanceListResource::collection($mergedCollection);
            return $this->responseWithSuccess('Attendance List', $data, 200);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), [], 400);
        }
    }


    public function attendanceStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),  [
                'class' => 'required',
                'section' => 'required',
                'date' => 'required|date',
                'students' => 'required|array',
                'attendances' => 'required|array'

            ]);

            if ($validator->fails()) {
                return $this->responseWithError('Validation error', $validator->errors(), 422);
            }

            Attendance::where('session_id', setting('session'))
                ->where('classes_id', $request->class)
                ->where('section_id', $request->section)
                ->where('date', $request->date)
                ->delete();

            $students = $request->students;
            $attendances = $request->attendances;
            $roles = $request->roles;
            foreach ($students as $key => $student) {
                $new = new Attendance();
                $new->session_id = setting('session');
                $new->student_id = $student;
                $new->classes_id = $request->class;
                $new->section_id = $request->section;
                $new->roll = $roles[$key] ?? null;
                $new->date = $request->date;
                $new->attendance = $attendances[$key] ?? null;
                $new->save();
            }
            ActivityLogEvent('Take Attendance');
            return $this->responseWithSuccess('Attendance Stored', [], 200);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), [], 400);
        }
    }

    public function issuedBookAPI()
    {
        try {
            $issue_book = IssueBook::orderBy('id', 'desc')->paginate(Settings::PAGINATE);
            $data['issue_books'] = IssuedBookResource::collection($issue_book);
            return $this->responseWithSuccess(___('alert.success'), $data);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }

    }



    public function prfileUpdate(Request $request){
        try {
            $user = auth()->user();
            $staff = $user->staff;

            if (!$user || !$staff) {
                return $this->responseWithError(___('alert.User Not Found'));
            }

            DB::transaction(function () use ($request, $user, $staff) {
                $user->update([
                    'name'          => $request->first_name . ' ' . $request->last_name,
                    'date_of_birth' => date('Y-m-d', strtotime($request->date_of_birth)),
                    'phone'         => $request->phone,
                    'upload_id'     => $this->UploadImageUpdate($request->image, 'backend/uploads/users', @$user->upload_id)
                ]);

                $user = $user->refresh();

                $staff->update([
                    'first_name'    => $request->first_name,
                    'last_name'     => $request->last_name,
                    'phone'        => $user->phone,
                    'dob'           => $user->date_of_birth,
                    'upload_id'      => $user->upload_id
                ]);
            });

            $profile    = new StudentProfileResource($staff);

            return $this->responseWithSuccess(___('alert.profile_has_been_updated_successfully'), $profile);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), [$th->getMessage()]);
        }
    }



}
