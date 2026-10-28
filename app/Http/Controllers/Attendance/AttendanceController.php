<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\AttendanceSearchRequest;
use App\Http\Requests\Attendance\AttendanceStoreRequest;
use App\Http\Requests\Report\AttendanceRequest;
use App\Http\Requests\SubjectAttendanceSearchRequest;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\Academic\SubjectRepository;
use App\Repositories\Attendance\AttendanceRepository;
use Illuminate\Http\Request;
use PDF;

class AttendanceController extends Controller
{
    private $repo;
    private $classRepo;
    private $classSetupRepo;

    private $subjectRepo;

    function __construct(
        AttendanceRepository   $repo,
        ClassesRepository      $classRepo,
        ClassSetupRepository   $classSetupRepo,
        SubjectRepository      $subjectRepo,
    ) {
        $this->repo              = $repo;
        $this->classRepo         = $classRepo;
        $this->classSetupRepo    = $classSetupRepo;
        $this->subjectRepo       = $subjectRepo;
    }

    public function index()
    {
        $data['title']              = ___('attendance.Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];

        return view('backend.attendance.index', compact('data'));
    }

    public function subjectAttendance()
    {
        $data['title']              = ___('attendance.Subject Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];
        $data['subjects']           = $this->subjectRepo->all();
        return view('backend.attendance.subject-attendance', compact('data'));
    }

    public function store(Request $request)
    {

        $result = $this->repo->store($request);
        if ($result['status']) {
            ActivityLogEvent('Take Attendance');
            return redirect(route('attendance.index'))->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function storeSubjectAttendance(Request $request)
    {
        $result = $this->repo->storeSubjectWiseAttendance($request);
        if ($result['status']) {
            ActivityLogEvent('Subject Attendance');
            return redirect()->back()->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function searchStudents(AttendanceSearchRequest $request)
    {

        $data = $this->repo->searchStudents($request);
        $data['title']    = ___('attendance.Attendance');
        $data['request']  = $request;
        $data['students'] = $data['students'];
        $data['status']   = $data['status'];
        $data['classes']  = $this->classRepo->assignedAll();
        $data['sections'] = $this->classSetupRepo->getSections($request->class);
        return view('backend.attendance.index', compact('data'));
    }


    public function searchAttendance(SubjectAttendanceSearchRequest $request)
    {
        $data = $this->repo->searchSubjectAttendance($request);
        $data['title']    = ___('attendance.Subject Attendance') . ' (' . $data['subject_name'] . ')';
        $data['request']  = $request;
        $data['subjects']           = $this->subjectRepo->all();
        $data['classes']  = $this->classRepo->assignedAll();
        $data['sections'] = $this->classSetupRepo->getSections($request->class);
        return view('backend.attendance.subject-attendance', compact('data'));
    }

    // report start----------------------------------------------------------------------------------------------

    public function report()
    {
        $data['title']              = ___('attendance.Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];
        $data['students']           = [];
        $data['request']            = [];

        return view('backend.attendance.report', compact('data'));
    }


    public function reportSearch(AttendanceRequest $request)
    {
        $data['title']        = ___('attendance.Attendance');
        $data['request']      = $request;
        $data['classes']      = $this->classRepo->assignedAll();
        $data['sections']     = $this->classSetupRepo->getSections($request->class);
        $results              = $this->repo->searchReport($request);
        $data['students']     = $results['students'];
        $data['days']         = $results['days'];
        $data['attendances']  = $results['attendances'];
        return view('backend.attendance.report', compact('data'));
    }

    public function generatePDF(Request $request)
    {
        // If dates are encrypted, decrypt them
        if ($request->has('dates')) {
            $request->merge([
                'dates' => \Illuminate\Support\Facades\Crypt::decryptString($request->dates),
            ]);
        }

        // Fetch data
        $results              = $this->repo->searchReportPDF($request);
        $data['students']     = $results['students'];
        $data['days']         = $results['days'];
        $data['attendances']  = $results['attendances'];
        $data['request']      = $request;

        // Render the blade view to HTML
        $html = view('backend.attendance.reportPDF', compact('data'))->render();

        // Create mPDF instance
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => storage_path('app/mpdf'),
            'mode'          => 'utf-8',
            'default_font'  => 'amiri',
            'margin_top'    => 30,
            'margin_bottom' => 20,
            'format' => 'A4-L'
        ]);

        $mpdf->autoLangToFont = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->SetDirectionality(findDirectionOfLang());

        // Optional: Set header & footer
        $mpdf->SetHTMLHeader(view('pdf.header')->render());
        $mpdf->SetHTMLFooter(view('pdf.footer')->render());

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Output file (use request values if available)
        $filename = 'attendance_'
            . ($request->class ?? 'NA') . '_'
            . ($request->section ?? 'NA') . '_'
            . date('d_m_Y') . '.pdf';

        return $mpdf->Output($filename, 'D'); // 'D' = force download
    }


    // report end----------------------------------------------------------------------------------------------
}
