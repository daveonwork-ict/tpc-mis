<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment as Status;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use App\Http\Controllers\Controller;
use App\Models\Admissions\Program;
use App\Models\Enrollment\TrainingBatch;
use App\Models\Session;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BatchController extends Controller
{
    use NullsBlankInput;

    public function index(Request $request)
    {
        $batches = TrainingBatch::with('program', 'session', 'trainer')
            ->withCount(['enrollments as seats_taken' => fn ($q) => $q->whereNotIn('status', Status::CLOSED_STATUSES)])
            ->orderByDesc('id')
            ->get();

        return view('backend.enrollment.batches.index', ['data' => [
            'title'    => ___('enrollment.training_batches'),
            'batches'  => $batches,
            'batch'    => $request->edit ? TrainingBatch::findOrFail($request->edit) : new TrainingBatch([
                'status'     => TrainingBatch::OPEN,
                'session_id' => setting('session'),
            ]),
            'programs' => Program::where('track', Admission::TRACK_TESDA)->where('status', 1)->orderBy('name')->get(),
            'sessions' => Session::orderByDesc('id')->get(),
            'trainers' => Staff::where('status', 1)->orderBy('last_name')->orderBy('first_name')->get(),
        ]]);
    }

    public function save(Request $request)
    {
        $data = $this->nullBlanks($request->validate([
            'id'          => ['nullable', 'exists:training_batches,id'],
            'program_id'  => ['required', Rule::exists('programs', 'id')->where('track', Admission::TRACK_TESDA)->where('status', 1)],
            'session_id'  => ['required', 'exists:sessions,id'],
            'code'        => ['required', 'string', 'max:30',
                Rule::unique('training_batches', 'code')->where('program_id', $request->program_id)->ignore($request->id)],
            'name'        => ['required', 'string', 'max:190'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'schedule'    => ['nullable', 'string', 'max:190'],
            'venue'       => ['nullable', 'string', 'max:190'],
            'trainer_id'  => ['nullable', 'exists:staff,id'],
            'slots'       => ['required', 'integer', 'min:1', 'max:500'],
            'status'      => ['required', Rule::in([TrainingBatch::OPEN, TrainingBatch::CLOSED])],
            'remarks'     => ['nullable', 'string', 'max:1000'],
        ]));

        $existing = isset($data['id']) ? TrainingBatch::find($data['id']) : null;
        if ($existing && $existing->enrollments()->exists() && (int) $data['program_id'] !== (int) $existing->program_id) {
            throw ValidationException::withMessages(['program_id' => ___('enrollment.batch_program_locked')]);
        }

        $taken = $existing ? $existing->activeEnrollmentCount() : 0;
        if ((int) $data['slots'] < $taken) {
            throw ValidationException::withMessages(['slots' => ___('enrollment.slots_below_taken')]);
        }

        TrainingBatch::updateOrCreate(['id' => $data['id'] ?? null], collect($data)->except('id')->all());

        return redirect()->route('enrollment.batches.index')->with('success', ___('enrollment.batch_saved'));
    }

    public function delete(TrainingBatch $batch)
    {
        if ($batch->enrollments()->exists()) {
            return back()->with('danger', ___('enrollment.batch_has_trainees'));
        }

        $batch->delete();

        return back()->with('success', ___('enrollment.batch_removed'));
    }
}
