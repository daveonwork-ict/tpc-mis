<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Admission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admissions\ApplicationRequest;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationRequirement;
use App\Models\Admissions\Program;
use App\Models\Admissions\RequirementRule;
use App\Models\Admissions\Term;
use App\Services\Admissions\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ApplyController extends Controller
{
    const SESSION_KEY = 'applicant_application_id';

    /** Requirement items an applicant may (re)upload from the status page. */
    const UPLOADABLE = [Admission::REQ_PENDING, Admission::REQ_SUBMITTED, Admission::REQ_REJECTED];

    public function __construct(private ApplicationService $service)
    {
    }

    public function create()
    {
        $data['programs'] = Program::open()->orderBy('track')->orderBy('name')->get();
        $data['terms']    = Term::openForAdmission()->orderBy('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->get();
        $data['rules']    = RequirementRule::with('requirementType')
            ->whereHas('requirementType', fn ($q) => $q->active())
            ->get()
            ->map(fn ($rule) => [
                'type'        => $rule->requirement_type_id,
                'name'        => $rule->requirementType->name,
                'description' => $rule->requirementType->description,
                'track'       => $rule->track,
                'applicant'   => $rule->applicant_type,
                'program'     => $rule->program_id,
                'required'    => $rule->is_required,
            ])->values();

        return view('frontend.apply', compact('data'));
    }

    public function store(ApplicationRequest $request)
    {
        $files = collect($request->file('documents', []))
            ->filter(fn ($file, $key) => ctype_digit((string) $key))
            ->mapWithKeys(fn ($file, $key) => [(int) $key => $file])
            ->all();

        $application = $this->service->submit($request->personData(), $request->applicationData(), $files, 'online');

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $application->id);

        return redirect()->route('frontend.apply.status')->with('applied', $application->application_no);
    }

    public function status(Request $request)
    {
        $application = $this->current($request);

        if (! $application) {
            return view('frontend.apply-status', ['application' => null]);
        }

        $application->load(['person', 'program', 'term', 'requirements.requirementType', 'latestDecision']);

        return view('frontend.apply-status', compact('application'));
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'application_no' => ['required', 'string', 'max:30'],
            'date_of_birth'  => ['required', 'date'],
        ]);

        $key = 'apply-lookup:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            throw ValidationException::withMessages(['application_no' => ___('admissions.too_many_attempts')]);
        }

        $application = Application::where('application_no', strtoupper(trim($request->application_no)))
            ->whereHas('person', fn ($q) => $q->whereDate('date_of_birth', $request->date_of_birth))
            ->first();

        if (! $application) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['application_no' => ___('admissions.application_not_found')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $application->id);

        return redirect()->route('frontend.apply.status');
    }

    public function upload(Request $request, ApplicationRequirement $requirement)
    {
        $application = $this->current($request);
        abort_unless($application && $requirement->application_id === $application->id, 403);
        abort_unless(in_array($requirement->status, self::UPLOADABLE, true), 422);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'file.mimes' => ___('admissions.document_types_allowed'),
            'file.max'   => ___('admissions.document_size_limit'),
        ]);

        $this->service->storeFile($requirement, $request->file('file'));

        return redirect()->route('frontend.apply.status')->with('uploaded', $requirement->requirementType->name);
    }

    public function leave(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('frontend.apply.status');
    }

    private function current(Request $request): ?Application
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? Application::find($id) : null;
    }
}
