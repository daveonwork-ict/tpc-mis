<?php

namespace App\Services\Admissions;

use App\Enums\Admission;
use App\Models\Admissions\AdmissionDecision;
use App\Services\StudentManual;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationRequirement;
use App\Models\Admissions\ApplicationScreening;
use App\Models\Admissions\ApplicationStatusLog;
use App\Models\Admissions\Inquiry;
use App\Models\Admissions\Person;
use App\Models\Admissions\RequirementRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    const DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $files  keyed by requirement_type_id
     */
    public function submit(array $person, array $application, array $files, string $source, ?int $userId = null, ?int $inquiryId = null): Application
    {
        return DB::transaction(function () use ($person, $application, $files, $source, $userId, $inquiryId) {
            $record = $this->resolvePerson($person, $source !== 'online');

            $this->guardDuplicate($record, $application);

            $app = Application::create($application + [
                'person_id'          => $record->id,
                'status'             => Admission::SUBMITTED,
                'source'             => $source,
                'submitted_at'       => now(),
                'privacy_consent_at' => now(),
                'encoded_by'         => $userId,
                'inquiry_id'         => $inquiryId,
            ]);
            $app->application_no = sprintf('TPC-%s-%06d', now()->format('Y'), $app->id);
            $app->save();

            $this->buildChecklist($app);
            $this->log($app, null, Admission::SUBMITTED, 'submitted', null, $userId);

            foreach ($app->requirements()->get() as $requirement) {
                if (isset($files[$requirement->requirement_type_id])) {
                    $this->storeFile($requirement, $files[$requirement->requirement_type_id], $userId, false);
                }
            }

            if ($inquiryId) {
                Inquiry::whereKey($inquiryId)->update([
                    'status'         => 'converted',
                    'application_id' => $app->id,
                    'person_id'      => $record->id,
                ]);
            }

            $this->refreshStatus($app->fresh(), $userId);

            return $app->fresh();
        });
    }

    public function resolvePerson(array $data, bool $mayUpdate): Person
    {
        $existing = Person::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->first();

        if (! $existing) {
            return Person::create($data);
        }

        // Public submissions never overwrite an existing identity; staff confirm changes in the admin.
        if ($mayUpdate) {
            $existing->fill(array_filter($data, fn ($value) => $value !== null && $value !== ''))->save();
        }

        return $existing;
    }

    public function buildChecklist(Application $app): void
    {
        $rules = RequirementRule::applyingTo($app->track, $app->applicant_type, $app->program_id)->get();

        foreach ($rules->groupBy('requirement_type_id') as $typeId => $group) {
            ApplicationRequirement::firstOrCreate(
                ['application_id' => $app->id, 'requirement_type_id' => $typeId],
                ['is_required' => $group->contains('is_required', true), 'status' => Admission::REQ_PENDING]
            );
        }
    }

    /**
     * After a program or applicant-type change: adds newly required items and drops untouched ones that no longer apply.
     */
    public function syncChecklist(Application $app, int $userId): void
    {
        $applicable = RequirementRule::applyingTo($app->track, $app->applicant_type, $app->program_id)
            ->pluck('requirement_type_id')->unique();

        $app->requirements()
            ->whereNotIn('requirement_type_id', $applicable)
            ->where('status', Admission::REQ_PENDING)
            ->whereNull('file_path')
            ->delete();

        $this->buildChecklist($app);
        $this->log($app, $app->status, $app->status, 'details_updated', null, $userId);
        $this->refreshStatus($app->fresh(), $userId);
    }

    public function storeFile(ApplicationRequirement $requirement, UploadedFile $file, ?int $userId = null, bool $log = true): void
    {
        $this->guardOpen($requirement->application);

        if ($requirement->status === Admission::REQ_VERIFIED) {
            throw ValidationException::withMessages(['file' => ___('admissions.already_verified')]);
        }

        if ($requirement->file_path) {
            Storage::disk(self::DISK)->delete($requirement->file_path);
        }

        $requirement->update([
            'file_path'     => $file->store('admissions/' . $requirement->application_id, self::DISK),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'status'        => Admission::REQ_SUBMITTED,
            'submitted_at'  => now(),
            'reviewed_by'   => null,
            'reviewed_at'   => null,
        ]);

        if ($log) {
            $app = $requirement->application;
            $this->log($app, $app->status, $app->status, 'requirement_uploaded', $requirement->requirementType->name, $userId);
            $this->refreshStatus($app->fresh(), $userId);
        }
    }

    public function reviewRequirement(ApplicationRequirement $requirement, string $status, ?string $remarks, int $userId): void
    {
        $app = $requirement->application;
        $this->guardOpen($app);

        if (in_array($status, [Admission::REQ_REJECTED, Admission::REQ_WAIVED], true) && blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => ___('admissions.remarks_required_for_' . $status)]);
        }

        if ($status === Admission::REQ_WAIVED && ! $requirement->is_required) {
            throw ValidationException::withMessages(['remarks' => ___('admissions.only_required_items_can_be_waived')]);
        }

        $requirement->update([
            'status'      => $status,
            'remarks'     => $remarks,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);

        $this->log($app, $app->status, $app->status, 'requirement_' . $status,
            $requirement->requirementType->name . ($remarks ? ' — ' . $remarks : ''), $userId);

        $this->refreshStatus($app->fresh(), $userId);
    }

    public function recordScreening(Application $app, array $data, int $userId): ApplicationScreening
    {
        $this->guardOpen($app);

        $screening = ApplicationScreening::create($data + [
            'application_id' => $app->id,
            'screened_by'    => $userId,
        ]);

        $this->log($app, $app->status, $app->status, 'screening_recorded',
            ___('admissions.screening_' . $data['type']) . ': ' . ___('admissions.' . $data['result']), $userId);

        return $screening;
    }

    public function decide(Application $app, string $decision, ?string $conditions, ?string $reason, int $userId): void
    {
        $decidable = [Admission::REQUIREMENTS_PENDING, Admission::FOR_EVALUATION, Admission::CONDITIONAL];
        if (! in_array($app->status, $decidable, true)) {
            throw ValidationException::withMessages(['decision' => ___('admissions.not_ready_for_decision')]);
        }

        if (in_array($decision, ['accepted', 'conditional'], true)) {
            StudentManual::assertAdmissible($app);
        }

        if ($decision === 'accepted' && ! $app->requirementsComplete()) {
            throw ValidationException::withMessages(['decision' => ___('admissions.accept_needs_complete_requirements')]);
        }
        if ($decision === 'conditional' && blank($conditions)) {
            throw ValidationException::withMessages(['conditions' => ___('admissions.conditions_required')]);
        }
        if ($decision === 'rejected' && blank($reason)) {
            throw ValidationException::withMessages(['reason' => ___('admissions.reason_required')]);
        }

        DB::transaction(function () use ($app, $decision, $conditions, $reason, $userId) {
            AdmissionDecision::create([
                'application_id' => $app->id,
                'decision'       => $decision,
                'conditions'     => $conditions,
                'reason'         => $reason,
                'decided_by'     => $userId,
            ]);

            $to = match ($decision) {
                'accepted'    => Admission::ADMITTED,
                'conditional' => Admission::CONDITIONAL,
                'rejected'    => Admission::REJECTED,
                default       => $app->status,
            };

            if ($decision !== 'pending') {
                $app->decided_at = now();
            }

            $this->transition($app, $to, 'decision_' . $decision, $conditions ?: $reason, $userId);
        });
    }

    public function withdraw(Application $app, ?string $remarks, int $userId): void
    {
        $this->guardOpen($app);
        $this->transition($app, Admission::WITHDRAWN, 'withdrawn', $remarks, $userId);
    }

    public function defer(Application $app, ?string $remarks, int $userId): void
    {
        $this->guardOpen($app);
        $this->transition($app, Admission::DEFERRED, 'deferred', $remarks, $userId);
    }

    public function reopen(Application $app, ?string $remarks, int $userId): void
    {
        if (! in_array($app->status, [Admission::WITHDRAWN, Admission::DEFERRED], true)) {
            throw ValidationException::withMessages(['status' => ___('admissions.only_withdrawn_or_deferred_can_reopen')]);
        }

        $this->transition($app, Admission::SUBMITTED, 'reopened', $remarks, $userId);
        $this->refreshStatus($app->fresh(), $userId);
    }

    /**
     * Moves an application between "requirements pending" and "for evaluation" as checklist items change.
     */
    public function refreshStatus(Application $app, ?int $userId = null): void
    {
        $auto = [Admission::SUBMITTED, Admission::REQUIREMENTS_PENDING, Admission::FOR_EVALUATION];
        if (! in_array($app->status, $auto, true)) {
            return;
        }

        $to = $app->load('requirements')->requirementsComplete() ? Admission::FOR_EVALUATION : Admission::REQUIREMENTS_PENDING;

        if ($to !== $app->status) {
            $this->transition($app, $to, 'checklist_updated', null, $userId);
        }
    }

    private function transition(Application $app, string $to, string $action, ?string $remarks, ?int $userId): void
    {
        $from = $app->status;
        $app->status = $to;
        $app->save();

        $this->log($app, $from, $to, $action, $remarks, $userId);
    }

    private function log(Application $app, ?string $from, string $to, string $action, ?string $remarks, ?int $userId): void
    {
        ApplicationStatusLog::create([
            'application_id' => $app->id,
            'from_status'    => $from,
            'to_status'      => $to,
            'action'         => $action,
            'remarks'        => $remarks,
            'user_id'        => $userId,
            'created_at'     => now(),
        ]);
    }

    private function guardOpen(Application $app): void
    {
        if ($app->isClosed()) {
            throw ValidationException::withMessages(['status' => ___('admissions.application_is_closed')]);
        }
    }

    private function guardDuplicate(Person $person, array $application): void
    {
        $exists = Application::where('person_id', $person->id)
            ->where('program_id', $application['program_id'])
            ->where('term_id', $application['term_id'] ?? null)
            ->whereNotIn('status', [Admission::REJECTED, Admission::WITHDRAWN, Admission::DEFERRED])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['program_id' => ___('admissions.duplicate_application')]);
        }
    }
}
