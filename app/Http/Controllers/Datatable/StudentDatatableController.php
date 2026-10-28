<?php

namespace App\Http\Controllers\Datatable;

use App\Http\Controllers\Controller;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\StudentInfo\StudentRepository;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTables;

class StudentDatatableController extends Controller
{
    private $repo;

    private $classRepo;

    public function __construct(
        StudentRepository $repo,
        ClassesRepository $classRepo,

    ) {
        $this->repo = $repo;
        $this->classRepo = $classRepo;
    }

    public function list()
    {
        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];
        $data['title'] = ___('student_info.student_list');

        return view('backend.student-info.student.student-datatable', compact('data'));
    }

    public function searchDatatable(Request $request)
    {
        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];
        $data['title'] = ___('student_info.student_list');
        $data['request'] = $request;

        return view('backend.student-info.student.student-datatable', compact('data'));
    }

    private function getStudent(Request $request)
    {
          increaseServerLoad();
        $students = SessionClassStudent::query()
            ->select('session_class_students.*') // IMPORTANT: avoid ambiguous id/student_id/etc
            ->with([
                'student:id,first_name,last_name,student_ar_name,admission_no,admission_date,roll_no,mobile,gender_id,department_id,admission_date,previous_school_info,health_status,emergency_contact,place_of_birth,parent_guardian_id,user_id,blood_group_id,religion_id,dob',
                'student.parent:id,guardian_name',
                'student.parent:id,guardian_name',
                'student.gender:id,name',
                'student.user.upload:id,path',
                'student.department:id,name',
                'student.blood:id,name',
                'student.religion:id,name',
                'student.religion:id,name',
                'class:id,name',
                'section:id,name',
            ])
            ->where('session_class_students.session_id', setting('session'));

        /* ----------- Filters ----------- */
        if ($request->class) {
            $students->where('session_class_students.classes_id', $request->class);
        }

        if ($request->section) {
            $students->where('session_class_students.section_id', $request->section);
        }

        if (! is_null($request->datatable_search)) {
            $datatable_search = "%{$request->datatable_search}%";
            $students->whereHas('student', function ($q) use ($datatable_search) {
                $q->where(function ($q) use ($datatable_search) {
                    $q->where('students.admission_no', 'like', $datatable_search)
                        ->orWhere('students.first_name', 'like', $datatable_search)
                        ->orWhere('students.student_ar_name', 'like', $datatable_search)
                        ->orWhere('students.last_name', 'like', $datatable_search)
                        ->orWhere('students.roll_no', 'like', $datatable_search)
                        ->orWhere('students.dob', 'like', $datatable_search);
                });
            });
        }

        if ($request->keyword) {
            $keyword = "%{$request->keyword}%";
            $students->whereHas('student', function ($q) use ($keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('students.admission_no', 'like', $keyword)
                        ->orWhere('students.first_name', 'like', $keyword)
                        ->orWhere('students.student_ar_name', 'like', $keyword)
                        ->orWhere('students.last_name', 'like', $keyword)
                        ->orWhere('students.roll_no', 'like', $keyword)
                        ->orWhere('students.dob', 'like', $keyword);
                });
            });
        }

        return $students;
    }

    public function dataTable(Request $request)
    {
        $students = $this->getStudent($request);

        return DataTables::of($students)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                $html = '<div class="dropdown dropdown-action">
                <button type="button" class="btn-dropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">';

                if (hasPermission('student_update')) {
                    $html .= '<li>
                    <a class="dropdown-item" href="'.route('student.edit', $row->id).'">
                        <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>
                        '.___('common.edit').'
                    </a>
                </li>';
                }

                if (hasPermission('student_delete')) {
                    $html .= '<li>
                    <a class="dropdown-item" href="javascript:void(0);"
                        onclick="delete_row(\'student/delete\','.$row->student_id.')">
                        <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                        <span>'.___('common.delete').'</span>
                    </a>
                </li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->addColumn('admission_no', fn ($row) => $row->student->admission_no)
            ->addColumn('name', function ($row) {
                $student = $row->student;
                $avatar = globalAsset(optional($student->user->upload)->path, 'avater.png');
                $url = route('student.show', $student->id);

                return '
                <a href="'.$url.'" target="_blank">
                    <div class="user-card">
                        <div class="user-avatar">
                            <img src="'.$avatar.'" alt="'.$student->first_name.' '.$student->last_name.'">
                        </div>
                        <div class="user-info">
                            '.$student->first_name.' '.$student->last_name.'
                        </div>
                    </div>
                </a>';
            })

            ->addColumn('class_section', fn ($row) => $row->class->name.' ('.$row->section->name.')'
            )
            ->addColumn('admission_date', function ($row) {
                return $row->student->admission_date ?? 'N/A';
            })

            ->addColumn('guardian', fn ($row) => $row->student->parent->guardian_name ?? 'N/A'
            )

            ->addColumn('gender', fn ($row) => $row->student->gender->name ?? 'N/A'
            )

            ->addColumn('mobile', fn ($row) => $row->student->mobile ?? 'N/A'
            )

            ->addColumn('actions_dropdown', function ($row) {
                $html = '<div class="dropdown dropdown-action">
                        <button type="button" class="btn-dropdown" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">';

                if (hasPermission('student_update')) {
                    $html .= '<li>
                    <a class="dropdown-item" href="'.route('student.edit', $row->id).'">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                </li>';
                }

                if (hasPermission('student_delete')) {
                    $html .= '<li>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="delete_row(\'student/delete\', '.$row->student_id.')">
                        <i class="fa-solid fa-trash-can"></i> Delete
                    </a>
                </li>';
                }

                return $html.'</ul></div>';
            })

            ->rawColumns(['actions', 'name', 'actions_dropdown', 'admission_date'])
            ->make(true);
    }

    public function pdfExport(Request $request)
    {
        try {
            $data['students'] = $this->getStudent($request)->get();

            $data['columns'] = $request->columns;
            $html = view('backend.student-info.student.student-pdf-export-datatable', compact('data'))->render();
            $mpdf = new Mpdf([
                'tempDir' => storage_path('app/mpdf'),
                'mode' => 'utf-8',
                'default_font' => 'amiri',
                'margin_top' => 40,   // same height as your header
                'margin_bottom' => 20, // same height as footer
                'orientation' => 'L',
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
            $students = $this->getStudent($request)->get();

            $selectedColumns = $request->columns;

            $filename = 'student_list_'.date('d_m_Y').'.csv';

            // Column map
            $columnMap = [

                'si' => [
                    'label' => '#',
                    'value' => fn ($item, $key) => $key + 1,
                ],

                'admission_no' => [
                    'label' => ___('student_info.admission_no'),
                    'value' => fn ($item) => optional($item->student)->admission_no,
                ],

                'roll_no' => [
                    'label' => ___('student_info.roll_no'),
                    'value' => fn ($item) => optional($item->student)->roll_no ?? $item->roll,
                ],

                'name' => [
                    'label' => ___('student_info.name'),
                    'value' => fn ($item) => optional($item->student)->full_name,
                ],

                'student_ar_name' => [
                    'label' => ___('student_info.arabic_name'),
                    'value' => fn ($item) => optional($item->student)->student_ar_name,
                ],

                'class_section' => [
                    'label' => ___('student_info.class'),
                    'value' => fn ($item) => (optional($item->class)->name ?? 'N/A').' ('.
                        (optional($item->section)->name ?? 'N/A').')',
                ],

                'department' => [
                    'label' => ___('student_info.department'),
                    'value' => fn ($item) => optional($item->student->department)->name,
                ],

                'gender' => [
                    'label' => ___('student.Gender'),
                    'value' => fn ($item) => optional($item->student->gender)->name,
                ],

                'mobile' => [
                    'label' => ___('student_info.mobile'),
                    'value' => fn ($item) => optional($item->student)->mobile ?? 'N/A',
                ],

                'dob' => [
                    'label' => ___('student_info.dob'),
                    'value' => fn ($item) => optional($item->student)->dob ?? 'N/A',
                ],

                'religion' => [
                    'label' => ___('student_info.religion'),
                    'value' => fn ($item) => optional($item->student->religion)->name ?? 'N/A',
                ],

                'blood_group' => [
                    'label' => ___('student_info.blood'),
                    'value' => fn ($item) => optional($item->student->blood)->name ?? 'N/A',
                ],

                'admission_date' => [
                    'label' => ___('student_info.admission_date'),
                    'value' => fn ($item) => optional($item->student)->admission_date ?? 'N/A',
                ],

                'previous_school' => [
                    'label' => ___('frontend.previous_school_information'),
                    'value' => fn ($item) => optional($item->student)->previous_school_info ?? 'N/A',
                ],

                'place_of_birth' => [
                    'label' => ___('frontend.Place_Of_Birth'),
                    'value' => fn ($item) => optional($item->student)->place_of_birth ?? 'N/A',
                ],

                'emergency_contact' => [
                    'label' => ___('frontend.Emergency_Contact'),
                    'value' => fn ($item) => optional($item->student)->emergency_contact ?? 'N/A',
                ],

                'health_status' => [
                    'label' => ___('common.Health Status'),
                    'value' => fn ($item) => optional($item->student)->health_status ?? 'N/A',
                ],
                'guardian' => [
                    'label' => ___('student_info.guardian'),
                    'value' => fn ($item) => optional($item->student->parent)->guardian_name,
                ],
            ];

            // STREAMED RESPONSE
            $response = new StreamedResponse(function () use ($students, $selectedColumns, $columnMap) {

                $handle = fopen('php://output', 'w');

                // ---- Write Header ----
                $header = [];
                foreach ($selectedColumns as $col) {
                    if (isset($columnMap[$col])) {
                        $header[] = $columnMap[$col]['label'];
                    }
                }
                fputcsv($handle, $header);

                // ---- Write Rows ----
                foreach ($students as $key => $item) {
                    $row = [];

                    foreach ($selectedColumns as $col) {
                        if (isset($columnMap[$col])) {
                            $callback = $columnMap[$col]['value'];
                            $row[] = $callback($item, $key);
                        }
                    }

                    fputcsv($handle, $row);
                }

                fclose($handle);
            });

            // Set response headers
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set(
                'Content-Disposition',
                'attachment; filename="'.setting('application_name').'_'.$filename.'"'
            );

            return $response;

        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage());
        }
    }
}
