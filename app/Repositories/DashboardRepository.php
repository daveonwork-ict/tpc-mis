<?php

namespace App\Repositories;

use App\Enums\Admission;
use App\Enums\Enrollment as EnrollmentStatus;
use App\Interfaces\DashboardInterface;
use App\Models\Admissions\Application;
use App\Models\Admissions\Inquiry;
use App\Models\Admissions\Term;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\TrainingBatch;
use App\Models\Event;
use App\Models\NoticeBoard;
use App\Models\Session;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\Student;

class DashboardRepository implements DashboardInterface
{
    public function index()
    {
        $terms = Term::with('session')->where('status', 1)
            ->orderBy('session_id')->orderByRaw("FIELD(semester, 'first', 'second', 'midyear')")
            ->get();
        $enrollmentTerm = $terms->firstWhere('enrollment_open', true);
        $session = Session::find(setting('session'));

        return [
            'session'         => $session,
            'admission_terms' => $terms->where('admission_open', true)->values(),
            'enrollment_term' => $enrollmentTerm,
            'kpi'             => $this->kpis($enrollmentTerm),
            'pipeline'        => $this->pipeline($session),
            'enrollment'      => $enrollmentTerm ? $this->enrollment($enrollmentTerm) : null,
            'offerings'       => $enrollmentTerm ? $this->offerings($enrollmentTerm) : null,
            'batches'         => $this->trainingBatches(),
            'applications'    => Application::with('person', 'program')->latest('id')->take(6)->get(),
            'follow_ups'      => Inquiry::with('program')
                ->whereIn('status', ['new', 'contacted', 'interested'])
                ->orderByRaw('follow_up_on IS NULL')->orderBy('follow_up_on')->latest('id')
                ->take(5)->get(),
            'events'          => Event::active()->where('date', '>=', date('Y-m-d'))->orderBy('date')->take(4)->get(),
            'notices'         => NoticeBoard::where('status', 1)->latest('publish_date')->latest('id')->take(4)->get(),
        ];
    }

    private function kpis(?Term $enrollmentTerm): array
    {
        return [
            'applications_in_process' => Application::whereNotIn('status', Admission::CLOSED_STATUSES)->count(),
            'awaiting_enrollment'     => Application::whereIn('status', [Admission::ADMITTED, Admission::CONDITIONAL])
                ->whereDoesntHave('enrollments')->count(),
            'enrolled'                => $enrollmentTerm
                ? Enrollment::where('term_id', $enrollmentTerm->id)->where('status', EnrollmentStatus::ENROLLED)->count()
                : null,
            'students'                => Student::where('status', 1)->count(),
            'staff'                   => Staff::where('status', 1)->count(),
            'new_inquiries'           => Inquiry::where('status', 'new')->count(),
        ];
    }

    /** College applications for terms in the active academic year, plus TESDA applications filed within it. */
    private function pipeline(?Session $session): array
    {
        $counts = Application::query()
            ->when($session, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('term', fn ($t) => $t->where('session_id', $session->id))
                ->orWhere(fn ($t) => $t->whereNull('term_id')
                    ->whereBetween('created_at', [$session->start_date, $session->end_date.' 23:59:59']))))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')
            ->pluck('total', 'status');

        $rows = [];
        foreach (Admission::APPLICATION_STATUSES as $status) {
            $rows[$status] = (int) ($counts[$status] ?? 0);
        }

        return ['rows' => $rows, 'total' => array_sum($rows), 'max' => max($rows ?: [0])];
    }

    private function enrollment(Term $term): array
    {
        $counts = Enrollment::where('term_id', $term->id)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [];
        foreach (EnrollmentStatus::STATUSES as $status) {
            $statuses[$status] = (int) ($counts[$status] ?? 0);
        }

        $byProgram = Enrollment::with('program')
            ->where('term_id', $term->id)
            ->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)
            ->selectRaw("program_id, SUM(status = 'enrolled') as enrolled, SUM(status <> 'enrolled') as in_progress")
            ->groupBy('program_id')
            ->orderByDesc('enrolled')
            ->get();

        return [
            'statuses'    => $statuses,
            'in_progress' => $statuses[EnrollmentStatus::ADVISING] + $statuses[EnrollmentStatus::FOR_ASSESSMENT] + $statuses[EnrollmentStatus::ASSESSED] + $statuses[EnrollmentStatus::CLEARED],
            'by_program'  => $byProgram,
        ];
    }

    private function trainingBatches(): array
    {
        $open = TrainingBatch::open()
            ->withCount(['enrollments as seats_taken' => fn ($q) => $q->whereNotIn('status', EnrollmentStatus::CLOSED_STATUSES)])
            ->get();

        return [
            'open'  => $open->count(),
            'slots' => (int) $open->sum('slots'),
            'taken' => (int) $open->sum('seats_taken'),
        ];
    }

    private function offerings(Term $term): array
    {
        $offerings = Offering::where('term_id', $term->id)
            ->where('status', 'open')
            ->withCount(['activeSeats as seats'])
            ->get(['id', 'capacity']);

        $capped = $offerings->whereNotNull('capacity');
        $capacity = (int) $capped->sum('capacity');
        $taken = (int) $capped->sum('seats');

        return [
            'open'      => $offerings->count(),
            'sections'  => Offering::where('term_id', $term->id)->where('status', 'open')->distinct()->count('section_code'),
            'capacity'  => $capacity,
            'taken'     => $taken,
            'fill'      => $capacity > 0 ? (int) round($taken / $capacity * 100) : null,
            'full'      => $capped->filter(fn ($o) => $o->seats >= $o->capacity)->count(),
            'enlisted'  => (int) $offerings->sum('seats'),
        ];
    }
}
