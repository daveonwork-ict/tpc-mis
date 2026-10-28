<?php

namespace App\Http\Controllers\StudentInfo;


use Mpdf\Mpdf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repositories\StudentInfo\ParentGuardianRepository;
use App\Http\Requests\StudentInfo\ParentGuardian\ParentGuardianStoreRequest;
use App\Http\Requests\StudentInfo\ParentGuardian\ParentGuardianUpdateRequest;

class ParentGuardianController extends Controller
{
    private $repo;
    private $user;

    function __construct(ParentGuardianRepository $repo, UserRepository $user)
    {
        $this->repo       = $repo;
        $this->user       = $user;
    }

    public function index()
    {
        $data['title']   = ___('student_info.parent_list');
        $data['parents'] = $this->repo->getPaginateAll();
        return view('backend.student-info.parent.index', compact('data'));
    }

    public function search(Request $request)
    {
        $data['title']   = ___('student_info.parent_list');
        $data['request'] = $request;
        $data['parents'] = $this->repo->searchParent($request);
        return view('backend.student-info.parent.index', compact('data'));
    }

    public function create()
    {
        $data['title']              = ___('student_info.parent_create');
        $data['staffs']            =  $this->user->getAllStaffs();
        return view('backend.student-info.parent.create', compact('data'));
    }

    public function getParent(Request $request)
    {
        $result = $this->repo->getParent($request);
        return response()->json($result);
    }

    public function store(ParentGuardianStoreRequest $request)
    {

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('parent.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['parent']      = $this->repo->show($id);
        $data['title']       = ___('student_info.parent_edit');
        return view('backend.student-info.parent.edit', compact('data'));
    }

    public function update(ParentGuardianUpdateRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result) {
            return redirect()->route('parent.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']):
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        else:
            $success[0] = $result['message'];
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
            return response()->json($success);
        endif;
    }




    public function pdfExport(Request $request)
    {

        increaseServerLoad();
        try {
            $data['parents'] = $this->repo->getAll();
            $data['title']   = ___('student_info.parent_list');
            $data['request'] = $request;

            // Render Blade to raw HTML
            $html = view('backend.student-info.parent.parent-pdf-export', compact('data'))->render();
            // Create mPDF instance (not the facade)
            $mpdf = new Mpdf([
                'tempDir' => storage_path('app/mpdf'),
                'mode' => 'utf-8',
                'default_font' => 'amiri',
                'margin_top' => 30,   // same height as your header
                'margin_bottom' => 10, // same height as footer
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
                ->header('Content-Disposition', 'attachment; filename="' . setting('application_name') . '_guardian_list_' . time() . '.pdf"');
        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage());
        }
    }



    public function csvExport(Request $request)
    {
        try {
            $parents = $this->repo->getAll();

            $filename = "guardian_list" . time() . ".csv";

            $response = new StreamedResponse(function () use ($parents) {
                $handle = fopen('php://output', 'w');

                // ===== School Info Header =====
                fputcsv($handle, [setting('application_name')]); // School name
                fputcsv($handle, [setting('address')]);          // Address
                fputcsv($handle, []);                            // Empty line

                // ===== CSV Table Header =====
                fputcsv($handle, [
                    ___('common.sr_no'),
                    ___('common.name'),
                    ___('common.childs'),
                    ___('common.phone'),
                    ___('common.email'),
                    ___('common.Position'),
                    ___('common.Relationship'),
                    ___('common.address')
                ]);

                // ===== CSV Data Rows =====
                foreach ($parents as $key => $item) {
                    $children = $item->children
                        ->map(fn($s) => $s->first_name . ' ' . $s->last_name . ' - ' . $s->admission_no)
                        ->implode(' | ');

                    fputcsv($handle, [
                        $key + 1,
                        $item->guardian_name,
                        @$children,
                        $item->guardian_mobile ?? 'N/A',
                        $item->guardian_email ?? 'N/A',
                        $item->guardian_profession ?? 'N/A',
                        $item->guardian_relation ?? 'N/A',
                        $item->guardian_address ?? 'N/A',
                    ]);
                }

                fclose($handle);
            });

            // Set headers for download
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . setting('application_name') . '_' . $filename . '"');

            return $response;
        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage());
        }
    }
}
