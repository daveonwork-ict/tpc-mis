<?php

namespace App\Http\Controllers\StudentInfo;

use Mpdf\Mpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use ImportValidationException;
use App\Imports\StudentsImport;
use App\Imports\StudentBulkUpdate;
use App\Exports\StudentsBulkExport;
use App\Models\StudentInfo\Student;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Attendance\Attendance;
use App\Repositories\GenderRepository;
use App\Repositories\ReligionRepository;
use App\Repositories\BloodGroupRepository;
use App\Repositories\Academic\ShiftRepository;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\SectionRepository;
use App\Repositories\Staff\DepartmentRepository;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\StudentInfo\StudentRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repositories\StudentPanel\SubjectListRepository;
use App\Repositories\StudentInfo\ParentGuardianRepository;
use App\Repositories\StudentInfo\StudentCategoryRepository;
use App\Http\Requests\StudentInfo\Student\StudentStoreRequest;
use App\Http\Requests\StudentInfo\Student\StudentImportRequest;
use App\Http\Requests\StudentInfo\Student\StudentUpdateRequest;

class StudentController extends Controller
{
    private $repo;

    private $classRepo;

    private $sectionRepo;

    private $classSetupRepo;

    private $shiftRepo;

    private $bloodRepo;

    private $religionRepo;

    private $genderRepo;

    private $categoryRepo;

    private $departmentRepo;

    private $parentGuardianRepo;

    private $subjectRepo;

    public function __construct(
        StudentRepository $repo,
        ClassesRepository $classRepo,
        SectionRepository $sectionRepo,
        ClassSetupRepository $classSetupRepo,
        ShiftRepository $shiftRepo,
        BloodGroupRepository $bloodRepo,
        ReligionRepository $religionRepo,
        GenderRepository $genderRepo,
        StudentCategoryRepository $categoryRepo,
        DepartmentRepository $departmentRepo,
        ParentGuardianRepository $parentGuardianRepo,
        SubjectListRepository $subjectRepo,
    ) {
        $this->repo = $repo;
        $this->classRepo = $classRepo;
        $this->sectionRepo = $sectionRepo;
        $this->classSetupRepo = $classSetupRepo;
        $this->shiftRepo = $shiftRepo;
        $this->bloodRepo = $bloodRepo;
        $this->religionRepo = $religionRepo;
        $this->genderRepo = $genderRepo;
        $this->categoryRepo = $categoryRepo;
        $this->departmentRepo = $departmentRepo;
        $this->parentGuardianRepo = $parentGuardianRepo;
        $this->subjectRepo = $subjectRepo;
    }

    public function index()
    {
        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];
        $data['title'] = ___('student_info.student_list');
        $data['students'] = $this->repo->getPaginateAll();

