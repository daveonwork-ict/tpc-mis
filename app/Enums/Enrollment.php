<?php

namespace App\Enums;

class Enrollment
{
    const ADVISING       = 'advising';
    const FOR_ASSESSMENT = 'for_assessment';
    const ASSESSED       = 'assessed';
    const CLEARED        = 'cleared';
    const ENROLLED       = 'enrolled';
    const CANCELLED      = 'cancelled';
    const WITHDRAWN      = 'withdrawn';

    const STATUSES = [self::ADVISING, self::FOR_ASSESSMENT, self::ASSESSED, self::CLEARED, self::ENROLLED, self::CANCELLED, self::WITHDRAWN];

    const CLOSED_STATUSES = [self::CANCELLED, self::WITHDRAWN];

    const STUDENT_TYPES = ['new', 'continuing', 'transferee', 'returnee', 'shiftee', 'second_degree', 'cross_enrollee', 'foreign', 'special'];

    const APPLICANT_TO_STUDENT_TYPE = [
        'freshman'       => 'new',
        'transferee'     => 'transferee',
        'returnee'       => 'returnee',
        'shiftee'        => 'shiftee',
        'second_degree'  => 'second_degree',
        'cross_enrollee' => 'cross_enrollee',
        'foreign'        => 'foreign',
        'special'        => 'special',
    ];

    const YEAR_LEVELS = [1, 2, 3, 4, 5];

    const CURRICULUM_STATUSES = ['draft', 'active', 'retired'];

    const REQUISITE_TYPES = ['prerequisite', 'corequisite'];

    const OFFERING_STATUSES = ['open', 'closed', 'dissolved'];

    const DAYS = [1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun'];

    const SCHEDULE_TYPES = ['lecture', 'lab'];

    const RESULTS = ['passed', 'failed', 'incomplete', 'dropped'];

    const SCHOLARSHIP_PROGRAMS = ['tes', 'twsp', 'step', 'uaqtea', 'other'];

    const FEE_CATEGORIES = ['tuition', 'laboratory', 'miscellaneous', 'other'];

    const FEE_BASES = ['per_unit', 'per_lab_unit', 'per_lab_course', 'fixed'];

    /** Issues an adviser may accept with a written reason; anything else blocks the course outright. */
    const OVERRIDABLE_ISSUES = ['already_passed', 'prerequisite', 'corequisite', 'conflict', 'full', 'not_in_curriculum', 'other_program'];

    const STUDENT_NUMBER_FORMAT = '{YYYY}-{SEQ}';
    const STUDENT_NUMBER_PADDING = 5;

    public static function badge(string $status): string
    {
        return match ($status) {
            self::ENROLLED, 'active', 'open', 'passed' => 'success',
            self::CLEARED => 'info',
            self::FOR_ASSESSMENT, self::ASSESSED, 'draft', 'incomplete', 'closed' => 'warning',
            self::CANCELLED, 'failed', 'dissolved' => 'danger',
            self::WITHDRAWN, 'retired', 'dropped' => 'secondary',
            default => 'info',
        };
    }

    public static function units($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');
    }
}
