<?php

namespace App\Http\Controllers\Admissions;

use App\Enums\Admission;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admissions\ApplicationRequest;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationRequirement;
use App\Models\Admissions\Inquiry;
use App\Models\Admissions\Program;
use App\Models\Admissions\Term;
use App\Services\Admissions\ApplicationService;
use App\Services\StudentManual;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    use NullsBlankInput;

    public function __construct(private ApplicationService $service)
    {
    }

    public function index(Request $request)
    {
        $query = Application::with(['person', 'program', 'term'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->track, fn ($q, $v) => $q->where('track', $v))
            ->when($request->program_id, fn ($q, $v) => $q->where('program_id', $v))
            ->when($request->term_id, fn ($q, $v) => $q->where('term_id', $v))
            ->when($request->applicant_type, fn ($q, $v) => $q->where('applicant_type', $v))
            ->when($request->q, function ($q, $keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('application_no', 'like', "%{$keyword}%")
                        ->orWhereHas('person', fn ($p) => $p->where('last_name', 'like', "%{$keyword}%")
                            ->orWhere('first_name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('mobile', 'like', "%{$keyword}%"));
                });
            });

        $ranked = $query->get()->sortBy(function ($app) {
            $score = StudentManual::matrix($app);
            $app->matrix_score = $score;

            return [
                $score['total'] === null ? 1 : 0,
                -1 * (float) ($score['total'] ?? 0),
                $app->tarlac_resident ? 0 : 1,
                -1 * $app->id,
            ];
        })->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $data['title']        = ___('admissions.applications');
        $data['applications'] = new LengthAwarePaginator(
            $ranked->forPage($page, 20)->values(),
            $ranked->count(),
            20,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $data['counts']       = Application::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $data['programs']     = Program::active()->orderBy('track')->orderBy('name')->get();
        $data['terms']        = Term::with('session')->orderByDesc('session_id')->orderBy('id')->get();

        return view('backend.admissions.applications.index', compact('data'));
    }

    public function create(Request $request)
    {
        $data['title']    = ___('admissions.new_walk_in_application');
        $data['programs'] = Program::active()->orderBy('name')->get();
        $data['terms']    = Term::where('status', 1)->orderByDesc('admission_open')->orderByDesc('session_id')->get();
        $data['inquiry']  = $request->inquiry ? Inquiry::find($request->inquiry) : null;

        return view('backend.admissions.applications.create', compact('data'));
    }

    public function store(ApplicationRequest $request)
    {
        $inquiry = $request->integer('inquiry_id') ?: null;

        $application = $this->service->submit(
            $request->personData(),
            $request->applicationData(),
            [],
            'walk_in',
            auth()->id(),
            $inquiry && Inquiry::whereKey($inquiry)->whereNull('application_id')->exists() ? $inquiry : null
        );

        return redirect()->route('admissions.applications.show', $application)
            ->with('success', ___('admissions.application_created') . ' ' . $application->application_no);
    }

    public function show(Application $application)
    {
        $application->load([
            'person', 'program.department', 'term', 'encoder',
            'requirements.requirementType', 'requirements.reviewer',
            'screenings.screener', 'decisions.decider', 'statusLogs.user',
        ]);

        $data['title']       = $application->application_no;
        $data['application'] = $application;

        return view('backend.admissions.applications.show', compact('data'));
    }

    public function edit(Application $application)
    {
        $data['title']       = ___('admissions.edit_application') . ' ' . $application->application_no;
        $data['application'] = $application->load('person');
        $data['programs']    = Program::active()->orderBy('name')->get();
        $data['terms']       = Term::where('status', 1)->orderByDesc('session_id')->get();
        $data['inquiry']     = null;

        return view('backend.admissions.applications.edit', compact('data'));
    }

    public function update(ApplicationRequest $request, Application $application)
    {
        abort_if($application->isClosed(), 422, ___('admissions.application_is_closed'));

        DB::transaction(function () use ($request, $application) {
            $application->person->update($request->personData());
            $application->update($request->applicationData());
            $this->service->syncChecklist($application->fresh(), auth()->id());
        });

        return redirect()->route('admissions.applications.show', $application)->with('success', ___('alert.updated_successfully'));
    }

    public function changeStatus(Request $request, Application $application)
    {
        $request->validate([
            'action'  => ['required', Rule::in(['withdraw', 'defer', 'reopen'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->{$request->action}($application, $request->remarks, auth()->id());

        return back()->with('success', ___('admissions.status_updated'));
    }

    public function screening(Request $request, Application $application)
    {
        $data = $request->validate([
            'type'        => ['required', Rule::in(Admission::SCREENING_TYPES)],
            'result'      => ['required', Rule::in(Admission::SCREENING_RESULTS)],
            'score'       => ['nullable', 'string', 'max:30'],
            'screened_on' => ['nullable', 'date'],
            'remarks'     => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->recordScreening($application, $this->nullBlanks($data), auth()->id());

        return back()->with('success', ___('admissions.screening_saved'));
    }

    public function decision(Request $request, Application $application)
    {
        $request->validate([
            'decision'   => ['required', Rule::in(Admission::DECISIONS)],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'reason'     => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->decide($application->load('requirements'), $request->decision, $request->conditions, $request->reason, auth()->id());

        return back()->with('success', ___('admissions.decision_saved'));
    }

    public function matrix(Request $request, Application $application)
    {
        $request->merge([
            'tpccat_points'    => $request->input('tpccat_points') === '' ? null : $request->input('tpccat_points'),
            'financial_points' => $request->input('financial_points') === '' ? null : $request->input('financial_points'),
        ]);
        $data = $request->validate([
            'tpccat_points'       => ['nullable', 'numeric', 'min:0', 'max:35'],
            'financial_points'    => ['nullable', 'numeric', 'min:0', 'max:30'],
            'sector_categories'   => ['nullable', 'array'],
            'sector_categories.*' => ['string', Rule::in(StudentManual::SECTORS)],
        ]);

        $application->update([
            'tpccat_points'     => $data['tpccat_points'] ?? null,
            'financial_points'  => $data['financial_points'] ?? null,
            'sector_categories' => array_values($data['sector_categories'] ?? []),
            'sector_verified'   => $request->boolean('sector_verified'),
            'tarlac_resident'   => $request->boolean('tarlac_resident'),
        ]);

        return back()->with('success', ___('admissions.matrix_saved'));
    }

    public function reviewRequirement(Request $request, ApplicationRequirement $requirement)
    {
        $request->validate([
            'status'  => ['required', Rule::in([Admission::REQ_VERIFIED, Admission::REQ_REJECTED, Admission::REQ_WAIVED])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->reviewRequirement($requirement, $request->status, $request->remarks, auth()->id());

        return back()->with('success', ___('admissions.requirement_updated'));
    }

    public function uploadRequirement(Request $request, ApplicationRequirement $requirement)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->service->storeFile($requirement, $request->file('file'), auth()->id());

        return back()->with('success', ___('admissions.file_uploaded'));
    }

    public function requirementFile(ApplicationRequirement $requirement)
    {
        abort_unless($requirement->file_path && Storage::disk(ApplicationService::DISK)->exists($requirement->file_path), 404);

        return Storage::disk(ApplicationService::DISK)->response($requirement->file_path, $requirement->original_name, [
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
