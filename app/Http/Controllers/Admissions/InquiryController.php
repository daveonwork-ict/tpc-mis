<?php

namespace App\Http\Controllers\Admissions;

use App\Enums\Admission;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Admissions\Inquiry;
use App\Models\Admissions\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    use NullsBlankInput;

    public function index(Request $request)
    {
        $data['title'] = ___('admissions.inquiries');
        $data['inquiries'] = Inquiry::with(['program', 'application', 'assignee'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->channel, fn ($q, $v) => $q->where('channel', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")->orWhere('mobile', 'like', "%{$v}%")))
            ->orderByRaw("FIELD(status, 'new', 'contacted', 'interested', 'converted', 'closed')")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.admissions.inquiries.index', compact('data'));
    }

    public function create()
    {
        return $this->form(new Inquiry(['channel' => 'walk_in', 'status' => 'new']), ___('admissions.new_inquiry'));
    }

    public function store(Request $request)
    {
        Inquiry::create($this->validated($request) + ['created_by' => auth()->id()]);

        return redirect()->route('admissions.inquiries.index')->with('success', ___('alert.created_successfully'));
    }

    public function edit(Inquiry $inquiry)
    {
        return $this->form($inquiry, ___('admissions.edit_inquiry'));
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $data = $this->validated($request);
        if ($inquiry->application_id) {
            $data['status'] = 'converted';
        }
        $inquiry->update($data);

        return redirect()->route('admissions.inquiries.index')->with('success', ___('alert.updated_successfully'));
    }

    public function delete(Inquiry $inquiry)
    {
        if ($inquiry->application_id) {
            return response()->json([___('admissions.converted_inquiry_cannot_be_deleted'), 'error', ___('alert.oops')]);
        }

        $inquiry->delete();

        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    private function form(Inquiry $inquiry, string $title)
    {
        $data['title']    = $title;
        $data['inquiry']  = $inquiry;
        $data['programs'] = Program::active()->orderBy('track')->orderBy('name')->get();
        $data['staff']    = User::whereNotIn('role_id', [6, 7])->orderBy('name')->get(['id', 'name']);

        return view('backend.admissions.inquiries.form', compact('data'));
    }

    private function validated(Request $request): array
    {
        return $this->nullBlanks($request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'email'        => ['nullable', 'email', 'max:150'],
            'mobile'       => ['nullable', 'string', 'max:30'],
            'channel'      => ['required', Rule::in(Admission::INQUIRY_CHANNELS)],
            'track'        => ['nullable', Rule::in(Admission::TRACKS)],
            'program_id'   => ['nullable', 'exists:programs,id'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'status'       => ['required', Rule::in(array_diff(Admission::INQUIRY_STATUSES, ['converted']))],
            'follow_up_on' => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'assigned_to'  => ['nullable', 'exists:users,id'],
        ]));
    }
}
