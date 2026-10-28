<?php

namespace App\Enums;

class Admission
{
    const TRACK_CHED  = 'ched';
    const TRACK_TESDA = 'tesda';

    const TRACKS = [self::TRACK_CHED, self::TRACK_TESDA];

    const APPLICANT_TYPES = [
        self::TRACK_CHED  => ['freshman', 'transferee', 'returnee', 'shiftee', 'second_degree', 'cross_enrollee', 'foreign', 'special'],
        self::TRACK_TESDA => ['new_trainee', 'returning_trainee'],
    ];

    const SEMESTERS = ['first', 'second', 'midyear'];

    const SUBMITTED            = 'submitted';
    const REQUIREMENTS_PENDING = 'requirements_pending';
    const FOR_EVALUATION       = 'for_evaluation';
    const ADMITTED             = 'admitted';
    const CONDITIONAL          = 'conditional';
    const REJECTED             = 'rejected';
    const WITHDRAWN            = 'withdrawn';
    const DEFERRED             = 'deferred';

    const APPLICATION_STATUSES = [
        self::SUBMITTED, self::REQUIREMENTS_PENDING, self::FOR_EVALUATION,
        self::ADMITTED, self::CONDITIONAL, self::REJECTED, self::WITHDRAWN, self::DEFERRED,
    ];

    const CLOSED_STATUSES = [self::ADMITTED, self::REJECTED, self::WITHDRAWN, self::DEFERRED];

    const REQ_PENDING   = 'pending';
    const REQ_SUBMITTED = 'submitted';
    const REQ_VERIFIED  = 'verified';
    const REQ_REJECTED  = 'rejected';
    const REQ_WAIVED    = 'waived';

    const REQUIREMENT_STATUSES = [self::REQ_PENDING, self::REQ_SUBMITTED, self::REQ_VERIFIED, self::REQ_REJECTED, self::REQ_WAIVED];

    const SCREENING_TYPES   = ['eligibility', 'interview', 'exam'];
    const SCREENING_RESULTS = ['passed', 'failed', 'pending'];

    const DECISIONS = ['accepted', 'conditional', 'pending', 'rejected'];

    const INQUIRY_CHANNELS = ['walk_in', 'online', 'phone', 'facebook', 'referral', 'other'];
    const INQUIRY_STATUSES = ['new', 'contacted', 'interested', 'converted', 'closed'];

    const SEX = ['male', 'female'];
    const CIVIL_STATUSES = ['single', 'married', 'widowed', 'separated'];

    public static function applicantTypes(?string $track = null): array
    {
        if ($track) {
            return self::APPLICANT_TYPES[$track] ?? [];
        }

        return array_merge(...array_values(self::APPLICANT_TYPES));
    }

    public static function badge(string $status): string
    {
        return match ($status) {
            self::ADMITTED, self::REQ_VERIFIED, 'accepted', 'passed', 'converted' => 'success',
            self::CONDITIONAL, self::REQUIREMENTS_PENDING, self::REQ_SUBMITTED, 'contacted', 'interested' => 'warning',
            self::REJECTED, self::REQ_REJECTED, 'failed' => 'danger',
            self::WITHDRAWN, self::DEFERRED, self::REQ_WAIVED, 'closed' => 'secondary',
            default => 'info',
        };
    }
}
