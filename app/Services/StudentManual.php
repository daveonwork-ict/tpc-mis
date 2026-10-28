<?php

namespace App\Services;

use App\Enums\Admission;
use App\Models\Admissions\Application;
use App\Models\Enrollment\Enrollment;
use App\Models\Enrollment\EnrollmentCourse;
use App\Models\Enrollment\ReturnServiceAgreement;
use App\Models\Enrollment\Offering;
use App\Models\Enrollment\OfferingAttendance;
use App\Models\Enrollment\RetentionRecord;
use App\Models\StudentInfo\Student;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Rules taken from the 2026 Tarlac Provincial College Student Manual.
 * A figure is used here only when that manual states it.
 */
class StudentManual
{
    const GRADE_SCALE = ['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '4.00', '5.00'];

    const SECTORS = [
        'first_generation', 'underserved_area', 'indigenous', 'indigent_4ps',
        'solo_parent', 'adolescent_parent', 'pwd', 'displaced_or_pdl',
    ];

    const STANDINGS = ['regular', 'warning', 'probation', 'dismissed', 'disqualified'];

    const FRESHMAN_FLOOR = 80.0;

    const MIDYEAR_CAP = 9.0;

    const GRADUATING_CAP = 28.0;

    const SPECIAL_SEMESTER_CAP = 6.0;

    const SPECIAL_YEAR_CAP = 12.0;

    const PROBATION_LOAD = 15.0;

    const SCHOLARSHIP_PROGRAMS = [
        'pgt_tes',
        'honorific',
        'grant_in_aid',
        'assistantship',
        'loan',
        'leadership',
        'government_donor',
    ];

    const SCHOLARSHIP_LOADS = [15.0, 18.0];

    const SCHOLARSHIP_GWA = 2.5;

    public static function academicPoints(?string $average): ?int
    {
        if ($average === null || ! is_numeric($average)) {
            return null;
        }
        $gwa = (float) $average;
        if ($gwa < self::FRESHMAN_FLOOR || $gwa > 100) {
            return null;
        }
        if ($gwa >= 96) {
            return 20;
        }
        if ($gwa >= 91) {
            return 16;
        }
        if ($gwa >= 86) {
            return 12;
        }
        if ($gwa >= 83) {
            return 8;
        }

        return 4;
    }

    /**
     * @return array{academic: ?int, sector: int, tpccat: ?float, financial: ?float, total: ?float, ineligible: bool, reason: ?string}
     */
    public static function matrix(Application $application): array
    {
        $application->loadMissing('program');
        $floor = self::freshmanFloor($application);
        $average = is_numeric($application->general_average) ? (float) $application->general_average : null;
        $ineligible = false;
        $reason = null;

        if ($application->applicant_type === 'freshman') {
            if ($average === null) {
                $ineligible = true;
                $reason = 'gwa_required';
            } elseif ($average < $floor || $average > 100) {
                $ineligible = true;
                $reason = $average < self::FRESHMAN_FLOOR ? 'below_80' : ($average < $floor ? 'below_program_floor' : 'gwa_invalid');
            }
        }

        $academic = self::academicPoints($application->general_average);
        $categories = array_values(array_intersect((array) $application->sector_categories, self::SECTORS));
        $sector = ($application->sector_verified && $categories !== []) ? 15 : 0;
        $tpccat = $application->tpccat_points !== null ? (float) $application->tpccat_points : null;
        $financial = $application->financial_points !== null ? (float) $application->financial_points : null;
        $complete = $academic !== null && $tpccat !== null && $financial !== null && ! $ineligible;

        return [
            'academic'   => $academic,
            'sector'     => $sector,
            'tpccat'     => $tpccat,
            'financial'  => $financial,
            'total'      => $complete ? round($academic + $sector + $tpccat + $financial, 2) : null,
            'ineligible' => $ineligible,
            'reason'     => $reason,
            'floor'      => $floor,
        ];
    }

    public static function assertAdmissible(Application $application): void
    {
        if ($application->applicant_type !== 'freshman') {
            return;
        }
        $matrix = self::matrix($application);
        if ($matrix['ineligible']) {
            throw ValidationException::withMessages([
                'decision' => ___('admissions.matrix_' . $matrix['reason']),
            ]);
        }
    }

    public static function freshmanFloor(Application $application): float
    {
        $programFloor = $application->program?->minimum_gwa;

        return $programFloor !== null ? max(self::FRESHMAN_FLOOR, (float) $programFloor) : self::FRESHMAN_FLOOR;
    }

    public static function normalizeGrade(?string $grade): ?string
    {
        if ($grade === null || trim($grade) === '') {
            return null;
        }
        $value = strtoupper(trim($grade));
        if ($value === 'INC') {
            return 'INC';
        }
        if (! is_numeric($value)) {
            throw ValidationException::withMessages(['grades' => ___('enrollment.grade_scale')]);
        }
        $normalized = number_format((float) $value, 2, '.', '');
        if (! in_array($normalized, self::GRADE_SCALE, true)) {
            throw ValidationException::withMessages(['grades' => ___('enrollment.grade_scale')]);
        }

        return $normalized;
    }

    public static function resultFor(?string $grade): ?string
    {
        if ($grade === null) {
            return null;
        }
        if ($grade === 'INC' || $grade === '4.00') {
            return 'incomplete';
        }
        if ($grade === '5.00') {
            return 'failed';
        }

        return 'passed';
    }

    /**
     * @return array{blocked: bool, needs_reason: bool, message: ?string}
     */
    public static function loadCheck(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('program', 'term', 'student');
        $units = (float) $enrollment->total_units;
        $programMax = $enrollment->program->max_units;
        $blocked = false;
        $needsReason = false;
        $message = null;

        if ($enrollment->student_type === 'special') {
            if ($units > self::SPECIAL_SEMESTER_CAP) {
                $blocked = true;
                $message = ___('enrollment.special_semester_cap');
            }
            $yearUnits = self::specialYearUnits($enrollment);
            if ($yearUnits > self::SPECIAL_YEAR_CAP) {
                $blocked = true;
                $message = ___('enrollment.special_year_cap');
            }
        }

        if ($enrollment->term?->semester === 'midyear' && $units > self::MIDYEAR_CAP) {
            $blocked = true;
            $message = ___('enrollment.midyear_cap');
        }

        if ($enrollment->is_graduating && $units > self::GRADUATING_CAP) {
            $blocked = true;
            $message = ___('enrollment.graduating_cap');
        }

        $standing = $enrollment->student->academic_standing ?? 'regular';
        if (in_array($standing, ['dismissed', 'disqualified'], true)) {
            $blocked = true;
            $message = ___('enrollment.retention_' . $standing);
        } elseif ($standing === 'probation') {
            $cap = $programMax !== null ? min(self::PROBATION_LOAD, (float) $programMax) : self::PROBATION_LOAD;
            if ($units > $cap) {
                $blocked = true;
                $message = ___('enrollment.retention_probation_load') . ' (' . \App\Enums\Enrollment::units($cap) . ')';
            }
        } elseif ($standing === 'warning' && $programMax !== null && $units > (float) $programMax - 3) {
            $blocked = true;
            $message = ___('enrollment.retention_warning_load') . ' (' . \App\Enums\Enrollment::units((float) $programMax - 3) . ')';
        }

        if (! $blocked && $programMax !== null && $units > (float) $programMax) {
            if ($enrollment->is_graduating && $units <= self::GRADUATING_CAP) {
                $needsReason = true;
                $message = ___('enrollment.over_max_units');
            } else {
                $blocked = true;
                $message = ___('enrollment.only_graduating_may_overload');
            }
        }

        return ['blocked' => $blocked, 'needs_reason' => $needsReason, 'message' => $message];
    }

    public static function specialYearUnits(Enrollment $enrollment): float
    {
        $sessionId = $enrollment->term?->session_id;
        if (! $sessionId) {
            return (float) $enrollment->total_units;
        }

        $others = EnrollmentCourse::query()
            ->where('status', 'enlisted')
            ->whereHas('enrollment', function ($q) use ($enrollment, $sessionId) {
                $q->where('student_id', $enrollment->student_id)
                    ->where('id', '!=', $enrollment->id)
                    ->where('student_type', 'special')
                    ->whereNotIn('status', \App\Enums\Enrollment::CLOSED_STATUSES)
                    ->whereHas('term', fn ($t) => $t->where('session_id', $sessionId));
            })
            ->get()
            ->sum(fn ($course) => (float) $course->lecture_units + (float) $course->lab_units);

        return $others + (float) $enrollment->total_units;
    }

    /**
     * @return array{meetings: int, absences: int, excused: int, rate: ?float, drop: bool, failing: bool}|null
     */
    public static function absence(Offering $offering, int $studentId): ?array
    {
        $offering->loadMissing('term', 'schedules');
        $term = $offering->term;
        $days = $offering->schedules->pluck('day')->unique()->filter()->values();
        if (! $term?->start_date || ! $term->end_date || $days->isEmpty()) {
            return null;
        }

        $meetings = 0;
        for ($day = $term->start_date->copy(); $day->lte($term->end_date); $day->addDay()) {
            if ($days->contains($day->dayOfWeekIso)) {
                $meetings++;
            }
        }
        if ($meetings === 0) {
            return null;
        }

        $marks = OfferingAttendance::where('offering_id', $offering->id)->where('student_id', $studentId)->get();
        $absent = $marks->where('status', 'absent')->count();
        $excused = $marks->where('status', 'excused')->count();
        $heavyLate = $marks->filter(fn ($mark) => $mark->status === 'late' && (int) $mark->minutes_late >= 15)->count();
        $unexcused = $absent + intdiv($heavyLate, 3);
        $absences = $unexcused + $excused;

        return [
            'meetings' => $meetings,
            'absences' => $absences,
            'excused'  => $excused,
            'rate'     => $absences / $meetings,
            'drop'     => ($absences / $meetings) >= 0.20,
            'failing'  => $unexcused > $excused,
        ];
    }

    public static function closeExpiredRemoval(EnrollmentCourse $course): void
    {
        if ($course->final_grade !== '4.00' || ! $course->removal_on) {
            return;
        }
        if (Carbon::parse($course->removal_on)->startOfDay()->gte(now()->startOfDay())) {
            return;
        }
        $course->update([
            'final_grade' => '5.00',
            'result'      => 'failed',
            'removal_on'  => null,
        ]);
    }

    public static function agreementReady(?ReturnServiceAgreement $agreement): bool
    {
        if (! $agreement) {
            return false;
        }
        if ($agreement->waived_on) {
            return true;
        }

        return $agreement->student_signed_on && $agreement->guardian_signed_on && $agreement->president_signed_on;
    }

    /**
     * Each academic year enrolled is half a year of return service.
     *
     * @return array{years: int, service: float, this_year_counted: bool}
     */
    public static function serviceObligation(Enrollment $enrollment): array
    {
        $sessions = Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('track', Admission::TRACK_CHED)
            ->where('status', \App\Enums\Enrollment::ENROLLED)
            ->whereHas('term', fn ($query) => $query->whereNotNull('session_id'))
            ->with('term:id,session_id')
            ->get()
            ->pluck('term.session_id')
            ->filter()
            ->unique();

        $sessionId = $enrollment->term?->session_id;

        return [
            'years'              => $sessions->count(),
            'service'            => $sessions->count() * 0.5,
            'this_year_counted'  => $sessionId && $sessions->contains($sessionId),
        ];
    }

    /**
     * Recompute standing from every finished college term, in order.
     * A term still carrying a 4.00 or INC is not finished.
     */
    public static function refreshRetention(int $studentId): void
    {
        EnrollmentCourse::query()
            ->where('final_grade', '4.00')
            ->whereNotNull('removal_on')
            ->whereHas('enrollment', fn ($query) => $query->where('student_id', $studentId)->where('status', \App\Enums\Enrollment::ENROLLED))
            ->get()
            ->each(fn ($course) => self::closeExpiredRemoval($course));

        $enrollments = Enrollment::with('term', 'courses')
            ->where('student_id', $studentId)
            ->where('track', Admission::TRACK_CHED)
            ->where('status', \App\Enums\Enrollment::ENROLLED)
            ->where('credit_waived', false)
            ->get()
            ->sortBy(fn ($enrollment) => self::termSort($enrollment))
            ->values();

        $kept = [];
        $previousStanding = 'regular';
        $previousBand = null;

        foreach ($enrollments as $enrollment) {
            $term = self::termFailure($enrollment);
            if ($term === null) {
                continue;
            }
            $standing = self::standingAfter($term, $previousStanding, $previousBand === 'warning');
            RetentionRecord::updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'student_id'      => $studentId,
                    'enrolled_units'  => $term['units'],
                    'failed_units'    => $term['failed'],
                    'failure_percent' => round($term['rate'] * 100, 2),
                    'band'            => $term['band'],
                    'standing'        => $standing,
                ]
            );
            $kept[] = $enrollment->id;
            $previousBand = $term['band'];
            $previousStanding = $standing;
        }

