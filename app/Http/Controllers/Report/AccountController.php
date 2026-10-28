<?php

namespace App\Http\Controllers\Report;

use App\Enums\AccountHeadType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Accounts\AccountHeadRepository;
use App\Repositories\Report\AccountRepository;
use Illuminate\Support\Facades\Crypt;
use PDF;

class AccountController extends Controller
{
    private $repo;
    private $accountHeadRepo;

    function __construct(
        AccountRepository      $repo,
        AccountHeadRepository  $accountHeadRepo,
    ) {
        $this->repo              = $repo;
        $this->accountHeadRepo   = $accountHeadRepo;
    }

    public function index()
    {
        $data['account_head'] = $this->accountHeadRepo->getIncomeHeads();
        return view('backend.report.account', compact('data'));
    }

    public function search(Request $request)
    {
        // dd($request->all());
        $data                 = $this->repo->search($request);
        $data['request']      = $request;

        if ($data['request']->type == AccountHeadType::INCOME)
            $data['account_head'] = $this->accountHeadRepo->getIncomeHeads();
        else
            $data['account_head'] = $this->accountHeadRepo->getExpenseHeads();

        return view('backend.report.account', compact('data'));
    }

    public function getAccountTypes(Request $request)
    {
        if ($request->id == AccountHeadType::INCOME)
            return $this->accountHeadRepo->getIncomeHeads();
        else
            return $this->accountHeadRepo->getExpenseHeads();
    }

    public function generatePDF(Request $request)
    {
        // Prepare request object
        $searchRequest = new \Illuminate\Http\Request([
            'type'  => $request->type,
            'head'  => $request->head,
            'dates' => $request->filtered_dates,
        ]);

        // Fetch data
        $data = $this->repo->searchPDF($searchRequest);

        // Render the blade view to HTML
        $html = view('backend.report.accountPDF', compact('data'))->render();

        // Create mPDF instance
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => storage_path('app/mpdf'),
            'mode'          => 'utf-8',
            'default_font'  => 'amiri',
            'margin_top'    => 30,
            'margin_bottom' => 20,
        ]);

        $mpdf->autoLangToFont = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->SetDirectionality(findDirectionOfLang());

        // Optional: Set header & footer
        $mpdf->SetHTMLHeader(view('pdf.header')->render());
        $mpdf->SetHTMLFooter(view('pdf.footer')->render());

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Output file
        $filename = 'account_' . date('d_m_Y') . '.pdf';
        return $mpdf->Output($filename, 'D'); // 'D' = force download
    }
}
