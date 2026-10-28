<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\Admission;
use App\Enums\Enrollment as Status;
use App\Http\Controllers\Controller;
use App\Models\Admissions\Term;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\TrainingBatch;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type = in_array($request->type, ['enrollment', 'clearance', 'batch'], true) ? $request->type : 'enrollment';
        $terms = Term::with('session')->where('status', 1)->orderByDesc('session_id')->orderBy('id')->get();
        $termId = $request->has('term_id') ? $request->term_id : ($terms->firstWhere('enrollment_open', true)->id ?? null);
        $batches = TrainingBatch::with('program')->orderByDesc('id')->get();
        $batchId = $request->batch_id ?: $batches->firstWhere('status', TrainingBatch::OPEN)?->id;

        $rows = match ($type) {
            'clearance' => $this->clearanceRows($termId),
            'batch'     => $this->batchRows($batchId),
            default     => $this->enrollmentRows($termId),
        };

        return view('backend.enrollment.reports.index', [
            'type'     => $type,
            'terms'    => $terms,
            'termId'   => $termId,
            'batches'  => $batches,
            'batchId'  => $batchId,
            'rows'     => $rows,
            'title'    => ___('enrollment.report_' . $type),
        ]);
    }

    private function enrollmentRows($termId)
    {
        return Enrollment::with('student.person', 'program', 'term')
            ->where('track', Admission::TRACK_CHED)
            ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
            ->whereNotIn('status', Status::CLOSED_STATUSES)
            ->orderBy('program_id')->orderBy('year_level')
            ->get()
            ->map(fn (Enrollment $row) => [
                $row->student->student_code ?: '—',
                $row->student->person?->formal_name ?? trim($row->student->last_name . ', ' . $row->student->first_name),
                $row->program->name,
                $row->year_level ? ___('enrollment.year_' . $row->year_level) : '—',
                ___('enrollment.type_' . $row->student_type),
                Status::units($row->total_units),
                ___('enrollment.' . $row->status),
            ]);
    }

    private function clearanceRows($termId)
    {
        return Enrollment::with('student.person', 'program', 'term', 'batch')
            ->whereNotNull('assessed_at')
            ->whereNotIn('status', Status::CLOSED_STATUSES)
            ->when($termId, fn ($q, $id) => $q->where(fn ($w) => $w->where('term_id', $id)->orWhere('track', Admission::TRACK_TESDA)))
            ->orderByDesc('balance')
            ->get()
            ->map(fn (Enrollment $row) => [
                $row->student->student_code ?: $row->enrollment_no,
                $row->student->person?->formal_name ?? trim($row->student->last_name . ', ' . $row->student->first_name),
                $row->program->name,
                $row->batch->name ?? $row->term?->name,
                number_format((float) $row->net_total, 2),
                number_format((float) $row->scholarship_total, 2),
                number_format((float) $row->paid_total, 2),
                number_format((float) $row->balance, 2),
                $row->funding_outcome ? ___('enrollment.outcome_' . $row->funding_outcome) : '—',
                ___('enrollment.' . $row->status),
            ]);
    }

    private function batchRows($batchId)
    {
        if (! $batchId) {
            return collect();
        }

        return Enrollment::with('student.person', 'batch')
            ->where('batch_id', $batchId)
            ->whereNotIn('status', Status::CLOSED_STATUSES)
            ->orderBy('id')
            ->get()
            ->map(fn (Enrollment $row) => [
                $row->enrollment_no,
                $row->student->student_code ?: '—',
                $row->student->person?->formal_name ?? trim($row->student->last_name . ', ' . $row->student->first_name),
                $row->student->person?->uli ?: ___('enrollment.not_yet_recorded'),
                ___('enrollment.type_' . $row->student_type),
                $row->eligibility_met === null ? ___('enrollment.eligibility_not_recorded') : ($row->eligibility_met ? ___('enrollment.eligibility_met') : ___('enrollment.eligibility_not_met')),
                ___('enrollment.' . $row->status),
            ]);
    }
}