        RetentionRecord::where('student_id', $studentId)->whereNotIn('enrollment_id', $kept)->delete();

        if ($kept !== []) {
            Student::whereKey($studentId)->update(['academic_standing' => $previousStanding]);
        }
    }

    /**
     * @return array{units: float, failed: float, rate: float, band: ?string, all_passed: bool}|null
     */
    public static function termFailure(Enrollment $enrollment): ?array
    {
        $courses = $enrollment->courses->where('status', 'enlisted');
        if ($courses->isEmpty()) {
            return null;
        }
        if ($courses->contains(fn ($course) => ! in_array($course->result, ['passed', 'failed'], true))) {
            return null;
        }

        $units = (float) $courses->sum(fn ($course) => (float) $course->lecture_units + (float) $course->lab_units);
        if ($units <= 0) {
            return null;
        }
        $failed = (float) $courses
            ->where('result', 'failed')
            ->sum(fn ($course) => (float) $course->lecture_units + (float) $course->lab_units);
        $rate = $failed / $units;
        $percent = round($rate * 100, 2);
        $band = null;
        if ($percent >= 100) {
            $band = 'disqualified';
        } elseif ($percent >= 76 && $units >= 9) {
            $band = 'dismissed';
        } elseif ($percent >= 50 && $percent <= 75 && $units >= 6) {
            $band = 'probation';
        } elseif ($percent >= 25 && $percent < 50) {
            $band = 'warning';
        }

        return [
            'units'      => $units,
            'failed'     => $failed,
            'rate'       => $rate,
            'band'       => $band,
            'all_passed' => $failed <= 0,
        ];
    }

    public static function standingAfter(array $term, string $previousStanding, bool $previousWarning): string
    {
        if ($term['band'] === 'disqualified') {
            return 'disqualified';
        }
        if ($previousStanding === 'probation' && round($term['rate'] * 100, 2) >= 50) {
            return 'dismissed';
        }
        if ($term['band'] === 'dismissed') {
            return 'dismissed';
        }
        if ($previousStanding === 'probation') {
            return 'probation';
        }
        if ($term['band'] === 'warning' && $previousWarning) {
            return 'probation';
        }
        if ($term['band'] !== null) {
            return $term['band'];
        }

        return 'regular';
    }

    /**
     * The grant is for the semester being enrolled. The conditions are read from the
     * college term immediately before it. A 1.00 is the best general average.
     *
     * @return array{qualified: bool, reasons: array<int, string>, enrollment: ?Enrollment, units: ?float, gwa: ?float}
     */
    public static function scholarshipEligibility(Enrollment $enrollment): array
    {
        $empty = ['qualified' => false, 'reasons' => ['no_previous'], 'enrollment' => null, 'units' => null, 'gwa' => null];
        if ($enrollment->isTrainee() || $enrollment->credit_waived) {
            return $empty;
        }

        $enrollment->loadMissing('term');
        $current = self::termSort($enrollment);
        $previous = Enrollment::with('term', 'courses')
            ->where('student_id', $enrollment->student_id)
            ->where('id', '!=', $enrollment->id)
            ->where('track', Admission::TRACK_CHED)
            ->where('status', \App\Enums\Enrollment::ENROLLED)
            ->where('credit_waived', false)
            ->get()
            ->filter(fn ($row) => self::termSort($row) < $current)
            ->sortBy(fn ($row) => self::termSort($row))
            ->last();

        if (! $previous) {
            return $empty;
        }

        $courses = $previous->courses->where('status', 'enlisted');
        $units = round((float) $courses->sum(fn ($course) => (float) $course->lecture_units + (float) $course->lab_units), 1);
        $reasons = [];
        $loadMatches = false;
        foreach (self::SCHOLARSHIP_LOADS as $load) {
            if (abs($units - $load) < 0.05) {
                $loadMatches = true;
            }
        }
        if (! $loadMatches) {
            $reasons[] = 'units';
        }

        $clean = true;
        $weighted = 0.0;
        $gradedUnits = 0.0;
        foreach ($courses as $course) {
            $grade = strtoupper(trim((string) $course->final_grade));
            $bad = in_array($grade, ['4.00', '5.00', 'INC'], true)
                || in_array($course->result, ['failed', 'incomplete'], true);
            if ($bad || ! in_array($course->result, ['passed'], true) || ! is_numeric($course->final_grade)) {
                $clean = false;
                if ($bad) {
                    $reasons[] = 'grade';
                } else {
                    $reasons[] = 'unfinished';
                }
                continue;
            }
            $courseUnits = (float) $course->lecture_units + (float) $course->lab_units;
            if ($courseUnits <= 0) {
                continue;
            }
            $weighted += (float) $course->final_grade * $courseUnits;
            $gradedUnits += $courseUnits;
        }
        $reasons = array_values(array_unique($reasons));

        $gwa = $clean && $gradedUnits > 0 ? round($weighted / $gradedUnits, 2) : null;
        if ($clean && ($gwa === null || $gwa > self::SCHOLARSHIP_GWA)) {
            $reasons[] = 'gwa';
        }

        return [
            'qualified'  => $reasons === [],
            'reasons'    => $reasons,
            'enrollment' => $previous,
            'units'      => $units,
            'gwa'        => $gwa,
        ];
    }

    private static function termSort(Enrollment $enrollment): string
    {
        $rank = ['first' => 1, 'second' => 2, 'midyear' => 3][$enrollment->term->semester ?? ''] ?? 9;

        return sprintf('%08d-%d-%08d', (int) ($enrollment->term->session_id ?? 0), $rank, (int) $enrollment->term_id);
    }
}