        return view('backend.student-info.student.index', compact('data'));
    }

    public function search(Request $request)
    {
        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = $this->classSetupRepo->getSections($request->class);
        $data['request'] = $request;
        $data['title'] = ___('student_info.student_list');
        $data['students'] = $this->repo->searchStudents($request);

        return view('backend.student-info.student.index', compact('data'));
    }

    public function pdfExport(Request $request)
    {
        try {
            $data['students'] = $this->repo->searchStudents($request, false);
            $html = view('backend.student-info.student.student-pdf-export', compact('data'))->render();
            // Create mPDF instance (not the facade)
            $mpdf = new Mpdf([
                'tempDir' => storage_path('app/mpdf'),
                'mode' => 'utf-8',
                'default_font' => 'amiri',
                'margin_top' => 40,   // same height as your header
                'margin_bottom' => 20, // same height as footer
            ]);

            $mpdf->autoLangToFont = true;
            $mpdf->autoScriptToLang = true;
            $mpdf->SetDirectionality(findDirectionOfLang());

            // Set header & footer for all pages
            $mpdf->SetHTMLHeader(view('pdf.header')->render());
            $mpdf->SetHTMLFooter(view('pdf.footer')->render());

            // Chunk the HTML string
            $chunkSize = 50000;
            $len = mb_strlen($html);
            $pos = 0;

            while ($pos < $len) {
                $part = mb_substr($html, $pos, $chunkSize);
                $mpdf->WriteHTML($part);
                $pos += $chunkSize;
            }

            // Output as download
            return response($mpdf->Output('', 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.setting('application_name').'_student_list_'.time().'.pdf"');
        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage());
        }
    }

    public function csvExport(Request $request)
    {
        try {
            $students = $this->repo->searchStudents($request, false);

            $filename = 'student_list_'.date('d_m_Y').'.csv';

            $response = new StreamedResponse(function () use ($students) {
                $handle = fopen('php://output', 'w');

                // ===== School Info Header =====
                fputcsv($handle, [setting('application_name')]); // School name
                fputcsv($handle, [setting('address')]);          // Address
                fputcsv($handle, []);                            // Empty line

                // ===== CSV Table Header =====
                fputcsv($handle, [
                    'ID',
                    'Admission No',
                    'Name',
                    'Roll',
                    'Department',
                    'Class (Section)',
                    'Gender',
                    'Guardian',
                    'Guardian Phone',
                    'Guardian Email',
                    'Guardian Address',
                ]);

                // ===== CSV Data Rows =====
                foreach ($students as $key => $item) {
                    fputcsv($handle, [
                        $key + 1,
                        optional($item->student)->admission_no,
                        optional($item->student)->first_name.' '.optional($item->student)->last_name,
                        $item->roll,
                        optional($item->student->department)->name,
                        optional($item->class)->name.' ('.optional($item->section)->name.')',
                        optional($item->student->gender)->name,
                        optional($item->student->parent)->guardian_name,
                        optional($item->student->parent)->guardian_mobile,
                        optional($item->student->parent)->guardian_email,
                        optional($item->student->parent)->guardian_address,
                    ]);
                }

                fclose($handle);
            });

            // Set headers for download
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.setting('application_name').'_'.$filename.'"');

            return $response;
        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage());
        }
    }

    public function create()
    {
        $data['title'] = ___('student_info.student_create');
        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];
        $data['shifts'] = $this->shiftRepo->all();
        $data['departments'] = $this->departmentRepo->getAsOptions();
        $data['bloods'] = $this->bloodRepo->all();
        $data['religions'] = $this->religionRepo->all();
        $data['genders'] = $this->genderRepo->all();
        $data['categories'] = $this->categoryRepo->all();
        $data['parentGuardians'] = $this->parentGuardianRepo->get();

        return view('backend.student-info.student.create', compact('data'));
    }

    public function addNewDocument(Request $request)
    {
        $counter = $request->counter;

        return view('backend.student-info.student.add-document', compact('counter'))->render();
    }

    public function getStudents(Request $request)
    {
        return $this->repo->getStudents($request);
    }

    public function store(StudentStoreRequest $request)
    {
        $result = $this->repo->store($request);

        if ($result['status']) {
            return redirect()->route('student.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['title'] = ___('student_info.student_edit');
        $data['session_class_student'] = $this->repo->getSessionStudent($id);
        $data['student'] = $this->repo->show($data['session_class_student']->student_id);
        $data['classes'] = $this->classRepo->assignedAll();
        $data['departments'] = $this->departmentRepo->getAsOptions();
        $data['sections'] = $this->classSetupRepo->getSections($data['session_class_student']->classes_id);
        $data['shifts'] = $this->shiftRepo->all();
        $data['bloods'] = $this->bloodRepo->all();
        $data['religions'] = $this->religionRepo->all();
        $data['genders'] = $this->genderRepo->all();
        $data['categories'] = $this->categoryRepo->all();
        $data['parentGuardians'] = $this->parentGuardianRepo->get();

        return view('backend.student-info.student.edit', compact('data'));
    }

    public function show($id)
    {
        $data = $this->repo->show($id);
        $subjects = @$this->subjectRepo->studentSubjects($id);

        $attendances['total_attendance'] = Attendance::where('student_id', $id)->where('session_id', setting('session'))->get();
        $attendances['total_present'] = $attendances['total_attendance']->where('attendance', 1)->count();
        $attendances['total_absent'] = $attendances['total_attendance']->where('attendance', 2)->count();

        $attendances['avg_present'] = $attendances['total_present'] > 0
            ? ($attendances['total_present'] / count($attendances['total_attendance'])) * 100
            : 0;

        $siblings = Student::with('session_class_student.class')
            ->where('parent_guardian_id', $data->parent_guardian_id)
            ->where('id', '!=', $data->id)
            ->get();

        $attendDaysInMonth = [];
        $date = \Carbon\Carbon::createFromDate(date('Y'), date('m'), 1);
        $endOfMonth = $date->copy()->endOfMonth();

        // Step 1: Create all days in 'dd-mm-yyyy' => [] format
        while ($date->lte($endOfMonth)) {
            $formattedKey = $date->format('l ').($date->day).$date->format(' F Y'); // 'Monday 26th May 2025'
            $daysInMonth[$formattedKey] = [];
            $date->addDay();
        }

        // Step 2: Loop attendance records and overwrite if date exists
        foreach ($attendances['total_attendance'] as $record) {
            $carbonDate = Carbon::parse($record['date']);
            $formattedKey = $carbonDate->format('l ').($carbonDate->day).$carbonDate->format(' F Y');

            if (isset($daysInMonth[$formattedKey])) {
                $type = match ($record['attendance']) {
                    1 => ___('attendance.Present'),
                    2 => ___('attendance.Late'),
                    3 => ___('attendance.Absent'),
                    4 => ___('attendance.Half'),
                    5 => ___('attendance.Leave'),
                    default => 'Unknown',
                };
                $note = $record['note'];

                $attendDaysInMonth[$formattedKey] = ['type' => $type, 'note' => $note];
            }
        }

        return view('backend.student-info.student.show', compact('data', 'siblings', 'attendances', 'attendDaysInMonth', 'subjects'));
    }

    public function update(StudentUpdateRequest $request)
    {
        $result = $this->repo->update($request, $request->id);

        if ($result['status']) {
            return redirect()->route('student.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {

        $result = $this->repo->destroy($id);
        if ($result['status']) {
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');

            return response()->json($success);
        } else {
            $success[0] = $result['message'];
            $success[1] = 'error';
            $success[2] = ___('alert.oops');

            return response()->json($success);
        }
    }

    public function import()
    {
        $data['title'] = ___('student_info.student_import');
        $data['classes'] = $this->classRepo->assignedAll();
        $data['categories'] = $this->categoryRepo->all();
        $data['sections'] = [];

        return view('backend.student-info.student.import', compact('data'));
    }

    public function importSubmit(StudentImportRequest $request)
    {
        try {
            Excel::import(new StudentsImport($request->class, $request->section), $request->file('file'));

            return redirect()->route('student.index')->with('success', ___('alert.Operation Successful'));
        } catch (ImportValidationException $e) {
            $errors = $e->errors();

            return back()->withErrors($errors)->withInput();
        }
    }

    public function sampleDownload()
    {
        $filePath = public_path('student_bulk_import_sample.xlsx');
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', 'File not found!');
        }
    }

    public function getChildren($parentId)
    {
        $data = $this->parentGuardianRepo->getStudentsByParent($parentId);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function bulkCsvExportView()
    {
        $data['title'] = ___('common.Bulk Update');
        $data['classes'] = $this->classRepo->assignedAll();
        $data['categories'] = $this->categoryRepo->all();
        $data['sections'] = [];

        return view('backend.student-info.student.bulk_info_update', compact('data'));
    }

    // public function bulkCsvExport(Request $request)
    // {
    //     try {
    //         // Fetch session class students
    //         $sess_cls_students = SessionClassStudent::query();

    //         if (! empty($request->class)) {
    //             $sess_cls_students->where('classes_id', $request->class);
    //         }
    //         if (! empty($request->section)) {
    //             $sess_cls_students->where('section_id', $request->section);
    //         }

    //         $sess_cls_students = $sess_cls_students->get();

    //         // Collect student IDs
    //         $student_ids = $sess_cls_students->pluck('student_id')->toArray();

    //         // Fetch students with relations
    //         $students = Student::with(['user', 'parent'])
    //             ->whereIn('id', $student_ids)
    //             ->get();

    //         // CSV Header
    //         $headers = [
    //             'admission_no',
    //             'roll_no',
    //             'first_name',
    //             'last_name',
    //             'student_ar_name',
    //             'mobile',
    //             'email',
    //             'user_name',
    //             'student_password',
    //             'dob',
    //             'admission_date',
    //             'student_category_id',
    //             'blood_group_id',
    //             'gender_id',
    //             'previous_school',
    //             'previous_school_info',
    //             'health_status',
    //             'rank_in_family',
    //             'place_of_birth',
    //             'nationality',
    //             'cpr_no',
    //             'spoken_lang_at_home',
    //             'residance_address',
    //             'emergency_contact',
    //             'gurdian_name',
    //             'gurdian_email',
    //             'gurdian_username',
    //             'guardian_mobile',
    //             'guardian_password',
    //         ];

    //         // CSV file callback
    //         $callback = function () use ($students, $headers) {
    //             $file = fopen('php://output', 'w');

    //             // Write header
    //             fputcsv($file, $headers);

    //             // Write rows
    //             foreach ($students as $student) {
    //                 fputcsv($file, [
    //                     $student->admission_no,
    //                     $student->roll_no,
    //                     $student->first_name,
    //                     $student->last_name,
    //                     $student->student_ar_name,
    //                     $student->mobile,
    //                     $student->email,
    //                     optional($student->user)->username,
    //                     null, // Password left blank for security
    //                     $student->dob,
    //                     $student->admission_date,
    //                     $student->student_category_id,
    //                     $student->blood_group_id,
    //                     $student->gender_id,
    //                     $student->previous_school == 1 ? 1 : null,
    //                     $student->previous_school_info,
    //                     $student->health_status,
    //                     $student->rank_in_family,
    //                     $student->place_of_birth,
    //                     $student->nationality,
    //                     $student->cpr_no,
    //                     $student->spoken_lang_at_home,
    //                     $student->residance_address,
    //                     $student->emergency_contact,
    //                     optional($student->parent)->guardian_name,
    //                     optional($student->parent)->guardian_email,
    //                     optional($student->parent)->guardian_username,
    //                     optional($student->parent)->guardian_mobile,
    //                     null, // Password left blank for security
    //                 ]);
    //             }

    //             fclose($file);
    //         };

    //         $fileName = 'student_bulk_export_'.date('d_m_Y').'.csv';

    //         return response()->streamDownload($callback, $fileName, [
    //             'Content-Type' => 'text/csv',
    //             'Content-Disposition' => "attachment; filename={$fileName}",
    //         ]);

    //     } catch (\Exception $e) {
    //         return back()->with('danger', $e->getMessage());
    //     }
    // }

    public function bulkCsvExport(Request $request)
    {
      
        try {
            return Excel::download(
                new StudentsBulkExport($request->class, $request->section),
                'student_bulk_export_'.date('d_m_Y').'.xlsx'
            );

        } catch (\Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    public function bulkCsvExportUpdate(Request $request)
    {

        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls',
            ]);

            Excel::import(new StudentBulkUpdate, $request->file('file'));

            return redirect()->route('student.index')->with('success', ___('alert.Operation Successful'));
        } catch (\ImportValidationException $e) {
            return back()->with('danger', $e->errors());
        }
    }
}
