<?php

namespace App\Http\Requests\Admissions;

use App\Enums\Admission;
use App\Http\Controllers\Admissions\Concerns\NullsBlankInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationRequest extends FormRequest
{
    use NullsBlankInput;

    const PERSON_FIELDS = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'sex', 'date_of_birth', 'place_of_birth',
        'civil_status', 'nationality', 'email', 'mobile', 'address_line', 'barangay', 'city_municipality',
        'province', 'zip_code', 'lrn', 'uli',
    ];

    const APPLICATION_FIELDS = [
        'track', 'program_id', 'applicant_type', 'term_id', 'preferred_schedule',
        'last_school', 'last_school_address', 'last_school_year', 'last_program_or_strand', 'general_average',
        'highest_attainment', 'employment_status', 'guardian_name', 'guardian_relationship', 'guardian_mobile',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function isPublic(): bool
    {
        return $this->routeIs('frontend.*');
    }

    public function rules(): array
    {
        $public = $this->isPublic();
        $track = $this->input('track');

        $program = Rule::exists('programs', 'id')->where('track', $track)->where('status', 1);
        $term = Rule::exists('terms', 'id')->where('status', 1);
        if ($public) {
            $program->where('accepting_applications', 1);
            $term->where('admission_open', 1);
        }

        return [
            'first_name'        => ['required', 'string', 'max:100'],
            'middle_name'       => ['nullable', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'suffix'            => ['nullable', 'string', 'max:20'],
            'sex'               => ['required', Rule::in(Admission::SEX)],
            'date_of_birth'     => ['required', 'date', 'before:today'],
            'place_of_birth'    => ['nullable', 'string', 'max:150'],
            'civil_status'      => ['nullable', Rule::in(Admission::CIVIL_STATUSES)],
            'nationality'       => ['nullable', 'string', 'max:60'],
            'email'             => [$public ? 'required' : 'nullable', 'email', 'max:150'],
            'mobile'            => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'address_line'      => ['nullable', 'string', 'max:190'],
            'barangay'          => ['required', 'string', 'max:100'],
            'city_municipality' => ['required', 'string', 'max:100'],
            'province'          => ['required', 'string', 'max:100'],
            'zip_code'          => ['nullable', 'string', 'max:10'],
            'lrn'               => ['nullable', 'digits:12'],
            'uli'               => ['nullable', 'string', 'max:30'],

            'track'                  => ['required', Rule::in(Admission::TRACKS)],
            'program_id'             => ['required', $program],
            'applicant_type'         => ['required', Rule::in(Admission::applicantTypes($track))],
            'term_id'                => [Rule::requiredIf($track === Admission::TRACK_CHED), 'nullable', $term],
            'preferred_schedule'     => ['nullable', 'string', 'max:190'],
            'last_school'            => ['nullable', 'string', 'max:190'],
            'last_school_address'    => ['nullable', 'string', 'max:190'],
            'last_school_year'       => ['nullable', 'string', 'max:20'],
            'last_program_or_strand' => ['nullable', 'string', 'max:150'],
            'general_average'        => ['nullable', 'string', 'max:20'],
            'highest_attainment'     => ['nullable', 'string', 'max:150'],
            'employment_status'      => ['nullable', 'string', 'max:100'],
            'guardian_name'          => ['nullable', 'string', 'max:150'],
            'guardian_relationship'  => ['nullable', 'string', 'max:60'],
            'guardian_mobile'        => ['nullable', 'string', 'max:30'],

            'privacy_consent'   => [$public ? 'accepted' : 'nullable'],
            'documents'         => ['nullable', 'array'],
            'documents.*'       => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'website'           => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.exists'        => ___('admissions.program_not_available'),
            'term_id.exists'           => ___('admissions.term_not_open'),
            'term_id.required'         => ___('admissions.term_required'),
            'privacy_consent.accepted' => ___('admissions.consent_required'),
            'documents.*.mimes'        => ___('admissions.document_types_allowed'),
            'documents.*.max'          => ___('admissions.document_size_limit'),
        ];
    }

    public function attributes(): array
    {
        return collect(array_merge(self::PERSON_FIELDS, self::APPLICATION_FIELDS))
            ->mapWithKeys(fn ($field) => [$field => mb_strtolower(___('admissions.' . $field))])
            ->all();
    }

    public function personData(): array
    {
        return $this->nullBlanks(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $this->only(self::PERSON_FIELDS)));
    }

    public function applicationData(): array
    {
        $data = $this->nullBlanks($this->only(self::APPLICATION_FIELDS));
        if ($data['track'] !== Admission::TRACK_CHED) {
            $data['term_id'] = null;
        }

        return $data;
    }
}
