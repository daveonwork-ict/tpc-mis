<?php

namespace App\Http\Controllers\Api\Teacher;

use PDF;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Traits\ReturnFormatTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Attendance\AttendanceRepository;

class TeacherReportController extends Controller
{
    use ReturnFormatTrait;
    private $attendanceRepo;

    function __construct(AttendanceRepository $attendanceRepo)
    {
        $this->attendanceRepo = $attendanceRepo;
    }

    public function attendanceReport(Request $request)
    {
        try {
            $request->merge(['view' => 1]);
            $results              = $this->attendanceRepo->searchReportPDF($request);
            $data['students']     = $results['students'];
            $data['days']         = $results['days'];
            $data['attendances']  = $results['attendances'];
            $data['request']      = $request;
            $data['class_name']      = Classes::find($request->classes_id)?->name;
            $data['section_name']      = Section::find($request->section_id) ? Section::find($request->section_id)->name : 'All';
            $pdf = PDF::loadView('backend.attendance.reportPDF', compact('data'));

           $filename = Str::slug('attendance__' . '_' . @$data['class_name'] . '_' . @$data['section_name']) . '.pdf';
            $path = 'attendance'; // folder name
            $fullPath = $path . '/' . $filename;

        if (setting('file_system') === 's3') {
            // Upload to S3
            Storage::disk('s3')->put($fullPath, $pdf->output());

            if (Storage::disk('s3')->exists($fullPath)) {
                // Optional: return S3 URL
                $url = Storage::disk('s3')->url($fullPath);
            }
        } else {
            // Local storage (public)
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0755, true);
            }

            file_put_contents(public_path($fullPath), $pdf->output());

            // Optional: return local URL
            $url = url($fullPath);
        }


            $result['class'] = @$data['class_name'];
            $result['section'] = @$data['section_name'];
            $result['attendance_date'] = Carbon::parse($request->date)->format('l j F Y');
            $result['attendance_report_download_url'] = @$url;
            return $this->responseWithSuccess(___('alert.success'), $result);
        } catch (\Throwable $th) {
            return $this->responseWithError($th->getMessage(), []);
        }
    }
}
