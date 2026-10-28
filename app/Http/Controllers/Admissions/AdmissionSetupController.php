<?php

namespace App\Http\Controllers\Admissions;

use App\Enums\Admission;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Admissions\Program;
use App\Models\Admissions\RequirementRule;
use App\Models\Admissions\RequirementType;
use App\Models\Admissions\Term;
use App\Models\Session;
use App\Models\Staff\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdmissionSetupController extends Controller
{
    use NullsBlankInput;

    public function programs(Request $request)
    {
        $data['title']       = ___('admissions.programs');
        $data['programs']    = Program::with('department')->orderBy('track')->orderBy('name')->get();
        $data['departments'] = Department::orderBy('name')->get();
        $data['program']     = $request->edit ? Program::findOrFail($request->edit) : new Program(['track' => Admission::TRACK_CHED, 'status' => 1, 'accepting_applications' => true]);

        return view('backend.admissions.setup.programs', compact('data'));
    }

    public function saveProgram(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'                     => ['nullable', 'exists:programs,id'],
            'track'                  => ['required', Rule::in(Admission::TRACKS)],
            'code'                   => ['nullable', 'string', 'max:30'],
            'name'                   => ['required', 'string', 'max:190'],
            'department_id'          => ['nullable', 'exists:departments,id'],
            'authority_reference'    => ['nullable', 'string', 'max:190'],
            'training_hours'         => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'entry_requirements'     => ['nullable', 'string', 'max:5000'],
            'max_units'              => ['nullable', 'numeric', 'min:1', 'max:60'],
            'minimum_gwa'            => ['nullable', 'numeric', 'min:80', 'max:100'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'accepting_applications' => ['nullable', 'boolean'],
            'status'                 => ['required', 'in:0,1'],
        ]));
        $data['accepting_applications'] = $request->boolean('accepting_applications');

        Program::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('admissions.setup.programs')->with('success', ___('alert.updated_successfully'));
    }

    public function terms(Request $request)
    {
        $data['title']    = ___('admissions.terms');
        $data['terms']    = Term::with('session')->orderByDesc('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")->get();
        $data['sessions'] = Session::orderByDesc('id')->get();
        $data['term']     = $request->edit ? Term::findOrFail($request->edit) : new Term(['status' => 1]);

        return view('backend.admissions.setup.terms', compact('data'));
    }

    public function saveTerm(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'             => ['nullable', 'exists:terms,id'],
            'session_id'     => ['required', 'exists:sessions,id'],
            'semester'       => ['required', Rule::in(Admission::SEMESTERS),
                Rule::unique('terms')->where('session_id', $request->session_id)->ignore($request->id)],
            'name'           => ['required', 'string', 'max:190'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'midterm_on'     => ['nullable', 'date'],
            'admission_open' => ['nullable', 'boolean'],
            'status'         => ['required', 'in:0,1'],
        ]));
        $data['admission_open'] = $request->boolean('admission_open');

        Term::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('admissions.setup.terms')->with('success', ___('alert.updated_successfully'));
    }

    public function requirements(Request $request)
    {
        $data['title']    = ___('admissions.requirements');
        $data['types']    = RequirementType::withCount('rules')->orderBy('name')->get();
        $data['rules']    = RequirementRule::with(['requirementType', 'program'])->orderBy('track')->orderBy('applicant_type')->get();
        $data['programs'] = Program::active()->orderBy('name')->get();
        $data['type']     = $request->edit ? RequirementType::findOrFail($request->edit) : new RequirementType(['status' => 1]);

        return view('backend.admissions.setup.requirements', compact('data'));
    }

    public function saveRequirement(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'          => ['nullable', 'exists:requirement_types,id'],
            'name'        => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['required', 'in:0,1'],
        ]));

        RequirementType::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('admissions.setup.requirements')->with('success', ___('alert.updated_successfully'));
    }

    public function saveRule(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'requirement_type_id' => ['required', 'exists:requirement_types,id'],
            'track'               => ['required', Rule::in(Admission::TRACKS)],
            'applicant_type'      => ['nullable', Rule::in(Admission::applicantTypes($request->track))],
            'program_id'          => ['nullable', Rule::exists('programs', 'id')->where('track', $request->track)],
            'is_required'         => ['nullable', 'boolean'],
        ]));
        $data['is_required'] = $request->boolean('is_required');

        RequirementRule::updateOrCreate(
            collect($data)->only(['requirement_type_id', 'track', 'applicant_type', 'program_id'])->all(),
            ['is_required' => $data['is_required']]
        );

        return redirect()->route('admissions.setup.requirements')->with('success', ___('alert.updated_successfully'));
    }

    public function deleteRule(RequirementRule $rule)
    {
        $rule->delete();

        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }
}
